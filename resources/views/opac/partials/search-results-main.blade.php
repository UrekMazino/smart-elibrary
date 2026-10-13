{{-- The search page's working area: advanced rows, filters, results. Rendered inside
     opac/search-results and, on its own, for the page's script (?partial=1), which swaps it in
     place when a filter, sort, page or view changes. Data: ResultController::index. --}}
@php
    use App\Services\CatalogSearchParams as SP;
    $r       = $result;
    $f       = $r['facets'] ?? [];
    $years   = $r['years'] ?? null;
    $isGrid  = $p->view === 'grid';
    $authed  = auth()->check();
    $advRows = $p->adv ?: [['join' => 'AND', 'field' => 'title', 'op' => 'contains', 'value' => ''], ['join' => 'AND', 'field' => 'subject', 'op' => 'contains', 'value' => '']];
    $limits  = ['subject' => 8, 'author' => 6, 'publisher' => 5];
    $yMin    = $years['min'] ?? null;
    $yMax    = $years['max'] ?? null;
    $from    = $p->yearFrom ?? $yMin;
    $to      = $p->yearTo ?? $yMax;
    $plural  = fn ($n, $w) => number_format($n) . ' ' . $w . ($n == 1 ? '' : 's');
@endphp

{{-- Advanced search: rows of field + match + value, joined with AND / OR / NOT. --}}
<div class="sr-adv" id="sr-adv" @unless($p->adv || ($advOpen ?? false)) hidden @endunless>
    <form class="container" id="sr-adv-form" method="get" action="{{ url('/results') }}">
        @foreach(array_diff_key($p->toQuery(), ['adv' => 1, 'page' => 1]) as $name => $value)
            @foreach((array) $value as $v)
                <input type="hidden" name="{{ is_array($value) ? $name . '[]' : $name }}" value="{{ $v }}">
            @endforeach
        @endforeach
        <div class="sr-adv-head">
            <h2>Advanced search</h2>
            <p>Each row narrows or widens the results. Rows are combined from top to bottom.</p>
        </div>
        <div class="sr-adv-rows" id="sr-adv-rows">
            @foreach($advRows as $i => $row)
                @include('opac.partials.search-adv-row', ['i' => $i, 'row' => $row])
            @endforeach
        </div>
        <div class="sr-adv-actions">
            <button type="button" class="sr-linkbtn" id="sr-adv-add">+ Add a row</button>
            <span class="sr-grow"></span>
            @if($p->adv)<a class="sr-linkbtn" href="{{ $p->with(fn ($c) => $c->adv = [])->url() }}" data-sr-link>Remove all rows</a>@endif
            <button type="submit" class="sr-btn sr-btn-solid">Apply</button>
        </div>
    </form>
</div>

<div class="container sr-layout">
    {{-- Filters. On phones this is a bottom sheet opened by the Filters button. --}}
    <form class="sr-facets" id="sr-filters" method="get" action="{{ url('/results') }}" aria-label="Filter results">
        <input type="hidden" name="query" value="{{ $p->q }}">
        @if($p->in !== 'all')<input type="hidden" name="in" value="{{ $p->in }}">@endif
        @if($p->series)<input type="hidden" name="series_statement_id" value="{{ $p->series }}">@endif
        @if($p->saved)<input type="hidden" name="saved" value="1">@endif
        @foreach($p->adv as $i => $row)
            @foreach($row as $k => $v)<input type="hidden" name="adv[{{ $i }}][{{ $k }}]" value="{{ $v }}">@endforeach
        @endforeach

        <div class="sr-sheet-head">
            <span>Filters</span>
            <button type="button" class="sr-close" data-sheet-close aria-label="Close filters">
                <svg width="14" height="14" viewBox="0 0 16 16" aria-hidden="true"><path stroke="currentColor" stroke-width="1.8" stroke-linecap="round" d="M3 3l10 10M13 3L3 13"/></svg>
            </button>
        </div>
        <h2 class="sr-facets-title">Filter results</h2>

        @php
            // Show a group only when it has options or is in use (an empty "None" list is noise).
            $showYear = ($years && $years['decades']) || $p->hasYearFilter() || ($years['undated'] ?? 0);
            $showFt   = $r['fulltext'] || $p->fulltext;
            $anyFacet = $f && ($f['type'] || $showYear || $f['subject'] || $f['author'] || $f['publisher'] || $showFt);
        @endphp
        @if($r['ok'] && !$anyFacet)
            <p class="sr-none">Nothing to filter in these results.</p>
        @endif
        @if($r['ok'])
        {{-- Material type --}}
        @if($f['type'])
        <fieldset class="sr-facet">
            <legend>Material type</legend>
            <div class="sr-opts">
                @foreach($f['type'] as $o)
                    <label class="sr-opt {{ $o['selected'] ? 'is-on' : '' }} {{ !$o['count'] && !$o['selected'] ? 'is-zero' : '' }}">
                        <input type="checkbox" name="type[]" value="{{ $o['value'] }}" @checked($o['selected']) @disabled(!$o['count'] && !$o['selected'])>
                        <span class="sr-opt-name">{{ $o['name'] }}</span><span class="sr-opt-n">{{ number_format($o['count']) }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>
        @endif

        {{-- Publication year: decade bars, range slider, exact years, undated records --}}
        <fieldset class="sr-facet" id="sr-year" data-min="{{ $yMin }}" data-max="{{ $yMax }}" @unless($showYear) hidden @endunless>
            <legend>Publication year</legend>
            @if($years && $years['decades'])
                @php $peak = max(1, max(array_column($years['decades'], 'count'))); @endphp
                <div class="sr-hist" role="group" aria-label="Records per decade">
                    @foreach($years['decades'] as $d)
                        <button type="button" class="{{ $d['in'] && $d['count'] ? 'is-in' : '' }}" style="height: {{ $d['count'] ? max(8, round($d['count'] / $peak * 100)) : 4 }}%"
                                data-decade="{{ $d['decade'] }}" title="{{ $d['decade'] }}s: {{ $plural($d['count'], 'record') }}"
                                aria-label="{{ $d['decade'] }}s, {{ $plural($d['count'], 'record') }}. Show only this decade"></button>
                    @endforeach
                </div>
                <div class="sr-hist-x" aria-hidden="true">
                    <span>{{ $years['decades'][0]['decade'] }}</span><span>{{ end($years['decades'])['decade'] }}s</span>
                </div>
                <div class="sr-range">
                    <div class="sr-range-track"></div><div class="sr-range-fill" id="sr-range-fill"></div>
                    <label class="visually-hidden" for="sr-ymin">Earliest year</label>
                    <input type="range" id="sr-ymin" min="{{ $yMin }}" max="{{ $yMax }}" step="1" value="{{ $from }}">
                    <label class="visually-hidden" for="sr-ymax">Latest year</label>
                    <input type="range" id="sr-ymax" min="{{ $yMin }}" max="{{ $yMax }}" step="1" value="{{ $to }}">
                </div>
                <div class="sr-years">
                    <label class="visually-hidden" for="sr-yfrom">From year</label>
                    <input type="number" id="sr-yfrom" min="{{ $yMin }}" max="{{ $yMax }}" value="{{ $from }}" inputmode="numeric">
                    <span>to</span>
                    <label class="visually-hidden" for="sr-yto">To year</label>
                    <input type="number" id="sr-yto" min="{{ $yMin }}" max="{{ $yMax }}" value="{{ $to }}" inputmode="numeric">
                </div>
            @else
                <p class="sr-none">No publication years in these results.</p>
            @endif
            <input type="hidden" name="year" id="sr-year-value" value="{{ $p->yearFrom !== null || $p->yearTo !== null ? ($p->yearFrom ?? '') . '-' . ($p->yearTo ?? '') : '' }}">
            <input type="hidden" name="undated" id="sr-undated-value" value="{{ $p->undated ? '' : '0' }}">
            @if($years && ($years['undated'] || !$p->undated))
                <label class="sr-opt sr-opt-plain">
                    <input type="checkbox" id="sr-undated" @checked($p->undated)>
                    <span class="sr-opt-name">Include records without a year</span><span class="sr-opt-n">{{ number_format($years['undated']) }}</span>
                </label>
            @endif
        </fieldset>

        {{-- Subject, author, publisher: long lists show their top options first --}}
        @foreach(['subject' => ['Subject', 'subject[]'], 'author' => ['Author', 'author[]'], 'publisher' => ['Publisher', 'publisher[]']] as $group => [$label, $name])
            @continue(!$f[$group])
            <fieldset class="sr-facet" data-facet="{{ $group }}">
                <legend>{{ $label }}</legend>
                @if($group === 'author' && count($f['author']) > $limits['author'])
                    <label class="visually-hidden" for="sr-find-author">Find an author</label>
                    <input type="search" class="sr-find" id="sr-find-author" data-find="author" placeholder="Find an author" autocomplete="off">
                @endif
                <div class="sr-opts" data-opts="{{ $group }}">
                    @forelse($f[$group] as $k => $o)
                        <label class="sr-opt {{ $o['selected'] ? 'is-on' : '' }} {{ !$o['count'] && !$o['selected'] ? 'is-zero' : '' }} {{ $k >= $limits[$group] && !$o['selected'] ? 'sr-extra' : '' }}">
                            <input type="checkbox" name="{{ $name }}" value="{{ $o['value'] }}" @checked($o['selected'])>
                            <span class="sr-opt-name">{{ $o['value'] }}</span><span class="sr-opt-n">{{ number_format($o['count']) }}</span>
                        </label>
                    @empty
                        <p class="sr-none">None in these results.</p>
                    @endforelse
                </div>
                @if(count($f[$group]) > $limits[$group])
                    <button type="button" class="sr-more" data-more="{{ $group }}" aria-expanded="false"
                            data-more-label="Show all {{ count($f[$group]) }}" data-less-label="Show fewer">Show all {{ count($f[$group]) }}</button>
                @endif
            </fieldset>
        @endforeach

        {{-- Availability --}}
        <fieldset class="sr-facet" @unless($showFt) hidden @endunless>
            <legend>Availability</legend>
            <label class="sr-opt {{ $p->fulltext ? 'is-on' : '' }} {{ !$r['fulltext'] && !$p->fulltext ? 'is-zero' : '' }}">
                <input type="checkbox" name="fulltext" value="1" @checked($p->fulltext) @disabled(!$r['fulltext'] && !$p->fulltext)>
                <span class="sr-opt-name">Full text available</span><span class="sr-opt-n">{{ number_format($r['fulltext']) }}</span>
            </label>
        </fieldset>
        @endif

        <noscript><button type="submit" class="sr-btn sr-btn-solid sr-noscript">Apply filters</button></noscript>
        <div class="sr-sheet-foot">
            <button type="button" class="sr-btn sr-btn-solid" data-sheet-close>Show {{ $plural($r['total'], 'result') }}</button>
        </div>
    </form>

    <section class="sr-results" aria-labelledby="sr-count">
        <div class="sr-head">
            <h2 class="sr-count" id="sr-count" data-total="{{ $r['total'] }}">
                @if($p->saved && !$p->q)
                    <strong>{{ number_format($r['total']) }}</strong> saved {{ $r['total'] == 1 ? 'record' : 'records' }}
                @else
                    <strong>{{ number_format($r['total']) }}</strong> {{ $r['total'] == 1 ? 'result' : 'results' }}@if($p->q) for <q>{{ $p->q }}</q>@if($p->in !== 'all') in {{ SP::FIELDS[$p->in] }}@endif @endif
                @endif
            </h2>
            <button type="button" class="sr-btn sr-filters-btn" data-sheet-open aria-controls="sr-filters">
                <svg width="14" height="14" viewBox="0 0 16 16" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" d="M2 4h12M4 8h8M6 12h4"/></svg>
                Filters @if(count($chips))<span class="sr-pill">{{ count($chips) }}</span>@endif
            </button>
            <label class="sr-sort" for="sr-sort">Sort
                <select id="sr-sort" name="sort" form="sr-filters">
                    @foreach(SP::SORTS as $key => $label)
                        <option value="{{ $key }}" @selected($p->sort === $key)>{{ $key === 'relevance' && $p->effectiveSort() !== 'relevance' ? 'Relevance (newest first)' : $label }}</option>
                    @endforeach
                </select>
            </label>
            <div class="sr-view" role="group" aria-label="Show results as">
                <button type="button" data-view="list" aria-pressed="{{ $isGrid ? 'false' : 'true' }}" aria-label="List" title="List">
                    <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" d="M2 3.5h12M2 8h12M2 12.5h12"/></svg>
                </button>
                <button type="button" data-view="grid" aria-pressed="{{ $isGrid ? 'true' : 'false' }}" aria-label="Grid" title="Grid">
                    <svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path fill="currentColor" d="M2 2h5v5H2zM9 2h5v5H9zM2 9h5v5H2zM9 9h5v5H9z"/></svg>
                </button>
            </div>
        </div>

        @if(count($chips))
            <div class="sr-chips" aria-label="Active filters">
                @foreach($chips as $chip)
                    <a class="sr-chip {{ $chip['kind'] === 'adv' ? 'is-adv' : '' }}" href="{{ $chip['params']->url() }}" data-sr-link title="Remove this filter">
                        <span>{{ $chip['label'] }}</span>
                        <svg width="10" height="10" viewBox="0 0 16 16" aria-hidden="true"><path stroke="currentColor" stroke-width="2.4" stroke-linecap="round" d="M3 3l10 10M13 3L3 13"/></svg>
                        <span class="visually-hidden">(remove)</span>
                    </a>
                @endforeach
                @if(count($chips) > 1)<a class="sr-linkbtn" href="{{ $p->cleared()->url() }}" data-sr-link>Clear filters</a>@endif
            </div>
        @endif

        @if(!$r['ok'])
            <div class="sr-empty" role="alert">
                <h3>Search is temporarily unavailable</h3>
                <p>The catalog search service isn't answering. Please try again in a few minutes.</p>
            </div>
        @elseif(!$r['total'])
            <div class="sr-empty">
                <h3>No records match @if($p->q)<q>{{ $p->q }}</q>@else these filters @endif @if($p->q && count($chips)) with these filters @endif</h3>
                @if($fixes)
                    <p>One of these changes will bring results back:</p>
                    <div class="sr-fixes">
                        @foreach($fixes as $fix)
                            <a class="sr-fix" href="{{ $fix['url'] }}" data-sr-link><span>{{ $fix['label'] }}</span><span class="sr-fix-n">{{ $plural($fix['count'], 'result') }}</span></a>
                        @endforeach
                    </div>
                @elseif($p->saved)
                    <p>Use the Save button on any search result to keep it here.</p>
                @else
                    <p>Check the spelling, or try fewer or more general words.</p>
                @endif
                @if($p->q && $aiEnabled)
                    <a class="sr-fix sr-fix-ai" href="{{ url('/results') }}?mode=ai&amp;query={{ rawurlencode($p->q) }}"><span>Ask AI about <q>{{ $p->q }}</q></span><span class="sr-fix-n">AI answer</span></a>
                @endif
            </div>
        @else
            <ol class="sr-list {{ $isGrid ? 'is-grid' : '' }}" start="{{ ($r['page'] - 1) * $r['perPage'] + 1 }}">
                @foreach($r['hits'] as $h)
                    @php
                        $url    = recordUrl(['catalog_id' => $h['catalog_id'], 'title_statement' => $h['title']]);
                        $read   = $h['has_fulltext'] ? getReadUrl($h['catalog_id']) : '';
                        $rating = starRatingAve($h['catalog_id']);
                        $avail  = checkStocks($h['catalog_id']);
                        $saved  = in_array($h['catalog_id'], $savedIds, true);
                        $year   = $h['pub_year'] ? trim($h['pub_year'], '[]. ') : null;
                    @endphp
                    <li class="sr-item">
                        <a class="sr-cover" href="{{ $url }}" tabindex="-1" aria-hidden="true">
                            <img src="{{ getThumbnailLink($h['catalog_id']) }}" alt="" loading="lazy">
                        </a>
                        <div class="sr-body">
                            <h3 class="sr-title"><a href="{{ $url }}">{!! searchHighlight($h['title_hl']) !!}</a></h3>
                            <p class="sr-meta">
                                @forelse(array_slice($h['authors'], 0, 3) as $i => $author)
                                    @if($i)<span class="sr-sep" aria-hidden="true">/</span>@endif
                                    <a class="sr-au" href="{{ $p->with(fn ($c) => $c->authors = array_values(array_unique([...$c->authors, $author])))->url() }}" data-sr-link
                                       title="Show records by {{ $author }}">{!! searchHighlight($h['authors_hl'][$i] ?? $author) !!}</a>
                                @empty
                                    <span>No author recorded</span>
                                @endforelse
                                @if(count($h['authors']) > 3)<span>and {{ count($h['authors']) - 3 }} more</span>@endif
                                @if($year)<span class="sr-dot" aria-hidden="true">·</span><span>{{ $year }}</span>@endif
                                @if($h['publisher'] && !$isGrid)<span class="sr-dot" aria-hidden="true">·</span><span>{!! searchHighlight($h['publisher_hl']) !!}</span>@endif
                            </p>
                            @if($h['summary_hl'] && !$isGrid)<p class="sr-snippet">{!! searchHighlight($h['summary_hl']) !!}</p>@endif
                            <div class="sr-badges">
                                @if($h['type'])<span class="sr-badge">{{ $h['type'] }}</span>@endif
                                @if($read)<span class="sr-badge is-ft">Full text</span>@endif
                                @if($avail)<span class="sr-badge is-avail">Available to borrow</span>@endif
                                @if(is_numeric($rating) && $rating > 0)<span class="rating sr-stars" title="Rated {{ $rating }} of 5"><i data-star="{{ $rating }}"></i></span>@endif
                                @if($h['call'] && !$isGrid)<span class="sr-callno">{!! searchHighlight($h['call_hl']) !!}</span>@endif
                            </div>
                            @if($h['subjects'] && !$isGrid)
                                <div class="sr-tags">
                                    @foreach(array_slice($h['subjects'], 0, 6) as $i => $subject)
                                        <a class="sr-tag {{ in_array($subject, $p->subjects, true) ? 'is-on' : '' }}" data-sr-link
                                           href="{{ $p->with(fn ($c) => $c->subjects = array_values(array_unique([...$c->subjects, $subject])))->url() }}"
                                           title="Show records about {{ $subject }}">{!! searchHighlight($h['subjects_hl'][$i] ?? $subject) !!}</a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        <div class="sr-acts">
                            @if($read)
                                <button type="button" class="sr-act sr-act-main" data-read="{{ $read }}" data-id="{{ $h['catalog_id'] }}" data-title="{{ $h['title'] }}">Read</button>
                                <button type="button" class="sr-act" data-download="{{ $h['catalog_id'] }}" title="Download the PDF (sign-in required)">Download</button>
                            @endif
                            <button type="button" class="sr-act" data-quick="{{ $h['catalog_id'] }}" aria-haspopup="dialog" title="See the details without leaving the results">Quick view</button>
                            <button type="button" class="sr-act" data-cite="{{ $h['catalog_id'] }}">Cite</button>
                            @if($authed)
                                <button type="button" class="sr-act" data-save="{{ $h['catalog_id'] }}" aria-pressed="{{ $saved ? 'true' : 'false' }}">{{ $saved ? 'Saved' : 'Save' }}</button>
                            @else
                                <a class="sr-act" href="{{ route('login') }}" title="Log in to save records">Save</a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>

            @if($r['pages'] > 1)
                @php
                    $cur = $r['page']; $last = $r['pages'];
                    $pages = array_values(array_unique(array_filter([1, $cur - 2, $cur - 1, $cur, $cur + 1, $cur + 2, $last], fn ($n) => $n >= 1 && $n <= $last)));
                @endphp
                <nav class="sr-pager" aria-label="Result pages">
                    @if($cur > 1)<a href="{{ $p->url(['page' => $cur - 1]) }}" data-sr-link data-sr-scroll rel="prev" aria-label="Previous page">‹</a>@else<span class="is-off" aria-hidden="true">‹</span>@endif
                    @foreach($pages as $k => $n)
                        @if($k && $n - $pages[$k - 1] > 1)<span class="sr-gap" aria-hidden="true">…</span>@endif
                        @if($n === $cur)<span aria-current="page">{{ $n }}</span>@else<a href="{{ $p->url(['page' => $n]) }}" data-sr-link data-sr-scroll>{{ $n }}</a>@endif
                    @endforeach
                    @if($cur < $last)<a href="{{ $p->url(['page' => $cur + 1]) }}" data-sr-link data-sr-scroll rel="next" aria-label="Next page">›</a>@else<span class="is-off" aria-hidden="true">›</span>@endif
                </nav>
            @endif
        @endif
    </section>
</div>
