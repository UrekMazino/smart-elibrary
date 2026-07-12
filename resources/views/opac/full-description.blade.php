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
			/* General info reorganized layout */
			#section_1 .box_general_3 { padding-top: 22px; }
			.fd-info { padding-top: 0 !important; }
			.fd-title { margin: 0 0 6px; font-size: 20px; line-height: 1.3; font-weight: 700; color: #222; }
			.fd-authors { color: #555; font-size: 14px; margin-bottom: 10px; }
			.fd-rating { margin-bottom: 12px; }
			.fd-stats { margin-bottom: 14px !important; }
			.fd-actions { display: flex; gap: 10px; align-items: center; margin-bottom: 4px; }
			.fd-actions form { margin: 0; }
			.fd-actions .btn_1 { margin: 0; }
			.fd-section { margin-top: 24px; }
			.fd-section-title { font-size: 14px; font-weight: 700; color: #00a496; text-transform: uppercase; letter-spacing: .3px; margin-bottom: 12px; border-bottom: 2px solid #e6e9ec; padding-bottom: 6px; }
			.fd-section-title i { margin-right: 6px; vertical-align: middle; }
			.fd-table { width: 100%; border-collapse: collapse; font-size: 14px; }
			.fd-table th, .fd-table td { text-align: left; vertical-align: top; padding: 8px 12px; border-bottom: 1px solid #eef0f2; }
			.fd-table th { width: 180px; color: #333; font-weight: 600; background: #f7f9fa; white-space: nowrap; }
			.fd-table td { color: #444; }
			.fd-table td.fd-justify { text-align: justify; }
			.fd-citation { background: #f7f9fa; border: 1px solid #e6e9ec; border-radius: 4px; padding: 14px 16px; }
			.fd-citation-style { display: inline-block; font-size: 11px; font-weight: 700; color: #fff; background: #00a496; border-radius: 3px; padding: 2px 8px; margin-bottom: 8px; }
			.fd-citation p { margin: 0; font-size: 14px; line-height: 1.55; color: #333; }
			.fd-copy-btn { margin-top: 12px !important; font-size: 12px; padding: 5px 14px; background-color: #029978; }
			.fd-copies { display: flex; gap: 12px; flex-wrap: wrap; }
			.fd-copy-badge { font-size: 14px; font-weight: 600; padding: 9px 18px; border-radius: 4px; background: #eef0f2; color: #444; }
			.fd-copy-badge.ok { background: #e3f5ef; color: #087f5b; }
			.fd-copy-badge.none { background: #fdecea; color: #c0392b; }
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
							<div class="profile fd-info">
							<h5 class="fd-title">{!! $result->title_statement !!}</h5>
							@if($result->joint_authors)
								<div class="fd-authors">{{ str_replace('/', '; ', trim($result->joint_authors, ' /')) }}</div>
							@endif
							<div class="fd-rating"><i data-star="{{ $ave }}" style="font-size:20px"></i></div>
							<ul class="statistic fd-stats">
								<li>{{ countNum('view',$result->catalog_id) }} Views</li>
								<li>{{ countNum('review',$result->catalog_id) }} Reviews</li>
								<li>{{ countNum('download',$result->catalog_id) }} Downloads</li>
							</ul>
							<div class="fd-actions">
								@if(checkIfHasPDF($result->catalog_id))
								<form method="post" action="{{ url('/download') }}" id="frm_download" @auth target="_blank" @endauth>
									@csrf
									<input type="hidden" name="holdings_download_id" value="{{ $result->catalog_id }}">
									<button class="btn_1" style="background-color:#029978"><i class="icon-download"></i> Download</button>
								</form>
								@endif
								<button class="btn_1" style="background-color:#029978" onclick="inquire('{{ $result->catalog_id }}')"><i class="icon-info-3"></i> Inquire</button>
							</div>
						</div>

						@php $fullRecord = getFullRecordFields($result->catalog_id); @endphp
						<div class="fd-section">
							<h6 class="fd-section-title"><i class="pe-7s-news-paper"></i> Full Record</h6>
							@if(count($fullRecord) || $result->material_type)
							<table class="fd-table">
								@if($result->material_type)
									<tr><th>Material Type</th><td>{{ $result->material_type }}</td></tr>
								@endif
								@foreach($fullRecord as $row)
									<tr><th>{{ $row['label'] }}</th><td class="{{ in_array($row['label'], ['Summary','Contents','Note','Bibliography Note']) ? 'fd-justify' : '' }}">{!! nl2br(e($row['value'])) !!}</td></tr>
								@endforeach
							</table>
							@else
								<p class="text-muted mb-0">No additional details available.</p>
							@endif
						</div>

						@php $citations = buildCitations($result); @endphp
						@if(!empty($citations))
						<div class="fd-section">
							<h6 class="fd-section-title"><i class="pe-7s-note2"></i> Citation</h6>
							<div class="fd-citation">
								<span class="fd-citation-style">APA</span>
								<p id="fd-cite-apa">{!! $citations['APA'] ?? reset($citations) !!}</p>
								<button type="button" class="btn_1 fd-copy-btn" onclick="copyFdCitation(this)">Copy citation</button>
							</div>
						</div>
						@endif

						@php
							$copiesTotal = countCopies($result->catalog_id);
							$copiesAvail = countAvailableCopies($result->catalog_id);
						@endphp
						<div class="fd-section">
							<h6 class="fd-section-title"><i class="pe-7s-albums"></i> Physical Copies</h6>
							<div class="fd-copies">
								<span class="fd-copy-badge">Total copies: {{ $copiesTotal }}</span>
								<span class="fd-copy-badge {{ $copiesAvail > 0 ? 'ok' : 'none' }}">Available: {{ $copiesAvail }}</span>
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
								<h3>Schedule Reservation</h3>
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
									<button type="submit" class="btn_1 full-width">Send Request</button>
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

function copyFdCitation(btn)
{
	var el = document.getElementById('fd-cite-apa');
	if(!el) return;
	var text = (el.innerText || el.textContent || '').trim();

	function flash(){
		var original = btn.getAttribute('data-label') || btn.innerHTML;
		btn.setAttribute('data-label', original);
		btn.innerHTML = 'Copied!';
		setTimeout(function(){ btn.innerHTML = btn.getAttribute('data-label'); }, 1200);
	}

	function legacyCopy(){
		var ta = document.createElement('textarea');
		ta.value = text;
		ta.style.position = 'fixed';
		ta.style.left = '-9999px';
		document.body.appendChild(ta);
		ta.focus();
		ta.select();
		try { document.execCommand('copy'); flash(); } catch(e) {}
		document.body.removeChild(ta);
	}

	// navigator.clipboard only exists on secure origins (https/localhost); the OPAC runs
	// over plain http, so fall back to the legacy execCommand copy there.
	if (navigator.clipboard && window.isSecureContext) {
		navigator.clipboard.writeText(text).then(flash).catch(legacyCopy);
	} else {
		legacyCopy();
	}
}
</script>
@endsection
