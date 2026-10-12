<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * What an OPAC search asks for, read from the URL of /results (see CatalogSearch).
 *
 * Every setting lives in the query string, so Back, refresh and shared links keep the search.
 * Older links still work: "seachtype[]" (material type ids, "0" = all) and "series_statement_id"
 * from the home page, record pages and sidebar. The old "layout" / "year1" / "year2" are ignored.
 */
final class CatalogSearchParams
{
    public const FIELDS = ['all' => 'All fields', 'title' => 'Title', 'author' => 'Author', 'subject' => 'Subject', 'publisher' => 'Publisher', 'call' => 'Call number', 'isbn' => 'ISBN / ISSN'];
    public const SORTS  = ['relevance' => 'Relevance', 'newest' => 'Newest first', 'oldest' => 'Oldest first', 'title' => 'Title A–Z', 'views' => 'Most viewed'];
    public const OPS    = ['contains' => 'has all the words', 'exact' => 'has the phrase', 'starts' => 'starts with'];
    public const JOINS  = ['AND', 'OR', 'NOT'];
    public const MAX_ADV_ROWS = 6;

    public string $q = '';
    public string $in = 'all';
    /** @var int[] */
    public array $types = [];
    /** @var string[] */
    public array $subjects = [];
    /** @var string[] */
    public array $authors = [];
    /** @var string[] */
    public array $publishers = [];
    public ?int $yearFrom = null;
    public ?int $yearTo = null;
    public bool $undated = true;     // include records without a year
    public bool $fulltext = false;
    public ?int $series = null;
    public bool $saved = false;
    /** @var array<int, array{join: string, field: string, op: string, value: string}> */
    public array $adv = [];
    public string $sort = 'relevance';
    public string $view = 'list';
    public int $page = 1;

    public static function fromRequest(Request $r): self
    {
        $p = new self;
        $p->q  = mb_substr(trim((string) $r->query('query', '')), 0, 200);
        $p->in = array_key_exists($in = (string) $r->query('in', 'all'), self::FIELDS) ? $in : 'all';

        $types = array_merge((array) $r->query('type', []), (array) $r->query('seachtype', []));
        $p->types = array_values(array_unique(array_filter(array_map('intval', array_filter($types, fn ($v) => is_scalar($v) && ctype_digit((string) $v))))));

        $p->subjects   = self::strings($r->query('subject', []));
        $p->authors    = self::strings($r->query('author', []));
        $p->publishers = self::strings($r->query('publisher', []));

        if (preg_match('/^(\d{4})?-(\d{4})?$/', (string) $r->query('year', ''), $m)) {
            $from = ($m[1] ?? '') !== '' ? (int) $m[1] : null;
            $to   = ($m[2] ?? '') !== '' ? (int) $m[2] : null;
            if ($from !== null && $to !== null && $from > $to) [$from, $to] = [$to, $from];
            [$p->yearFrom, $p->yearTo] = [$from, $to];
        }
        $p->undated  = (string) $r->query('undated', '1') !== '0';
        $p->fulltext = (string) $r->query('fulltext', '') === '1';
        $p->series   = ($s = (int) $r->query('series_statement_id', 0)) > 0 ? $s : null;
        $p->saved    = (string) $r->query('saved', '') === '1';

        foreach ((array) $r->query('adv', []) as $row) {
            if (! is_array($row) || count($p->adv) >= self::MAX_ADV_ROWS) continue;
            $value = mb_substr(trim((string) ($row['value'] ?? '')), 0, 120);
            if ($value === '') continue;
            $p->adv[] = [
                'join'  => in_array($j = strtoupper((string) ($row['join'] ?? 'AND')), self::JOINS, true) ? $j : 'AND',
                'field' => array_key_exists($f = (string) ($row['field'] ?? 'all'), self::FIELDS) ? $f : 'all',
                'op'    => array_key_exists($o = (string) ($row['op'] ?? 'contains'), self::OPS) ? $o : 'contains',
                'value' => $value,
            ];
        }

        $p->sort = array_key_exists($sort = (string) $r->query('sort', 'relevance'), self::SORTS) ? $sort : 'relevance';
        $view = (string) ($r->query('view') ?? $r->cookie('sr_view') ?? 'list');
        $p->view = in_array($view, ['list', 'grid'], true) ? $view : 'list';
        $p->page = max(1, min(2000, (int) $r->query('page', 1)));

        return $p;
    }

    private static function strings($values): array
    {
        $out = [];
        foreach ((array) $values as $v) {
            if (! is_scalar($v)) continue;
            $v = mb_substr(trim((string) $v), 0, 255);
            if ($v !== '') $out[$v] = $v;
        }
        return array_values($out);
    }

    /** Year range narrowed from "all years", or undated records left out. */
    public function hasYearFilter(): bool
    {
        return $this->yearFrom !== null || $this->yearTo !== null || ! $this->undated;
    }

    public function hasFilters(): bool
    {
        return $this->types || $this->subjects || $this->authors || $this->publishers || $this->hasYearFilter()
            || $this->fulltext || $this->series || $this->saved || $this->adv;
    }

    /** Sort actually used: relevance only means something when there are search words. */
    public function effectiveSort(): string
    {
        return $this->sort === 'relevance' && $this->q === '' && ! $this->adv ? 'newest' : $this->sort;
    }

    /** Query-string values for this search (defaults left out, so links stay short). */
    public function toQuery(array $override = []): array
    {
        $year = $this->yearFrom !== null || $this->yearTo !== null ? ($this->yearFrom ?? '') . '-' . ($this->yearTo ?? '') : null;
        $q = array_filter([
            'query'     => $this->q,
            'in'        => $this->in !== 'all' ? $this->in : null,
            'type'      => $this->types,
            'subject'   => $this->subjects,
            'author'    => $this->authors,
            'publisher' => $this->publishers,
            'year'      => $year,
            'undated'   => $this->undated ? null : '0',
            'fulltext'  => $this->fulltext ? '1' : null,
            'series_statement_id' => $this->series,
            'saved'     => $this->saved ? '1' : null,
            'adv'       => $this->adv,
            'sort'      => $this->sort !== 'relevance' ? $this->sort : null,
            'page'      => $this->page > 1 ? $this->page : null,
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);

        foreach ($override as $k => $v) {
            if ($v === null || $v === [] || $v === '') unset($q[$k]); else $q[$k] = $v;
        }
        return $q;
    }

    public function url(array $override = []): string
    {
        $qs = http_build_query($this->toQuery($override), '', '&', PHP_QUERY_RFC3986);
        $qs = preg_replace('/%5B\d+%5D=/', '%5B%5D=', $qs);   // type[]=1&type[]=2, not type[0]=1
        return url('/results') . ($qs !== '' ? '?' . $qs : '');
    }

    /** A copy with one change applied, back on page 1. */
    public function with(callable $change): self
    {
        $c = clone $this;
        $change($c);
        $c->page = 1;
        return $c;
    }

    /**
     * The active filters as removable chips: [label, kind, params-without-it].
     *
     * @param  array<int, string>  $typeNames  material_type_id => name
     */
    public function chips(array $typeNames, ?string $seriesName = null): array
    {
        $chips = [];
        foreach ($this->adv as $i => $row) {
            $chips[] = ['kind' => 'adv', 'label' => ($i ? $row['join'] . ' · ' : '') . self::FIELDS[$row['field']] . ' ' . self::OPS[$row['op']] . ' “' . $row['value'] . '”',
                'params' => $this->with(fn ($c) => $c->adv = array_values(array_filter($c->adv, fn ($r, $k) => $k !== $i, ARRAY_FILTER_USE_BOTH)))];
        }
        foreach ($this->types as $id) {
            $chips[] = ['kind' => 'filter', 'label' => $typeNames[$id] ?? 'Type ' . $id, 'params' => $this->with(fn ($c) => $c->types = array_values(array_diff($c->types, [$id])))];
        }
        foreach ($this->subjects as $v) {
            $chips[] = ['kind' => 'filter', 'label' => 'Subject: ' . $v, 'params' => $this->with(fn ($c) => $c->subjects = array_values(array_diff($c->subjects, [$v])))];
        }
        foreach ($this->authors as $v) {
            $chips[] = ['kind' => 'filter', 'label' => 'Author: ' . $v, 'params' => $this->with(fn ($c) => $c->authors = array_values(array_diff($c->authors, [$v])))];
        }
        foreach ($this->publishers as $v) {
            $chips[] = ['kind' => 'filter', 'label' => 'Publisher: ' . $v, 'params' => $this->with(fn ($c) => $c->publishers = array_values(array_diff($c->publishers, [$v])))];
        }
        if ($this->yearFrom !== null || $this->yearTo !== null) {
            $label = $this->yearFrom !== null && $this->yearTo !== null ? ($this->yearFrom === $this->yearTo ? (string) $this->yearFrom : $this->yearFrom . '–' . $this->yearTo)
                : ($this->yearFrom !== null ? 'From ' . $this->yearFrom : 'Up to ' . $this->yearTo);
            $chips[] = ['kind' => 'filter', 'label' => $label, 'params' => $this->with(fn ($c) => [$c->yearFrom, $c->yearTo] = [null, null])];
        }
        if (! $this->undated) {
            $chips[] = ['kind' => 'filter', 'label' => 'Has a year', 'params' => $this->with(fn ($c) => $c->undated = true)];
        }
        if ($this->fulltext) {
            $chips[] = ['kind' => 'filter', 'label' => 'Full text', 'params' => $this->with(fn ($c) => $c->fulltext = false)];
        }
        if ($this->series) {
            $chips[] = ['kind' => 'filter', 'label' => 'Series: ' . ($seriesName ?: '#' . $this->series), 'params' => $this->with(fn ($c) => $c->series = null)];
        }
        if ($this->saved) {
            $chips[] = ['kind' => 'filter', 'label' => 'Saved records', 'params' => $this->with(fn ($c) => $c->saved = false)];
        }
        return $chips;
    }

    /** Everything cleared except the search words, field, sort and view. */
    public function cleared(): self
    {
        return $this->with(function ($c) {
            $c->types = $c->subjects = $c->authors = $c->publishers = $c->adv = [];
            $c->yearFrom = $c->yearTo = $c->series = null;
            $c->undated = true;
            $c->fulltext = $c->saved = false;
        });
    }
}
