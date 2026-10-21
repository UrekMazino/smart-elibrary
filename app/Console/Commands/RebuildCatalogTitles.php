<?php

namespace App\Console\Commands;

use App\Models\Frontpage;
use App\Models\ViewCatalog;
use App\Services\RagSync;
use App\Services\SearchIndex;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rebuild every record's display title from its full MARC 245 (all subfields, see marcTitle()).
 *
 * Records saved before the fix stored only 245$a, so ISBD-punctuated titles showed truncated
 * ("2021 expanded national nutrition survey monograph series :" — subtitle and responsibility
 * missing) in the record lists, OPAC, search, suggestions and generated covers.
 *
 * Dry run by default. With --apply it updates view_catalogs.title_statement and the copies'
 * titles, re-syncs the search index, queues the AI (RAG) re-sync, regenerates placeholder
 * covers (gen-*) for the changed records, and clears the homepage cache.
 */
class RebuildCatalogTitles extends Command
{
    protected $signature = 'catalog:rebuild-titles
                            {--apply : Write the changes (without it, only reports what would change)}';

    protected $description = 'Rebuild record titles from the full MARC 245 (all subfields), not just 245$a';

    public function handle()
    {
        $changes = [];   // catalog_id => [old, new]

        DB::table('catalogs')
            ->join('view_catalogs', 'view_catalogs.catalog_id', '=', 'catalogs.id')
            ->whereNull('catalogs.deleted_at')
            ->select('catalogs.id', 'catalogs.field_data', 'view_catalogs.title_statement')
            ->orderBy('catalogs.id')
            ->chunk(500, function ($rows) use (&$changes) {
                foreach ($rows as $r) {
                    $new = marcTitle($r->field_data);
                    if ($new !== null && $new !== (string) $r->title_statement) {
                        $changes[$r->id] = [(string) $r->title_statement, $new];
                    }
                }
            });

        if (! $changes) {
            $this->info('Every title already matches its full MARC 245 — nothing to do.');
            return self::SUCCESS;
        }

        $this->table(['Record', 'Current title', 'Full 245 title'], collect($changes)->take(15)->map(
            fn ($c, $id) => [$id, mb_strimwidth($c[0], 0, 55, '…'), mb_strimwidth($c[1], 0, 90, '…')]
        )->values()->all());
        if (count($changes) > 15) {
            $this->line('… and ' . (count($changes) - 15) . ' more.');
        }

        if (! $this->option('apply')) {
            $this->warn('Dry run: ' . count($changes) . ' record(s) would get their full title. Re-run with --apply to write.');
            return self::SUCCESS;
        }

        $ids = array_keys($changes);
        DB::transaction(function () use ($changes) {
            foreach ($changes as $id => [, $new]) {
                DB::table('view_catalogs')->where('catalog_id', $id)->update(['title_statement' => $new]);
                DB::table('copies')->where('catalog_id', $id)->update(['title' => $new]);
            }
        });
        $this->info('Updated ' . count($changes) . ' title(s) (and their copies).');

        // Search index (sent in chunks).
        SearchIndex::refresh($ids);
        $this->info('Search index re-synced.');

        // AI index: the worker re-reads the record, so queueing is enough.
        RagSync::enqueueMany($ids, RagSync::UPSERT, 'title-rebuild');
        $this->info('Queued for AI (RAG) re-sync.');

        // Placeholder covers print the title: regenerate the generated ones (real covers untouched).
        $regenerated = 0;
        foreach (Frontpage::whereIn('catalog_id', $ids)->whereNull('deleted_at')->where('FrontPageName', 'like', 'gen-%')->get() as $fp) {
            $vc = ViewCatalog::where('catalog_id', $fp->catalog_id)->first();
            if ($vc && generatePlaceholderCover($vc, public_path('frontpages/' . $fp->FrontPageName))) {
                makeThumbnail(public_path('frontpages/' . $fp->FrontPageName), public_path('thumb/' . $fp->FrontPageName));
                $regenerated++;
            }
        }
        $this->info("Regenerated {$regenerated} placeholder cover(s).");

        clearHomeCache();
        $this->info('Homepage cache cleared.');

        return self::SUCCESS;
    }
}
