@extends('opac.layout.master')

@section('css')
    <!-- SPECIFIC CSS -->
    <link href="{{ asset('opac/css/date_picker.css') }}" rel="stylesheet">
    <style>
    .ol-list-row { display: flex; gap: 18px; padding: 16px 4px; border-bottom: 1px solid #e7e7e7; align-items: flex-start; }
    .ol-list-cover { flex-shrink: 0; width: 75px; text-align: center; }
    .ol-list-cover img { max-width: 75px; max-height: 105px; width: auto; box-shadow: 0 1px 5px rgba(0,0,0,.2); border-radius: 2px; }
    .ol-list-body { flex: 1; min-width: 0; }
    .ol-list-title { display: inline-block; font-size: 18px; font-weight: 600; line-height: 1.25; color: #0176d3; text-decoration: none; }
    .ol-list-title:hover { text-decoration: underline; }
    .ol-list-author { color: #555; font-size: 14px; margin-top: 2px; }
    .ol-list-rating { margin-top: 5px; }
    .ol-list-meta { color: #8a8a8a; font-size: 13px; margin-top: 4px; }
    .ol-list-actions { flex-shrink: 0; align-self: center; display: flex; flex-direction: column; gap: 7px; width: 150px; }
    .ol-list-actions .btn_1.ol-full-desc { display: block; width: 100%; text-align: center; border-radius: 4px; font-size: 12px; padding: 6px 10px; white-space: nowrap; }
    .ol-btn-row { display: flex; gap: 6px; }
    .ol-icon-btn { flex: 1; display: inline-flex; align-items: center; justify-content: center; height: 30px; border: 1px solid #d0d7de; border-radius: 4px; background: #fff; color: #3f4079; font-size: 14px; cursor: pointer; text-decoration: none; transition: background .12s, color .12s, border-color .12s; }
    .ol-icon-btn:hover { background: #00a496; border-color: #00a496; color: #fff; }
    .ol-icon-btn.disabled { opacity: .5; cursor: not-allowed; background: #f4f5f7; border-color: #e1e4e8; color: #9aa0a6; }
    .ol-icon-btn.disabled:hover { background: #f4f5f7; border-color: #e1e4e8; color: #9aa0a6; }
    @media (max-width: 575px) { .ol-list-actions { display: none; } }
    /* Read overlay (PDF.js) */
    .ol-reader { position: fixed; inset: 0; z-index: 20000; background: rgba(18,20,24,.94); display: none; flex-direction: column; }
    .ol-reader-bar { flex: 0 0 46px; display: flex; align-items: center; justify-content: space-between; padding: 0 14px; background: #1b1d21; color: #fff; }
    .ol-reader-title { font-size: 14px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-right: 12px; }
    .ol-reader-close { background: transparent; border: 0; color: #fff; font-size: 26px; line-height: 1; cursor: pointer; padding: 0 6px; }
    .ol-reader-close:hover { color: #ff6b6b; }
    .ol-reader-frame { flex: 1 1 auto; width: 100%; border: 0; background: #3a3a3a; }
    .margin_30_35 { margin-top: 30px; margin-bottom: 35px; }
    .margin_30_35 h3.alert-info { font-size: 16px; line-height: 1.4; margin-bottom: 20px; }
    #sidebar .box_general_3 .title h3 { font-size: 17px; }
    #sidebar .box_general_3 .title.title_with_layout { display: flex; align-items: center; justify-content: space-between; }
    #sidebar .title_with_layout h3 { margin-bottom: 0; }
    .layout_view_inline { margin-bottom: 0; padding: 4px 9px; line-height: 1; }
    .layout_view_inline a { color: #3f4079; font-size: 17px; text-decoration: none; }
    .layout_view_inline a + a { margin-left: 9px; }
    .layout_view_inline a.active { color: #00a496; }
    .layout_view_inline a:hover { color: #e74e84; }
    </style>
 @endsection

@section('content')
   <div id="results" style="background-color: #00a496;">
       <div class="container">
           <div class="row">
               <div class="col-md-6">
                   <!-- <h4><strong>Showing 10</strong> of {{ $result->total() }} results</h4> -->
               </div>
               <div class="col-md-6">
               	<form method="get" action="{{ url('/results') }}" id="frm">
					@csrf
                    <div class="search_bar_list">
                    	<input type="text" class="form-control" name="querysearch" id="querysearch" value="{{ $_GET['query'] }}" placeholder="Search a Material...">
						@if(isset($_GET['query']))
	                    <input type="hidden" name="query" id="query" value="{{ $_GET['query'] }}">
						@else
						<input type="hidden" name="query" id="query" value="">
						@endif
	                    <input type="hidden" name="series_statement_id" value="{{ $_GET['series_statement_id'] }}">
	                    <input type="hidden" name="page" value="{{ $_GET['page'] }}">
	                    <input type="hidden" name="layout" id="layout" value="{{ $_GET['layout'] }}">
						@foreach($search_type as $type)
						<input type="hidden" value="{{ $type }}" name="seachtype[]">
						@endforeach
						
	                    <input type="submit" value="Search">
                	</div>
				</form>
               </div>
           </div>
           <!-- /row -->
       </div>
       <!-- /container -->
   </div>
   <!-- /results -->
   
	<div class="container margin_30_35">
		<div class="row">
			<div class="col-lg-9">
				@if(isset($_GET['query']) && $_GET['query'] != '')
				<h3 class="alert alert-info">Results for "{{ $_GET['query'] }}"</h3>
				@else
					@if($_GET['series_statement_id'] != 0)
						<h3 class="alert alert-info">Results for series : "{{ getSeriesDesc($_GET['series_statement_id']) }}"</h3>
					@endif
				@endif
				<div class="ol-list">
					@foreach($result AS $results)
					<div class="ol-list-row">
						<a href="#0" class="ol-list-cover" onclick="submitFrm('{{ $results->catalog_id }}')">
							{!! getThumbnailResult($results->catalog_id) !!}
						</a>
						<div class="ol-list-body">
							<a href="#0" class="ol-list-title" onclick="submitFrm('{{ $results->catalog_id }}')">{{ maxTitle($results->title_statement,200) }}</a>
							@if($results->joint_authors)
								<div class="ol-list-author">by {{ trim(explode('/', $results->joint_authors)[0]) }}</div>
							@endif
							<div class="ol-list-rating rating">
								<i data-star="{{ $ave = starRatingAve($results->catalog_id) }}"></i>
							</div>
							<div class="ol-list-meta">
								@if($results->material){{ $results->material }}@endif
								@if($results->pub_year) &middot; {{ $results->pub_year }}@endif
								@if($results->edition != '' && $results->edition != null) &middot; {{ formatText($results->edition) }} ed.@endif
							</div>
						</div>
						@php $pdfUrl = getReadPdfUrl($results->catalog_id); $canBorrow = checkStocks($results->catalog_id); @endphp
						<div class="ol-list-actions">
							<a href="#0" class="btn_1 ol-full-desc" onclick="submitFrm('{{ $results->catalog_id }}'); return false;">Full description</a>
							<div class="ol-btn-row">
								@if($pdfUrl)
									<a href="#0" class="ol-icon-btn" title="Read online" onclick="openReader('{{ $pdfUrl }}', @js($results->title_statement)); return false;"><i class="icon-book"></i></a>
									<a href="#0" class="ol-icon-btn" title="Download PDF (login required)" onclick="downloadPdf('{{ $results->catalog_id }}'); return false;"><i class="icon-download"></i></a>
								@endif
								@if($canBorrow)
									<a href="#0" class="ol-icon-btn" title="Available to borrow — view details" onclick="submitFrm('{{ $results->catalog_id }}'); return false;"><i class="icon-library"></i></a>
								@else
									<span class="ol-icon-btn disabled" title="No physical copy available for borrowing"><i class="icon-library"></i></span>
								@endif
							</div>
						</div>
					</div>
					@endforeach
				</div>
				<!-- /row -->


				{{ $result->appends(['series_statement_id' => $_GET['series_statement_id'],'layout' => $_GET['layout']])->links('vendor.pagination.default') }}

				<!-- /pagination -->
			</div>

			<aside class="col-xl-3 col-lg-3" id="sidebar">
				<!-- <div class="box_general_3 booking">
						<div class="title" style="background-color: #00a496;">
						<h3>Filter Result</h3>
						</div>
						<ul class="treatments clearfix">
							<li>
								<div class="form-check checkbox">
									<input type="checkbox" class="form-check-input css-checkbox" value="0" id="checkAll" name="seachtype[]" checked>
									<label class="css-label" for="checkAll">All</label>
								</div>

							</li>
							@foreach(getMaterials() AS $materials)
								<li>
									<div class="form-check checkbox">
										<input type="checkbox" class="form-check-input css-checkbox" id="mat_{{$materials->id}}" name="seachtype[]" value="{{ $materials->id }}">
										<label for="mat_{{$materials->id}}" class="css-label">{{ $materials->name }}</label>
									</div>
								</li>
							@endforeach
							</ul>
						
				</div> -->





				<div class="box_general_3 booking">
						<div class="title title_with_layout" style="background-color: #00a496;">
						<h3>Filter Result</h3>
						<div class="layout_view layout_view_inline">
							<a href="#0" data-bs-toggle="tooltip" data-bs-placement="top" title="Grid View"><i class="icon-th" onclick="changeLayout('grid')"></i></a>
							<a href="#0" class="active"><i class="icon-th-list"></i></a>
						</div>
						</div>
						<form method="get" action="{{ url('/results') }}" id="frm">
						@csrf
						@if(isset($_GET['query']))
	                    <input type="hidden" name="query" id="query" value="{{ $_GET['query'] }}">
						@else
						<input type="hidden" name="query" id="query" value="">
						@endif
	                    <input type="hidden" name="series_statement_id" value="{{ $_GET['series_statement_id'] }}">
	                    <input type="hidden" name="page" value="{{ $_GET['page'] }}">
	                    <input type="hidden" name="layout" id="layout" value="{{ $_GET['layout'] }}">
						<p>Material Type</p>
						<ul class="treatments clearfix">
							<li>
								<div class="checkbox">
									<input type="checkbox" class="css-checkbox" id="checkAll" name="seachtype[]" value="0">
									<label for="checkAll" class="css-label">All</label>
								</div>
							</li>
							@foreach(getMaterials() AS $materials)
								<li>
									<div class="checkbox">
										<input type="checkbox" class="css-checkbox form-check-input" id="mat_{{$materials->id}}" name="seachtype[]" value="{{ $materials->id }}">
										<label for="mat_{{$materials->id}}" class="css-label">{{ $materials->name }}</label>
									</div>
								</li>
							@endforeach
							</ul>
						<br>
						<br>
						<p>Publication Year</p>
						<div class="row">
							<div class="col-6">
								<small>From</small>
								<select class="form-control" name="year1" id="year1">
									<option value="">-</option>
									@for($i=date('Y'); $i >= 1970; $i--)
										<option value="{{$i}}">{{$i}}</option>
									@endfor
								</select>
							</div>
							<div class="col-6">
								<small>To</small>
								<select class="form-control" name="year2" id="year2">
									<option value="">-</option>
									@for($i=date('Y'); $i >= 1970; $i--)
										<option value="{{$i}}">{{$i}}</option>
									@endfor
								</select>
							</div>
						</div>
						<br>
						<button type="submit" class="btn_1 full-width">Submit</button>
						</form>
				</div>
			</aside>
			<!-- /col -->
		</div>
		<!-- /row -->
	</div>
<form method="post" action="{{ url('/full-description') }}" id="frm2">
	@csrf
	<input type="hidden" name="catalog_id" id="catalog_id">
</form>

{{-- Download (login enforced by DownloadController auth+patron middleware; increments Downloads) --}}
<form method="post" action="{{ url('/download') }}" id="frm_download_grid" target="_blank">
	@csrf
	<input type="hidden" name="holdings_download_id" id="dl_catalog_id" value="">
</form>

{{-- Read overlay: in-browser PDF.js reader (open to everyone) --}}
<div id="ol-reader" class="ol-reader">
	<div class="ol-reader-bar">
		<span class="ol-reader-title" id="ol-reader-title"></span>
		<button type="button" class="ol-reader-close" onclick="closeReader()" aria-label="Close reader">&times;</button>
	</div>
	<iframe id="ol-reader-frame" class="ol-reader-frame" src="about:blank" title="Online reader"></iframe>
</div>
@endsection

@section('js')
	<!-- SPECIFIC SCRIPTS -->
	<script src="{{ asset('opac/js/markerclusterer.js') }}"></script>
    <script src="{{ asset('opac/js/map_listing.js') }}"></script>
    <script src="{{ asset('opac/js/infobox.js') }}"></script>
    <script type="text/javascript">
	
	$("#checkAll").click(function () {
        $('.form-check-input').not(this).prop('checked', false);
      });

	$(".form-check-input").click(function () {
        $('#checkAll').not(this).prop('checked', false);
      });

	function submitFrm(id)
	{
		$("#catalog_id").val(id)
		$("#frm2").submit();
	}

	function downloadPdf(id)
	{
		$("#dl_catalog_id").val(id);
		$("#frm_download_grid").submit();
	}

	function positionReader()
	{
		var reader = document.getElementById('ol-reader');
		if(!reader || reader.style.display === 'none') return;
		var header = document.querySelector('header.header_sticky') || document.querySelector('header');
		var top = header ? Math.max(0, header.getBoundingClientRect().bottom) : 0;
		reader.style.top = top + 'px';
	}

	function openReader(pdfPath, title)
	{
		var viewer = '/pdfjs/web/viewer.html?file=' + encodeURIComponent(pdfPath);
		document.getElementById('ol-reader-frame').src = viewer;
		document.getElementById('ol-reader-title').textContent = title || 'Reading';
		document.getElementById('ol-reader').style.display = 'flex';
		document.body.style.overflow = 'hidden';
		positionReader();
	}

	function closeReader()
	{
		document.getElementById('ol-reader').style.display = 'none';
		document.getElementById('ol-reader-frame').src = 'about:blank';
		document.body.style.overflow = '';
	}

	document.addEventListener('keydown', function(e){ if(e.key === 'Escape'){ closeReader(); } });
	window.addEventListener('resize', positionReader);

	$("#frm").submit(function(){
		$("#query").val($("#querysearch").val());
	});
	
	function changeLayout(layout)
	{
		$("#layout").val(layout);
		$("#frm").submit();
	}

	
</script>
@endsection