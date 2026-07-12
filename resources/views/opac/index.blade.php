@extends('opac.layout.master')

@section('css')
<style>
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
</style>
@endsection

@section('content')
{{-- @if(Auth::user() && !checkCSF()) {{ session(['message' => 'Hello! Can we have a minute of your time to answer our CSF form. Thank you!']); }} @endif --}}
		@if (session('message'))
			<div class="alert @if(!checkCSF())alert-warning @else alert-success @endif text-center" style="margin-bottom:0; padding: 10px 1rem;" role="alert">
				{{ session('message') }} @if(!checkCSF()) <a href="{{ url('/csf/system-review') }}"><u>Go to CSF</u></a> @endif
			</div>
			@php session()->forget('message'); @endphp
		@endif

		<div class="hero_home version_1">
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
					<div id="custom-search-input">
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

		<style>
	/* ---- Admont Abbey Library inspired "Featured Materials" display shelf ---- */
	.admont-featured { position: relative; padding: 66px 15px 74px; overflow: hidden;
		background: radial-gradient(140% 120% at 50% -15%, #fdf9ef 0%, #f6eedb 52%, #efe4c8 100%); }
	.admont-featured::before { content:""; position:absolute; left:0; right:0; top:0; height:5px;
		background: linear-gradient(90deg, transparent, #c9a227 16%, #efd88b 50%, #c9a227 84%, transparent); }
	.admont-head { text-align:center; margin-bottom:30px; }
	.admont-head .orn { display:block; color:#c9a227; font-size:22px; line-height:1; margin-bottom:2px; }
	.admont-head h2 { font-family:Georgia,"Palatino Linotype","Book Antiqua",serif; font-size:30px;
		letter-spacing:3px; text-transform:uppercase; color:#4a3a25; margin:0; font-weight:700; }
	.admont-head .rule { display:inline-block; width:230px; max-width:72%; height:16px; margin-top:8px; position:relative; }
	.admont-head .rule::before { content:""; position:absolute; top:50%; left:0; right:0; height:1px; background:#c9a227; }
	.admont-head .rule::after { content:"\2666"; position:absolute; left:50%; top:50%; transform:translate(-50%,-50%);
		color:#c9a227; font-size:12px; background:#f7efda; padding:0 9px; }
	.admont-bay { position:relative; max-width:1140px; margin:0 auto; padding:30px 46px 0;
		background:linear-gradient(#fefcf6,#f6efdf); border:1px solid #d9c07a; border-radius:5px;
		box-shadow: inset 0 1px 0 #fff, 0 24px 44px -28px rgba(74,58,37,.55); }
	.admont-cornice { position:absolute; left:-1px; right:-1px; top:-1px; height:10px; border-radius:5px 5px 0 0;
		background:linear-gradient(#f0dca0,#cba52a); border-bottom:1px solid #b6901d; }
	.admont-pil { position:absolute; top:26px; bottom:46px; width:14px; border:1px solid #d9c07a; border-radius:2px;
		background:repeating-linear-gradient(90deg,#f7efd8,#f7efd8 2px,#e7d9af 3px,#f7efd8 5px); }
	.admont-pil.l { left:14px; } .admont-pil.r { right:14px; }
	.admont-shelf-area { position:relative; z-index:2; }
	.admont-featured #most-downloaded .owl-dots { display:none; }
	.admont-featured #most-downloaded .item { display:flex; align-items:flex-end; justify-content:center; height:252px; }
	.admont-featured #most-downloaded .item a { display:inline-block; line-height:0; }
	.admont-featured #most-downloaded .item img { height:242px !important; width:auto !important; max-width:90% !important;
		object-fit:contain !important; display:block; border-radius:2px;
		box-shadow: 0 2px 2px rgba(0,0,0,.28), 0 16px 18px -12px rgba(0,0,0,.55); }
	.admont-shelf { position:relative; height:30px; margin:-10px -46px 0; z-index:3;
		background:linear-gradient(#fdf8ec 0%, #f0e6cb 45%, #e3d3a5 62%, #cdb373 100%);
		border-top:2px solid #d9c07a;
		box-shadow: 0 -9px 12px -9px rgba(0,0,0,.3), 0 12px 18px -8px rgba(74,58,37,.55); }
	.admont-shelf::before { content:""; position:absolute; left:0; right:0; top:-2px; height:3px;
		background:linear-gradient(90deg,transparent,#efd88b,#c9a227,#efd88b,transparent); }
	.admont-shelf::after { content:""; position:absolute; left:0; right:0; bottom:0; height:7px;
		background:linear-gradient(#b6901d,#8c6d15); }
	@media (max-width:767px){
		.admont-bay { padding:20px 18px 0; }
		.admont-pil { display:none; }
		.admont-shelf { margin:-10px -18px 0; }
		.admont-featured #most-downloaded .item { height:196px; }
		.admont-featured #most-downloaded .item img { height:186px !important; }
		.admont-head h2 { font-size:22px; letter-spacing:2px; }
	}
	</style>
	<div class="admont-featured">
		<div class="admont-head">
			<span class="orn">&#10087;</span>
			<h2>Featured Materials</h2>
			<span class="rule"></span>
		</div>
		<div class="admont-bay">
			<span class="admont-cornice"></span>
			<span class="admont-pil l"></span>
			<span class="admont-pil r"></span>
			<div class="admont-shelf-area">
				<div id="most-downloaded" class="owl-carousel owl-theme">
					@foreach(getFeaturedMaterials() as $url)
						<div class="item">
							<a href="#"><img src="{{ $url }}" class="img-fluid img-cover fill" alt=""></a>
						</div>
					@endforeach
				</div>
				<div class="admont-shelf" aria-hidden="true"></div>
			</div>
		</div>
	</div>
	<!-- /featured -->

		{{-- <div class="bg_color_2">
			<div class="container margin_120_95">
				<div class="main_title">
					<h2 style="color:#FFF">Most Viewed</h2>
				</div>
				<div id="editors-choice" class="owl-carousel owl-theme">
					@foreach(mostViewed() AS $values)
						<div class="item">
							<a href="{{ url('/full-description/'.$values->catalog_id) }}">
								<div class="views"><i class="icon-eye-7"></i>{{$values->total}}</div>
								<div class="title">
								</div>{!! getFrontpage($values->catalog_id) !!}
							</a>
						</div>
					@endforeach
				</div>
			</div>
		</div> --}}

		<div class="bg_color_2">
			<div class="container margin_120_95">
				<div class="main_title">
					<h2 style="color:#FFF">Annual Reports</h2>
				</div>
				<div id="editors-choice" class="owl-carousel owl-theme">
					@foreach(getAnnualReports() AS $url)
						<div class="item">
							<a href="#">
								{{-- <div class="views"><i class="icon-eye-7"></i></div> --}}
								<div class="title">
									{{-- <h3>Title</h3> --}}
								</div>
								<img src="{{ $url }}" class="img-fluid img-cover fill" alt="">
							</a>
						</div>
					@endforeach
				</div>
			</div>
		</div>
		<!-- /white_bg -->
		<div class="container">
				<div class="row">
					<div class="col-lg-6 col-sm-12">
						<div class="container margin_120_95">
							<div class="main_title">
								<h2>Recent Uploads</h2>
							</div>
							<div class="row add_bottom_30">

								<div class="widget">
									<ul class="comments-list">
									@foreach(getLatestMaterial() AS $results)
									<li>
										<div class="alignleft">
											{!! getFrontpage($results->catalog_id) !!}
										</div>
										<small>Year : {{ $results->pub_year }}</small>
										<h3><a href="#" title="" onclick="sendFrm('{{ $results->catalog_id }}')" style="font-size:17px">{{ $results->title_statement }}</a><br><small>{{ $results->joint_authors }}</small></h3>
									</li>
									@endforeach
									</ul>
								</div>
							</div>
						</div>
					</div>
					<div class="col-lg-6 col-sm-12">
						<div class="container margin_120_95">
							<div class="main_title">
								<h2>Related News</h2>
							</div>
							<div class="row add_bottom_30">
								<div class="widget">
									<ul class="comments-list">
										@foreach(getLatestNews() AS $news)
										<li>
											<div class="alignleft">
												@if($news->news_cover)
													<a href="{{ $news->redirect_url ?? '#' }}"><img src="{{ url('/'.$news->news_cover) }}" alt=""></a>
												@else
													<a href="#"><img src="{{ url('/frontpages/no-preview.jpg') }}" alt=""></a>
												@endif
											</div>
											<small>{{ \Carbon\Carbon::parse($news->created_at)->diffForHumans() }}</small>
											<h3>
												<a href="{{ $news->redirect_url ?? '#' }}" target="_BLANK" title="{{ $news->news_title ?? '' }}" style="font-size:17px;">
													{{ $news->news_title ?? '' }}
												</a>
											</h3>
										</li>
										@endforeach
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>
		</div>
		
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
		if (lastQ && lastQ.length >= 2 && $list.children().length) open();
	});

	// Close on outside click
	$(document).on('mousedown', function (e) {
		if (!$(e.target).closest('#instant-results, .instant-wrap').length) close();
	});

	// Keep the (fixed) dropdown aligned to the search bar
	$(window).on('scroll resize', function () { if ($box.is(':visible')) position(); });
})();
</script>

@endsection