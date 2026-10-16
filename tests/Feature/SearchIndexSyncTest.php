<?php

namespace Tests\Feature;

use App\Models\Frontpage;
use App\Models\Multimedia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\NullEngine;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Writes that change what the OPAC search index or the homepage caches show must update them.
 * Covers and full text were written with the query builder, which skips the Frontpage /
 * Multimedia hooks that re-index the record (EVENT_DRIVEN_SIDE_EFFECTS_PLAN.md, section 3.1).
 */
class SearchIndexSyncTest extends TestCase
{
    use RefreshDatabase;

    /** Stands in for Meilisearch: keeps every document the app sends to the index. */
    private NullEngine $index;

    private User $staff;
    private int $catalogId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->index = new class extends NullEngine {
            public array $documents = [];

            public function update($models)
            {
                foreach ($models as $model) {
                    $this->documents[] = $model->toSearchableArray();
                }
            }
        };
        $index = $this->index;   // extend() rebinds the closure, so $this would be the manager
        $this->app->make(EngineManager::class)->extend('recording', fn () => $index);
        config(['scout.driver' => 'recording']);

        $this->staff = User::factory()->create(['usertype' => 'Developer']);
        $this->staff->assignRole(Role::findOrCreate('developer'));

        $material = DB::table('materials')->insertGetId(['name' => 'Book']);
        $this->catalogId = DB::table('catalogs')->insertGetId(['material_type_id' => $material, 'field_data' => '[]', 'is_published' => 1]);
        DB::table('view_catalogs')->insert(['catalog_id' => $this->catalogId, 'title_statement' => 'Rice farming',
            'material_type_id' => $material, 'is_published' => 1]);
    }

    /** The last document the index received for the record. */
    private function indexed(): array
    {
        $docs = array_values(array_filter($this->index->documents, fn ($d) => (int) $d['catalog_id'] === $this->catalogId));
        $this->assertNotEmpty($docs, 'The record was not re-indexed.');
        return end($docs);
    }

    public function test_removing_a_full_text_turns_off_has_fulltext_in_the_index()
    {
        Multimedia::create(['catalog_id' => $this->catalogId, 'FileName' => 'ft.pdf', 'FileLocation' => 'pdf/ft.pdf', 'FileType' => 'pdf']);
        $this->assertTrue($this->indexed()['has_fulltext']);
        $this->index->documents = [];

        $this->actingAs($this->staff)->delete('/admin/delete-full-text', ['catalog_id' => $this->catalogId])->assertOk();

        $this->assertFalse($this->indexed()['has_fulltext']);
        $this->assertSame(1, DB::table('rag_sync_queue')->where('catalog_id', $this->catalogId)->where('reason', 'fulltext_removed')->count());
    }

    public function test_removing_a_cover_turns_off_has_cover_in_the_index()
    {
        Frontpage::create(['catalog_id' => $this->catalogId, 'FrontPageName' => 'cp-test.jpg', 'FrontPageLocation' => 'frontpages/cp-test.jpg', 'FrontPageType' => 'jpg']);
        $this->assertTrue($this->indexed()['has_cover']);
        $this->index->documents = [];

        $this->actingAs($this->staff)->delete('/admin/delete-cover-photo', ['catalog_id' => $this->catalogId])->assertOk();

        $this->assertFalse($this->indexed()['has_cover']);
    }

    public function test_a_real_cover_uploaded_over_a_generated_placeholder_turns_on_has_cover()
    {
        // ensurePlaceholderCover() gives new records a generated "gen-" cover, which never counts.
        Frontpage::create(['catalog_id' => $this->catalogId, 'FrontPageName' => "gen-{$this->catalogId}.jpg",
            'FrontPageLocation' => "frontpages/gen-{$this->catalogId}.jpg", 'FrontPageType' => 'jpg']);
        $this->index->documents = [];

        try {
            $this->actingAs($this->staff)->post('/admin/upload/cover-photo', [
                'catalog_id' => $this->catalogId, 'coverImage' => UploadedFile::fake()->image('cover.jpg', 300, 400),
            ])->assertOk();

            $this->assertTrue($this->indexed()['has_cover']);
        } finally {
            // The controller stores the upload under public/ itself: remove what the test created.
            foreach (Frontpage::where('catalog_id', $this->catalogId)->pluck('FrontPageName') as $name) {
                if (str_starts_with($name, 'cp-')) {
                    @unlink(public_path("frontpages/$name"));
                    @unlink(public_path("thumb/$name"));
                }
            }
        }
    }

    public function test_publishing_or_holding_refreshes_the_homepage_shelves()
    {
        foreach (['/admin/hold-view-from-opac', '/admin/publish-to-opac'] as $url) {
            cache()->put('home_shelves_45', ['a stale shelf'], 600);

            $this->actingAs($this->staff)->post($url, ['catalog_id' => $this->catalogId])->assertOk();

            $this->assertFalse(cache()->has('home_shelves_45'), "$url left the homepage shelves cached.");
        }

        $batch = DB::table('catalog_imports')->insertGetId(['created_at' => now(),
            'material_type_id' => DB::table('catalogs')->where('id', $this->catalogId)->value('material_type_id')]);
        DB::table('catalogs')->where('id', $this->catalogId)->update(['catalog_import_id' => $batch]);
        DB::table('view_catalogs')->where('catalog_id', $this->catalogId)->update(['catalog_import_id' => $batch]);
        cache()->put('home_shelves_45', ['a stale shelf'], 600);

        $this->actingAs($this->staff)->post('/admin/batch-publish', ['catalog_import_id' => $batch, 'action' => 0]);

        $this->assertFalse(cache()->has('home_shelves_45'), 'Holding a batch left the homepage shelves cached.');
        $this->assertSame(0, (int) DB::table('view_catalogs')->where('catalog_id', $this->catalogId)->value('is_published'));
    }
}
