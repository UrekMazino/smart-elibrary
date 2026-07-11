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
    .ol-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(125px, 1fr)); gap: 38px 30px; }
    .ol-card { text-align: center; min-width: 0; }
    .ol-card .ol-cover { display: flex; align-items: flex-end; justify-content: center; height: 165px; }
    .ol-card .ol-cover img { max-height: 165px; max-width: 100%; width: auto; box-shadow: 0 1px 6px rgba(0,0,0,.22); border-radius: 2px; }
    .ol-card .ol-title { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; margin-top: 8px; font-size: 12.5px; line-height: 1.3; color: #0176d3; font-weight: 600; text-decoration: none; }
    .ol-card .ol-title:hover { text-decoration: underline; }
    .ol-card .ol-author { font-size: 11.5px; color: #666; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ol-card .ol-action { margin-top: 7px; }
    .ol-card .ol-action .btn_1 { font-size: 11px; padding: 4px 12px; display: inline-block; line-height: 1.4; }
    </style>
 @endsection

@section('content')
   <div id="results" style="background-color: #00a496;">
       <div class="container">
           <div class="row">
               <div class="col-md-6">
                   {{-- <h4><strong>Showing 10</strong> of {{ $result->total() }} results</h4> --}}
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
					<!-- <h6>Type</h6>
					<div class="form-check form-check-inline">
							<input class="form-check-input" type="checkbox" value="0" id="checkAll" name="seachtype[]" checked>
	  						<label class="form-check-label" for="checkAll" style="padding: 2px!important;background-color: transparent;">
	    								All
	  						</label>
  						</div>
  						@foreach(getMaterials() AS $materials)
  						<div class="form-check form-check-inline">
							<input class="form-check-input" type="checkbox" value="{{ $materials->id }}" name="seachtype[]" id="materials-{{ $materials->id }}">
	  						<label class="form-check-label lbl-check" id="materials-{{ $materials->id }}" style="padding: 2px!important;background-color: transparent;" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $materials->tooltip }}">
	    								{{ $materials->name }}
	  						</label>
  						</div>
  						@endforeach -->
				</li>
				<li>
					<h6>Layout</h6>
					<div class="layout_view">
						<a href="#0" class="active"><i class="icon-th"></i></a>
						<a href="#0" tooltip="List View"  data-bs-toggle="tooltip" data-bs-placement="top" title="List View"><i class="icon-th-list" onclick="changeLayout('list')"></i></a>
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
				<div class="ol-grid">
					@foreach($result AS $results)
					<div class="ol-card">
						<a href="#0" class="ol-cover" onclick="submitFrm('{{ $results->catalog_id }}')">
							{!! getThumbnailResult($results->catalog_id) !!}
						</a>
						<a href="#0" class="ol-title" onclick="submitFrm('{{ $results->catalog_id }}')">{{ maxTitle($results->title_statement, 70) }}</a>
						@if($results->joint_authors)
							<div class="ol-author">{{ trim(explode('/', $results->joint_authors)[0]) }}</div>
						@endif
						<div class="ol-action">
							<a href="#0" class="btn_1" onclick="submitFrm('{{ $results->catalog_id }}')">Full description</a>
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
<form method="POST" action="{{ url('/full-description') }}" id="frm2">
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