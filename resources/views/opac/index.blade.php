@extends('opac.layout.master')

@section('meta_title', 'DOST-PCAARRD eLibrary — AANR research collection')
@section('meta_description', 'Search the DOST-PCAARRD eLibrary: books, theses, technical reports and other agriculture, aquatic and natural resources (AANR) research materials.')
@section('canonical', url('/'))


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
				<form method="get" action="{{ url('/results') }}" id="frm">
					@csrf
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

						{{-- Material-type filters only apply to keyword search; hidden in AI mode. --}}
						<div id="type-filters">
						<br/>
						<div class="form-check form-check-inline">
							<input class="form-check-input" type="checkbox" value="0" id="checkAll" name="seachtype[]" checked>
	  						<label class="form-check-label" for="checkAll" style="font-size:17px;padding: 2px!important;background-color: transparent;">
	    								All
	  						</label>
  						</div>

  						@foreach(getMaterials() AS $materials)
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
							<a href="{{ url('/full-description/'.$values->catalog_id) }}">
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
			.cc-cap { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; margin-top:7px; font-size:12.5px; line-height:1.3; color:#0176d3; text-decoration:none; font-weight:600; }
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
							<a class="cc-cover" href="{{ url('/full-description/'.$results->catalog_id) }}">
								<img loading="lazy" src="{{ getThumbnailLink($results->catalog_id) }}" alt="{{ $results->title_statement }}">
							</a>
							<a class="cc-cap" href="{{ url('/full-description/'.$results->catalog_id) }}" title="{{ $results->title_statement }}">{{ $results->title_statement }}</a>
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
<form method="post" action="{{ url('/full-description') }}" id="frm1">
	@csrf
	<input type="hidden" name="catalog_id" id="catalog_id">
</form>
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

	function sendFrm(val)
	{
		$("#catalog_id").val(val);
		$("#frm1").submit();
	}

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

@endsection
