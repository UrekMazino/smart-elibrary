<?php

namespace App\Console\Commands;

use App\Models\ViewCatalog;
use App\Services\SearchIndex;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Nightly safety net for the OPAC search index (scheduled in app/Console/Kernel.php):
 *  - records missing from the index (an update lost while Meilisearch was down) are added;
 *  - documents whose record was deleted or no longer exists are removed;
 *  - records viewed since the last run are re-sent, so the "Most viewed" sort uses current
 *    view counts (a page view does not re-index the record; that would be one call per visit).
 */
class ReconcileSearchIndex extends Command
{
    protected $signature = 'search:reconcile
                            {--viewed-hours=25 : Re-send records viewed in this many past hours}
                            {--dry-run : Report the differences without changing the index}';

    protected $description = 'Bring the OPAC search index in line with the database (nightly)';

    public function handle(SearchIndex $index): int
    {
        $indexed = $index->indexedKeys();
        if ($indexed === null) {
            $this->info('The search driver is "' . config('scout.driver') . '", which keeps no index to reconcile.');
            return self::SUCCESS;
        }

        // Every record that is not deleted belongs in the index (on-hold ones too: the OPAC
        // filters on is_published), keyed by view_catalogs.id.
        $current = DB::table('view_catalogs')->whereNull('deleted_at')->pluck('catalog_id', 'id');
        $indexedSet = array_flip($indexed);

        $missing = $current->reject(fn ($catalogId, $key) => isset($indexedSet[$key]));
        $extra = array_values(array_filter($indexed, fn ($key) => ! $current->has($key)));
        $deleted = DB::table('view_catalogs')->whereIn('id', $extra)->pluck('catalog_id');   // still in the table, but deleted
        $gone = array_values(array_diff($extra, DB::table('view_catalogs')->whereIn('id', $extra)->pluck('id')->all()));
        $viewed = DB::table('views')->where('created_at', '>=', now()->subHours((int) $this->option('viewed-hours')))
            ->distinct()->pluck('catalog_id')
            ->intersect($current->values());

        $this->line(sprintf('Index: %d documents; database: %d records.', count($indexed), $current->count()));
        $this->line(sprintf('  missing from the index : %d', $missing->count()));
        $this->line(sprintf('  deleted, still indexed  : %d', $deleted->count()));
        $this->line(sprintf('  no longer exist         : %d', count($gone)));
        $this->line(sprintf('  viewed, counts to update: %d', $viewed->count()));

        if ($this->option('dry-run')) {
            $this->comment('Dry run: nothing changed.');
            return self::SUCCESS;
        }

        SearchIndex::refresh($missing->values()->merge($deleted)->merge($viewed)->unique());   // sent at once: no transaction
        $index->deleteKeys($gone);

        $this->info('Search index reconciled.');
        return self::SUCCESS;
    }
}
