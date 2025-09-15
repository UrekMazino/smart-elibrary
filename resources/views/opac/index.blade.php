@extends('opac.layout.master')

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
						<div class="input-group">
							<input type="text" class="search-query" name="query" placeholder="Search a Material...">
							<input type="submit" class="btn_search" value="Search">
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
													<a href="#"><img src="http://via.placeholder.com/160x160.jpg" alt=""></a>
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

@endsection