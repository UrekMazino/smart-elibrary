<?php

namespace Tests\Feature;

use App\Models\ViewCatalog;
use App\Services\SearchIndex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\NullEngine;
use Tests\TestCase;

/**
 * SearchIndex is the one owner of the OPAC search index: updates go out after the change commits,
 * a rolled-back change sends nothing, bulk work is sent in chunks, and nightly reconcile repairs drift.
 */
class SearchIndexTest extends TestCase
{
    use RefreshDatabase;

    /** Stands in for Meilisearch: records every call the app makes to the index. */
    private NullEngine $engine;

    private int $material;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = new class extends NullEngine {
            public array $updates = [];   // one entry per update() call: the catalogue ids sent
            public array $deletes = [];   // removals arrive as search keys (view_catalogs.id) only

            public function update($models)
            {
                $this->updates[] = $models->pluck('catalog_id')->map(fn ($id) => (int) $id)->all();
            }

            public function delete($models)
            {
                $this->deletes[] = $models->map(fn ($m) => (int) $m->getScoutKey())->all();
            }
        };
        $engine = $this->engine;   // extend() rebinds the closure, so $this would be the manager
        $this->app->make(EngineManager::class)->extend('recording', fn () => $engine);
        config(['scout.driver' => 'recording']);

        $this->material = DB::table('materials')->insertGetId(['name' => 'Book']);
    }

    private function records(int $count, array $extra = []): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $id = DB::table('catalogs')->insertGetId(['material_type_id' => $this->material, 'field_data' => '[]']);
            DB::table('view_catalogs')->insert(['catalog_id' => $id, 'title_statement' => "Record $id", 'material_type_id' => $this->material] + $extra);
            $ids[] = $id;
        }
        return $ids;
    }

    /** The search key (view_catalogs.id) of a record. */
    private function key(int $catalogId): int
    {
        return (int) DB::table('view_catalogs')->where('catalog_id', $catalogId)->value('id');
    }

    private function sent(): array
    {
        return array_merge([], ...$this->engine->updates);
    }

    public function test_saving_a_model_no_longer_indexes_by_itself()
    {
        $id = DB::table('catalogs')->insertGetId(['material_type_id' => $this->material, 'field_data' => '[]']);
        ViewCatalog::create(['catalog_id' => $id, 'title_statement' => 'Rice']);

        $this->assertSame([], $this->engine->updates, 'Scout indexed the record on its own: there must be one owner.');
    }

    public function test_an_update_goes_out_after_the_change_commits()
    {
        [$id] = $this->records(1);

        DB::transaction(function () use ($id) {
            SearchIndex::refresh($id);
            SearchIndex::refresh($id);   // the same record twice in one change: sent once
            $this->assertSame([], $this->engine->updates, 'Sent before the change committed.');
        });

        $this->assertSame([[$id]], $this->engine->updates);
    }

    public function test_a_rolled_back_change_sends_nothing()
    {
        try {
            DB::transaction(function () {
                [$id] = $this->records(1);
                SearchIndex::refresh($id);
                throw new \RuntimeException('save failed');
            });
        } catch (\RuntimeException) {
        }

        app(SearchIndex::class)->finish();   // what runs when the request ends

        $this->assertSame([], $this->sent(), 'A record that was never saved reached the index.');
    }

    public function test_held_updates_are_sent_in_chunks_of_500()
    {
        $ids = $this->records(1200);

        SearchIndex::hold();
        foreach ($ids as $id) SearchIndex::refresh($id);   // like a batch import, record by record
        SearchIndex::release();

        $this->assertCount(3, $this->engine->updates);     // not 1,200 calls
        $this->assertEqualsCanonicalizing($ids, $this->sent());
    }

    public function test_a_deleted_record_is_removed_from_the_index()
    {
        [$kept] = $this->records(1);
        [$deleted] = $this->records(1, ['deleted_at' => now()]);

        SearchIndex::refresh([$kept, $deleted]);

        $this->assertSame([$kept], $this->sent());
        $this->assertSame([[$this->key($deleted)]], $this->engine->deletes);
    }

    public function test_the_nightly_reconcile_repairs_drift_and_refreshes_view_counts()
    {
        [$indexed, $missing, $viewed] = $this->records(3);
        [$deleted] = $this->records(1, ['deleted_at' => now()]);
        DB::table('views')->insert(['catalog_id' => $viewed, 'is_guest' => 'YES', 'created_at' => now()->subHours(2)]);

        // Meilisearch holds: two current records, one deleted since, and one whose row is gone.
        $index = new class extends SearchIndex {
            public array $keys = [];
            public array $deletedKeys = [];
            public function indexedKeys(): ?array { return $this->keys; }
            public function deleteKeys(array $keys): void { $this->deletedKeys = $keys; }
        };
        $index->keys = [$this->key($indexed), $this->key($viewed), $this->key($deleted), 999999];
        $this->app->instance(SearchIndex::class, $index);

        $this->artisan('search:reconcile')->assertSuccessful();

        $this->assertEqualsCanonicalizing([$missing, $viewed], $this->sent());   // added, and view count refreshed
        $this->assertSame([[$this->key($deleted)]], $this->engine->deletes);
        $this->assertSame([999999], $index->deletedKeys);
    }
}
