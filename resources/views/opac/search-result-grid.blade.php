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
				<div class="row">
					@foreach($result AS $results)
					<div class="col-md-4">
						<div class="box_list wow fadeIn">
							<figure>
								
								{!! getFrontpageResult($results->catalog_id) !!}
								</a>
							</figure>
							<div class="wrapper">
								<small>{{ $results->material }}</small>
								<h5>{{ maxTitle($results->title_statement)  }}</h5>

								<span class="text-muted">{{ $results->joint_authors }}</span><br>

								@if($results->publisher != '' && $results->publisher != null)
									<b>Publisher : </b> {{ formatText($results->publisher) }}<br/>
								@endif

								{!! pubYear($results->pub_year) !!}
								
								@if($results->edition != '' && $results->edition != null)
									<b>Edition : </b> {{ formatText($results->edition) }}<br/>
								@endif
								
								<div class="rating">
									<i data-star="{{ $ave = starRatingAve($results->catalog_id) }}"></i>
								</div>
							</div>
							<ul>
								<li><a href="#">
								<li><a href="#" target="_blank"><i class="icon_pin_alt"></i></a></li>
								<li><a href="#" onclick="submitFrm('{{ $results->catalog_id }}')">Full description</a></li>
							</ul>
						</div>
					</div>
					@endforeach
					<!-- /box_list -->

					
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