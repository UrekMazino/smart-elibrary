<?php

namespace App\Console\Commands;

use App\Models\Frontpage;
use App\Models\ViewCatalog;
use App\Services\RagSync;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rebuild every record's authors (view_catalogs.joint_authors) from its MARC main entries:
 * 100 (personal name) and 110 (corporate name) — see marcAuthors().
 *
 * Records saved before the fix took authors from 100 only, so a record whose author is a
 * corporate body (110, e.g. "Food and Nutrition Research Institute") showed no author in the
 * OPAC, citations, search and on its generated cover.
 *
 * Dry run by default. With --apply it updates joint_authors, re-syncs the search index, queues
 * the AI (RAG) re-sync, regenerates placeholder covers (they print the author) and clears the
 * homepage cache.
 */
class RebuildCatalogAuthors extends Command
{
    protected $signature = 'catalog:rebuild-authors
                            {--apply : Write the changes (without it, only reports what would change)}';

    protected $description = 'Rebuild record authors from MARC 100 + 110 (personal and corporate main entries)';

    public function handle()
    {
        $changes = [];   // catalog_id => [old, new]

        DB::table('catalogs')
            ->join('view_catalogs', 'view_catalogs.catalog_id', '=', 'catalogs.id')
            ->whereNull('catalogs.deleted_at')
            ->select('catalogs.id', 'catalogs.field_data', 'view_catalogs.joint_authors')
            ->orderBy('catalogs.id')
            ->chunk(500, function ($rows) use (&$changes) {
                foreach ($rows as $r) {
                    $new = marcAuthors($r->field_data);
                    if ($new !== null && $new !== $r->joint_authors) {
                        $changes[$r->id] = [(string) $r->joint_authors, $new];
                    }
                }
            });

        if (! $changes) {
            $this->info('Every record’s authors already match its MARC 100 / 110 — nothing to do.');
            return self::SUCCESS;
        }

        $this->table(['Record', 'Current authors', 'From MARC 100 + 110'], collect($changes)->take(15)->map(
            fn ($c, $id) => [$id, $c[0] === '' ? '—' : mb_strimwidth($c[0], 0, 45, '…'), mb_strimwidth($c[1], 0, 80, '…')]
        )->values()->all());
        if (count($changes) > 15) {
            $this->line('… and ' . (count($changes) - 15) . ' more.');
        }

        if (! $this->option('apply')) {
            $this->warn('Dry run: ' . count($changes) . ' record(s) would get their authors from 100 + 110. Re-run with --apply to write.');
            return self::SUCCESS;
        }

        $ids = array_keys($changes);
        DB::transaction(function () use ($changes) {
            foreach ($changes as $id => [, $new]) {
                DB::table('view_catalogs')->where('catalog_id', $id)->update(['joint_authors' => $new]);
            }
        });
        $this->info('Updated ' . count($changes) . ' record(s).');

        foreach (array_chunk($ids, 500) as $chunk) {
            ViewCatalog::whereIn('catalog_id', $chunk)->searchable();
        }
        RagSync::enqueueMany($ids, RagSync::UPSERT, 'authors-rebuild');

        $regenerated = 0;
        foreach (Frontpage::whereIn('catalog_id', $ids)->whereNull('deleted_at')->where('FrontPageName', 'like', 'gen-%')->get() as $fp) {
            $vc = ViewCatalog::where('catalog_id', $fp->catalog_id)->first();
            if ($vc && generatePlaceholderCover($vc, public_path('frontpages/' . $fp->FrontPageName))) {
                makeThumbnail(public_path('frontpages/' . $fp->FrontPageName), public_path('thumb/' . $fp->FrontPageName));
                $regenerated++;
            }
        }

        clearHomeCache();
        $this->info("Search re-synced, AI re-sync queued, {$regenerated} placeholder cover(s) regenerated, homepage cache cleared.");

        return self::SUCCESS;
    }
}
