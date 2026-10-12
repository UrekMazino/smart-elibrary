{{-- The OPAC search band (teal): [Search | Ask AI] [Advanced] [Saved]  ……  [Field ▾][ search words ][Search]
     with grouped suggestions (titles, authors, subjects) as you type. The same on the search results
     page and the record page; on desktop it stays docked under the site header (functions.js sets
     its top from id="results").
     $q, $in     — the current search words and field (empty / "all" outside the results page)
     $advToggle  — true on the results page: Advanced opens the rows in place; elsewhere it links there
     $advOpen    — the Advanced rows are showing
     $savedOn    — the patron is looking at their saved list --}}
@php
    $q         = $q ?? '';
    $in        = $in ?? 'all';
    $advToggle = $advToggle ?? false;
    $advOpen   = $advOpen ?? false;
    $savedOn   = $savedOn ?? false;
    $aiOn      = app(\App\Services\AiSearchService::class)->isEnabled();
    $savedN    = auth()->check() ? \App\Models\SavedRecord::where('user_id', auth()->id())->count() : 0;
@endphp
<style>
:root {
    --sr-teal: #00a496; --sr-teal-ink: #00756b; --sr-teal-dark: #005f57; --sr-teal-soft: #e3f5f2; --sr-teal-line: #a7dcd5;
    --sr-indigo: #3f4079; --sr-indigo-soft: #ececf7; --sr-ink: #1d2433; --sr-muted: #5d6973; --sr-line: #e1e7ea; --sr-sunk: #f3f6f7;
    --sr-mark: #ffe9a8; --sr-rose: #b8345f; --sr-rose-soft: #fbe9ef; --sr-green: #1d6f42; --sr-green-soft: #e3f3e9;
    --sr-mono: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
}
/* id="results" keeps the theme's behaviour (functions.js docks it under the header and pads the body
   when the header turns fixed); the theme colours #results indigo, the OPAC band is teal. */
#results.sr-band { background: var(--sr-teal); color: #fff; padding: 0; box-shadow: 0 3px 10px rgba(0,0,0,.08); position: relative; z-index: 1001; }
@media (min-width: 992px) { #results.sr-band { position: -webkit-sticky; position: sticky; } }
@media (max-width: 991.98px) { #results.sr-band { top: auto !important; } }   /* scrolls with the page below desktop */
.sr-band [hidden] { display: none !important; }
.sr-band :focus-visible { outline: 2px solid #fff; outline-offset: 2px; }
.sr-band-inner { display: flex; align-items: center; gap: 10px 24px; padding: 16px 0; }
.sr-band-inner .sr-search { flex: 0 1 700px; margin-left: auto; }
.sr-search { display: flex; align-items: stretch; background: #fff; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,.12); position: relative; min-width: 0; margin: 0; }
.sr-search:focus-within { box-shadow: 0 0 0 3px rgba(255,255,255,.6); }
.sr-search :focus-visible { outline-color: var(--sr-teal-ink); }
.sr-search select { flex: none; max-width: 140px; border: 0; border-radius: 6px 0 0 6px; background-color: var(--sr-sunk); color: var(--sr-ink); font-size: 13px; font-weight: 500; padding: 0 28px 0 12px;
    appearance: none; -webkit-appearance: none; background-image: linear-gradient(45deg, transparent 50%, var(--sr-muted) 50%), linear-gradient(135deg, var(--sr-muted) 50%, transparent 50%);
    background-position: calc(100% - 14px) 52%, calc(100% - 9px) 52%; background-size: 5px 5px; background-repeat: no-repeat; }
.sr-q { flex: 1; min-width: 0; }
.sr-q input { width: 100%; height: 46px; border: 0; background: transparent; padding: 0 12px; font-size: 15px; color: var(--sr-ink); outline: none; }
.sr-go { flex: none; border: 0; background: var(--sr-teal-dark); color: #fff; font-weight: 600; padding: 0 22px; border-radius: 0 6px 6px 0; }
.sr-go:hover { background: #004a44; }
.sr-tools { flex: none; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
.sr-seg { display: inline-flex; background: rgba(255,255,255,.18); border-radius: 999px; padding: 3px; }
.sr-seg button { border: 0; background: none; color: #fff; font-size: 12.5px; font-weight: 500; padding: 4px 13px; border-radius: 999px; }
.sr-seg button[aria-pressed="true"] { background: #fff; color: var(--sr-teal-dark); box-shadow: 0 1px 3px rgba(0,0,0,.15); }
.sr-tool { display: inline-flex; align-items: center; gap: 6px; border: 1px solid rgba(255,255,255,.6); background: transparent; color: #fff; border-radius: 8px; padding: 4px 11px; font-size: 12.5px; font-weight: 500; line-height: 1.5; text-decoration: none; }
.sr-tool:hover { background: rgba(255,255,255,.16); color: #fff; }
.sr-tool[aria-expanded="true"], .sr-tool.is-on { background: #fff; color: var(--sr-teal-dark); border-color: #fff; }
.sr-pill { display: inline-block; min-width: 20px; text-align: center; font-size: 11.5px; font-variant-numeric: tabular-nums; background: var(--sr-sunk); border-radius: 999px; padding: 0 7px; }
.sr-tool .sr-pill { background: rgba(255,255,255,.22); }
.sr-tool.is-on .sr-pill, .sr-tool[aria-expanded="true"] .sr-pill { background: var(--sr-teal-soft); }
/* Suggestions under the search box */
.sr-suggest { position: absolute; left: 0; right: 0; top: calc(100% + 6px); z-index: 1100; background: #fff; color: var(--sr-ink); border: 1px solid var(--sr-line); border-radius: 10px; box-shadow: 0 12px 30px rgba(0,0,0,.18); max-height: min(70vh, 470px); overflow: auto; padding: 4px 0; }
.sr-suggest mark { background: var(--sr-mark); color: inherit; padding: 0 1px; border-radius: 2px; }
.sr-sg-h { font-size: 10.5px; text-transform: uppercase; letter-spacing: .7px; font-weight: 600; color: var(--sr-muted); padding: 8px 14px 2px; }
.sr-sg-group + .sr-sg-group { border-top: 1px solid var(--sr-line); }
.sr-sg-item { display: flex; gap: 10px; align-items: baseline; padding: 7px 14px; color: var(--sr-ink); text-decoration: none; }
.sr-sg-item:hover, .sr-sg-item.is-active { background: var(--sr-teal-soft); color: var(--sr-ink); }
.sr-sg-item .t { flex: 1; min-width: 0; overflow-wrap: anywhere; font-size: 14px; }
.sr-sg-item .k { font-size: 12px; color: var(--sr-muted); white-space: nowrap; font-variant-numeric: tabular-nums; }
.sr-sg-foot { border-top: 1px solid var(--sr-line); padding: 7px 14px 3px; font-size: 12px; color: var(--sr-muted); }
.sr-sg-foot kbd { font-family: var(--sr-mono); font-size: 11px; color: var(--sr-ink); background: var(--sr-sunk); border: 1px solid var(--sr-line); border-radius: 4px; padding: 0 4px; }
.sr-sg-hint { padding: 9px 14px; font-size: 13px; color: var(--sr-muted); }
@media (max-width: 767.98px) {
    /* Phones: the search box first, full width; the buttons under it. */
    .sr-band-inner { flex-wrap: wrap; padding: 12px 0; }
    .sr-band-inner .sr-search { order: -1; flex-basis: 100%; margin-left: 0; }
    .sr-search select { max-width: 104px; font-size: 12px; padding-left: 9px; }
    .sr-go { padding: 0 14px; }
}
</style>

<div class="sr-band" id="results">
    <div class="container">
        <div class="sr-band-inner">
            <div class="sr-tools">
                @if($aiOn)
                    <div class="sr-seg" role="group" aria-label="Search mode">
                        <button type="button" aria-pressed="true">Search</button>
                        <button type="button" id="sr-ask-ai" aria-pressed="false" title="Get an answer written from the collection (sign-in required)">Ask AI</button>
                    </div>
                @endif
                @if($advToggle)
                    <button type="button" class="sr-tool" id="sr-adv-toggle" aria-expanded="{{ $advOpen ? 'true' : 'false' }}" aria-controls="sr-adv">
                @else
                    <a class="sr-tool" href="{{ url('/results') }}?advanced=1">
                @endif
                    <svg width="14" height="14" viewBox="0 0 16 16" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" d="M2 4h12M4 8h8M6 12h4"/></svg>Advanced
                @if($advToggle)</button>@else</a>@endif
                @auth
                    <a class="sr-tool {{ $savedOn ? 'is-on' : '' }}" href="{{ $savedOn ? url('/results') : url('/results') . '?saved=1' }}" id="sr-saved-link">
                        <svg width="14" height="14" viewBox="0 0 16 16" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" d="M8 1.8l1.9 3.9 4.3.6-3.1 3 .7 4.3L8 11.6l-3.8 2 .7-4.3-3.1-3 4.3-.6z"/></svg>
                        Saved <span class="sr-pill" id="sr-saved-n">{{ $savedN }}</span>
                    </a>
                @endauth
            </div>
            <form class="sr-search" id="sr-search" method="get" action="{{ url('/results') }}" role="search" autocomplete="off">
                <input type="hidden" name="mode" id="sr-mode" value="">
                <label class="visually-hidden" for="sr-in">Search in</label>
                <select id="sr-in" name="in">
                    @foreach(App\Services\CatalogSearchParams::FIELDS as $key => $label)
                        <option value="{{ $key }}" @selected($in === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="sr-q">
                    <label class="visually-hidden" for="sr-query">Search words</label>
                    <input type="search" id="sr-query" name="query" value="{{ $q }}" maxlength="200" placeholder="Search titles, authors, subjects, call numbers…"
                           role="combobox" aria-autocomplete="list" aria-controls="sr-suggest" aria-expanded="false">
                    <div class="sr-suggest" id="sr-suggest" role="listbox" aria-label="Suggestions" hidden></div>
                </div>
                <button type="submit" class="sr-go">Search</button>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';
    var SUGGEST = @json(url('/search/suggest'));
    var q = document.getElementById('sr-query'), box = document.getElementById('sr-suggest');
    var timer = null, seq = 0, index = -1;
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
    function items() { return box.querySelectorAll('.sr-sg-item'); }
    function close() { box.hidden = true; q.setAttribute('aria-expanded', 'false'); q.removeAttribute('aria-activedescendant'); index = -1; }
    function open(html) { box.innerHTML = html; box.hidden = false; q.setAttribute('aria-expanded', 'true'); index = -1; }

    function render(data) {
        var html = '', n = 0;
        function group(label, list, row) {
            if (!list || !list.length) return;
            html += '<div class="sr-sg-group" role="group" aria-label="' + label + '"><div class="sr-sg-h">' + label + '</div>';
            list.forEach(function (it) { html += '<a class="sr-sg-item" role="option" id="sr-sg-' + (n++) + '" href="' + esc(it.url) + '">' + row(it) + '</a>'; });
            html += '</div>';
        }
        // Titles arrive escaped, with <mark> around the matched words (searchHighlight()).
        group('Titles', data.titles, function (t) { return '<span class="t">' + t.title + '</span><span class="k">' + esc(t.year || '') + '</span>'; });
        var count = function (o) { return '<span class="t">' + esc(o.value) + '</span><span class="k">' + o.count + ' record' + (o.count === 1 ? '' : 's') + '</span>'; };
        group('Authors', data.authors, count);
        group('Subjects', data.subjects, count);
        if (!n) { close(); return; }
        open(html + '<div class="sr-sg-foot"><kbd>↑</kbd> <kbd>↓</kbd> to move, <kbd>Enter</kbd> to open, <kbd>Esc</kbd> to close</div>');
    }

    q.addEventListener('input', function () {
        clearTimeout(timer);
        var text = q.value.trim();
        if (text.length < 2) { close(); return; }
        timer = setTimeout(function () {
            var mine = ++seq;
            fetch(SUGGEST + '?groups=1&q=' + encodeURIComponent(text), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (data) { if (mine === seq && q.value.trim() === text) render(data); })
                .catch(function (err) { console.error(err); close(); });
        }, 160);
    });
    q.addEventListener('keydown', function (e) {
        var list = items();
        if (box.hidden || !list.length) return;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            index = (index + (e.key === 'ArrowDown' ? 1 : -1) + list.length) % list.length;
            list.forEach(function (el, i) { el.classList.toggle('is-active', i === index); el.setAttribute('aria-selected', i === index ? 'true' : 'false'); });
            list[index].scrollIntoView({ block: 'nearest' });
            q.setAttribute('aria-activedescendant', list[index].id);
        } else if (e.key === 'Enter' && index >= 0) {
            e.preventDefault(); location.href = list[index].href;
        } else if (e.key === 'Escape') {
            close();
        }
    });
    document.addEventListener('mousedown', function (e) { if (!e.target.closest('.sr-search')) close(); });

    // Ask AI: the same words go to the AI answer page.
    var ask = document.getElementById('sr-ask-ai');
    if (ask) ask.addEventListener('click', function () {
        if (!q.value.trim()) { q.focus(); open('<div class="sr-sg-hint">Type a question first, then choose Ask AI.</div>'); return; }
        document.getElementById('sr-mode').value = 'ai';
        document.getElementById('sr-search').submit();
    });
})();
</script>
