@extends('opac.layout.master')

@push('json_ld')
<script type="application/ld+json">{!! seoSiteJsonLd() !!}</script>
@endpush

@section('meta_title', 'DOST-PCAARRD eLibrary — AANR research collection')
@section('meta_description', 'Search the DOST-PCAARRD eLibrary: books, theses, technical reports and other agriculture, aquatic and natural resources (AANR) research materials.')
@section('canonical', url('/home'))


@section('css')
<style>
/* ---- Search-engine toggle: Classic (Meilisearch) <-> AI mode (Hermes/RAG) ---- */
.mode-toggle {
	display: inline-flex; gap: 3px; margin: 0 auto 14px; padding: 3px;
	background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.28);
	border-radius: 30px; backdrop-filter: blur(3px);
}
.mode-btn {
	border: 0; background: transparent; color: #fff; cursor: pointer;
	font-size: 13.5px; font-weight: 600; letter-spacing: .2px;
	padding: 7px 18px; border-radius: 30px; line-height: 1;
	display: inline-flex; align-items: center; gap: 6px;
	transition: background .16s ease, color .16s ease;
}
.mode-btn:hover { background: rgba(255,255,255,.14); }
.mode-btn.active { background: #fff; color: #00796b; box-shadow: 0 1px 4px rgba(0,0,0,.16); }
.mode-btn .mode-ico { font-size: 14px; line-height: 1; }
#ai-note {
	margin-top: 14px; font-size: 13.5px; color: rgba(255,255,255,.92);
	text-shadow: 0 1px 2px rgba(0,0,0,.25); max-width: 520px; margin-left: auto; margin-right: auto;
}
/* Guests: one quiet line under the search on what a free account adds (Register / log in). */
.home-join { margin: 16px auto 0; max-width: 640px; font-size: 13.5px; line-height: 1.5; color: rgba(255,255,255,.92); text-shadow: 0 1px 2px rgba(0,0,0,.25); }
.home-join svg { vertical-align: -2px; margin-right: 4px; }
.home-join a { color: #fff; font-weight: 700; text-decoration: underline; text-underline-offset: 3px; text-decoration-thickness: 1px; }
.home-join a:hover { text-decoration-thickness: 2px; color: #fff; }
.home-join a:focus-visible { outline: 2px solid #fff; outline-offset: 2px; border-radius: 2px; }
/* AI mode restyles the submit button so the mode is unmistakable */
#custom-search-input.is-ai .btn_search { background-color: #00796b; }

.instant-wrap { position: relative; }
.instant-results {
	/* moved to <body> and positioned via JS, so no ancestor overflow/stacking/transform can clip it */
	position: absolute; z-index: 9999;
	background: #fff; border: 1px solid #e3e6ea;
	border-radius: 8px; box-shadow: 0 12px 32px rgba(0,0,0,.18);
	overflow: hidden; text-align: left;
}
.instant-list { max-height: 380px; overflow-y: auto; }
.instant-row {
	display: flex; align-items: flex-start; gap: 10px; padding: 10px 16px; border-bottom: 1px solid #f0f2f4; color: #222; text-decoration: none;
}
.instant-thumb { width: 40px; height: 54px; object-fit: cover; border-radius: 3px; flex-shrink: 0; background: #eef2f7; border: 1px solid #eceef1; }
.instant-text { flex: 1; min-width: 0; }
.instant-row:last-child { border-bottom: 0; }
.instant-row:hover, .instant-row.active { background: #f5f8ff; }
.instant-title { font-size: 15px; font-weight: 600; line-height: 1.3; color: #1a2b4a; }
.instant-meta { font-size: 12.5px; color: #6b7280; margin-top: 2px; }
.instant-meta .instant-type {
	display: inline-block; background: #eef2f7; color: #45607e; border-radius: 4px; padding: 0 6px; margin-left: 6px; font-size: 11px;
}
.instant-meta .instant-year { margin-left: 6px; }
.instant-empty, .instant-loading { padding: 16px; color: #6b7280; font-size: 14px; text-align: center; }
.instant-seeall {
	display: block; padding: 11px 16px; text-align: center; background: #f8fafc;
	border-top: 1px solid #e3e6ea; color: #2563eb; font-weight: 600; font-size: 14px; text-decoration: none;
}
.instant-seeall:hover { background: #eef4ff; color: #1d4ed8; }

/* ---- Minimalist section nav: fixed dots on the right, smooth-scroll to each section ---- */
#sec-search, #sec-shelves, #sec-misc { scroll-margin-top: 110px; }
.section-nav { position: fixed; top: 50%; right: 20px; transform: translateY(-50%); z-index: 900; display: flex; flex-direction: column; gap: 16px; }
.section-nav a { position: relative; display: block; width: 13px; height: 13px; }
.section-nav a i { position: absolute; inset: 0; border-radius: 50%; border: 2px solid #c32148; background: transparent; transition: background .2s ease, transform .2s ease; }
.section-nav a:hover i { background: #c32148; }
.section-nav a.active i { background: #c32148; transform: scale(1.28); }
.section-nav a::after { content: attr(data-label); position: absolute; right: 24px; top: 50%; transform: translateY(-50%); white-space: nowrap; background: #a83e63; color: #fff; font-size: 12px; font-weight: 600; letter-spacing: .3px; padding: 4px 10px; border-radius: 6px; box-shadow: 0 3px 8px rgba(0,0,0,.2); opacity: 0; pointer-events: none; transition: opacity .18s ease; }
.section-nav a:hover::after, .section-nav a.active::after { opacity: 1; }
@media (max-width: 900px) { .section-nav { display: none; } }

/* ---- Advanced search + Saved, under the search box (same pills as the mode toggle) ---- */
.home-tools { display: flex; justify-content: center; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
.home-tool {
	display: inline-flex; align-items: center; gap: 6px; cursor: pointer; text-decoration: none;
	border: 1px solid rgba(255,255,255,.45); background: rgba(255,255,255,.14); color: #fff;
	border-radius: 30px; padding: 6px 14px; font-size: 13px; font-weight: 600; line-height: 1.2; backdrop-filter: blur(3px);
}
.home-tool:hover { background: rgba(255,255,255,.26); color: #fff; }
.home-tool[aria-expanded="true"] { background: #fff; color: #00796b; border-color: #fff; }
.home-tool .n { background: rgba(255,255,255,.25); border-radius: 999px; padding: 0 7px; font-size: 11.5px; font-variant-numeric: tabular-nums; }
.home-tool:focus-visible { outline: 2px solid #fff; outline-offset: 2px; }
.home-tool[hidden] { display: none; }
/* Opens by growing from 0 to its height (and fading in), so the page below slides instead of jumping.
   Clipped only while it moves; once open, the card's shadow shows. */
.home-adv-wrap { height: 0; overflow: hidden; opacity: 0; transition: height .32s cubic-bezier(.2, .7, .2, 1), opacity .22s ease; }
.home-adv-wrap.is-open { opacity: 1; }
.home-adv-wrap.is-settled { overflow: visible; }
.home-adv-wrap[hidden] { display: none; }
@media (prefers-reduced-motion: reduce) { .home-adv-wrap { transition: none; } }
.home-adv { margin-top: 12px; background: #fff; color: #1d2433; text-align: left; text-shadow: none; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,.25); padding: 14px 16px; }
.home-adv-head { font-size: 14px; font-weight: 600; margin-bottom: 2px; }
.home-adv-help { font-size: 12.5px; color: #5d6973; margin-bottom: 10px; }
.home-adv-rows { display: grid; gap: 8px; }
.home-adv .sr-adv-row { display: grid; grid-template-columns: 78px 132px 150px minmax(0, 1fr) 34px; gap: 8px; align-items: center; }
.home-adv .sr-adv-row select, .home-adv .sr-adv-row input[type="text"] {
	width: 100%; min-width: 0; height: 36px; border: 1px solid #dfe5e8; border-radius: 8px; padding: 0 9px; font-size: 13px; color: #1d2433; background: #fff;
}
.home-adv .sr-adv-where { font-size: 12px; text-transform: uppercase; letter-spacing: .5px; color: #5d6973; text-align: center; }
.home-adv .sr-icon-btn { width: 34px; height: 34px; display: grid; place-items: center; border: 1px solid #dfe5e8; background: #fff; color: #5d6973; border-radius: 8px; padding: 0; }
.home-adv .sr-icon-btn:hover { color: #b8345f; border-color: #b8345f; }
.home-adv-actions { display: flex; align-items: center; gap: 12px; margin-top: 10px; }
.home-adv-actions .add { border: 0; background: none; color: #00756b; font-weight: 600; font-size: 13px; padding: 0; }
.home-adv-actions .add:hover { text-decoration: underline; }
.home-adv-actions .go { margin-left: auto; border: 0; background: #00756b; color: #fff; border-radius: 8px; padding: 8px 18px; font-weight: 600; font-size: 13.5px; }
.home-adv-actions .go:hover { background: #005f57; }
@media (max-width: 767px) {
	/* Phones: [join] [field] [x] / [match] [words] */
	.home-adv .sr-adv-row { grid-template-columns: 1fr 1fr 34px; }
	.home-adv .sr-adv-row > .sr-adv-where, .home-adv .sr-adv-row > [name$="[join]"] { order: 1; }
	.home-adv .sr-adv-row > [name$="[field]"] { order: 2; }
	.home-adv .sr-adv-row > .sr-icon-btn { order: 3; }
	.home-adv .sr-adv-row > [name$="[op]"] { order: 4; }
	.home-adv .sr-adv-row > [name$="[value]"] { order: 5; grid-column: span 2; }
}
</style>
@endsection

@section('content')
	<nav class="section-nav" aria-label="Page sections">
		<a href="#sec-search"  data-label="Search"  class="active"><i></i></a>
		<a href="#sec-shelves" data-label="Shelves"><i></i></a>
		<a href="#sec-misc"    data-label="Recent"><i></i></a>
	</nav>
{{-- @if(Auth::user() && !checkCSF()) {{ session(['message' => 'Hello! Can we have a minute of your time to answer our CSF form. Thank you!']); }} @endif --}}
		@if (session('message'))
			<div class="alert @if(!checkCSF())alert-warning @else alert-success @endif text-center" style="margin-bottom:0; padding: 10px 1rem;" role="alert">
				{{ session('message') }} @if(!checkCSF()) <a href="{{ url('/csf/system-review') }}"><u>Go to CSF</u></a> @endif
			</div>
			@php session()->forget('message'); @endphp
		@endif

		<div class="hero_home version_1" id="sec-search">
			<div class="content">
				<h3><img src="{{ asset('opac/img/elibrary-06.png') }}" alt="" width="400"class="img-fluid"></h3>
				<p>
					<!-- Ridiculus sociosqu cursus neque cursus curae ante scelerisque vehicula. -->
				</p>
				{{-- A GET search: no @csrf, or the session token would land in every search address. --}}
				<form method="get" action="{{ url('/results') }}" id="frm">
					<input type="hidden" name="page" value="1">
					<input type="hidden" name="series_statement_id" value="0">
					<input type="hidden" name="layout" value="grid">
					{{-- Empty = classic Meilisearch; "ai" = route to the assistant. Set by the toggle. --}}
					<input type="hidden" name="mode" id="search-mode" value="">
					<div id="custom-search-input">

						{{-- Engine toggle: Classic (Meilisearch) <-> AI mode (Hermes/RAG).
						     Hidden when AI mode is switched off in Admin > Settings; the routes
						     are gated server-side too, so this is presentation only. --}}
						@if(settingEnabled('ai_mode_enabled', false))
						<div class="mode-toggle" role="tablist" aria-label="Search mode">
							<button type="button" class="mode-btn active" data-mode="" role="tab" aria-selected="true">
								<span class="mode-ico">&#128269;</span> Search
							</button>
							<button type="button" class="mode-btn" data-mode="ai" role="tab" aria-selected="false">
								<span class="mode-ico">&#10022;</span> AI mode
							</button>
						</div>
						@endif

						<div class="instant-wrap">
						<div class="input-group">
							<input type="text" class="search-query" name="query" placeholder="Search a Material..." autocomplete="off">
							<input type="submit" class="btn_search" value="Search">
						</div>
						<div id="instant-results" class="instant-results" style="display:none;">
							<div class="instant-list"></div>
							<a href="#" class="instant-seeall"></a>
						</div>
						</div>

						{{-- Advanced search (keyword search only, hidden in AI mode) and the patron's Saved list,
						     as on the search results page. The rows go to /results with the search words and types. --}}
						<div class="home-tools">
							<button type="button" class="home-tool" id="home-adv-toggle" aria-expanded="false" aria-controls="home-adv">
								<svg width="14" height="14" viewBox="0 0 16 16" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" d="M2 4h12M4 8h8M6 12h4"/></svg>
								Advanced search
							</button>
							@auth
								<a class="home-tool" href="{{ url('/results') }}?saved=1">
									<svg width="14" height="14" viewBox="0 0 16 16" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" d="M8 1.8l1.9 3.9 4.3.6-3.1 3 .7 4.3L8 11.6l-3.8 2 .7-4.3-3.1-3 4.3-.6z"/></svg>
									Saved <span class="n" id="home-saved-n">{{ \App\Models\SavedRecord::where('user_id', auth()->id())->count() }}</span>
								</a>
							@endauth
						</div>
						{{-- The wrapper animates open / closed; the card inside keeps its own look. --}}
						<div class="home-adv-wrap" id="home-adv" hidden>
						<div class="home-adv">
							<div class="home-adv-head">Advanced search</div>
							<div class="home-adv-help">Each row narrows or widens the results. Rows are combined from top to bottom, together with the words and types above.</div>
							<div class="home-adv-rows" id="home-adv-rows">
								@include('opac.partials.search-adv-row', ['i' => 0, 'row' => ['join' => 'AND', 'field' => 'title', 'op' => 'contains', 'value' => '']])
								@include('opac.partials.search-adv-row', ['i' => 1, 'row' => ['join' => 'AND', 'field' => 'subject', 'op' => 'contains', 'value' => '']])
							</div>
							<div class="home-adv-actions">
								<button type="button" class="add" id="home-adv-add">+ Add a row</button>
								<button type="submit" class="go">Search</button>
							</div>
						</div>
						</div>
						<template id="home-adv-row-tpl">@include('opac.partials.search-adv-row', ['i' => 999, 'row' => ['join' => 'AND', 'field' => 'all', 'op' => 'contains', 'value' => '']])</template>

						{{-- Material-type filters only apply to keyword search; hidden in AI mode. --}}
						<div id="type-filters">
						<br/>
						<div class="form-check form-check-inline">
							<input class="form-check-input" type="checkbox" value="0" id="checkAll" name="seachtype[]" checked>
	  						<label class="form-check-label" for="checkAll" style="font-size:17px;padding: 2px!important;background-color: transparent;">
	    								All
	  						</label>
  						</div>

  						@foreach(opacMaterials() AS $materials)
  						<div class="form-check form-check-inline">
							<input class="form-check-input" type="checkbox" value="{{ $materials->id }}" name="seachtype[]" id="materials-{{ $materials->id }}">
	  						<label class="form-check-label lbl-check" id="materials-{{ $materials->id }}" style="font-size:17px;padding: 2px!important;background-color: transparent;" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $materials->tooltip }}">
	    								{{ $materials->name }}
	  						</label>
  						</div>
  						@endforeach
						</div>

						{{-- Replaces the type filters when AI mode is on. --}}
						<div id="ai-note" style="display:none;">
							Ask a question in your own words &mdash; answers come from the library&rsquo;s
							collection, with sources.
						</div>

						{{-- Guests: what a free account adds, with the way in. Signed-in patrons see their Saved pill instead. --}}
						@guest
						<p class="home-join">
							<svg width="14" height="14" viewBox="0 0 16 16" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" d="M8 1.8l1.9 3.9 4.3.6-3.1 3 .7 4.3L8 11.6l-3.8 2 .7-4.3-3.1-3 4.3-.6z"/></svg>
							{{ settingEnabled('ai_mode_enabled', false) ? 'Save materials, reserve copies and ask the AI librarian' : 'Save materials and reserve copies' }} with a free account.
							<a href="{{ route('register') }}">Register</a> or <a href="{{ route('login') }}">log in</a>
						</p>
						@endguest

					</div>
				</form>
			</div>
		</div>
		{{-- <div class="container margin_120_95">
			<div class="main_title">
				<h2>Most Downloaded Materials</h2>
				<p>Usu habeo equidem sanctus no. Suas summo id sed, erat erant oporteat cu pri. In eum omnes molestie. Sed ad debet scaevola, ne mel.</p>
			</div>
			<div class="row add_bottom_30">
				<div id="most-downloaded" class="owl-carousel owl-theme">
					@foreach(mostDownloaded() AS $values)
						<div class="item">
							<a href="{{ recordUrl($values) }}">
								<div class="views"><i class="icon-download-5"></i>{{ $values->total}}</div>
								<div class="title">
								</div>{!! getFrontpage($values->catalog_id) !!}
							</a>
						</div>
					@endforeach
				</div>
			</div>
		</div> --}}

		<div id="sec-shelves">@include('opac.partials.shelf-browser')</div>
		<style>
			/* Recent section — compact, minimalist carousels (Recent Uploads / Top lists / News) */
			#sec-misc { padding:20px 0 36px; }
			.cover-carousel { max-width:1680px; margin:0 auto; padding:4px 24px 2px; }
			.cover-carousel .main_title { text-align:center; margin-bottom:6px; }
			.cover-carousel .main_title h2 { font-family:Georgia,"Palatino Linotype",serif; font-size:21px; letter-spacing:1.5px; text-transform:uppercase; color:#0a5f56; font-weight:700; margin:0; }
			.cc-strip { position:relative; }
			/* safe center: centers items when they fit, else flex-start (scrollable) so the arrows take over. */
			.cc-track { display:flex; gap:20px; overflow-x:auto; scroll-behavior:smooth; padding:4px 6px 8px; justify-content:safe center; scrollbar-width:none; -ms-overflow-style:none; }
			.cc-track::-webkit-scrollbar { display:none; }   /* no scrollbar — prev/next arrows only */
			.cc-item { flex:0 0 auto; width:132px; text-align:center; }
			.cc-cover { position:relative; display:flex; align-items:flex-end; justify-content:center; height:178px; }
			.cc-cover img { max-height:178px; max-width:100%; width:auto; border-radius:3px; box-shadow:0 2px 6px rgba(0,0,0,.22); transition:transform .14s ease, box-shadow .14s ease; }
			.cc-cover:hover img { transform:translateY(-5px); box-shadow:0 10px 18px rgba(0,0,0,.28); }
			/* Record covers: the figure hugs the image so the save star sits on its corner, and the
			   whole figure (image + star) lifts on hover. News covers keep the plain link above. */
			.cv-fig { position:relative; display:block; line-height:0; transition:transform .14s ease; }
			.cv-link { display:block; }
			.cv-fig img { max-width:132px; }
			.cc-cover:hover .cv-fig { transform:translateY(-5px); }
			.cc-cover:hover .cv-fig img { transform:none; }

			/* Save star (opac.partials.save-star): upper-right corner of a record cover. An unsaved one
			   shows on hover or keyboard focus (always on touch screens); a saved one (yellow) stays shown. */
			.save-star { position:absolute; top:6px; right:6px; z-index:4; width:28px; height:28px; padding:0; border:0; border-radius:50%;
				display:flex; align-items:center; justify-content:center; line-height:0; cursor:pointer;
				background:rgba(255,255,255,.94); box-shadow:0 1px 4px rgba(0,0,0,.35);
				opacity:0; transform:scale(.85); transition:opacity .16s ease, transform .16s ease; }
			.save-star svg { width:16px; height:16px; fill:none; stroke:#4b5563; stroke-width:1.4; stroke-linejoin:round; transition:fill .16s ease, stroke .16s ease; }
			.save-star:hover svg { stroke:#111827; }
			.save-star.is-saved svg { fill:#ffc107; stroke:#d69e00; }
			.cc-item:hover .save-star, .col-book:hover .save-star, .save-star:focus-visible, .save-star.is-saved { opacity:1; transform:none; }
			.save-star:focus-visible { outline:2px solid #0a5f56; outline-offset:2px; }
			.save-star.pop svg { animation:save-pop .32s ease; }
			@keyframes save-pop { 50% { transform:scale(1.35); } }
			@media (hover:none) { .save-star { opacity:1; transform:none; } }
			@media (prefers-reduced-motion:reduce) { .save-star, .save-star svg { transition:none; } .save-star.pop svg { animation:none; } }
			.cc-cap { display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; margin-top:7px; font-size:12.5px; line-height:1.3; color:#0176d3; text-decoration:none; font-weight:600; }
			.cc-cap:hover { text-decoration:underline; }
			.cc-arrow { position:absolute; top:44%; transform:translateY(-50%); width:42px; height:42px; border-radius:50%; border:1px solid #d0d7de; background:#fff; color:#3f4079; font-size:22px; line-height:1; cursor:pointer; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 8px rgba(0,0,0,.16); z-index:3; }
			.cc-arrow:hover { background:#00a496; border-color:#00a496; color:#fff; }
			.cc-arrow.prev { left:-4px; } .cc-arrow.next { right:-4px; }
			.cc-arrow:disabled { opacity:.3; cursor:default; background:#fff; border-color:#d0d7de; color:#3f4079; }

			/* Ranked "Top 10": rank-number overlay + detail badge revealed on hover. */
			.cover-carousel.ranked .cc-track { padding-top:24px; }
			.rk-item .cc-cover { overflow:visible; }
			.rk-num { position:absolute; top:-16px; left:-8px; z-index:3; font-family:Georgia,"Palatino Linotype",serif; font-weight:800; font-size:44px; line-height:1;
				color:#fff; -webkit-text-stroke:2px #0a5f56; text-shadow:0 2px 5px rgba(0,0,0,.4); pointer-events:none; }
			.rk-badge { position:absolute; left:0; right:0; bottom:0; z-index:2; background:linear-gradient(transparent, rgba(4,46,42,.92)); color:#fff;
				font-size:11.5px; font-weight:700; letter-spacing:.2px; text-align:center; padding:20px 6px 7px; border-radius:0 0 3px 3px; opacity:0; transition:opacity .2s ease; pointer-events:none; }
			.rk-cover:hover .rk-badge { opacity:1; }
		</style>
		<div id="sec-misc">
		@include('opac.partials.ranked-carousel', ['title' => 'Most Downloaded', 'items' => getMostDownloaded(10)])
		@include('opac.partials.ranked-carousel', ['title' => 'Most Viewed', 'items' => getMostViewed(10)])
		@include('opac.partials.ranked-carousel', ['title' => 'Highest Rating', 'items' => getHighestRated(10)])
		<div class="cover-carousel">
			<div class="main_title"><h2>Recent Uploads</h2></div>
			<div class="cc-strip">
				<button type="button" class="cc-arrow prev" aria-label="Previous">&#8249;</button>
				<div class="cc-track">
					@foreach(getLatestMaterial() as $results)
						<div class="cc-item">
							<div class="cc-cover">
								<span class="cv-fig">
									<a class="cv-link" href="{{ recordUrl($results) }}">
										<img loading="lazy" src="{{ getThumbnailLink($results->catalog_id) }}" alt="{{ $results->title_statement }}">
									</a>
									@include('opac.partials.save-star', ['id' => $results->catalog_id, 'title' => $results->title_statement])
								</span>
							</div>
							<a class="cc-cap" href="{{ recordUrl($results) }}" title="{{ $results->title_statement }}">{{ $results->title_statement }}</a>
						</div>
					@endforeach
				</div>
				<button type="button" class="cc-arrow next" aria-label="Next">&#8250;</button>
			</div>
		</div>
		<div class="cover-carousel">
			<div class="main_title"><h2>Related News</h2></div>
			<div class="cc-strip">
				<button type="button" class="cc-arrow prev" aria-label="Previous">&#8249;</button>
				<div class="cc-track">
					@foreach(getLatestNews() as $news)
						<div class="cc-item">
							<a class="cc-cover" href="{{ $news->redirect_url ?? '#' }}" @if($news->redirect_url) target="_blank" rel="noopener" @endif>
								<img loading="lazy" src="{{ $news->news_cover ? url('/'.$news->news_cover) : url('/frontpages/no-preview.jpg') }}" alt="{{ $news->news_title ?? '' }}">
							</a>
							<a class="cc-cap" href="{{ $news->redirect_url ?? '#' }}" @if($news->redirect_url) target="_blank" rel="noopener" @endif title="{{ $news->news_title ?? '' }}">{{ $news->news_title ?? '' }}</a>
						</div>
					@endforeach
				</div>
				<button type="button" class="cc-arrow next" aria-label="Next">&#8250;</button>
			</div>
		</div>
		<script type="text/javascript">
		(function(){
			function wire(strip){
				var track = strip.querySelector('.cc-track');
				var prev = strip.querySelector('.cc-arrow.prev');
				var next = strip.querySelector('.cc-arrow.next');
				if(!track) return;
				function upd(){
					var max = track.scrollWidth - track.clientWidth - 2;
					if(prev) prev.disabled = track.scrollLeft <= 2;
					if(next) next.disabled = track.scrollLeft >= max;
					var overflow = track.scrollWidth > track.clientWidth + 4;
					if(prev) prev.style.display = overflow ? '' : 'none';
					if(next) next.style.display = overflow ? '' : 'none';
				}
				function step(dir){ track.scrollBy({ left: dir * track.clientWidth * 0.85, behavior:'smooth' }); }
				if(prev) prev.addEventListener('click', function(){ step(-1); });
				if(next) next.addEventListener('click', function(){ step(1); });
				track.addEventListener('scroll', upd);
				window.addEventListener('resize', upd);
				upd();
			}
			document.querySelectorAll('.cc-strip').forEach(wire);
		})();
		</script>
		</div>{{-- /#sec-misc --}}
			<!-- /app_section -->
		<!-- /Hero -->
<!-- Modal -->
{{-- <div class="modal fade" id="csfModal" tabindex="-1" role="dialog" aria-labelledby="csfModalTitle" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
	<div class="modal-content">
		<div class="modal-header" style="background-color: #74d1c6">
		<h5 class="modal-title" id="csfModalLongTitle">Hello! @if(Auth::user()) {{ Auth::user()->name }} @endif</h5>
		<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
			<span aria-hidden="true">&times;</span>
		</button>
		</div>
		<div class="modal-body">
			Can we have a minute of your time to answer our CSF form. We greatly appreciate your response. Thank you!
		</div>
		<div class="modal-footer">
		<a href="{{ url('/csf/system-review') }}"><button type="button" class="btn_1 btn-primary">Go to CSF</button></a>
		</div>
	</div>
	</div>
</div> --}}

@endsection

@section('js')
<script type="text/javascript">
	// ---- Minimalist section nav: smooth-scroll (offset for the sticky header) + scrollspy ----
	(function () {
		var nav = document.querySelector('.section-nav');
		if (!nav) return;
		var links = [].slice.call(nav.querySelectorAll('a'));
		var secs = links.map(function (a) { return document.querySelector(a.getAttribute('href')); });
		function headerOffset() {
			var h = document.querySelector('header.header_sticky');
			return (h ? h.offsetHeight : 90) + 10;
		}
		links.forEach(function (a) {
			a.addEventListener('click', function (e) {
				var t = document.querySelector(a.getAttribute('href'));
				if (!t) return;
				e.preventDefault();
				var y = t.getBoundingClientRect().top + window.pageYOffset - headerOffset();
				window.scrollTo({ top: Math.max(0, y), behavior: 'smooth' });
			});
		});
		function setActive(i) { links.forEach(function (l, j) { l.classList.toggle('active', i === j); }); }
		if ('IntersectionObserver' in window) {
			var io = new IntersectionObserver(function (entries) {
				entries.forEach(function (en) {
					if (en.isIntersecting) { var i = secs.indexOf(en.target); if (i > -1) setActive(i); }
				});
			}, { rootMargin: '-45% 0px -45% 0px', threshold: 0 });
			secs.forEach(function (s) { if (s) io.observe(s); });
		}
	})();

	$("#checkAll").click(function () {
        $('.form-check-input').not(this).prop('checked', false);
      });

	$(".form-check-input").click(function () {
        $('#checkAll').not(this).prop('checked', false);
      });

</script>
@if(!checkCSF() && Auth::user())
<script type="text/javascript">

    $(window).on('load', function() {
        $('#csfModal').modal('show');
    });
</script>
@endif

<script type="text/javascript">
// OPAC instant search (search-as-you-type) -> /search/suggest -> Meilisearch
(function () {
	var $form   = $('#frm');
	var $input  = $form.find('.search-query');
	var $box    = $('#instant-results');
	var $list   = $box.find('.instant-list');
	var $seeall = $box.find('.instant-seeall');
	var endpoint = "{{ url('/search/suggest') }}";
	var timer = null, lastQ = null, activeIdx = -1;
	var $anchor = $('#custom-search-input .input-group').first();   // the search bar (stays in place)
	$box.appendTo('body');   // escape the hero's overflow:hidden / stacking context

	function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

	function selectedType() {
		var vals = $form.find('input[name="seachtype[]"]:checked').map(function () { return $(this).val(); }).get();
		var specific = vals.filter(function (v) { return v !== '0'; });
		// "All" checked, none, or multiple specific -> search across all types
		return (vals.indexOf('0') !== -1 || specific.length !== 1) ? 0 : specific[0];
	}

	function position() {
		var el = $anchor[0] || $input[0];
		var r = el.getBoundingClientRect();
		$box.css({ top: (r.bottom + window.pageYOffset + 6) + 'px', left: (r.left + window.pageXOffset) + 'px', width: r.width + 'px' });
	}
	function open()  { position(); $box.show(); }
	function close() { $box.hide(); activeIdx = -1; }

	function render(data) {
		activeIdx = -1;
		if (!data.hits || !data.hits.length) {
			$list.html('<div class="instant-empty">No matches for &ldquo;' + esc(data.query) + '&rdquo;</div>');
			$seeall.hide(); open(); return;
		}
		var html = '';
		data.hits.forEach(function (h) {
			html += '<a class="instant-row" href="' + esc(h.url) + '">'
			     +    (h.thumb ? '<img class="instant-thumb" src="' + esc(h.thumb) + '" alt="">' : '')
			     +    '<div class="instant-text">'
			     +      '<div class="instant-title">' + esc(h.title) + '</div>'
			     +      '<div class="instant-meta">'
			     +        (h.author ? esc(h.author) : '')
			     +        (h.type ? '<span class="instant-type">' + esc(h.type) + '</span>' : '')
			     +        (h.year ? '<span class="instant-year">' + esc(h.year) + '</span>' : '')
			     +      '</div>'
			     +    '</div>'
			     +  '</a>';
		});
		$list.html(html);
		$seeall.text('See all ' + data.total + ' result' + (data.total == 1 ? '' : 's')).show();
		open();
	}

	function run(q) {
		$.getJSON(endpoint, { q: q, type: selectedType() })
			.done(render)
			.fail(function () { close(); });
	}

	$input.on('input', function () {
		// AI mode asks a question rather than matching titles, so the keyword
		// type-ahead has nothing useful to offer — stay out of the way.
		if (window.aiModeActive) { clearTimeout(timer); close(); return; }
		var q = $.trim($(this).val());
		if (q === lastQ) return;
		lastQ = q;
		clearTimeout(timer);
		if (q.length < 2) { close(); return; }
		$list.html('<div class="instant-loading">Searching&hellip;</div>');
		$seeall.hide(); open();
		timer = setTimeout(function () { run(q); }, 160);
	});

	// Re-run when the material-type filter changes mid-search
	$form.on('change', 'input[name="seachtype[]"]', function () {
		var q = $.trim($input.val());
		if (q.length >= 2) run(q);
	});

	// "See all N results" -> run the real search on /results
	$seeall.on('click', function (e) { e.preventDefault(); $form.trigger('submit'); });

	// Keyboard navigation
	$input.on('keydown', function (e) {
		if (e.key === 'Escape') { close(); return; }
		var $rows = $list.find('.instant-row');
		if (!$rows.length) return;
		if (e.key === 'ArrowDown')      { e.preventDefault(); activeIdx = Math.min(activeIdx + 1, $rows.length - 1); }
		else if (e.key === 'ArrowUp')   { e.preventDefault(); activeIdx = Math.max(activeIdx - 1, 0); }
		else if (e.key === 'Enter' && activeIdx >= 0) { e.preventDefault(); window.location = $rows.eq(activeIdx).attr('href'); return; }
		else return;
		$rows.removeClass('active').eq(activeIdx).addClass('active');
	});

	$input.on('focus', function () {
		if (window.aiModeActive) return;
		if (lastQ && lastQ.length >= 2 && $list.children().length) open();
	});

	// Close on outside click
	$(document).on('mousedown', function (e) {
		if (!$(e.target).closest('#instant-results, .instant-wrap').length) close();
	});

	// Keep the (fixed) dropdown aligned to the search bar
	$(window).on('scroll resize', function () { if ($box.is(':visible')) position(); });

	// Let the mode toggle dismiss the dropdown when switching to AI mode.
	window.instantSearchClose = close;
})();
</script>

@if(settingEnabled('ai_mode_enabled', false))
<script type="text/javascript">
// Search-engine toggle: Classic (Meilisearch) <-> AI mode (Hermes/RAG).
// Only swaps which engine the form targets — the search box itself is shared.
// Not emitted at all when AI mode is off, so a remembered 'ai' choice in localStorage
// can't resurrect the mode after an admin switches it off.
(function () {
	var STORAGE_KEY = 'elib_search_mode';
	var $form   = $('#frm');
	var $input  = $form.find('.search-query');
	var $wrap   = $('#custom-search-input');
	var $mode   = $('#search-mode');
	var $submit = $form.find('.btn_search');

	var COPY = {
		'':   { placeholder: 'Search a Material...',                     submit: 'Search' },
		'ai': { placeholder: 'Ask a question about the collection...',   submit: 'Ask' }
	};

	function apply(mode, focus) {
		var c = COPY[mode] || COPY[''];

		window.aiModeActive = (mode === 'ai');
		$mode.val(mode);
		$input.attr('placeholder', c.placeholder);
		$submit.val(c.submit);
		$wrap.toggleClass('is-ai', mode === 'ai');

		$('.mode-btn').each(function () {
			var on = ($(this).data('mode') || '') === mode;
			$(this).toggleClass('active', on).attr('aria-selected', on ? 'true' : 'false');
		});

		// Material-type filters are a keyword-search concept; AI mode explains itself instead.
		$('#type-filters').toggle(mode !== 'ai');
		$('#ai-note').toggle(mode === 'ai');

		if (mode === 'ai' && window.instantSearchClose) window.instantSearchClose();
		if (focus) $input.trigger('focus');
	}

	// AI mode is members-only. A guest clicking it is prompted to sign in; the mode
	// does not switch (and the endpoint/page enforce it too).
	var isGuest = !@json(auth()->check());

	function promptLogin() {
		if (window.Swal) {
			Swal.fire({
				icon: 'info',
				title: 'Sign in to use AI mode',
				text: 'Asking the library’s AI is available to registered members. Log in or create a free account to continue.',
				showDenyButton: true,
				showCancelButton: true,
				confirmButtonText: 'Log in',
				denyButtonText: 'Register',
				cancelButtonText: 'Not now',
				reverseButtons: true
			}).then(function (r) {
				if (r.isConfirmed) window.location = "{{ url('/login') }}";
				else if (r.isDenied) window.location = "{{ url('/register') }}";
			});
		} else {
			window.location = "{{ url('/login') }}";
		}
	}

	$('.mode-btn').on('click', function () {
		var mode = $(this).data('mode') || '';
		if (mode === 'ai' && isGuest) { promptLogin(); return; }   // don't switch
		try { localStorage.setItem(STORAGE_KEY, mode); } catch (e) {}
		apply(mode, true);
	});

	// Remember the choice across visits (localStorage may throw in private mode).
	// A guest is never restored into AI mode even if a prior session saved it.
	var saved = '';
	try { saved = localStorage.getItem(STORAGE_KEY) || ''; } catch (e) {}
	apply((saved === 'ai' && !isGuest) ? 'ai' : '', false);
})();
</script>
@endif

<script type="text/javascript">
// Advanced search under the home search box: rows of field + match + words (opac/partials/search-adv-row),
// sent with the search words and types to /results. Keyword search only, so hidden in AI mode.
(function () {
	var form = document.getElementById('frm'), panel = document.getElementById('home-adv');   // the animated wrapper
	var toggle = document.getElementById('home-adv-toggle'), rows = document.getElementById('home-adv-rows');
	var mode = document.getElementById('search-mode');
	if (!form || !panel || !toggle) return;
	var seq = 100, isOpen = false, timer = null;
	var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	// The hero centres its content vertically (a table cell), so a growing panel would push the logo and
	// search box UP by half its height. While the panel is open, pin the content where it stands and let
	// the panel grow downward only; release it once the panel is closed again.
	var cell = form.closest('.content');
	function pin() {
		if (!cell || cell.dataset.pinned || getComputedStyle(cell).display !== 'table-cell') return;
		var first = cell.firstElementChild;
		var top = first.getBoundingClientRect().top - cell.getBoundingClientRect().top;
		var margin = parseFloat(getComputedStyle(first).marginTop) || 0;
		cell.style.verticalAlign = 'top';
		cell.style.paddingTop = Math.max(0, top - margin) + 'px';
		cell.dataset.pinned = '1';
	}
	function unpin() {
		if (!cell || !cell.dataset.pinned) return;
		cell.style.verticalAlign = ''; cell.style.paddingTop = ''; delete cell.dataset.pinned;
	}

	function afterMove(done) {
		clearTimeout(timer);
		if (reduceMotion) { done(); return; }
		var finished = false;
		var finish = function (e) { if ((e && e.propertyName !== 'height') || finished) return; finished = true; panel.removeEventListener('transitionend', finish); done(); };
		panel.addEventListener('transitionend', finish);
		timer = setTimeout(finish, 450);   // in case transitionend never fires
	}
	function setOpen(open) {
		if (open === isOpen) return;
		isOpen = open;
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		if (open) {
			pin();
			var from = panel.hidden ? 0 : panel.getBoundingClientRect().height;   // reopened mid-close: carry on from there
			panel.hidden = false;
			panel.classList.remove('is-settled');
			panel.style.height = from + 'px';
			void panel.offsetHeight;
			panel.classList.add('is-open');
			panel.style.height = panel.scrollHeight + 'px';
			var first = rows.querySelector('input[type="text"]');
			if (first) first.focus({ preventScroll: true });
			afterMove(function () { if (!isOpen) return; panel.style.height = 'auto'; panel.classList.add('is-settled'); });
		} else {
			panel.classList.remove('is-settled');
			panel.style.height = panel.getBoundingClientRect().height + 'px';  // from its current height...
			void panel.offsetHeight;
			panel.classList.remove('is-open');
			panel.style.height = '0px';                      // ...down to 0
			afterMove(function () { if (isOpen) return; panel.hidden = true; unpin(); });
		}
	}
	toggle.addEventListener('click', function () { setOpen(!isOpen); });

	document.getElementById('home-adv-add').addEventListener('click', function () {
		if (rows.children.length >= 6) return;
		var html = document.getElementById('home-adv-row-tpl').innerHTML.replace(/999/g, String(++seq));
		rows.insertAdjacentHTML('beforeend', html);
		rows.lastElementChild.querySelector('input[type="text"]').focus();
	});
	rows.addEventListener('click', function (e) {
		var rm = e.target.closest('[data-adv-remove]');
		if (!rm) return;
		var row = rm.closest('.sr-adv-row');
		if (rows.children.length > 1) row.remove(); else row.querySelector('input[type="text"]').value = '';
	});

	// AI mode is plain-language questions: no rows. Follow the mode toggle (and its remembered state).
	function syncMode() {
		var ai = mode && mode.value === 'ai';
		toggle.hidden = ai;
		if (ai) setOpen(false);
	}
	document.querySelectorAll('.mode-btn').forEach(function (b) { b.addEventListener('click', function () { setTimeout(syncMode, 0); }); });
	syncMode();

	// Send only the rows that have words, and none while the panel is closed.
	form.addEventListener('submit', function () {
		rows.querySelectorAll('.sr-adv-row').forEach(function (row) {
			var text = row.querySelector('input[type="text"]');
			var skip = !isOpen || !text.value.trim();
			row.querySelectorAll('input, select').forEach(function (el) { el.disabled = skip; });
		});
		// Re-enable after the navigation starts, so Back shows the rows as they were.
		setTimeout(function () { rows.querySelectorAll('input, select').forEach(function (el) { el.disabled = false; }); }, 0);
	});
})();
</script>

{{-- Save stars on the homepage covers (shelves, Top 10 lists, Recent Uploads). The same record can
     appear in several places (and in the shelf clones), so every star for it changes together. --}}
<script type="text/javascript">
(function () {
	var SAVE = @json(url('/search/saved'));
	var isGuest = !@json(auth()->check());
	var tokenMeta = document.querySelector('meta[name="csrf_token"]');
	var busy = {};

	function paint(id, on) {
		document.querySelectorAll('.save-star[data-home-save="' + id + '"]').forEach(function (b) {
			b.classList.toggle('is-saved', on);
			b.setAttribute('aria-pressed', on ? 'true' : 'false');
			b.title = on ? 'Saved (click to remove)' : 'Save to your list';
		});
	}
	function toast(text, icon) {
		if (!window.Swal) return;
		Swal.fire({ toast: true, position: 'bottom', icon: icon || 'success', title: text, showConfirmButton: false, timer: 1800, timerProgressBar: false });
	}
	function promptLogin() {
		if (!window.Swal) { window.location = "{{ url('/login') }}"; return; }
		Swal.fire({
			icon: 'info',
			title: 'Sign in to save materials',
			text: 'Saving materials to your list is available to registered members. Log in or create a free account to continue.',
			showDenyButton: true,
			showCancelButton: true,
			confirmButtonText: 'Log in',
			denyButtonText: 'Register',
			cancelButtonText: 'Not now',
			reverseButtons: true
		}).then(function (r) {
			if (r.isConfirmed) window.location = "{{ url('/login') }}";
			else if (r.isDenied) window.location = "{{ url('/register') }}";
		});
	}

	document.addEventListener('click', function (e) {
		var star = e.target.closest('.save-star[data-home-save]');
		if (!star) return;
		e.preventDefault();
		if (isGuest) { promptLogin(); return; }
		var id = star.getAttribute('data-home-save');
		if (busy[id]) return;
		busy[id] = true;

		var was = star.classList.contains('is-saved');
		paint(id, !was);   // show it straight away; undone below if the request fails
		fetch(SAVE, {
			method: 'POST',
			headers: { 'X-CSRF-TOKEN': tokenMeta ? tokenMeta.content : '', 'X-Requested-With': 'XMLHttpRequest',
				'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
			body: 'catalog_id=' + encodeURIComponent(id)
		})
			.then(function (r) {
				if (r.status === 401 || r.status === 419) throw new Error('auth');
				if (!r.ok) throw new Error();
				return r.json();
			})
			.then(function (data) {
				paint(id, data.saved);
				if (data.saved) {
					document.querySelectorAll('.save-star[data-home-save="' + id + '"]').forEach(function (b) {
						b.classList.remove('pop'); void b.offsetWidth; b.classList.add('pop');
					});
				}
				var n = document.getElementById('home-saved-n');
				if (n) n.textContent = data.count;
				toast(data.saved ? 'Saved to your list' : 'Removed from your list');
			})
			.catch(function (err) {
				paint(id, was);
				if (err.message === 'auth') promptLogin();   // signed out in another tab, or the page sat too long
				else toast('That didn\u2019t save. Please try again.', 'error');
			})
			.finally(function () { busy[id] = false; });
	});
})();
</script>

@endsection
