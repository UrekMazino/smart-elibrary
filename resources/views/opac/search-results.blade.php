@extends('opac.layout.master')

{{-- OPAC search results: field search with suggestions, filters with counts (type, year, subject,
     author, publisher, full text), advanced rows, sort, list / grid, Quick view, Cite and Save.
     Engine: App\Services\CatalogSearch (Meilisearch). The working area is
     opac/partials/search-results-main, which the script below swaps in place on every change. --}}

@section('meta_title', 'Search results — DOST-PCAARRD eLibrary')
@section('robots', 'noindex, follow')

{{-- Breadcrumb: in the site header on desktop, a slim strip under it on smaller screens. --}}
@php $crumbs = [['Home', url('/home')], ['Search Results', null]]; @endphp
@section('header_breadcrumb')
    @include('opac.partials.breadcrumb', ['crumbs' => $crumbs])
@endsection

@section('css')
<style>
/* Search page. The palette (--sr-*) comes with the search band (opac/partials/search-band):
   teal band, deeper teal for text and buttons on white (readable), indigo for badges. */
.sr-page { --sr-top: 96px; color: var(--sr-ink); background: #fff; }
.sr-page mark { background: var(--sr-mark); color: inherit; padding: 0 1px; border-radius: 2px; }
.sr-page :focus-visible { outline: 2px solid var(--sr-teal-ink); outline-offset: 2px; }
.sr-page [hidden] { display: none !important; }
.sr-grow { flex: 1; }

/* Buttons and small controls */
.sr-btn { display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--sr-line); background: #fff; color: var(--sr-ink); border-radius: 8px; padding: 6px 12px; font-size: 13px; font-weight: 500; line-height: 1.3; }
.sr-btn:hover { border-color: var(--sr-teal-line); }
.sr-btn-solid { background: var(--sr-teal-ink); border-color: var(--sr-teal-ink); color: #fff; font-weight: 600; }
.sr-btn-solid:hover { background: var(--sr-teal-dark); color: #fff; }
.sr-linkbtn { border: 0; background: none; padding: 4px 2px; color: var(--sr-teal-ink); font-size: 13px; font-weight: 500; text-decoration: none; }
.sr-linkbtn:hover { text-decoration: underline; color: var(--sr-teal-dark); }
.sr-icon-btn { width: 36px; height: 36px; display: grid; place-items: center; border: 1px solid var(--sr-line); background: #fff; color: var(--sr-muted); border-radius: 8px; }
.sr-icon-btn:hover { color: var(--sr-rose); border-color: var(--sr-rose); }
.sr-close { width: 32px; height: 32px; display: grid; place-items: center; border: 0; background: var(--sr-sunk); border-radius: 50%; color: var(--sr-ink); }

/* Advanced rows */
.sr-adv { background: var(--sr-sunk); border-bottom: 1px solid var(--sr-line); }
.sr-adv form { display: grid; gap: 10px; padding-top: 16px; padding-bottom: 16px; }
.sr-adv-head h2 { font-size: 15px; font-weight: 600; margin: 0; }
.sr-adv-head p { margin: 2px 0 0; font-size: 13px; color: var(--sr-muted); }
.sr-adv-rows { display: grid; gap: 8px; }
.sr-adv-row { display: grid; grid-template-columns: 84px 160px 170px minmax(0, 1fr) 36px; gap: 8px; align-items: center; }
.sr-adv-row select, .sr-adv-row input[type="text"] { width: 100%; min-width: 0; height: 36px; border: 1px solid var(--sr-line); background: #fff; color: var(--sr-ink); border-radius: 8px; padding: 0 9px; font-size: 13px; }
.sr-adv-where { font-size: 12px; text-transform: uppercase; letter-spacing: .5px; color: var(--sr-muted); text-align: center; }
.sr-adv-actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }

/* Layout */
.sr-main { transition: opacity .15s; }
.sr-main.is-loading { opacity: .55; pointer-events: none; }
.sr-layout { display: grid; grid-template-columns: 260px minmax(0, 1fr); gap: 30px; align-items: start; padding-top: 24px; padding-bottom: 48px; }

/* Filters */
.sr-facets { position: sticky; top: var(--sr-top); max-height: calc(100vh - var(--sr-top) - 16px); overflow: auto; overscroll-behavior: contain; padding-right: 6px; margin: 0; }
.sr-facets-title { font-size: 16px; font-weight: 600; margin: 0 0 6px; }
.sr-facet { border: 0; border-top: 1px solid var(--sr-line); padding: 12px 0 10px; margin: 0; min-width: 0; }
.sr-facet legend { float: left; width: 100%; font-size: 11.5px; text-transform: uppercase; letter-spacing: .7px; font-weight: 600; color: var(--sr-muted); margin: 0 0 8px; padding: 0; }
.sr-facet legend + * { clear: left; }
.sr-opts { display: grid; gap: 1px; }
.sr-opt { display: flex; align-items: flex-start; gap: 9px; padding: 4px 6px; margin: 0; border-radius: 6px; cursor: pointer; font-size: 13.5px; line-height: 1.35; }
.sr-opt:hover { background: var(--sr-sunk); }
.sr-opt input { flex: none; margin: 2px 0 0; accent-color: var(--sr-teal-ink); width: 15px; height: 15px; }
.sr-opt-name { flex: 1; min-width: 0; overflow-wrap: anywhere; }
.sr-opt-n { font-size: 12px; color: var(--sr-muted); font-variant-numeric: tabular-nums; padding-top: 1px; }
.sr-opt.is-on .sr-opt-name { font-weight: 600; }
.sr-opt.is-zero { opacity: .45; cursor: default; }
.sr-opt-plain { margin-top: 6px; }
.sr-extra { display: none; }
.sr-facet.is-expanded .sr-extra, .sr-facet.is-finding .sr-extra { display: flex; }
.sr-more { border: 0; background: none; color: var(--sr-teal-ink); font-size: 12.5px; font-weight: 500; padding: 5px 6px; }
.sr-more:hover { text-decoration: underline; }
.sr-find { width: 100%; height: 32px; border: 1px solid var(--sr-line); border-radius: 8px; padding: 0 9px; font-size: 13px; margin-bottom: 6px; }
.sr-none { font-size: 13px; color: var(--sr-muted); margin: 0; padding: 2px 6px; }
.sr-noscript { margin-top: 12px; width: 100%; justify-content: center; }

/* Year: decade bars + range */
.sr-hist { display: flex; align-items: flex-end; gap: 3px; height: 56px; }
.sr-hist button { flex: 1; min-width: 0; border: 0; padding: 0; background: var(--sr-line); border-radius: 3px 3px 0 0; }
.sr-hist button.is-in { background: var(--sr-teal); }
.sr-hist button:hover { background: var(--sr-teal-ink); }
.sr-hist-x { display: flex; justify-content: space-between; font: 10.5px var(--sr-mono); color: var(--sr-muted); margin-top: 3px; }
.sr-range { position: relative; height: 26px; margin-top: 4px; }
.sr-range-track, .sr-range-fill { position: absolute; top: 11px; height: 4px; border-radius: 2px; }
.sr-range-track { left: 0; right: 0; background: var(--sr-line); }
.sr-range-fill { background: var(--sr-teal); }
.sr-range input[type="range"] { position: absolute; left: 0; right: 0; top: 11px; width: 100%; height: 4px; margin: 0; background: none; pointer-events: none; -webkit-appearance: none; appearance: none; }
.sr-range input[type="range"]::-webkit-slider-runnable-track { height: 4px; background: transparent; }
.sr-range input[type="range"]::-moz-range-track { height: 4px; background: transparent; }
.sr-range input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; pointer-events: auto; width: 18px; height: 18px; margin-top: -7px; border-radius: 50%; background: #fff; border: 2px solid var(--sr-teal-ink); cursor: grab; }
.sr-range input[type="range"]::-moz-range-thumb { pointer-events: auto; width: 14px; height: 14px; border-radius: 50%; background: #fff; border: 2px solid var(--sr-teal-ink); cursor: grab; }
.sr-years { display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: var(--sr-muted); }
.sr-years input { width: 76px; height: 30px; border: 1px solid var(--sr-line); border-radius: 7px; padding: 0 7px; font-size: 13px; color: var(--sr-ink); font-variant-numeric: tabular-nums; }

/* Results */
.sr-results { min-width: 0; display: grid; gap: 12px; align-content: start; }
.sr-head { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 14px; }
.sr-count { flex: 1 1 auto; min-width: 0; margin: 0; font-size: 16px; font-weight: 400; color: var(--sr-ink); scroll-margin-top: var(--sr-top); }
.sr-count strong { font-weight: 600; font-variant-numeric: tabular-nums; }
.sr-count q { font-style: italic; }
.sr-filters-btn { display: none; }
.sr-sort { display: inline-flex; align-items: center; gap: 6px; margin: 0; font-size: 13px; color: var(--sr-muted); }
.sr-sort select { height: 32px; border: 1px solid var(--sr-line); border-radius: 8px; padding: 0 8px; font-size: 13px; color: var(--sr-ink); background: #fff; }
.sr-view { display: inline-flex; border: 1px solid var(--sr-line); border-radius: 8px; overflow: hidden; }
.sr-view button { border: 0; background: #fff; color: var(--sr-muted); padding: 5px 9px; display: grid; place-items: center; }
.sr-view button[aria-pressed="true"] { background: var(--sr-teal-soft); color: var(--sr-teal-ink); }
.sr-chips { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
.sr-chip { display: inline-flex; align-items: center; gap: 7px; max-width: 100%; background: var(--sr-teal-soft); color: var(--sr-teal-ink); border: 1px solid var(--sr-teal-line); border-radius: 999px; padding: 3px 11px; font-size: 12.5px; font-weight: 500; text-decoration: none; }
.sr-chip > span:first-child { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.sr-chip:hover { background: var(--sr-teal-line); color: var(--sr-teal-dark); }
.sr-chip.is-adv { background: var(--sr-indigo-soft); color: var(--sr-indigo); border-color: transparent; }

.sr-list { list-style: none; margin: 0; padding: 0; border: 1px solid var(--sr-line); border-radius: 12px; background: #fff; }
.sr-item { display: grid; grid-template-columns: 70px minmax(0, 1fr) auto; gap: 16px; padding: 16px; border-top: 1px solid var(--sr-line); }
.sr-item:first-child { border-top: 0; }
.sr-cover { display: block; align-self: start; }
.sr-cover img { width: 100%; aspect-ratio: 3 / 4; object-fit: cover; border-radius: 3px; box-shadow: 0 1px 4px rgba(0,0,0,.2); background: var(--sr-sunk); }
.sr-body { min-width: 0; display: grid; gap: 5px; align-content: start; }
.sr-title { margin: 0; font-size: 16.5px; line-height: 1.35; font-weight: 600; overflow-wrap: anywhere; }
.sr-title a { color: var(--sr-ink); text-decoration: none; }
.sr-title a:hover { color: var(--sr-teal-ink); text-decoration: underline; text-underline-offset: 3px; }
.sr-meta { margin: 0; font-size: 13px; color: var(--sr-muted); display: flex; flex-wrap: wrap; gap: 2px 8px; align-items: baseline; }
.sr-au { color: var(--sr-ink); text-decoration: none; }
.sr-au:hover { color: var(--sr-teal-ink); text-decoration: underline; }
.sr-sep, .sr-dot { color: #b9c3c9; }
.sr-snippet { margin: 0; font-size: 13.5px; color: var(--sr-muted); max-width: 75ch; }
.sr-badges { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; }
.sr-badge { font-size: 11.5px; font-weight: 500; border-radius: 999px; padding: 1px 9px; background: var(--sr-indigo-soft); color: var(--sr-indigo); }
.sr-badge.is-ft { background: var(--sr-teal-soft); color: var(--sr-teal-ink); }
.sr-badge.is-avail { background: var(--sr-green-soft); color: var(--sr-green); }
.sr-stars { line-height: 1; }
.sr-callno { font: 11.5px var(--sr-mono); color: var(--sr-muted); }
.sr-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 2px; }
.sr-tag { border: 1px solid var(--sr-line); border-radius: 999px; padding: 0 9px; font-size: 12px; color: var(--sr-muted); text-decoration: none; }
.sr-tag:hover { border-color: var(--sr-teal-ink); color: var(--sr-teal-ink); }
.sr-tag.is-on { border-color: var(--sr-teal-ink); background: var(--sr-teal-soft); color: var(--sr-teal-ink); }
.sr-acts { display: flex; flex-direction: column; gap: 6px; align-items: stretch; }
.sr-act { display: inline-flex; align-items: center; justify-content: center; border: 1px solid var(--sr-line); background: #fff; color: var(--sr-ink); border-radius: 7px; padding: 4px 12px; font-size: 12.5px; font-weight: 500; white-space: nowrap; text-decoration: none; }
.sr-act:hover { border-color: var(--sr-teal-ink); color: var(--sr-teal-ink); }
.sr-act-main { background: var(--sr-teal-ink); border-color: var(--sr-teal-ink); color: #fff; }
.sr-act-main:hover { background: var(--sr-teal-dark); color: #fff; }
.sr-act[aria-pressed="true"] { color: var(--sr-rose); border-color: var(--sr-rose); background: var(--sr-rose-soft); }

.sr-list.is-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); }
.sr-list.is-grid .sr-item { grid-template-columns: minmax(0, 1fr); border-top: 0; border-right: 1px solid var(--sr-line); border-bottom: 1px solid var(--sr-line); gap: 10px; align-content: start; }
.sr-list.is-grid .sr-cover { width: 110px; }
.sr-list.is-grid .sr-title { font-size: 14.5px; display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden; }
.sr-list.is-grid .sr-acts { flex-direction: row; flex-wrap: wrap; }

.sr-empty { border: 1px solid var(--sr-line); border-radius: 12px; padding: 22px; display: grid; gap: 10px; }
.sr-empty h3 { margin: 0; font-size: 18px; font-weight: 600; }
.sr-empty p { margin: 0; color: var(--sr-muted); }
.sr-fixes { display: grid; gap: 6px; }
.sr-fix { display: flex; align-items: center; gap: 12px; border: 1px solid var(--sr-line); border-radius: 9px; padding: 9px 12px; color: var(--sr-ink); text-decoration: none; font-size: 14px; }
.sr-fix:hover { border-color: var(--sr-teal-ink); color: var(--sr-ink); }
.sr-fix > span:first-child { flex: 1; min-width: 0; }
.sr-fix-n { font-size: 12.5px; font-weight: 600; color: var(--sr-teal-ink); white-space: nowrap; }
.sr-fix-ai .sr-fix-n { color: var(--sr-indigo); }

.sr-pager { display: flex; flex-wrap: wrap; gap: 4px; justify-content: center; padding-top: 8px; }
.sr-pager a, .sr-pager span { min-width: 36px; height: 36px; display: grid; place-items: center; padding: 0 6px; border: 1px solid var(--sr-line); border-radius: 8px; font-size: 13px; color: var(--sr-ink); text-decoration: none; font-variant-numeric: tabular-nums; }
.sr-pager a:hover { border-color: var(--sr-teal-ink); color: var(--sr-teal-ink); }
.sr-pager [aria-current] { background: var(--sr-teal-ink); border-color: var(--sr-teal-ink); color: #fff; font-weight: 600; }
.sr-pager .is-off { opacity: .4; }
.sr-pager .sr-gap { border: 0; min-width: 16px; }

/* Cite dialog, toast */
.sr-scrim { position: fixed; inset: 0; background: rgba(14,22,28,.45); z-index: 2000; }
.sr-dialog { position: fixed; z-index: 2001; left: 50%; top: 50%; transform: translate(-50%, -50%); width: min(620px, calc(100vw - 32px)); max-height: calc(100vh - 48px); overflow: auto; background: #fff; border-radius: 14px; box-shadow: 0 20px 50px rgba(0,0,0,.25); padding: 20px 22px; }
.sr-dialog-h { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px; }
.sr-dialog-h h2 { flex: 1; min-width: 0; margin: 0; font-size: 18px; font-weight: 600; }
.sr-cite { border: 1px solid var(--sr-line); border-radius: 10px; padding: 10px 12px; margin-top: 10px; }
.sr-cite h3 { display: flex; align-items: center; margin: 0 0 6px; font-size: 11.5px; text-transform: uppercase; letter-spacing: .7px; color: var(--sr-muted); font-weight: 600; }
.sr-cite h3 .sr-act { margin-left: auto; text-transform: none; letter-spacing: 0; }
.sr-cite p { margin: 0; padding-left: 1.6em; text-indent: -1.6em; font-size: 14.5px; line-height: 1.55; overflow-wrap: anywhere; }
/* Quick view dialog (opac.partials.quick-view) */
.sr-qv-cover { width: 72px; flex: none; border-radius: 4px; box-shadow: 0 2px 6px rgba(0,0,0,.18); }
.sr-qv-head { flex: 1; min-width: 0; }
.sr-qv-head h2 { margin: 0 0 8px; font-size: 17px; line-height: 1.35; font-weight: 600; overflow-wrap: anywhere; }
.sr-qv-dl { display: grid; grid-template-columns: 150px minmax(0, 1fr); gap: 8px 14px; margin: 6px 0 0; font-size: 13.5px; line-height: 1.5; }
.sr-qv-dl dt { color: var(--sr-muted); font-weight: 500; }
.sr-qv-dl dd { margin: 0; color: var(--sr-ink); overflow-wrap: anywhere; }
.sr-qv-mono { font-family: var(--sr-mono); font-size: 12.5px; }
.sr-qv-tags { display: flex; flex-wrap: wrap; gap: 6px; }
.sr-qv-tags .sr-tag, .sr-qv-tags .sr-tag:hover { cursor: default; border-color: var(--sr-line); color: var(--sr-muted); }   /* labels here, not links */
.sr-qv-acts { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--sr-line); }
.sr-qv-acts .sr-act { padding: 6px 14px; font-size: 13px; }
@media (max-width: 575.98px) {
    .sr-qv-cover { width: 56px; }
    .sr-qv-dl { grid-template-columns: minmax(0, 1fr); gap: 2px; }
    .sr-qv-dl dd { margin-bottom: 8px; }
}
.sr-toast { position: fixed; z-index: 2100; left: 50%; bottom: 24px; transform: translateX(-50%); background: var(--sr-ink); color: #fff; border-radius: 10px; padding: 9px 16px; font-size: 13px; box-shadow: 0 8px 24px rgba(0,0,0,.25); max-width: calc(100vw - 32px); }

/* Read overlay (PDF.js), as on the record page */
.sr-reader { position: fixed; inset: 0; z-index: 20000; background: rgba(18,20,24,.94); display: none; flex-direction: column; }
.sr-reader-bar { flex: 0 0 46px; display: flex; align-items: center; justify-content: space-between; padding: 0 14px; background: #1b1d21; color: #fff; }
.sr-reader-title { font-size: 14px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-right: 12px; }
.sr-reader-close { background: transparent; border: 0; color: #fff; font-size: 26px; line-height: 1; padding: 0 6px; }
.sr-reader-frame { flex: 1 1 auto; width: 100%; border: 0; background: #3a3a3a; }

.sr-sheet-head, .sr-sheet-foot { display: none; }

@media (max-width: 991.98px) {
    .sr-layout { grid-template-columns: minmax(0, 1fr); padding-top: 16px; }
    .sr-filters-btn { display: inline-flex; }
    .sr-facets { position: fixed; z-index: 2001; left: 0; right: 0; bottom: 0; top: auto; max-height: 86vh; background: #fff; border-radius: 18px 18px 0 0; box-shadow: 0 -10px 30px rgba(0,0,0,.2);
        padding: 0 16px 12px; transform: translateY(105%); transition: transform .25s ease; visibility: hidden; }
    body.sr-sheet-open .sr-facets { transform: none; visibility: visible; }
    .sr-facets-title { display: none; }
    .sr-sheet-head { display: flex; align-items: center; gap: 8px; position: sticky; top: 0; z-index: 1; background: #fff; padding: 16px 0 10px; font-weight: 600; font-size: 16px; }
    .sr-sheet-head::before { content: ""; position: absolute; top: 6px; left: 50%; width: 38px; height: 4px; margin-left: -19px; border-radius: 2px; background: var(--sr-line); }
    .sr-sheet-head .sr-close { margin-left: auto; }
    .sr-sheet-foot { display: flex; position: sticky; bottom: 0; background: #fff; padding: 10px 0 4px; border-top: 1px solid var(--sr-line); }
    .sr-sheet-foot .sr-btn { flex: 1; justify-content: center; padding: 11px; font-size: 14px; }
}
@media (max-width: 767.98px) {
    /* [join] [field] [x] / [match] [words] */
    .sr-adv-row { grid-template-columns: 1fr 1fr 36px; }
    .sr-adv-row > .sr-adv-where, .sr-adv-row > [name$="[join]"] { order: 1; }
    .sr-adv-row > [name$="[field]"] { order: 2; }
    .sr-adv-row > .sr-icon-btn { order: 3; }
    .sr-adv-row > [name$="[op]"] { order: 4; }
    .sr-adv-row > [name$="[value]"] { order: 5; grid-column: span 2; }
    .sr-item { grid-template-columns: 56px minmax(0, 1fr); gap: 12px; }
    .sr-acts { grid-column: 1 / -1; flex-direction: row; flex-wrap: wrap; }
    .sr-count { flex-basis: 100%; }
    .sr-list.is-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .sr-list.is-grid .sr-cover { width: 84px; }
}
@media (prefers-reduced-motion: reduce) { .sr-page *, .sr-page *::before, .sr-page *::after { transition: none !important; } }
</style>
@endsection

@section('content')
<div class="sr-page">
    @include('opac.partials.breadcrumb', ['crumbs' => $crumbs, 'variant' => 'strip'])
    @include('opac.partials.search-band', ['q' => $p->q, 'in' => $p->in, 'advToggle' => true, 'advOpen' => $p->adv || $advOpen, 'savedOn' => $p->saved])

    <div class="sr-main" id="sr-main">
        @include('opac.partials.search-results-main')
    </div>

    <template id="sr-adv-row-tpl">@include('opac.partials.search-adv-row', ['i' => 999, 'row' => ['join' => 'AND', 'field' => 'all', 'op' => 'contains', 'value' => '']])</template>
    <div class="visually-hidden" id="sr-status" role="status" aria-live="polite"></div>
    <div class="sr-scrim" id="sr-scrim" hidden></div>
    {{-- Moved to <body> on load: <main> is its own stacking layer (z-index 1), which kept the dialog
         under the site header. The filter sheet keeps #sr-scrim, inside <main> with it. --}}
    <div class="sr-scrim" id="sr-dialog-scrim" hidden></div>
    <div class="sr-dialog" id="sr-dialog" role="dialog" aria-modal="true" aria-labelledby="sr-dialog-title" hidden></div>
    <div class="sr-toast" id="sr-toast" role="status" hidden></div>
</div>

{{-- Download (sign-in enforced by DownloadController; counts the download) --}}
<form method="post" action="{{ url('/download') }}" id="sr-download" @auth target="_blank" @endauth>
    @csrf
    <input type="hidden" name="holdings_download_id" id="sr-download-id" value="">
</form>

{{-- Read overlay: in-browser PDF.js reader (open to everyone) --}}
<div id="sr-reader" class="sr-reader">
    <div class="sr-reader-bar">
        <span class="sr-reader-title" id="sr-reader-title"></span>
        <button type="button" class="sr-reader-close" id="sr-reader-close" aria-label="Close reader">&times;</button>
    </div>
    <iframe id="sr-reader-frame" class="sr-reader-frame" src="about:blank" title="Online reader"></iframe>
</div>
@endsection

@section('js')
<script>
(function () {
    'use strict';
    var RESULTS = @json(url('/results'));
    var SUGGEST = @json(url('/search/suggest'));
    var FACET   = @json(url('/search/facet'));
    var CITE    = @json(url('/search/cite'));
    var QUICK   = @json(url('/search/quick'));
    var SAVE    = @json(url('/search/saved'));
    var LOGIN   = @json(route('login'));
    var CSRF    = @json(csrf_token());

    var main = document.getElementById('sr-main');
    var advOpen = !document.getElementById('sr-adv').hidden;
    var expanded = {};          // facet groups whose "Show all" is open, kept across reloads
    var request = null;
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function $(id) { return document.getElementById(id); }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

    /* ---------- the results area: swap it in place ---------- */
    function cleanUrl(url) {
        var u = new URL(url, location.href);
        u.searchParams.delete('partial');
        return u.pathname + u.search;
    }
    function focusKey(el) {
        if (!el || !main.contains(el)) return null;
        if (el.id) return '#' + CSS.escape(el.id);
        if (el.name) return '[name="' + CSS.escape(el.name) + '"]' + (el.value ? '[value="' + CSS.escape(el.value) + '"]' : '');
        return null;
    }
    function load(url, opts) {
        opts = opts || {};
        if (request) request.abort();
        request = new AbortController();
        var key = focusKey(document.activeElement);
        var u = new URL(url, location.href);
        u.searchParams.set('partial', '1');
        main.classList.add('is-loading');
        main.setAttribute('aria-busy', 'true');
        return fetch(u.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: request.signal })
            .then(function (res) { if (!res.ok) throw new Error('HTTP ' + res.status); return res.text(); })
            .then(function (html) {
                main.innerHTML = html;
                if (opts.push !== false) history.pushState({ sr: 1 }, '', cleanUrl(url));
                afterRender(key);
                if (opts.scroll) { var c = $('sr-count'); if (c) c.scrollIntoView({ block: 'start', behavior: reduceMotion ? 'auto' : 'smooth' }); }
                var count = $('sr-count');
                $('sr-status').textContent = count ? count.textContent.replace(/\s+/g, ' ').trim() : '';
            })
            .catch(function (e) { if (e.name !== 'AbortError') location.href = cleanUrl(url); })   // fall back to a normal page load
            .finally(function () { main.classList.remove('is-loading'); main.removeAttribute('aria-busy'); });
    }
    function afterRender(key) {
        var adv = $('sr-adv');
        if (adv) adv.hidden = !advOpen;
        $('sr-adv-toggle').setAttribute('aria-expanded', advOpen ? 'true' : 'false');
        Object.keys(expanded).forEach(function (g) { setExpanded(g, true); });
        findOriginal = {};
        initYear();
        if (key) { var el = main.querySelector(key); if (el) el.focus({ preventScroll: true }); }
    }
    function filtersUrl() {
        var form = $('sr-filters');
        var qs = new URLSearchParams();
        new FormData(form).forEach(function (v, k) {
            if (v === '' || (k === 'sort' && v === 'relevance')) return;
            qs.append(k, v);
        });
        var s = qs.toString();
        return RESULTS + (s ? '?' + s : '');
    }
    window.addEventListener('popstate', function () { load(location.href, { push: false }); });

    // Any filter or the Sort menu changed.
    main.addEventListener('change', function (e) {
        var t = e.target;
        if (t.id === 'sr-undated') { $('sr-undated-value').value = t.checked ? '' : '0'; load(filtersUrl()); return; }
        if (t.matches('#sr-ymin, #sr-ymax')) { setYear(+$('sr-ymin').value, +$('sr-ymax').value); return; }
        if (t.matches('#sr-yfrom, #sr-yto')) { setYear(+$('sr-yfrom').value, +$('sr-yto').value); return; }
        if (t.matches('[data-find]')) return;
        if (t.form && t.form.id === 'sr-filters') load(filtersUrl());
    });

    // Links inside the results area (chips, pages, authors, subjects, fixes) load in place.
    main.addEventListener('click', function (e) {
        var a = e.target.closest('a[data-sr-link]');
        if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        e.preventDefault();
        load(a.href, { scroll: a.hasAttribute('data-sr-scroll') });
    });

    /* ---------- year: decade bars, slider, exact years ---------- */
    function yearBounds() { var f = $('sr-year'); return f && f.dataset.min ? [+f.dataset.min, +f.dataset.max] : null; }
    function paintRange() {
        var b = yearBounds(), lo = $('sr-ymin'), hi = $('sr-ymax'), fill = $('sr-range-fill');
        if (!b || !lo || !fill) return;
        var span = Math.max(1, b[1] - b[0]);
        fill.style.left = ((+lo.value - b[0]) / span * 100) + '%';
        fill.style.width = ((+hi.value - +lo.value) / span * 100) + '%';
    }
    function initYear() { paintRange(); }
    // An end at the edge of the slider stays open ("From 1990", "Up to 2010"); a decade bar is exact.
    function setYear(a, b, exact) {
        var bounds = yearBounds(); if (!bounds) return;
        a = Math.round(a || bounds[0]); b = Math.round(b || bounds[1]);
        if (a > b) { var t = a; a = b; b = t; }
        var from = exact ? a : (a <= bounds[0] ? '' : a), to = exact ? b : (b >= bounds[1] ? '' : b);
        $('sr-year-value').value = (from === '' && to === '') ? '' : from + '-' + to;
        load(filtersUrl());
    }
    main.addEventListener('input', function (e) {
        var t = e.target;
        if (t.id === 'sr-ymin' || t.id === 'sr-ymax') {
            var lo = $('sr-ymin'), hi = $('sr-ymax');
            if (+lo.value > +hi.value) { if (t === lo) lo.value = hi.value; else hi.value = lo.value; }
            $('sr-yfrom').value = lo.value; $('sr-yto').value = hi.value;
            paintRange();
        }
        if (t.matches('[data-find]')) findOptions(t);
    });
    main.addEventListener('click', function (e) {
        var bar = e.target.closest('[data-decade]');
        if (bar) { var d = +bar.dataset.decade; setYear(d, d + 9, true); }
    });

    /* ---------- long option lists ---------- */
    function setExpanded(group, on) {
        var fs = main.querySelector('[data-facet="' + group + '"]'), btn = fs && fs.querySelector('[data-more]');
        if (!fs || !btn) return;
        fs.classList.toggle('is-expanded', on);
        btn.setAttribute('aria-expanded', on ? 'true' : 'false');
        btn.textContent = on ? btn.dataset.lessLabel : btn.dataset.moreLabel;
    }
    main.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-more]');
        if (!btn) return;
        var g = btn.dataset.more, on = !btn.closest('.sr-facet').classList.contains('is-expanded');
        if (on) expanded[g] = true; else delete expanded[g];
        setExpanded(g, on);
    });

    // "Find an author": matching names with counts for the current search; ticked ones stay listed.
    var findTimer = null, findSeq = 0, findOriginal = {};
    function findOptions(input) {
        var group = input.dataset.find, list = main.querySelector('[data-opts="' + group + '"]'), fs = input.closest('.sr-facet');
        if (findOriginal[group] === undefined) findOriginal[group] = list.innerHTML;   // the list to restore when the box is cleared
        clearTimeout(findTimer);
        var term = input.value.trim();
        findTimer = setTimeout(function () {
            findTimer = null;
            if (term === '') { list.innerHTML = findOriginal[group]; fs.classList.remove('is-finding'); delete findOriginal[group]; return; }
            var seq = ++findSeq, qs = new URLSearchParams(location.search);
            qs.delete('page'); qs.set('group', group); qs.set('term', term);
            fetch(FACET + '?' + qs.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (seq !== findSeq) return;
                    var name = group + '[]', ticked = new URLSearchParams(location.search).getAll(name), seen = {}, html = '';
                    ticked.forEach(function (v) { seen[v] = true; html += opt(name, v, null, true); });
                    data.values.forEach(function (o) { if (!seen[o.value]) html += opt(name, o.value, o.count, false); });
                    list.innerHTML = html || '<p class="sr-none">No ' + group + 's match “' + esc(term) + '”.</p>';
                    fs.classList.add('is-finding');
                });
        }, 200);
    }
    function opt(name, value, count, on) {
        return '<label class="sr-opt' + (on ? ' is-on' : '') + '"><input type="checkbox" name="' + esc(name) + '" value="' + esc(value) + '"' + (on ? ' checked' : '') + '>'
            + '<span class="sr-opt-name">' + esc(value) + '</span><span class="sr-opt-n">' + (count == null ? '' : Number(count).toLocaleString()) + '</span></label>';
    }

    /* ---------- list / grid (remembered in a cookie the server reads) ---------- */
    main.addEventListener('click', function (e) {
        var b = e.target.closest('[data-view]');
        if (!b || b.getAttribute('aria-pressed') === 'true') return;
        document.cookie = 'sr_view=' + b.dataset.view + '; path=/; max-age=31536000; samesite=lax';
        load(location.href, { push: false });
    });

    /* ---------- phones: filters in a bottom sheet ---------- */
    function openSheet() { document.body.classList.add('sr-sheet-open'); $('sr-scrim').hidden = false; var c = main.querySelector('.sr-sheet-head .sr-close'); if (c) c.focus(); }
    function closeSheet() {
        if (!document.body.classList.contains('sr-sheet-open')) return;
        document.body.classList.remove('sr-sheet-open');
        $('sr-scrim').hidden = true;
        var b = main.querySelector('[data-sheet-open]'); if (b) b.focus();
    }
    main.addEventListener('click', function (e) {
        if (e.target.closest('[data-sheet-open]')) openSheet();
        else if (e.target.closest('[data-sheet-close]')) closeSheet();
    });

    /* ---------- advanced rows ---------- */
    $('sr-adv-toggle').addEventListener('click', function () {
        advOpen = !advOpen;
        afterRender(null);
        if (advOpen) { var first = main.querySelector('#sr-adv input[type="text"]'); if (first) first.focus(); }
    });
    var advSeq = 100;
    main.addEventListener('click', function (e) {
        if (e.target.closest('#sr-adv-add')) {
            var rows = $('sr-adv-rows');
            if (rows.children.length >= 6) { toast('Up to 6 rows'); return; }
            var i = ++advSeq, html = $('sr-adv-row-tpl').innerHTML.replace(/999/g, String(i));
            rows.insertAdjacentHTML('beforeend', html);
            rows.lastElementChild.querySelector('input[type="text"]').focus();
        }
        var rm = e.target.closest('[data-adv-remove]');
        if (rm) {
            var rowsEl = $('sr-adv-rows'), row = rm.closest('.sr-adv-row');
            if (rowsEl.children.length > 1) row.remove(); else row.querySelector('input[type="text"]').value = '';
        }
    });
    main.addEventListener('submit', function (e) {
        if (e.target.id === 'sr-filters') { e.preventDefault(); load(filtersUrl()); return; }
        if (e.target.id !== 'sr-adv-form') return;
        e.preventDefault();
        var qs = new URLSearchParams(), rows = {};
        new FormData(e.target).forEach(function (v, k) {
            var m = k.match(/^adv\[(\d+)\]\[(\w+)\]$/);
            if (m) { (rows[m[1]] = rows[m[1]] || {})[m[2]] = v; return; }
            if (v !== '') qs.append(k, v);
        });
        var n = 0;
        Object.keys(rows).forEach(function (k) {
            var r = rows[k];
            if (!r.value || !r.value.trim()) return;
            ['join', 'field', 'op', 'value'].forEach(function (f) { qs.append('adv[' + n + '][' + f + ']', r[f] || (f === 'join' ? 'AND' : '')); });
            n++;
        });
        load(RESULTS + '?' + qs.toString(), { scroll: true });
    });

    /* ---------- Quick view, Cite, Save, Read, Download ---------- */
    ['sr-dialog-scrim', 'sr-dialog', 'sr-toast'].forEach(function (id) { document.body.appendChild($(id)); });
    var lastFocus = null;
    function openDialog(html) {
        var d = $('sr-dialog');
        if (d.hidden || !d.contains(document.activeElement)) lastFocus = document.activeElement;   // Cite from Quick view keeps the list button
        d.innerHTML = html; d.hidden = false; $('sr-dialog-scrim').hidden = false;
        var c = d.querySelector('[data-dialog-close]'); if (c) c.focus();
    }
    function closeDialog() {
        $('sr-dialog').hidden = true;
        $('sr-dialog-scrim').hidden = true;
        if (lastFocus && document.contains(lastFocus)) lastFocus.focus();
    }
    var toastTimer = null;
    function toast(msg) { var t = $('sr-toast'); t.textContent = msg; t.hidden = false; clearTimeout(toastTimer); toastTimer = setTimeout(function () { t.hidden = true; }, 2600); }
    function copy(text, el) {
        var fallback = function () {
            var r = document.createRange(); r.selectNodeContents(el); var s = getSelection(); s.removeAllRanges(); s.addRange(r);
            toast('Press Ctrl+C to copy the selected citation');
        };
        try { navigator.clipboard.writeText(text).then(function () { toast('Citation copied'); }, fallback); } catch (err) { fallback(); }
    }
    var closeIcon = '<svg width="14" height="14" viewBox="0 0 16 16" aria-hidden="true"><path stroke="currentColor" stroke-width="1.8" stroke-linecap="round" d="M3 3l10 10M13 3L3 13"/></svg>';

    // The result buttons, also used inside the Quick view dialog (which sits outside #sr-main).
    function onAction(e) {
        var quick = e.target.closest('[data-quick]');
        if (quick) {
            fetch(QUICK + '/' + encodeURIComponent(quick.dataset.quick), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { if (!r.ok) throw new Error(); return r.text(); })
                .then(openDialog)   // server-rendered (opac.partials.quick-view); values escaped there
                .catch(function () { toast('The details could not be loaded. Please try again.'); });
            return;
        }

        var cite = e.target.closest('[data-cite]');
        if (cite) {
            fetch(CITE + '/' + encodeURIComponent(cite.dataset.cite), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { if (!r.ok) throw new Error(); return r.json(); })
                .then(function (data) {
                    var html = '<div class="sr-dialog-h"><h2 id="sr-dialog-title">Cite this record</h2><button type="button" class="sr-close" data-dialog-close aria-label="Close">' + closeIcon + '</button></div>';
                    Object.keys(data.citations || {}).forEach(function (style, i) {
                        // Citation HTML comes from buildCitations() (values escaped there; only <em> added).
                        html += '<div class="sr-cite"><h3>' + esc(style) + '<button type="button" class="sr-act" data-copy="sr-cite-' + i + '">Copy</button></h3><p id="sr-cite-' + i + '">' + data.citations[style] + '</p></div>';
                    });
                    openDialog(html);
                })
                .catch(function () { toast('The citation could not be loaded. Please try again.'); });
            return;
        }

        var save = e.target.closest('[data-save]');
        if (save) {
            save.disabled = true;
            fetch(SAVE, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                body: 'catalog_id=' + encodeURIComponent(save.dataset.save) })
                .then(function (r) { if (r.status === 401 || r.status === 419) { location.href = LOGIN; throw new Error('auth'); } if (!r.ok) throw new Error(); return r.json(); })
                .then(function (data) {
                    // The same record's button in the list and in Quick view.
                    document.querySelectorAll('[data-save="' + save.dataset.save + '"]').forEach(function (b) {
                        b.setAttribute('aria-pressed', data.saved ? 'true' : 'false');
                        b.textContent = data.saved ? 'Saved' : 'Save';
                    });
                    var n = $('sr-saved-n'); if (n) n.textContent = data.count;
                    toast(data.saved ? 'Saved to your list' : 'Removed from your list');
                })
                .catch(function (err) { if (err.message !== 'auth') toast('That didn’t save. Please try again.'); })
                .finally(function () { save.disabled = false; });
            return;
        }

        var read = e.target.closest('[data-read]');
        if (read) {
            openReader(read.dataset.read, read.dataset.title);
            // Count this read as a view, like opening the record page.
            fetch('/read/view', { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/x-www-form-urlencoded' }, body: 'catalog_id=' + encodeURIComponent(read.dataset.id) }).catch(function () {});
            return;
        }

        var dl = e.target.closest('[data-download]');
        if (dl) { $('sr-download-id').value = dl.dataset.download; $('sr-download').submit(); }
    }
    main.addEventListener('click', onAction);
    $('sr-dialog').addEventListener('click', function (e) {
        if (e.target.closest('[data-dialog-close]')) { closeDialog(); return; }
        var c = e.target.closest('[data-copy]');
        if (c) { var p = $(c.dataset.copy); copy(p.textContent, p); return; }
        onAction(e);
    });
    $('sr-scrim').addEventListener('click', closeSheet);
    $('sr-dialog-scrim').addEventListener('click', closeDialog);

    function openReader(url, title) {
        $('sr-reader-frame').src = '/pdfjs/web/viewer.html?file=' + encodeURIComponent(url);
        $('sr-reader-title').textContent = title || 'Reading';
        $('sr-reader').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        var header = document.querySelector('header.header_sticky');
        $('sr-reader').style.top = header ? Math.max(0, header.getBoundingClientRect().bottom) + 'px' : '0';
    }
    function closeReader() {
        $('sr-reader').style.display = 'none';
        $('sr-reader-frame').src = 'about:blank';
        document.body.style.overflow = '';
    }
    $('sr-reader-close').addEventListener('click', closeReader);

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        if ($('sr-reader').style.display === 'flex') closeReader();
        else if (!$('sr-dialog').hidden) closeDialog();
        else closeSheet();
    });

    /* ---------- the filter column sticks under the site header and the docked search band ---------- */
    function measure() {
        var h = document.querySelector('header.header_sticky'), band = $('results');
        var bandH = band && getComputedStyle(band).position === 'sticky' ? band.offsetHeight : 0;
        document.querySelector('.sr-page').style.setProperty('--sr-top', ((h ? h.offsetHeight : 80) + bandH + 16) + 'px');
    }
    measure();
    window.addEventListener('resize', measure);
    window.addEventListener('scroll', function once() { measure(); window.removeEventListener('scroll', once); }, { passive: true });

    afterRender(null);
    history.replaceState({ sr: 1 }, '', location.href);
})();
</script>
@endsection
