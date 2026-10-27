<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\SeedsReferenceData;
use Tests\TestCase;

/**
 * Administration → Settings: the options are saved together, the page then says what the save changed, and a refused
 * save changes nothing while keeping what was typed. A whole Search Console tag pasted in keeps only its code.
 */
class SettingsPageTest extends TestCase
{
    use RefreshDatabase, SeedsReferenceData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedAccess();
        Http::fake();   // nothing here may reach a real AI gateway
    }

    private function developer(): User
    {
        return tap(User::factory()->create(['usertype' => 'Developer']))->assignRole('developer');
    }

    /** Settings, without the AI permission (manage.ai.mode). */
    private function settingsAdmin(): User
    {
        $permissions = ['access.configuration', 'access.settings'];
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, 'web');
        }

        return tap(User::factory()->create(['usertype' => 'Administrator']))->givePermissionTo($permissions);
    }

    public function test_the_page_keeps_options_apart_from_tools_and_shows_what_is_in_effect()
    {
        $this->actingAs($this->developer())->get('/admin/settings')->assertOk()
            ->assertSeeInOrder(['Now in effect', 'Options', 'Covers', 'AI answers', 'Search engines', 'Save changes',
                                'Tools', 'Check search', 'Rebuild search index', 'Rebuild sitemap', 'Refresh cached pages'])
            ->assertSee('<span class="st-tile-value">Off</span>', false)   // AI answers, off by default
            ->assertSee('value="gemini_local"', false);
    }

    public function test_a_save_says_what_it_changed_and_saving_again_says_nothing_changed()
    {
        $admin = $this->developer();
        // Covers off (an unticked switch is absent), AI answers on with the local AI, search engines asked to stay away.
        $form = ['ai_mode_enabled' => '1', 'ai_answer_provider' => 'local', 'seo_discourage_indexing' => '1', 'seo_verification_google' => ''];

        $this->actingAs($admin)->post('/admin/settings', $form)->assertRedirect('/admin/settings')->assertSessionHas('saved', [
            'Records without a cover now show “No preview available”.',
            'Search engines are now asked not to list the catalogue.',
            'AI answers are now on in the OPAC.',
            'AI answers are now written by: Local AI only (this server).',
        ]);
        settingsFlush();
        $this->assertSame(['0', '1', '1', 'local'], [setting('enable_placeholder_covers'), setting('seo_discourage_indexing'),
                                                     setting('ai_mode_enabled'), setting('ai_answer_provider')]);

        $this->actingAs($admin)->followingRedirects()->post('/admin/settings', $form)->assertOk()
            ->assertSee('Nothing to save.')
            ->assertSee('<span class="st-tile-value">On · Local AI only</span>', false);
    }

    public function test_a_whole_search_console_tag_keeps_only_its_code()
    {
        $this->actingAs($this->developer())->post('/admin/settings', [
            'enable_placeholder_covers' => '1',
            'seo_verification_google' => '<meta name="google-site-verification" content="AbC_123-xyz.Q" />',
        ])->assertSessionHasNoErrors()->assertSessionHas('saved', ['The Google verification code was saved.']);

        settingsFlush();
        $this->assertSame('AbC_123-xyz.Q', setting('seo_verification_google'));
    }

    public function test_a_refused_save_changes_nothing_and_keeps_what_was_typed()
    {
        $admin = $this->developer();

        $this->actingAs($admin)->from('/admin/settings')->followingRedirects()
            ->post('/admin/settings', ['ai_mode_enabled' => '1', 'seo_verification_google' => 'not a code'])
            ->assertOk()
            ->assertSee('Not saved.')
            ->assertSee('value="not a code"', false)                       // the form keeps the edit
            ->assertSee('<span class="st-tile-value">Off</span>', false);  // what is in effect: AI answers still off

        settingsFlush();
        $this->assertSame('1', setting('enable_placeholder_covers'));   // still on, though the refused form switched it off
        $this->assertNull(setting('ai_mode_enabled'));
    }

    public function test_saving_without_the_ai_permission_leaves_ai_answers_alone()
    {
        Setting::create(['key' => 'ai_mode_enabled', 'value' => '1']);
        $admin = $this->settingsAdmin();

        $this->actingAs($admin)->post('/admin/settings', ['enable_placeholder_covers' => '1'])->assertSessionHas('saved', []);
        settingsFlush();
        $this->assertSame('1', setting('ai_mode_enabled'));

        $this->actingAs($admin)->get('/admin/settings')->assertOk()
            ->assertDontSee('name="ai_mode_enabled"', false)
            ->assertDontSee('name="ai_answer_provider"', false);
    }
}
