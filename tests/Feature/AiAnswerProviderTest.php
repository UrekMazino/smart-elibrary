<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\AiSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\SeedsReferenceData;
use Tests\TestCase;

/**
 * AI mode "Answer with" (Admin > Settings > AI mode): who writes AI answers. The choice travels
 * with each question to the gateway (rag_api.py), which reports who actually answered; the OPAC
 * says so. Settings shows whether the gateway has a Gemini key, never the key itself.
 */
class AiAnswerProviderTest extends TestCase
{
    use RefreshDatabase, SeedsReferenceData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
        config(['services.hermes.url' => 'http://gateway.test', 'services.hermes.token' => 'test-token']);
        cache()->forget('ai_gateway_health');

        // One published record the gateway's passage can cite (stem -> accession_number).
        $material = DB::table('materials')->insertGetId(['name' => 'Book']);
        $id = DB::table('catalogs')->insertGetId(['material_type_id' => $material, 'field_data' => '[]', 'is_published' => 1, 'accession_number' => 'stem-1']);
        DB::table('view_catalogs')->insert(['catalog_id' => $id, 'title_statement' => 'Malunggay and iron', 'material_type_id' => $material, 'is_published' => 1, 'accession_number' => 'stem-1']);

        $this->setting('ai_mode_enabled', '1');
    }

    private function setting(string $key, string $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        settingsFlush();
    }

    private function fakeGateway(?array $answeredBy = ['provider' => 'gemini', 'model' => 'gemini-flash-latest'], ?array $gemini = ['configured' => true, 'model' => 'gemini-flash-latest']): void
    {
        Http::fake([
            'gateway.test/healthz' => Http::response(array_filter(['ok' => true, 'docs' => 10, 'gemini' => $gemini])),
            'gateway.test/agent/answer' => Http::response([
                'answer' => 'Malunggay leaves are rich in iron [1].',
                'sources' => [['stem' => 'stem-1', 'page' => 3, 'score' => 0.91, 'snippet' => 'Iron content of malunggay']],
                'abstained' => false,
                'took_ms' => 900,
                'answered_by' => $answeredBy,
                'fallback' => $answeredBy && $answeredBy['provider'] === 'local' ? 'HTTP 429 (rate limit or quota)' : null,
            ]),
        ]);
    }

    private function sentProvider(): ?string
    {
        $sent = Http::recorded(fn (Request $r) => str_ends_with($r->url(), '/agent/answer'));
        return $sent->isEmpty() ? null : $sent->last()[0]->data()['provider'] ?? null;
    }

    private function ask(): \Illuminate\Testing\TestResponse
    {
        $patron = User::factory()->create(['usertype' => 'Patron']);
        return $this->actingAs($patron)->getJson('/search/ai-answer?q=' . urlencode('Is malunggay rich in iron?'));
    }

    public function test_without_a_saved_choice_answers_ask_for_gemini_with_the_local_ai_as_backup()
    {
        $this->fakeGateway();

        $this->ask()->assertOk()->assertJson(['ok' => true, 'abstained' => false, 'answered_by' => 'gemini']);
        $this->assertSame('gemini_local', $this->sentProvider());
    }

    public function test_the_saved_choice_is_what_the_gateway_is_asked_for()
    {
        foreach (['local', 'gemini', 'gemini_local'] as $choice) {
            $this->setting('ai_answer_provider', $choice);
            $this->fakeGateway();
            $this->ask()->assertOk();
            $this->assertSame($choice, $this->sentProvider(), "choice $choice");
        }
    }

    public function test_an_unknown_stored_value_falls_back_to_the_default()
    {
        $this->setting('ai_answer_provider', 'openai');
        $this->assertSame(AiSearchService::DEFAULT_PROVIDER, app(AiSearchService::class)->provider());
    }

    public function test_the_opac_says_who_wrote_the_answer_but_not_why_gemini_was_skipped()
    {
        $this->fakeGateway(['provider' => 'local', 'model' => 'qwen3-30b']);

        $res = $this->ask()->assertOk()->assertJson(['answered_by' => 'local']);
        $this->assertStringNotContainsString('429', $res->getContent());
        $this->assertStringNotContainsString('fallback', $res->getContent());
    }

    public function test_a_gateway_that_does_not_report_who_answered_still_works()
    {
        $this->fakeGateway(null, null);

        $this->ask()->assertOk()->assertJson(['ok' => true, 'abstained' => false, 'answered_by' => null]);
    }

    public function test_an_admin_with_the_ai_permission_can_choose_and_an_unknown_choice_is_refused()
    {
        Http::fake();
        $admin = tap(User::factory()->create(['usertype' => 'Developer']))->assignRole('developer');

        $this->actingAs($admin)->post('/admin/settings', ['ai_mode_enabled' => '1', 'ai_answer_provider' => 'local'])->assertRedirect('/admin/settings');
        settingsFlush();
        $this->assertSame('local', setting('ai_answer_provider'));

        $this->actingAs($admin)->post('/admin/settings', ['ai_mode_enabled' => '1', 'ai_answer_provider' => 'openai'])->assertSessionHasErrors('ai_answer_provider');
        settingsFlush();
        $this->assertSame('local', setting('ai_answer_provider'));

        $this->actingAs($admin)->get('/admin/settings')->assertOk()
            ->assertSee('name="ai_answer_provider"', false)
            ->assertSee('Google Gemini, with the local AI as backup');
    }

    public function test_settings_status_reports_whether_the_gateway_has_a_gemini_key_without_the_key()
    {
        $this->fakeGateway(gemini: ['configured' => false, 'model' => 'gemini-flash-latest']);
        $admin = tap(User::factory()->create(['usertype' => 'Developer']))->assignRole('developer');

        $this->actingAs($admin)->getJson('/admin/settings/ai-status')->assertOk()->assertJson([
            'ok' => true,
            'provider' => 'gemini_local',
            'gemini' => ['configured' => false, 'model' => 'gemini-flash-latest'],
        ]);
    }
}
