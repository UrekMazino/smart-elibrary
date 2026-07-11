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
    .ol-list-meta { color: #8a8a8a; font-size: 13px; margin-top: 4px; }
    .ol-list-actions { flex-shrink: 0; align-self: center; }
    .ol-list-actions .btn_1 { font-size: 12px; padding: 5px 14px; white-space: nowrap; }
    @media (max-width: 575px) { .ol-list-actions { display: none; } }
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
   
   <div class="filters_listing">
		<div class="container">
			<ul class="clearfix">
				<li>  			
				</li>
				<li>
					<h6>Layout</h6>
					<div class="layout_view">
						<a href="#0" tooltip="List View"  data-bs-toggle="tooltip" data-bs-placement="top" title="Grid View"><i class="icon-th" onclick="changeLayout('grid')"></i></a>
						<a href="#0" class="active" ><i class="icon-th-list"></i></a>
					</div>
				</li>
			</ul>
		</div>
		<!-- /container -->
	</div>
	<!-- /filters -->
	   
	<div class="container margin_60_35">
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
							<div class="ol-list-meta">
								@if($results->material){{ $results->material }}@endif
								@if($results->pub_year) &middot; {{ $results->pub_year }}@endif
								@if($results->edition != '' && $results->edition != null) &middot; {{ formatText($results->edition) }} ed.@endif
							</div>
						</div>
						<div class="ol-list-actions">
							<a href="#0" class="btn_1" onclick="submitFrm('{{ $results->catalog_id }}')">Full description</a>
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
						<div class="title" style="background-color: #00a496;">
						<h3>Filter Result</h3>
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