<?php

namespace App\Services;

use App\Models\Material;
use Illuminate\Support\Str;
use Meilisearch\Client;
use Meilisearch\Contracts\FacetSearchQuery;
use Meilisearch\Contracts\SearchQuery;

/**
 * The OPAC search page's engine: one Meilisearch multi-search per page view.
 *
 *  - the main query returns the page of results (with highlighted matches) and the counts for
 *    filter groups nobody has used yet;
 *  - each filter group that IS in use gets its own count query that leaves that group out, so
 *    its options show what ticking them would give (other options of the same group are OR-ed,
 *    groups are AND-ed);
 *  - one more count gives the records without a year.
 *
 * Advanced-search rows run as separate searches first; their record sets are combined top to
 * bottom with AND / OR / NOT and passed on as a catalog_id filter.
 *
 * Index fields come from ViewCatalog::toSearchableArray(); settings from config/scout.php.
 */
final class CatalogSearch
{
    public const PER_PAGE = ['list' => 10, 'grid' => 15];
    /** Highlight markers around matched words, turned into <mark> after escaping (searchHighlight()). */
    public const HL_PRE = "\u{E000}";
    public const HL_POST = "\u{E001}";

    private const INDEX = 'view_catalogs';
    private const BASE = ['is_published = 1', 'deleted_at IS NULL'];
    private const MAX_IDS = 20000;   // = pagination.maxTotalHits in config/scout.php
    private const FIELD_ATTRS = [
        'title' => ['title_statement'], 'author' => ['joint_authors'], 'subject' => ['subjects'],
        'publisher' => ['publisher'], 'call' => ['call_number'], 'isbn' => ['isbn', 'issn'],
    ];
    private const SORT = ['newest' => ['year:desc', 'title_sort:asc'], 'oldest' => ['year:asc', 'title_sort:asc'], 'title' => ['title_sort:asc'], 'views' => ['views:desc']];
    /** Filter group => index attribute it counts. */
    private const FACETS = ['type' => 'material_type_id', 'subject' => 'subjects', 'author' => 'authors', 'publisher' => 'publisher', 'year' => 'year', 'fulltext' => 'has_fulltext'];
    private const RETRIEVE = ['id', 'catalog_id', 'title_statement', 'joint_authors', 'authors', 'subjects', 'year', 'pub_year', 'publisher',
        'material_type', 'material_type_id', 'call_number', 'has_fulltext', 'summary'];
    private const HIGHLIGHT = ['title_statement', 'authors', 'subjects', 'publisher', 'call_number', 'summary'];

    public function __construct(private Client $client)
    {
    }

    /**
     * @param  int[]  $savedIds  the signed-in patron's saved catalog ids (for "Saved records")
     */
    public function search(CatalogSearchParams $p, array $savedIds = []): array
    {
        try {
            return $this->run($p, $savedIds);
        } catch (\Throwable $e) {
            report($e);
            return ['ok' => false, 'total' => 0, 'hits' => [], 'page' => 1, 'pages' => 1, 'facets' => [], 'years' => null, 'fulltext' => 0];
        }
    }

    private function run(CatalogSearchParams $p, array $savedIds): array
    {
        $groups = $this->groups($p, $savedIds, $p->adv ? $this->advancedIds($p) : null);
        $perPage = self::PER_PAGE[$p->view];

        $main = $this->query($p)
            ->setFilter($this->filter($groups))
            ->setFacets(array_values(array_map(fn ($g) => self::FACETS[$g], array_keys(array_filter(self::FACETS, fn ($a, $g) => $groups[$g] === null, ARRAY_FILTER_USE_BOTH)))))
            ->setHitsPerPage($perPage)->setPage($p->page)
            ->setAttributesToRetrieve(self::RETRIEVE)
            ->setAttributesToHighlight(self::HIGHLIGHT)
            ->setAttributesToCrop(['summary'])->setCropLength(36)
            ->setHighlightPreTag(self::HL_PRE)->setHighlightPostTag(self::HL_POST);
        if ($sort = self::SORT[$p->effectiveSort()] ?? null) {
            $main->setSort($sort);
        }

        $queries = ['main' => $main];
        foreach (self::FACETS as $group => $attr) {
            if ($groups[$group] !== null) {
                $queries[$group] = $this->count($p, $this->filter($groups, $group))->setFacets([$attr]);
            }
        }
        $queries['undated'] = $this->count($p, array_merge($this->filter($groups, 'year'), ['year IS NULL']));

        $results = array_combine(array_keys($queries), $this->client->multiSearch(array_values($queries))['results']);

        $facetsFor = fn (string $group) => ($results[$groups[$group] === null ? 'main' : $group]['facetDistribution'][self::FACETS[$group]] ?? []);
        $total = (int) ($results['main']['totalHits'] ?? 0);

        return [
            'ok'       => true,
            'total'    => $total,
            'page'     => $p->page,
            'pages'    => max(1, (int) ($results['main']['totalPages'] ?? 1)),
            'perPage'  => $perPage,
            'hits'     => array_map(fn ($h) => $this->hit($h), $results['main']['hits'] ?? []),
            'facets'   => [
                'type'      => $this->typeOptions($facetsFor('type'), $p->types),
                'subject'   => $this->options($facetsFor('subject'), $p->subjects),
                'author'    => $this->options($facetsFor('author'), $p->authors),
                'publisher' => $this->options($facetsFor('publisher'), $p->publishers),
            ],
            'years'    => $this->years($facetsFor('year'), $p, (int) ($results['undated']['totalHits'] ?? 0)),
            'fulltext' => (int) ($facetsFor('fulltext')['true'] ?? 0),
        ];
    }

    /**
     * When nothing matches: how many results each one-click change would give.
     *
     * @return array<int, array{label: string, url: string, count: int}>
     */
    public function fixes(CatalogSearchParams $p, array $savedIds, array $typeNames, ?string $seriesName): array
    {
        $options = [];
        if ($p->in !== 'all' && $p->q !== '') {
            $options[] = ['label' => 'Search all fields, not just ' . CatalogSearchParams::FIELDS[$p->in], 'params' => $p->with(fn ($c) => $c->in = 'all')];
        }
        foreach ($p->chips($typeNames, $seriesName) as $chip) {
            $options[] = ['label' => 'Remove “' . $chip['label'] . '”', 'params' => $chip['params']];
        }
        if (! $options) return [];

        try {
            $queries = [];
            foreach ($options as $o) {
                $groups = $this->groups($o['params'], $savedIds, $o['params']->adv ? $this->advancedIds($o['params']) : null);
                $queries[] = $this->count($o['params'], $this->filter($groups));
            }
            $results = $this->client->multiSearch($queries)['results'];
        } catch (\Throwable $e) {
            report($e);
            return [];
        }

        $out = [];
        foreach ($options as $i => $o) {
            $n = (int) ($results[$i]['totalHits'] ?? 0);
            if ($n > 0) $out[] = ['label' => $o['label'], 'url' => $o['params']->url(), 'count' => $n];
        }
        usort($out, fn ($a, $b) => $b['count'] <=> $a['count']);
        return array_slice($out, 0, 4);
    }

    /**
     * Options of one filter group that match some typed text (the "Find an author" box),
     * counted against the current search with that group left out.
     *
     * @return array<int, array{value: string, count: int}>
     */
    public function facetValues(CatalogSearchParams $p, array $savedIds, string $group, string $text): array
    {
        if (! in_array($group, ['subject', 'author', 'publisher'], true)) return [];
        try {
            $groups = $this->groups($p, $savedIds, $p->adv ? $this->advancedIds($p) : null);
            $q = (new FacetSearchQuery)->setFacetName(self::FACETS[$group])->setFacetQuery($text)
                ->setQuery($p->q)->setFilter($this->filter($groups, $group))->setMatchingStrategy('all');
            if ($p->in !== 'all') $q->setAttributesToSearchOn(self::FIELD_ATTRS[$p->in]);
            $hits = $this->client->index(self::INDEX)->facetSearch($q)->getFacetHits();
        } catch (\Throwable $e) {
            report($e);
            return [];
        }
        return array_map(fn ($h) => ['value' => (string) $h['value'], 'count' => (int) $h['count']], array_slice($hits, 0, 50));
    }

    /**
     * Search-as-you-type: top titles plus matching authors and subjects (published records only).
     */
    public function suggest(string $text): array
    {
        $out = ['titles' => [], 'authors' => [], 'subjects' => []];
        try {
            $res = $this->client->index(self::INDEX)->search($text, [
                'filter' => self::BASE, 'limit' => 5, 'attributesToRetrieve' => ['catalog_id', 'title_statement', 'pub_year', 'material_type'],
                'attributesToHighlight' => ['title_statement'], 'highlightPreTag' => self::HL_PRE, 'highlightPostTag' => self::HL_POST,
            ]);
            foreach ($res->getHits() as $h) {
                $out['titles'][] = ['catalog_id' => $h['catalog_id'], 'title' => $h['title_statement'], 'title_hl' => $h['_formatted']['title_statement'] ?? $h['title_statement'],
                    'year' => $h['pub_year'], 'type' => $h['material_type']];
            }
            foreach (['authors' => 'authors', 'subjects' => 'subjects'] as $key => $attr) {
                $q = (new FacetSearchQuery)->setFacetName($attr)->setFacetQuery($text)->setFilter(self::BASE);
                foreach (array_slice($this->client->index(self::INDEX)->facetSearch($q)->getFacetHits(), 0, 4) as $h) {
                    $out[$key][] = ['value' => (string) $h['value'], 'count' => (int) $h['count']];
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
        return $out;
    }

    /* ------------------------------------------------------------------ */

    /** A search with the words, field and matching rules of $p (filters and paging set by the caller). */
    private function query(CatalogSearchParams $p): SearchQuery
    {
        $q = (new SearchQuery)->setIndexUid(self::INDEX)->setQuery($p->q)->setMatchingStrategy('all');
        if ($p->in !== 'all') $q->setAttributesToSearchOn(self::FIELD_ATTRS[$p->in]);
        return $q;
    }

    /** Just the number of matches (and any facets the caller adds). */
    private function count(CatalogSearchParams $p, array $filter): SearchQuery
    {
        return $this->query($p)->setFilter($filter)->setHitsPerPage(0)->setPage(1);
    }

    /**
     * Filter expression per group (null = group not in use). "base" and "fixed" always apply;
     * the others can be left out to count that group's options.
     */
    private function groups(CatalogSearchParams $p, array $savedIds, ?array $advIds): array
    {
        $in = fn (string $attr, array $values) => $attr . ' IN [' . implode(', ', array_map(fn ($v) => is_int($v) ? (string) $v : $this->quote($v), $values)) . ']';

        $fixed = [];
        if ($p->series) $fixed[] = 'series_statement_id = ' . $p->series;
        if ($p->saved) $fixed[] = $savedIds ? $in('catalog_id', array_map('intval', $savedIds)) : 'catalog_id = -1';
        if ($advIds !== null) $fixed[] = $advIds ? $in('catalog_id', $advIds) : 'catalog_id = -1';

        $year = null;
        if ($p->hasYearFilter()) {
            $range = $p->yearFrom !== null || $p->yearTo !== null
                ? 'year ' . ($p->yearFrom ?? 0) . ' TO ' . ($p->yearTo ?? 9999)
                : 'year IS NOT NULL';
            $year = $p->undated && ($p->yearFrom !== null || $p->yearTo !== null) ? '(' . $range . ' OR year IS NULL)' : $range;
        }

        return [
            'base'      => self::BASE,
            'fixed'     => $fixed,
            'type'      => $p->types ? $in('material_type_id', $p->types) : null,
            'subject'   => $p->subjects ? $in('subjects', $p->subjects) : null,
            'author'    => $p->authors ? $in('authors', $p->authors) : null,
            'publisher' => $p->publishers ? $in('publisher', $p->publishers) : null,
            'year'      => $year,
            'fulltext'  => $p->fulltext ? 'has_fulltext = true' : null,
        ];
    }

    /** The groups as one AND-ed filter, optionally leaving one group out. */
    private function filter(array $groups, ?string $except = null): array
    {
        $out = array_merge($groups['base'], $groups['fixed']);
        foreach (self::FACETS as $group => $attr) {
            if ($group !== $except && $groups[$group] !== null) $out[] = $groups[$group];
        }
        return $out;
    }

    private function quote(string $v): string
    {
        return '"' . addcslashes($v, '"\\') . '"';
    }

    /** catalog_ids matching the advanced rows, combined top to bottom. */
    private function advancedIds(CatalogSearchParams $p): array
    {
        $queries = [];
        foreach ($p->adv as $row) {
            $attrs = $row['field'] === 'all' ? null : self::FIELD_ATTRS[$row['field']];
            $text = $row['op'] === 'contains' ? $row['value'] : '"' . str_replace('"', '', $row['value']) . '"';
            $q = (new SearchQuery)->setIndexUid(self::INDEX)->setQuery($text)->setFilter(self::BASE)->setMatchingStrategy('all')
                ->setLimit(self::MAX_IDS)->setAttributesToRetrieve(array_merge(['catalog_id'], $attrs ?? ['title_statement']));
            if ($attrs) $q->setAttributesToSearchOn($attrs);
            $queries[] = $q;
        }
        $results = $this->client->multiSearch($queries)['results'];

        $set = null;
        foreach ($p->adv as $i => $row) {
            $ids = [];
            foreach ($results[$i]['hits'] ?? [] as $h) {
                if ($row['op'] === 'starts' && ! $this->startsWith($h, $row['value'])) continue;
                $ids[(int) $h['catalog_id']] = true;
            }
            $set = $set === null ? $ids : match ($row['join']) {
                'OR'    => $set + $ids,
                'NOT'   => array_diff_key($set, $ids),
                default => array_intersect_key($set, $ids),
            };
        }
        return array_keys($set ?? []);
    }

    /** "Starts with": any retrieved field value begins with the words (case and punctuation ignored). */
    private function startsWith(array $hit, string $value): bool
    {
        $norm = fn ($s) => trim(preg_replace('/[^a-z0-9]+/', ' ', Str::lower(Str::ascii((string) $s))));
        $needle = $norm($value);
        foreach ($hit as $key => $v) {
            if ($key === 'catalog_id') continue;
            foreach ((array) $v as $one) {
                if ($needle !== '' && str_starts_with($norm($one), $needle)) return true;
            }
        }
        return false;
    }

    private function hit(array $h): array
    {
        $f = $h['_formatted'] ?? [];
        return [
            'catalog_id'   => (int) $h['catalog_id'],
            'title'        => (string) $h['title_statement'],
            'title_hl'     => (string) ($f['title_statement'] ?? $h['title_statement']),
            'authors'      => $h['authors'] ?? [],
            'authors_hl'   => $f['authors'] ?? ($h['authors'] ?? []),
            'subjects'     => $h['subjects'] ?? [],
            'subjects_hl'  => $f['subjects'] ?? ($h['subjects'] ?? []),
            'year'         => $h['year'] ?? null,
            'pub_year'     => $h['pub_year'] ?? null,
            'publisher'    => $h['publisher'] ?? null,
            'publisher_hl' => $f['publisher'] ?? ($h['publisher'] ?? null),
            'type'         => $h['material_type'] ?? null,
            'type_id'      => $h['material_type_id'] ?? null,
            'call'         => $h['call_number'] ?? null,
            'call_hl'      => $f['call_number'] ?? ($h['call_number'] ?? null),
            'has_fulltext' => (bool) ($h['has_fulltext'] ?? false),
            // A summary snippet only when the search words are in it.
            'summary_hl'   => isset($f['summary']) && str_contains((string) $f['summary'], self::HL_PRE) ? $f['summary'] : null,
        ];
    }

    /** @return array<int, array{value: string, count: int, selected: bool}> */
    private function options(array $distribution, array $selected): array
    {
        $out = [];
        foreach ($distribution as $value => $count) {
            $out[(string) $value] = ['value' => (string) $value, 'count' => (int) $count, 'selected' => in_array((string) $value, $selected, true)];
        }
        foreach ($selected as $v) {
            $out[$v] ??= ['value' => $v, 'count' => 0, 'selected' => true];
        }
        // Selected first, then by count (Meilisearch already sorts by count).
        uasort($out, fn ($a, $b) => [$b['selected'], $b['count']] <=> [$a['selected'], $a['count']]);
        return array_values($out);
    }

    private function typeOptions(array $distribution, array $selected): array
    {
        $names = Material::pluck('name', 'id');
        $out = [];
        foreach ($this->options($distribution, array_map('strval', $selected)) as $o) {
            $id = (int) $o['value'];
            $out[] = ['value' => $id, 'name' => $names[$id] ?? 'Type ' . $id, 'count' => $o['count'], 'selected' => $o['selected']];
        }
        return $out;
    }

    /** Decade bars for the histogram, the slider's bounds and the undated count. */
    private function years(array $distribution, CatalogSearchParams $p, int $undated): ?array
    {
        $byYear = [];
        foreach ($distribution as $year => $n) $byYear[(int) $year] = (int) $n;
        if (! $byYear) {
            return ['decades' => [], 'min' => null, 'max' => null, 'undated' => $undated];
        }
        $min = min(array_keys($byYear));
        $max = max(max(array_keys($byYear)), (int) date('Y'));
        $decades = [];
        for ($d = intdiv($min, 10) * 10; $d <= intdiv($max, 10) * 10; $d += 10) {
            $n = 0;
            foreach ($byYear as $y => $c) if ($y >= $d && $y <= $d + 9) $n += $c;
            $decades[] = ['decade' => $d, 'count' => $n,
                'in' => ($p->yearFrom === null || $d + 9 >= $p->yearFrom) && ($p->yearTo === null || $d <= $p->yearTo)];
        }
        return ['decades' => $decades, 'min' => $min, 'max' => $max, 'undated' => $undated];
    }
}
