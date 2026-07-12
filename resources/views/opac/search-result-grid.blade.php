@extends('opac.layout.master')
<?php
	if(isset($_GET['year1']))
		$yr1 = $_GET['year1'];
	else
		$yr1 = 1970;
	
	if(isset($_GET['year2']))
		$yr2 = $_GET['year2'];
	else
		$yr2 = date('Y');
?>
@section('css')
    <!-- SPECIFIC CSS -->
    <link href="{{ asset('opac/css/date_picker.css') }}" rel="stylesheet">
    <style>
    .ol-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 34px 28px; align-content: start; min-height: 990px; }
    .ol-card { text-align: center; min-width: 0; }
    .ol-card .ol-cover { position: relative; display: flex; align-items: flex-end; justify-content: center; height: 230px; overflow: hidden; border-radius: 3px; }
    .ol-card .ol-cover img { max-height: 230px; max-width: 100%; width: auto; box-shadow: 0 1px 6px rgba(0,0,0,.22); border-radius: 2px; }
    .ol-card .ol-hover-info { position: absolute; left: 0; right: 0; bottom: 0; box-sizing: border-box; padding: 10px 9px; background: linear-gradient(to top, rgba(0,0,0,.88) 60%, rgba(0,0,0,0)); text-align: left; opacity: 0; transform: translateY(6px); transition: opacity .16s ease, transform .16s ease; pointer-events: none; }
    .ol-card .ol-cover:hover .ol-hover-info { opacity: 1; transform: translateY(0); }
    .ol-card .ol-hover-title { color: #fff; font-size: 12.5px; line-height: 1.32; font-weight: 600; display: -webkit-box; -webkit-line-clamp: 5; -webkit-box-orient: vertical; overflow: hidden; }
    .ol-card .ol-hover-rating { display: block; margin-top: 5px; line-height: 1; }
    .ol-card .ol-hover-rating [data-star] { font-size: 13px; }
    .ol-card .ol-actions { margin-top: 9px; display: flex; flex-direction: column; gap: 6px; }
    .ol-card .ol-btn-row { display: flex; gap: 6px; }
    .ol-icon-btn { flex: 1; display: inline-flex; align-items: center; justify-content: center; height: 30px; border: 1px solid #d0d7de; border-radius: 4px; background: #fff; color: #3f4079; font-size: 14px; cursor: pointer; text-decoration: none; transition: background .12s, color .12s, border-color .12s; }
    .ol-icon-btn:hover { background: #00a496; border-color: #00a496; color: #fff; }
    .ol-icon-btn.disabled { opacity: .5; cursor: not-allowed; background: #f4f5f7; border-color: #e1e4e8; color: #9aa0a6; }
    .ol-icon-btn.disabled:hover { background: #f4f5f7; border-color: #e1e4e8; color: #9aa0a6; }
    .ol-card .ol-full-desc { display: block; width: 100%; text-align: center; border-radius: 4px; font-size: 11.5px; padding: 7px 8px; line-height: 1.3; }
    /* Read overlay (PDF.js) */
    .ol-reader { position: fixed; inset: 0; z-index: 20000; background: rgba(18,20,24,.94); display: none; flex-direction: column; }
    .ol-reader-bar { flex: 0 0 46px; display: flex; align-items: center; justify-content: space-between; padding: 0 14px; background: #1b1d21; color: #fff; }
    .ol-reader-title { font-size: 14px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-right: 12px; }
    .ol-reader-close { background: transparent; border: 0; color: #fff; font-size: 26px; line-height: 1; cursor: pointer; padding: 0 6px; }
    .ol-reader-close:hover { color: #ff6b6b; }
    .ol-reader-frame { flex: 1 1 auto; width: 100%; border: 0; background: #3a3a3a; }
    @media (max-width: 1199px) { .ol-grid { grid-template-columns: repeat(4, 1fr); } }
    @media (max-width: 767px)  { .ol-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 479px)  { .ol-grid { grid-template-columns: repeat(2, 1fr); } }
    #breadcrumb.breadcrumb_inline { background: transparent; padding: 0; }
    @media (max-width: 767px) { #breadcrumb.breadcrumb_inline { padding: 0 0 12px; } }
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
    /* Keep the search bar always visible (sticky over the header) */
    #results { position: -webkit-sticky; position: sticky; top: 0; z-index: 1001; box-shadow: 0 3px 10px rgba(0,0,0,.15); }
    </style>
 @endsection

@section('content')
   <div id="results" style="background-color: #00a496;">
       <div class="container">
           <div class="row align-items-center">
               <div class="col-md-6">
                   <div id="breadcrumb" class="breadcrumb_inline">
                       <ul>
                           <li><a href="{{ url('/home') }}">Home</a></li>
                           <li>Search Results</li>
                       </ul>
                   </div>
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
				<div class="ol-grid">
					@foreach($result AS $results)
					<div class="ol-card">
						<a href="#0" class="ol-cover" onclick="submitFrm('{{ $results->catalog_id }}')" title="{{ $results->title_statement }}">
							{!! getThumbnailResult($results->catalog_id) !!}
							<span class="ol-hover-info">
								<span class="ol-hover-title">{{ $results->title_statement }}</span>
								<span class="ol-hover-rating rating"><i data-star="{{ starRatingAve($results->catalog_id) }}"></i></span>
							</span>
						</a>
						@php $pdfUrl = getReadPdfUrl($results->catalog_id); $canBorrow = checkStocks($results->catalog_id); @endphp
						<div class="ol-actions">
							<div class="ol-btn-row">
								@if($pdfUrl)
									<a href="#0" class="ol-icon-btn" title="Read online" onclick="readMaterial('{{ $results->catalog_id }}', '{{ $pdfUrl }}', @js($results->title_statement)); return false;"><i class="icon-book"></i></a>
									<a href="#0" class="ol-icon-btn" title="Download PDF (login required)" onclick="downloadPdf('{{ $results->catalog_id }}'); return false;"><i class="icon-download"></i></a>
								@endif
								@if($canBorrow)
									<a href="#0" class="ol-icon-btn" title="Available to borrow — view details" onclick="submitFrm('{{ $results->catalog_id }}'); return false;"><i class="icon-library"></i></a>
								@else
									<span class="ol-icon-btn disabled" title="No physical copy available for borrowing"><i class="icon-library"></i></span>
								@endif
							</div>
							<a href="#0" class="btn_1 ol-full-desc" onclick="submitFrm('{{ $results->catalog_id }}'); return false;">Full description</a>
						</div>
					</div>
					@endforeach
				</div>
				<!-- /row -->


				{{ $result->appends(['query' => $_GET['query'],'seachtype' => $_GET['seachtype'],'series_statement_id' => $_GET['series_statement_id'],'layout' => $_GET['layout'],'year1' => $yr1,'year2' => $yr2])->links('vendor.pagination.default') }}

				<!-- /pagination -->
			</div>

			<aside class="col-xl-3 col-lg-3" id="sidebar">
				<div class="box_general_3 booking">
						<div class="title title_with_layout" style="background-color: #00a496;">
						<h3>Filter Result</h3>
						<div class="layout_view layout_view_inline">
							<a href="#0" class="active"><i class="icon-th"></i></a>
							<a href="#0" data-bs-toggle="tooltip" data-bs-placement="top" title="List View"><i class="icon-th-list" onclick="changeLayout('list')"></i></a>
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
<form method="POST" action="{{ url('/full-description') }}" id="frm2">
	@csrf
	<input type="hidden" name="catalog_id" id="catalog_id">
</form>

{{-- Download (login enforced by DownloadController auth+patron middleware; increments Downloads) --}}
<form method="post" action="{{ url('/download') }}" id="frm_download_grid" @auth target="_blank" @endauth>
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
	
	$("#year1").val({{$yr1}});
	$("#year2").val({{$yr2}});

	<?php
	  $getsearchtype ="";
	  if(isset($_GET['seachtype']))
	  {
		foreach($_GET['seachtype'] as $key => $value)
		{  
			$getsearchtype .= $value.',';
		}
		$getsearchtype = "[".str_replace(".", "", $getsearchtype)."]";
	  }
	  else
	  {
		$getsearchtype = "[]";
	  }
	  
	?>

	var arrType = {{$getsearchtype}};

	$.each(arrType, function(index, value) {
		$("input[name='seachtype[]'][value='"+value+"']").prop("checked", true);
	});

	function submitFrm(id)
	{
		$("#catalog_id").val(id);
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

	function readMaterial(id, pdfPath, title)
	{
		openReader(pdfPath, title);
		// Count this read as a view (guest-aware; same as opening full-description)
		try {
			var tokenEl = document.querySelector('input[name=_token]');
			fetch('/read/view', {
				method: 'POST',
				headers: { 'X-CSRF-TOKEN': tokenEl ? tokenEl.value : '', 'Content-Type': 'application/x-www-form-urlencoded' },
				body: 'catalog_id=' + encodeURIComponent(id)
			});
		} catch (e) {}
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

	function submitForm()
	{
		$("#frm").submit();
	}
</script>
@endsection