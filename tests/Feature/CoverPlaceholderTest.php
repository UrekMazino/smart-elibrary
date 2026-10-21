<?php

namespace Tests\Feature;

use App\Models\Frontpage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Placeholder covers in the admin: they count as a cover and are tagged as placeholders, and
 * staff turn them on or off per record from the record page. Covers are written under public/,
 * so the app's public directory points at a temporary one: a test record's gen-<id>.jpg must
 * never overwrite a real record's.
 */
class CoverPlaceholderTest extends TestCase
{
    use RefreshDatabase;

    private string $public;
    private User $staff;
    private int $catalogId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->public = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'elib-cover-test-' . uniqid();
        File::ensureDirectoryExists($this->public . '/frontpages');
        File::ensureDirectoryExists($this->public . '/thumb');
        // The record page stamps its script with filemtime(public_path(...)): bring it along.
        File::ensureDirectoryExists($this->public . '/js/admin');
        File::copy(public_path('js/admin/catalog-view-record.js'), $this->public . '/js/admin/catalog-view-record.js');
        $this->app->usePublicPath($this->public);

        $this->staff = User::factory()->create(['usertype' => 'Developer']);
        $this->staff->assignRole(Role::findOrCreate('developer'));
        $this->catalogId = $this->record('Rice farming in the uplands', published: true);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->public);
        parent::tearDown();
    }

    private function record(string $title, bool $published): int
    {
        $material = DB::table('materials')->where('name', 'Book')->value('id') ?? DB::table('materials')->insertGetId(['name' => 'Book']);
        $id = DB::table('catalogs')->insertGetId(['material_type_id' => $material, 'field_data' => '[]', 'is_published' => (int) $published]);
        DB::table('view_catalogs')->insert(['catalog_id' => $id, 'title_statement' => $title, 'material_type_id' => $material, 'is_published' => (int) $published]);
        return $id;
    }

    private function placeholder(int $id): ?Frontpage
    {
        return Frontpage::where('catalog_id', $id)->where('FrontPageName', 'like', 'gen-%')->first();
    }

    private function toggle(bool $on)
    {
        return $this->actingAs($this->staff)
            ->post("/admin/view-record/{$this->catalogId}/cover-placeholder", ['on' => $on ? 1 : 0, 'title' => 'Rice farming']);
    }

    public function test_turning_the_placeholder_off_removes_it_and_no_generator_brings_it_back()
    {
        $this->assertTrue(ensurePlaceholderCover($this->catalogId));
        $file = public_path('frontpages/' . $this->placeholder($this->catalogId)->FrontPageName);
        $this->assertFileExists($file);

        $this->toggle(false)->assertOk()->assertSee('Placeholder cover off');

        $this->assertSame(0, (int) DB::table('catalogs')->where('id', $this->catalogId)->value('cover_placeholder'));
        $this->assertSame(0, Frontpage::withTrashed()->where('catalog_id', $this->catalogId)->count());
        $this->assertFileDoesNotExist($file);

        $this->assertFalse(ensurePlaceholderCover($this->catalogId));
        Artisan::call('covers:placeholders');
        $this->assertNull($this->placeholder($this->catalogId));
    }

    public function test_turning_it_back_on_generates_a_placeholder()
    {
        DB::table('catalogs')->where('id', $this->catalogId)->update(['cover_placeholder' => 0]);

        $this->toggle(true)->assertOk()->assertSee('Placeholder cover on');

        $this->assertSame(1, (int) DB::table('catalogs')->where('id', $this->catalogId)->value('cover_placeholder'));
        $this->assertNotNull($placeholder = $this->placeholder($this->catalogId));
        $this->assertFileExists(public_path($placeholder->FrontPageLocation));
    }

    public function test_removing_a_real_cover_brings_the_placeholder_back_while_it_is_on()
    {
        Frontpage::create(['catalog_id' => $this->catalogId, 'FrontPageName' => 'cp-real.jpg', 'FrontPageLocation' => 'frontpages/cp-real.jpg', 'FrontPageType' => 'jpg']);

        $this->actingAs($this->staff)->delete('/admin/delete-cover-photo', ['catalog_id' => $this->catalogId])->assertOk();

        $this->assertTrue(Frontpage::onlyTrashed()->where('FrontPageName', 'cp-real.jpg')->exists());
        $this->assertNotNull($this->placeholder($this->catalogId));
    }

    public function test_removing_a_placeholder_is_turning_it_off()
    {
        ensurePlaceholderCover($this->catalogId);

        $this->actingAs($this->staff)->delete('/admin/delete-cover-photo', ['catalog_id' => $this->catalogId])->assertOk();

        $this->assertSame(0, (int) DB::table('catalogs')->where('id', $this->catalogId)->value('cover_placeholder'));
        $this->assertNull($this->placeholder($this->catalogId));
    }

    public function test_a_real_cover_replaces_the_placeholder_and_its_generated_files()
    {
        ensurePlaceholderCover($this->catalogId);
        $generated = public_path($this->placeholder($this->catalogId)->FrontPageLocation);

        $this->actingAs($this->staff)->post('/admin/upload/cover-photo', [
            'catalog_id' => $this->catalogId, 'coverImage' => UploadedFile::fake()->image('cover.jpg', 300, 400),
        ])->assertOk();

        $this->assertFileDoesNotExist($generated);
        $this->assertNull($this->placeholder($this->catalogId));
        $this->assertStringStartsWith('cp-', Frontpage::where('catalog_id', $this->catalogId)->value('FrontPageName'));
    }

    public function test_lists_count_a_placeholder_as_a_cover_and_tag_it()
    {
        ensurePlaceholderCover($this->catalogId);
        $withReal = $this->record('Goat husbandry', published: true);
        Frontpage::create(['catalog_id' => $withReal, 'FrontPageName' => 'cp-goat.jpg', 'FrontPageLocation' => 'frontpages/cp-goat.jpg', 'FrontPageType' => 'jpg']);

        $rows = collect($this->actingAs($this->staff)->getJson('/admin/record-list-json?draw=1&start=0&length=25')->assertOk()->json('aaData'))->keyBy('id');

        $this->assertSame([1, 1], [$rows[$this->catalogId]['has_coverpage'], $rows[$this->catalogId]['cover_placeholder']]);
        $this->assertSame([1, 0], [$rows[$withReal]['has_coverpage'], $rows[$withReal]['cover_placeholder']]);
        $this->actingAs($this->staff)->get('/admin/record-list')->assertOk()->assertSee('PLACEHOLDER_TAG', false);
    }

    public function test_the_record_page_has_one_action_menu_and_the_placeholder_switch()
    {
        ensurePlaceholderCover($this->catalogId);

        $this->actingAs($this->staff)->get("/admin/view-record/{$this->catalogId}")->assertOk()
            ->assertSee('id="recordActionBtn"', false)
            ->assertSeeInOrder(['Add to On-Hold list', 'Edit', 'MARC21 binary (.mrc)', 'MARCXML (.xml)', 'Re-index for AI', 'Delete'])
            ->assertSee('id="placeholderSwitch"', false)
            ->assertSee('Placeholder on')
            ->assertDontSee('Set on Hold')
            ->assertDontSee('Remove Cover Photo');
    }

    public function test_deleting_from_the_record_page_returns_to_the_list_it_was_in()
    {
        $held = $this->record('Draft record', published: false);

        $this->actingAs($this->staff)->delete("/admin/delete-record/{$this->catalogId}", ['return_to' => 'list'])->assertRedirect('/admin/record-list');
        $this->actingAs($this->staff)->delete("/admin/delete-record/{$held}", ['return_to' => 'list'])->assertRedirect('/admin/on-hold-list');
        $this->assertNotNull(DB::table('catalogs')->where('id', $held)->value('deleted_at'));
    }
}
