@extends('opac.layout.master')

@section('css')
    <!-- SPECIFIC CSS -->
    <link href="{{ asset('opac/css/date_picker.css') }}" rel="stylesheet">
	<style>
		.accordion-button:not(.collapsed) {
			color: #fff;
			background-color: #74d1c6;
			box-shadow: inset 0 -1px 0 rgba(0,0,0,.125);
		}
	</style>
 @endsection

@section('content')
<form method="get" action="{{ url('/results') }}" id="frm" target="_blank">
	@csrf
	<input type="hidden" class="form-control" name="query" value="">
	<input type="hidden" class="form-control" name="series_statement_id" value="{{ isset($result->series_statement_id) ? $result->series_statement_id : '';   }}">
	<input type="hidden" name="seachtype[]" value="all-material">
	<input type="hidden" class="form-control" name="page" value="1">
	<input type="hidden" class="form-control" name="layout" value="grid">
</form>
<?php
	$review = showReview($result->catalog_id);
	$review_ctr = count($review);
	$ave = starRatingAve($result->catalog_id);
?>
<form method="post" action="{{ url('/review') }}" id="frm_review">
	@csrf
	<input type="hidden" class="form-control" name="catalog_id" value="{{ $result->catalog_id }}">
</form>
		<div id="breadcrumb" style="background-color: #00a496;">
			<div class="container">
				<ul>
					<li><a href="{{ url('/home') }}">Home</a></li>
					<li><a href="{{ url('/home') }}#search" onclick="if(document.referrer){history.back();return false;}">Search Results</a></li>
					<li>Description</li>
				</ul>
			</div>
		</div>
		<!-- /breadcrumb -->

		<style>
			.fd-cover { margin: 0; }
			.fd-cover img { box-shadow: 0 2px 10px rgba(0,0,0,.25); border-radius: 3px; max-width: 320px; width: 100%; }
		</style>
		<div class="container margin_60">
			<div class="row">
				<div class="col-xl-8 col-lg-8 order-2">
					<nav id="secondary_nav" style="background-color: #00a496;">
						<div class="container">
							<ul class="clearfix">
								<li><a href="#section_1" class="active">General info</a></li>
								<li><a href="#section_2">Reviews</a></li>
							</ul>
						</div>
					</nav>
					<div id="section_1">
						<div class="box_general_3">
							<div class="profile">
								<div class="row">
									<div class="col-12">
										<small>{{ $result->material }}</small>
										<br><i data-star="{{ $ave }}" style="font-size:20px"></i>
										<h5>{!! $result->title_statement !!}</h5>

										<small>{!! pubYear($result->pub_year) !!}</small>

										@if(isset($result->series_description))
											<br><small>Series : <a href="#" onclick="getElementById('frm').submit()">{{ $result->series_description }}</a></small>
										@endif

										<br><br>
										<ul class="statistic">
											<li>{{ countNum('view',$result->catalog_id) }} Views</li>
											<li>{{ countNum('review',$result->catalog_id) }} Reviews</li>
											<li>{{ countNum('download',$result->catalog_id) }} Downloads</li>
										</ul>
										<ul class="statistic">
											@if(checkIfHasPDF($result->catalog_id))
											<li style="background-color: #FFF;padding: 3px;">
												<form method="post" action="{{ url('/download') }}" id="frm_download" target="_blank">
													@csrf
													<input type="hidden" name="holdings_download_id" value="{{ $result->catalog_id }}">
													<button class="btn_1" style="background-color:#029978"><i class="icon-download"></i> Download</button>
												</form>
											</li>
											@endif
											<li style="background-color: #FFF;padding: 3px;">
													<button class="btn_1" style="background-color:#029978" onclick="inquire('{{ $result->catalog_id }}')"><i class="icon-info-3"></i> Inquire</button>
											</li>
											
										</ul>
									</div>
								</div>
							</div>
					
							
							<div class="accordion" id="materialDetails">
								<div class="accordion-item">
									<h2 class="accordion-header" id="headingOne">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
										SUMMARY
									</button>
									</h2>
									<div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#materialDetails">
									<div class="accordion-body">
										<!-- /profile -->
										<div class="indent_title_in">
											<i class="pe-7s-notebook"></i>
										</div>
										<div class="wrapper_indent">
											@if($result->summary != null) {!! $result->summary !!} @else  No summary available @endif
										</div>
									</div>
									</div>
								</div>

								<div class="accordion-item">
									<h2 class="accordion-header" id="headingTwo">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
										GENERAL NOTE
									</button>
									</h2>
									<div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#materialDetails">
										<div class="accordion-body">
											<!-- /profile -->
											<div class="indent_title_in">
												<i class="pe-7s-notebook"></i>
											</div>
											<div class="wrapper_indent">
												@if($result->general_note != null) {!! $result->general_note !!} @else  No information available @endif
											</div>
										</div>
									</div>
								</div>
								<div class="accordion-item">
									<h2 class="accordion-header" id="headingThree">
									<button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
										OTHER DETAILS
									</button>
									</h2>
									<div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#materialDetails">
										<div class="accordion-body">
														

											@if($result->pub_url !='' || $result->pub_url != null)
												<div class="indent_title_in">
													<i class="pe-7s-notebook"></i>
													<h3>URL</h3>
												</div>
												<div>
													<?php echo $result->pub_url; ?>
												</div>
												<hr>
											@endif

											<div class="indent_title_in">
												<i class="pe-7s-news-paper"></i>
												<h3>Other Details</h3>
											</div>
											<div class="container">
												<div class="row">
													<div class="col  mb-2">
													<strong>Material Type </strong>
													</div>
													<div class="col text-center">
													@if($result->material_type != null) {{ $result->material_type }} @else  Information not provided @endif
													</div>
												</div>
												<div class="row">
													<div class="col mb-2">
													<strong>Call Number </strong>
													</div>
													<div class="col text-center">
													@if($result->call_number != null) {{ $result->call_number }} @else  Information not provided @endif
													</div>
												</div>
												<div class="row">
													<div class="col mb-2">
													<strong>Authors </strong>
													</div>
													<div class="col text-center">
													@if($result->joint_authors != null) {{ $result->joint_authors }} @else  Information not provided @endif
													</div>
												</div>
												<div class="row">
													<div class="col mb-2">
													<strong>Publisher </strong>
													</div>
													<div class="col text-center">
													@if($result->publisher != null) {{ $result->publisher }} @else  Information not provided @endif
													</div>
												</div>
											</div>
						
										</div>
									</div>
								</div>
							</div>


						</div>
						<!-- /section_1 -->
					</div>
					<!-- /box_general -->

					<div id="section_2">
						<div class="box_general_3">
							<div class="reviews-container">
								<div class="row">
									<div class="col-lg-3">
										<div id="review_summary" style="background-color: #00a496;">
											

											<strong>{{ $ave }}</strong>
											<div class="rating">
												<i data-star="{{ $ave }}"></i>
											</div>
											@if($review_ctr > 0)
											<small>
												Based on {{ $review_ctr }} reviews
											</small>
											@endif
										</div>
									</div>
									<div class="col-lg-9">
										<div class="row">
											<div class="col-lg-10 col-9">
												<div class="progress">
													<div class="progress-bar" role="progressbar" style="width: {{ starRatingBar($review_ctr,5,$result->catalog_id) }}%" aria-valuenow="90" aria-valuemin="0" aria-valuemax="100"></div>
												</div>
											</div>
											<div class="col-lg-2 col-3"><small><strong>5 stars</strong></small></div>
										</div>
										<!-- /row -->
										<div class="row">
											<div class="col-lg-10 col-9">
												<div class="progress">
													<div class="progress-bar" role="progressbar" style="width: {{ starRatingBar($review_ctr,4,$result->catalog_id) }}%" aria-valuenow="95" aria-valuemin="0" aria-valuemax="100"></div>
												</div>
											</div>
											<div class="col-lg-2 col-3"><small><strong>4 stars</strong></small></div>
										</div>
										<!-- /row -->
										<div class="row">
											<div class="col-lg-10 col-9">
												<div class="progress">
													<div class="progress-bar" role="progressbar" style="width: {{ starRatingBar($review_ctr,3,$result->catalog_id) }}%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
												</div>
											</div>
											<div class="col-lg-2 col-3"><small><strong>3 stars</strong></small></div>
										</div>
										<!-- /row -->
										<div class="row">
											<div class="col-lg-10 col-9">
												<div class="progress">
													<div class="progress-bar" role="progressbar" style="width: {{ starRatingBar($review_ctr,2,$result->catalog_id) }}%" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
												</div>
											</div>
											<div class="col-lg-2 col-3"><small><strong>2 stars</strong></small></div>
										</div>
										<!-- /row -->
										<div class="row">
											<div class="col-lg-10 col-9">
												<div class="progress">
													<div class="progress-bar" role="progressbar" style="width: {{ starRatingBar($review_ctr,1,$result->catalog_id) }}" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
												</div>
											</div>
											<div class="col-lg-2 col-3"><small><strong>1 stars</strong></small></div>
										</div>
										<!-- /row -->
									</div>
								</div>
								<!-- /row -->

								<hr>

								@foreach($review AS $reviews)
									<div class="row">
										<div class="rev-content">
											<i data-star="{{ $reviews->rating }}"></i>
											<div class="rev-info">
												{!! $reviews->name.'&nbsp&nbsp&nbsp'.date('M d, Y',strtotime($reviews->created_at)) !!}
											</div>
											<div class="rev-text">
												<p>
													{{ $reviews->remarks }}
												</p>
											</div>
										</div>
									</div>
									<br/>
								<!-- End review-box -->
								@endforeach
							</div>
							<!-- End review-container -->
							<hr>
							<div class="text-end"><a href="#" class="btn_1" onclick="getElementById('frm_review').submit()">Submit review</a></div>
						</div>
					</div>
					<!-- /section_2 -->
				</div>
				<!-- /col -->
				<aside class="col-xl-4 col-lg-4 order-1" id="sidebar">
					<figure class="fd-cover" style="margin-bottom: 20px; text-align: center;">
						{!! getFrontpageResult($result->catalog_id) !!}
					</figure>
					@if(checkStocks($result->catalog_id))
					<div class="box_general_3 booking">
							<div class="title text-center" style="background-color: #00a496;">
								<h3>Schedule a Visit</h3>
								<small>Monday to Friday 08:00am-05:00pm</small>
							</div>
							@auth
								<form method="post" action="{{ url('/request-reservation') }}" id="frm_review">
								@csrf


								
								<div class="row">
									<div class="col-6">
										<div class="form-group">
											<input class="form-control" name="visit_scheddate" type="text" id="booking_date" data-lang="en" data-min-year="2020" data-max-year="2024" data-disabled-days="10/17/2017,11/18/2017" required>
										</div>
									</div>
									<div class="col-6">
										<div class="form-group">
											<input class="form-control" type="text" name="visit_schedtime" id="booking_time" value="9:00 am">
										</div>
									</div>
								</div>
								<input type="hidden" class="css-checkbox" id="visit2" name="catalog_id" value="{{ $result->catalog_id }}">
								<input type="hidden" name="title_statement" value="{{ $result->title_statement }}">
								{{-- <div class="row">
									<div class="col-12">
										<div class="form-group">
											Physical copy of this material is available at
											<br>
											<br>	
											<select class="form-control" name="visit_location" required>
												<option value="0" disabled selected>---Select Location---</option>
												<option value="PCAARRD - Los Banos">PCAARRD - Los Banos</option>
												<option value="STII - Bicutan">STII - Bicutan</option>
											</select>
										</div>
									</div>
								</div> --}}
								<!-- /row -->
									<hr>
									<button type="submit" class="btn_1 full-width">Borrow a physical copy</button>
								</form>
							@else
								
								<div class="row">
									<div class="col-12 text-center">
										<h6 >Please <a href="{{ url('login') }}" target="_BLANK"> LOGIN</a> first.</h6>
									<p>Do not have an account yet? <a href="{{ url('register') }}" target="_BLANK"><strong>Register now!</strong></a></p>
									</div>
								</div>
							@endauth

					</div>
					@else
					<div class="box_general_3 booking">
						<div class="title text-center" style="background-color: #00a496;">
							<h3>NOT AVAILABLE FOR BORROWING</h3>
						</div>
						<div class="row">
							<div class="col-12 text-center">
								<h6 >No physical copy/ies available.</h6>
							</div>
						</div>
					</div>
					@endif
					<!-- /box_general -->
				</aside>
				<!-- /asdide -->
			</div>
			<!-- /row -->
		</div>
		<!-- /container -->


		<div class="modal fade" id="inquireModal" tabindex="-1" role="dialog">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="title" id="smallModalLabel">Inquiry on this material</h4>
					</div>
					<form method="post" action="{{ url('/inquiry/submit') }}" id="frm_inquiry">
					@csrf
					<input type='hidden' name='catalog_id_inquiry' id='catalog_id_inquiry'>
					<div class="modal-body">
						@if(!Auth::check())
						<div class="row">
										<div class="col-12">
											<div class="form-group">
												<span>Fullname : </span>
												<input class="form-control" name="inquiry_fullname" type="text" data-lang="en" placeholder="" required>
											</div>
										</div>
										<div class="col-12">
											<div class="form-group">
												<span>Contact number : </span>
												<input class="form-control" name="inquiry_contactnum" type="text" placeholder="">
											</div>
										</div>
										<div class="col-12">
											<div class="form-group">
											<span>Email : </span>
												<input class="form-control" name="inquiry_email" type="email" placeholder="">
											</div>
										</div>
									</div>
						@endauth
						<span>Message : </span>
						<textarea class='form-control' name="inquiry_message"></textarea>
					</div>
					<div class="modal-footer">
						<button type="submit" class="btn btn-success">Submit</button>
						<button type="button" class="btn btn-danger" data-bs-dismiss="modal">CLOSE</button>
					</div>
					</form>
				</div>
			</div>
		</div>
@endsection

@section('js')
<script type="text/javascript">
function inquire(holdingsid)
{
	$("#catalog_id_inquiry").val(holdingsid);
	$("#inquireModal").modal('toggle')
}
</script>
@endsection