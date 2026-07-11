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
	display: block; padding: 10px 16px; border-bottom: 1px solid #f0f2f4; color: #222; text-decoration: none;
}
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

		<div class="container margin_120_95">
			<div class="main_title">
				<h2>Featured Materials</h2>
				{{-- <p>Usu habeo equidem sanctus no. Suas summo id sed, erat erant oporteat cu pri. In eum omnes molestie. Sed ad debet scaevola, ne mel.</p> --}}
			</div>
			<div class="row add_bottom_30">
				<div id="most-downloaded" class="owl-carousel owl-theme">
					@foreach(getFeaturedMaterials() as $url)
						<div class="item">
							<a href="#">
								{{-- <div class="views"><i class="icon-download-5"></i></div> --}}
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
		<!-- /container -->

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
			     +    '<div class="instant-title">' + esc(h.title) + '</div>'
			     +    '<div class="instant-meta">'
			     +      (h.author ? esc(h.author) : '')
			     +      (h.type ? '<span class="instant-type">' + esc(h.type) + '</span>' : '')
			     +      (h.year ? '<span class="instant-year">' + esc(h.year) + '</span>' : '')
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