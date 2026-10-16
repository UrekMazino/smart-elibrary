<?php

namespace App\Console\Commands;

use App\Models\ViewCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fill the OPAC search page's fields for existing records, then rebuild the search index.
 *
 *  - view_catalogs.subjects     : MARC 600/610/650/651/653 $a, cleaned (see marcSubjects())
 *  - view_catalogs.pub_year_num : the year as a number, from pub_year's text (see pubYearNumber())
 *
 * New and edited records get both on save; this command is for records saved before. Dry run
 * by default. With --apply it writes the changes, syncs the Meilisearch index settings and
 * re-indexes every record, so the index also gains the computed fields (authors list,
 * has_fulltext, has_cover, views, title_sort).
 */
class RebuildSearchFields extends Command
{
    protected $signature = 'catalog:rebuild-search-fields
                            {--apply : Write the changes and rebuild the search index (without it, only reports)}';

    protected $description = 'Fill subjects and numeric publication years for the OPAC search page, then re-index';

    public function handle()
    {
        $changes = [];   // view_catalogs.id => ['subjects' => json|null, 'pub_year_num' => int|null]
        $withSubjects = 0; $withYear = 0; $total = 0; $samples = [];

        DB::table('view_catalogs')
            ->join('catalogs', 'catalogs.id', '=', 'view_catalogs.catalog_id')
            ->select('view_catalogs.id', 'view_catalogs.catalog_id', 'view_catalogs.pub_year', 'view_catalogs.pub_year_num', 'view_catalogs.subjects', 'catalogs.field_data')
            ->orderBy('view_catalogs.id')
            ->chunk(500, function ($rows) use (&$changes, &$withSubjects, &$withYear, &$total, &$samples) {
                foreach ($rows as $r) {
                    $total++;
                    $subjects = marcSubjects($r->field_data);
                    $year     = pubYearNumber($r->pub_year);
                    if ($subjects) $withSubjects++;
                    if ($year !== null) $withYear++;

                    $newSubjects = $subjects ? json_encode($subjects, JSON_UNESCAPED_UNICODE) : null;
                    $oldSubjects = $r->subjects === null ? null : json_encode(json_decode($r->subjects, true), JSON_UNESCAPED_UNICODE);
                    if ($newSubjects !== $oldSubjects || $year !== ($r->pub_year_num === null ? null : (int) $r->pub_year_num)) {
                        $changes[$r->id] = ['subjects' => $newSubjects, 'pub_year_num' => $year];
                        if (count($samples) < 12) {
                            $samples[] = [$r->catalog_id, mb_strimwidth((string) $r->pub_year, 0, 14, '…') . ' → ' . ($year ?? '—'), mb_strimwidth(implode('; ', $subjects) ?: '—', 0, 70, '…')];
                        }
                    }
                }
            });

        $this->info("{$total} record(s): {$withSubjects} with subjects, {$withYear} with a publication year.");

        if ($changes) {
            $this->table(['Record', 'Year', 'Subjects'], $samples);
            if (count($changes) > count($samples)) {
                $this->line('… and ' . (count($changes) - count($samples)) . ' more.');
            }
        } else {
            $this->info('Subjects and years are already up to date.');
        }

        if (! $this->option('apply')) {
            $this->warn('Dry run: ' . count($changes) . ' record(s) would change. Re-run with --apply to write them and rebuild the search index.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($changes) {
            foreach ($changes as $id => $values) {
                DB::table('view_catalogs')->where('id', $id)->update($values);
            }
        });
        $this->info('Updated ' . count($changes) . ' record(s).');

        if (config('scout.driver') !== 'meilisearch') {
            $this->warn('SCOUT_DRIVER is not "meilisearch": skipped the search index.');
            return self::SUCCESS;
        }

        // Every document gains new fields, so re-index all records (and sync the settings first:
        // the new filters and sorts must be registered before searches use them).
        $this->call('scout:sync-index-settings');
        ViewCatalog::makeAllSearchable();
        $this->info('Search index rebuilt for ' . ViewCatalog::count() . ' record(s). Meilisearch finishes indexing in the background within seconds.');

        return self::SUCCESS;
    }
}
