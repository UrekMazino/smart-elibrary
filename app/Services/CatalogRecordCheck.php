<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Data-quality check of a catalogue record, made on the values its OPAC row (view_catalogs) is
 * built from. The batch import uses it to decide what to import and what to flag for review; an
 * edit of a flagged record uses it again to clear what was fixed.
 *
 * A record is imported when it has a title and at least MIN_BASIC of the four basic elements
 * (title, author, publication year, publisher). Whatever basic element is missing is flagged, as
 * is an ISBN / ISSN that another record already has (a possible duplicate).
 *
 * A problem is ['code' => ..., 'level' => 'error' | 'warning', 'message' => ...]. An 'error' means
 * the record is not imported.
 */
class CatalogRecordCheck
{
    public const MIN_BASIC = 2;

    public static function problems(array $opacRow, ?int $catalogId = null): array
    {
        $pubDate = trim((string) ($opacRow['pub_year'] ?? ''));
        $has = [
            'title'     => trim((string) ($opacRow['title_statement'] ?? '')) !== '',
            'author'    => trim((string) ($opacRow['joint_authors'] ?? '')) !== '',
            'pub_year'  => pubYearNumber($pubDate) !== null,
            'publisher' => trim((string) ($opacRow['publisher'] ?? '')) !== '',
        ];

        if (! $has['title']) {
            return [self::problem('no_title', 'error', 'No title (245 $a).')];
        }
        if (count(array_filter($has)) < self::MIN_BASIC) {
            return [self::problem('too_little_data', 'error', 'Only a title: no author, publication year or publisher.')];
        }

        $problems = [];
        if (! $has['author']) {
            $problems[] = self::problem('no_author', 'warning', 'No author (100 / 110 / 700 / 710).');
        }
        if (! $has['pub_year']) {
            $problems[] = self::problem('no_pub_year', 'warning', $pubDate === ''
                ? 'No publication year (260 $c).'
                : 'The publication date "' . $pubDate . '" has no year (260 $c).');
        }
        if (! $has['publisher']) {
            $problems[] = self::problem('no_publisher', 'warning', 'No publisher (260 $b).');
        }

        foreach (['isbn' => 'ISBN', 'issn' => 'ISSN'] as $column => $label) {
            $value = trim((string) ($opacRow[$column] ?? ''));
            if ($value === '') continue;
            $same = DB::table('view_catalogs')->where($column, $value)->whereNull('deleted_at')
                ->when($catalogId, fn ($q) => $q->where('catalog_id', '!=', $catalogId))
                ->orderBy('catalog_id')->limit(3)->pluck('catalog_id');
            if ($same->isNotEmpty()) {
                $problems[] = self::problem('possible_duplicate', 'warning',
                    "Possible duplicate: record " . $same->map(fn ($id) => "#$id")->implode(', ') . " has the same $label ($value).");
            }
        }

        return $problems;
    }

    public static function problem(string $code, string $level, string $message): array
    {
        return ['code' => $code, 'level' => $level, 'message' => $message];
    }

    /** Does any problem stop the record from being imported? */
    public static function blocksImport(array $problems): bool
    {
        return collect($problems)->contains('level', 'error');
    }
}
