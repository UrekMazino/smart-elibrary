@extends('opac.layout.master')

{{-- Per-record SEO. Every record page used to share one site-wide title/description, which left
     Google nothing to tell ~7,600 records apart. See OPAC_SEO_PLAN.md workstream A. --}}
{{-- No e() here: Blade's @yield already escapes section values, so e() would double-escape
     ("&" -> "&amp;amp;") in titles and descriptions. --}}
@section('meta_title', seoPageTitle($result->title_statement))
@section('meta_description', seoRecordDescription($result))
@section('canonical', recordUrl($result))
@section('og_type', 'article')
@section('og_image', getFrontpageLink($result->catalog_id))

{{-- JSON-LD must be emitted RAW (it is already JSON_HEX_TAG-encoded), so it goes through a
     stack rather than @yield, which would HTML-escape the quotes and break the JSON. --}}
@push('json_ld')
<script type="application/ld+json">{!! seoRecordJsonLd($result) !!}</script>
@endpush

{{-- Breadcrumb, as on the search results page: in the site header on desktop, a slim strip under
     it on smaller screens. "Search Results" goes back to the patron's own search when they came
     from one (filters and scroll position kept), otherwise to the catalog search. --}}
@php
    $referer = (string) request()->headers->get('referer', '');
    $crumbs  = [
        ['Home', url('/home')],
        ['Search Results', str_starts_with($referer, url('/results')) ? $referer : url('/results'), 'back' => true],
        ['Description', null],
    ];
@endphp
@section('header_breadcrumb')
    @include('opac.partials.breadcrumb', ['crumbs' => $crumbs])
@endsection

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
		@include('opac.partials.breadcrumb', ['crumbs' => $crumbs, 'variant' => 'strip'])
		@include('opac.partials.search-band')

		<style>
			.fd-cover { margin: 0; }
			.fd-cover img { box-shadow: 0 2px 10px rgba(0,0,0,.25); border-radius: 3px; max-width: 320px; width: 100%; }
			/* General info reorganized layout */
			#section_1 .box_general_3 { padding-top: 22px; }
            #section_1 { padding-bottom: 0px; }
			.fd-info { padding-top: 0 !important; }
			.fd-title { margin: 0 0 6px; font-size: 20px; line-height: 1.3; font-weight: 700; color: #222; }
			.fd-authors { color: #555; font-size: 14px; margin-bottom: 10px; }
			.fd-rating { margin-bottom: 12px; }
			.fd-stats { margin-bottom: 14px !important; }
			.fd-actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: stretch; margin-bottom: 4px; }
			.fd-actions form { margin: 0; display: flex; }
			.fd-section { margin-top: 24px; }
			.fd-section-title { font-size: 14px; font-weight: 700; color: #00857a; text-transform: uppercase; letter-spacing: .3px; margin-bottom: 12px; border-bottom: 2px solid #e6e9ec; padding-bottom: 6px; }
			.fd-section-title i { margin-right: 6px; vertical-align: middle; }
			.fd-table { width: 100%; border-collapse: collapse; font-size: 14px; }
			.fd-table th, .fd-table td { text-align: left; vertical-align: top; padding: 8px 12px; border-bottom: 1px solid #eef0f2; }
			.fd-table th { width: 180px; color: #333; font-weight: 600; background: #f7f9fa; white-space: nowrap; }
			.fd-table td { color: #444; }
			.fd-table td.fd-justify { text-align: justify; }
			/* Catalogers format values as plain text (line breaks, "• " bullets, indentation): show them as written. */
			.fd-table td.fd-val { white-space: pre-wrap; overflow-wrap: anywhere; }
			.fd-citation { background: #f7f9fa; border: 1px solid #e6e9ec; border-radius: 4px; padding: 14px 16px; }
			.fd-citation-style { display: inline-block; font-size: 11px; font-weight: 700; color: #fff; background: #00857a; border-radius: 3px; padding: 2px 8px; margin-bottom: 8px; }
			.fd-citation p { margin: 0; font-size: 14px; line-height: 1.55; color: #333; }
			.fd-copy-btn { margin-top: 12px !important; font-size: 12px; padding: 5px 14px; background-color: #029978; }
			.fd-copies { display: flex; gap: 12px; flex-wrap: wrap; }
			.fd-copy-badge { font-size: 14px; font-weight: 600; padding: 9px 18px; border-radius: 4px; background: #eef0f2; color: #444; }
			.fd-copy-badge.ok { background: #e3f5ef; color: #087f5b; }
			.fd-copy-badge.none { background: #fdecea; color: #c0392b; }
			/* Flat, icon-on-top action buttons (icon stacked above the label) */
			.fd-btn { display: inline-flex; flex-direction: column; align-items: center; justify-content: center; gap: 6px; min-width: 78px; padding: 10px 12px; background: #fff !important; color: #3f4079 !important; border: 1px solid #e3e8ec !important; border-radius: 8px !important; font-size: 12px; font-weight: 600; line-height: 1.1; text-align: center; box-shadow: none; transition: background .12s, border-color .12s, color .12s; }
			.fd-btn i { font-size: 22px; line-height: 1; color: #029978; }
			.fd-btn:hover { background: #f1faf7 !important; border-color: #029978 !important; color: #029978 !important; }
			.fd-btn:hover i { color: #029978; }
			.fd-btn[disabled], .fd-btn:disabled { background: #f6f8f9 !important; color: #b4babf !important; border-color: #e9edf0 !important; cursor: not-allowed; opacity: 1 !important; }
			.fd-btn[disabled] i, .fd-btn:disabled i { color: #c4cacf; }
			/* Save: outline star; filled amber once the record is in the patron's list. */
			/* Guest Save is a link: the theme's `a.btn_1 { display:inline-block }` outranks .fd-btn,
			   which put the star beside the label. Restore the stacked icon-over-label tile. */
			.fd-actions a.fd-btn { display: inline-flex; flex-direction: column; align-items: center; justify-content: center; text-decoration: none;
				font-size: 12px; font-weight: 600; line-height: 1.1; }
			.fd-save-ico { width: 22px; height: 22px; fill: none; stroke: #029978; stroke-width: 1.25; stroke-linejoin: round; }
			.fd-btn.is-saved { background: #fff8e6 !important; border-color: #e0a100 !important; color: #8a6200 !important; }
			.fd-btn.is-saved .fd-save-ico { fill: #f5b301; stroke: #e0a100; }
			.fd-btn.is-saved:hover { background: #fff2cc !important; }
			.fd-btn.fd-save[aria-busy="true"] { opacity: .6; pointer-events: none; }
			/* Read overlay (PDF.js) */
			.ol-reader { position: fixed; inset: 0; z-index: 20000; background: rgba(18,20,24,.94); display: none; flex-direction: column; }
			.ol-reader-bar { flex: 0 0 46px; display: flex; align-items: center; justify-content: space-between; padding: 0 14px; background: #1b1d21; color: #fff; }
			.ol-reader-title { font-size: 14px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; padding-right: 12px; }
			.ol-reader-close { background: transparent; border: 0; color: #fff; font-size: 26px; line-height: 1; cursor: pointer; padding: 0 6px; }
			.ol-reader-close:hover { color: #ff6b6b; }
			.ol-reader-frame { flex: 1 1 auto; width: 100%; border: 0; background: #3a3a3a; }
			/* Left column: match every section to the coverphoto width + tighten spacing */
			#sidebar .fd-cover { text-align: left; }
			#sidebar .fd-cover img, #sidebar .box_general_3 { max-width: 320px; }
			#sidebar .box_general_3 { padding: 18px; margin-bottom: 10px; }
			#sidebar .booking .title { margin: -18px -18px 16px -18px; padding: 13px 16px; }
			#sidebar .booking .title h3 { font-size: 18px; line-height: 1.25; }
			#sidebar .booking .title small { font-size: 12px; }
			#sidebar .box_general_3 hr { margin: 16px -18px; }
			.fd-review-box #review_summary { display: flex; align-items: center; justify-content: center; gap: 14px; margin: -18px -18px 14px -18px; padding: 15px 18px; color: #fff; border-radius: 5px 5px 0 0; }
			.fd-review-box .fd-avg-num { font-size: 2.3rem; line-height: 1; font-weight: 700; flex: 0 0 auto; }
			.fd-review-box .fd-avg-meta { flex: 0 0 auto; text-align: left; }
			.fd-review-box .fd-avg-meta .rating { line-height: 1; }
			.fd-review-box .fd-avg-meta small { display: block; margin-top: 5px; font-size: 12px; opacity: .92; }
			.fd-rating-bars { margin-top: 12px; }
			.fd-rating-bars .row { margin: 0 0 7px; align-items: center; }
			.fd-rating-bars .progress { height: 9px; }
			.fd-rating-bars small { font-size: 11px; white-space: nowrap; }

			/* Desktop: the header and details cards read as one card (as before the split). */
			@media (min-width: 992px) {
				.fd-head { margin-bottom: 0; border-bottom: 0; border-radius: 5px 5px 0 0; padding-bottom: 6px; }
				.fd-details { border-top: 0; border-radius: 0 0 5px 5px; padding-top: 0; }
			}

			/* ---- Phones & tablets: one column, most important first -------------------------------
			   title + actions -> cover & availability -> full record / citation / copies -> ratings ->
			   reviews. The two Bootstrap columns are flattened (display: contents) so their parts can be
			   ordered individually; desktop keeps the two-column layout. */
			@media (max-width: 991.98px) {
				.fd-layout { flex-direction: column; flex-wrap: nowrap; margin-left: 0; margin-right: 0; }
				.fd-layout > .fd-main, .fd-layout > #sidebar, #sidebar > .theiaStickySidebar, .fd-main > #section_1 { display: contents; }
				#secondary_nav { display: none !important; }   /* in-page tabs: sections simply follow each other */
				.fd-head      { order: 1; }
				.fd-side-top  { order: 2; }
				.fd-details   { order: 3; }
				.fd-side-rate { order: 4; }
				#section_2    { order: 5; }
				.fd-head, .fd-details, #section_2 .box_general_3, #sidebar .box_general_3 { padding: 18px 16px; margin-bottom: 12px; max-width: none; }
				.fd-details .fd-section:first-child { margin-top: 0; }
				#sidebar .fd-cover { text-align: center !important; margin: 0 0 12px !important; }
				#sidebar .fd-cover img { max-width: 210px; }
				#sidebar .booking .title { margin: -18px -16px 14px -16px; }
				#sidebar .booking .title h3 { font-size: 15px; }
				.fd-review-box #review_summary { margin: -18px -16px 14px -16px; }
				.fd-title { font-size: 18px; }
			}
			@media (max-width: 575.98px) {
				/* Action buttons: five equal tiles in one row (Read, Download, Inquire, Share, Save). */
				.fd-actions { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 6px; }
				.fd-actions form, .fd-actions form .fd-btn { width: 100%; }
				.fd-btn, .fd-actions a.fd-btn { min-width: 0; padding: 10px 4px; font-size: 11.5px; }
				/* Full Record: label above value (a two-column table leaves values one word wide). */
				.fd-table, .fd-table tbody, .fd-table tr, .fd-table th, .fd-table td { display: block; width: auto; }
				.fd-table tr { border-bottom: 1px solid #eef0f2; padding: 9px 0; }
				.fd-table th { background: none; border: 0; padding: 0 0 2px; white-space: normal; font-size: 11.5px; text-transform: uppercase; letter-spacing: .3px; color: #7a8590; }
				.fd-table td { border: 0; padding: 0; font-size: 14px; }
				.fd-copies .fd-copy-badge { flex: 1 1 0; text-align: center; padding: 9px 10px; }
			}
		</style>
		<div class="container margin_60">
			@if(session('success'))
				<div class="alert alert-success alert-dismissible fade show" role="alert">
					{{ session('success') }}
					<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
				</div>
			@endif
			<div class="row fd-layout">
				<div class="col-xl-9 col-lg-8 order-2 fd-main">
					<nav id="secondary_nav" style="background-color: #00857a;">
						<div class="container">
							<ul class="clearfix">
								<li><a href="#section_1" class="active">General info</a></li>
								<li><a href="#section_2">Reviews</a></li>
							</ul>
						</div>
					</nav>
					<div id="section_1">
						{{-- Header card: title, authors, rating, stats, actions. Its own card so phones can show it first. --}}
						<div class="box_general_3 fd-head">
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
							@php $pdfUrl = getReadUrl($result->catalog_id); @endphp
							<div class="fd-actions">
								@if($pdfUrl)
									<button type="button" class="btn_1 fd-btn" onclick="openReader('{{ $pdfUrl }}', @js($result->title_statement))"><i class="icon-book"></i> Read</button>
									<form method="post" action="{{ url('/download') }}" id="frm_download" @auth target="_blank" @endauth>
										@csrf
										<input type="hidden" name="holdings_download_id" value="{{ $result->catalog_id }}">
										<button class="btn_1 fd-btn"><i class="icon-download"></i> Download</button>
									</form>
								@else
									<button type="button" class="btn_1 fd-btn" disabled title="No file available to read"><i class="icon-book"></i> Read</button>
									<button type="button" class="btn_1 fd-btn" disabled title="No file available to download"><i class="icon-download"></i> Download</button>
								@endif
								<button type="button" class="btn_1 fd-btn" onclick="inquire('{{ $result->catalog_id }}')"><i class="icon-info-3"></i> Inquire</button>
								<button type="button" class="btn_1 fd-btn" data-share-url="{{ recordUrl($result) }}" data-share-title="{{ $result->title_statement }}" onclick="shareRecord(this)" title="Copy link to this record"><i class="pe-7s-link"></i> Share</button>
								{{-- Save to the patron's list (same toggle as the search results / homepage stars). --}}
								@auth
									@php $fdSaved = isSavedRecord($result->catalog_id); @endphp
									<button type="button" class="btn_1 fd-btn fd-save{{ $fdSaved ? ' is-saved' : '' }}" data-fd-save="{{ $result->catalog_id }}"
										aria-pressed="{{ $fdSaved ? 'true' : 'false' }}" title="{{ $fdSaved ? 'Saved — click to remove from your list' : 'Save to your list' }}" onclick="toggleFdSave(this)">
										<svg class="fd-save-ico" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 1.8l1.9 3.9 4.3.6-3.1 3 .7 4.3L8 11.6l-3.8 2 .7-4.3-3.1-3 4.3-.6z"/></svg>
										<span class="fd-save-label">{{ $fdSaved ? 'Saved' : 'Save' }}</span>
									</button>
								@else
									<a class="btn_1 fd-btn fd-save" href="{{ route('login') }}" title="Log in to save records">
										<svg class="fd-save-ico" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 1.8l1.9 3.9 4.3.6-3.1 3 .7 4.3L8 11.6l-3.8 2 .7-4.3-3.1-3 4.3-.6z"/></svg>
										<span class="fd-save-label">Save</span>
									</a>
								@endauth
							</div>
						</div>
						</div>

						{{-- Details card: full record, citation, physical copies (joined to the header card on desktop). --}}
						<div class="box_general_3 fd-details">

						@php $fullRecord = getFullRecordFields($result->catalog_id); @endphp
						<div class="fd-section">
							<h6 class="fd-section-title"><i class="pe-7s-news-paper"></i> Full Record</h6>
							@if(count($fullRecord) || $result->material_type)
							<table class="fd-table">
								@if($result->material_type)
									<tr><th>Material Type</th><td>{{ $result->material_type }}</td></tr>
								@endif
								@foreach($fullRecord as $row)
									<tr><th>{{ $row['label'] }}</th><td class="fd-val {{ !empty($row['justify']) || in_array($row['label'], ['Summary','Contents','Note','Bibliography Note']) ? 'fd-justify' : '' }}">{{ $row['value'] }}</td></tr>
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
							<h6 class="fd-section-title" style="margin-top:0;"><i class="pe-7s-chat"></i> Reviews</h6>
							<div class="reviews-container">
								@forelse($review AS $reviews)
									<div class="row">
										<div class="rev-content">
											<i data-star="{{ $reviews->rating }}"></i>
											<div class="rev-info">
												{!! $reviews->name.'&nbsp&nbsp&nbsp'.date('M d, Y',strtotime($reviews->created_at)) !!}
											</div>
											<div class="rev-text">
												<p>{{ $reviews->remarks }}</p>
											</div>
										</div>
									</div>
									<br/>
								@empty
									<p class="text-muted mb-0">No reviews yet. Be the first to review this material.</p>
								@endforelse
							</div>
						</div>
					</div>
					<!-- /section_2 -->
				</div>
				<!-- /col -->
				<aside class="col-xl-3 col-lg-4 order-1" id="sidebar">
					<div class="fd-side-top">
					<figure class="fd-cover" style="margin-bottom: 10px; text-align: left;">
						{!! getFrontpageResult($result->catalog_id) !!}
					</figure>
					@if(checkStocks($result->catalog_id))
					<div class="box_general_3 booking">
							<div class="title text-center" style="background-color: #00857a;">
								<h3>Schedule Reservation</h3>
								<small>Monday to Friday 08:00am-05:00pm</small>
							</div>
							@auth
								<form method="post" action="{{ url('/request-reservation') }}" id="frm_reservation">
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
                                    <div class="row text-center">
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

                                            <button type="button" class="btn_1 full-width" onclick="confirmReservation()">Send Request</button>
                                    </div>
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
						<div class="title text-center" style="background-color: #00857a;">
							<h3>NOT AVAILABLE FOR BORROWING</h3>
						</div>
						<div class="row">
							<div class="col-12 text-center">
								<h6 >No copies available.</h6>
							</div>
						</div>
					</div>
					@endif
					</div>
					<!-- /box_general -->
					{{-- Ratings summary + submit review (moved from the reviews section) --}}
					<div class="fd-side-rate">
					<div class="box_general_3 booking fd-review-box">
						<div id="review_summary" style="background-color: #00857a;">
							<div class="fd-avg-num">{{ $ave }}</div>
							<div class="fd-avg-meta">
								<div class="rating"><i data-star="{{ $ave }}" style="font-size: 18px;"></i></div>
								@if($review_ctr > 0)
									<small>Based on {{ $review_ctr }} {{ $review_ctr == 1 ? 'review' : 'reviews' }}</small>
								@endif
							</div>
						</div>
						<div class="fd-rating-bars">
							@foreach([5,4,3,2,1] as $starRow)
							<div class="row">
								<div class="col-9">
									<div class="progress">
										<div class="progress-bar" role="progressbar" style="width: {{ starRatingBar($review_ctr,$starRow,$result->catalog_id) }}%"></div>
									</div>
								</div>
								<div class="col-3"><small><strong>{{ $starRow }} stars</strong></small></div>
							</div>
							@endforeach
						</div>
						<a href="#" class="btn_1 full-width text-center" onclick="getElementById('frm_review').submit()">Submit review</a>
					</div>
					</div>
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
						{{-- Inquiry form is for registered users only; guests are shown a reminder to email the library. --}}
						<span>Subject : </span>
						<input type="text" class="form-control mb-3" name="inquiry_subject" maxlength="150" placeholder="Subject of your inquiry..." required>
						<span>Message : </span>
						<textarea class="form-control" name="inquiry_message" rows="4" placeholder="Type your inquiry about this material..." required></textarea>
					</div>
					<div class="modal-footer">
						<button type="submit" class="btn btn-success">Submit</button>
						<button type="button" class="btn btn-danger" data-bs-dismiss="modal">CLOSE</button>
					</div>
					</form>
				</div>
			</div>
		</div>

	{{-- Confirm reservation before sending the request --}}
	<div class="modal fade" id="reserveConfirmModal" tabindex="-1" role="dialog">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="title">Confirm reservation</h4>
				</div>
				<div class="modal-body">
					<p class="mb-2">Please confirm your visit reservation for:</p>
					<p class="mb-3"><strong>{{ $result->title_statement }}</strong></p>
					<ul class="list-unstyled mb-2">
						<li><strong>Date:</strong> <span id="reserveConfirmDate">&mdash;</span></li>
						<li><strong>Time:</strong> <span id="reserveConfirmTime">&mdash;</span></li>
					</ul>
					<p class="text-muted mb-0" style="font-size:13px;">Your request will be sent to the library for approval.</p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-success" onclick="submitReservation(this)">Confirm &amp; send</button>
					<button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
				</div>
			</div>
		</div>
	</div>

	{{-- Read overlay: in-browser PDF.js reader --}}
	<div id="ol-reader" class="ol-reader">
		<div class="ol-reader-bar">
			<span class="ol-reader-title" id="ol-reader-title"></span>
			<button type="button" class="ol-reader-close" onclick="closeReader()" aria-label="Close reader">&times;</button>
		</div>
		<iframe id="ol-reader-frame" class="ol-reader-frame" src="about:blank" title="Online reader"></iframe>
	</div>
@endsection

@section('js')
<script type="text/javascript">
var inquiryIsGuest = {{ Auth::check() ? 'false' : 'true' }};
var inquiryIsBlocked = {{ Auth::check() && Auth::user()->is_blocked ? 'true' : 'false' }};
var libraryEmail = 'info@fprdi.dost.gov.ph';

function inquire(holdingsid)
{
	// Blocked accounts cannot use chat / inquiry.
	if (inquiryIsBlocked) {
		if (window.Swal) {
			Swal.fire({
				icon: 'warning',
				title: 'Account blocked',
				html: 'Your account is <strong>blocked</strong> from sending inquiries.<br>Please contact the library at <a href="mailto:' + libraryEmail + '"><strong>' + libraryEmail + '</strong></a>.',
				confirmButtonColor: '#00857a'
			});
		} else {
			alert('Your account is blocked from sending inquiries. Please contact the library at ' + libraryEmail + '.');
		}
		return;
	}

	// The inquiry form is for registered users only. Remind guests to email the library instead.
	if (inquiryIsGuest) {
		if (window.Swal) {
			Swal.fire({
				icon: 'info',
				title: '<a href="{{ url('login') }}"><strong>Login</strong></a> required',
				html: 'Submitting an inquiry here is available to <strong>registered users</strong> only.<br><br>' +
				      'Please <a href="{{ url('register') }}"><strong>register</strong></a> or send your inquiry to the library\'s official email:<br>' +
				      '<a href="mailto:' + libraryEmail + '"><strong>' + libraryEmail + '</strong></a>',
				showCancelButton: true,
				confirmButtonText: 'Email the library',
				cancelButtonText: 'Close',
				confirmButtonColor: '#00857a'
			}).then(function (res) {
				if (res.isConfirmed) { window.location.href = 'mailto:' + libraryEmail + '?subject=' + encodeURIComponent('Library Inquiry'); }
			});
		} else {
			alert('Inquiries here are for registered users. Please send your inquiry to the library at ' + libraryEmail + '.');
		}
		return;
	}

	// Registered user: open the inquiry form
	$("#catalog_id_inquiry").val(holdingsid);
	$("#inquireModal").modal('show');
}

function confirmReservation()
{
	var form = document.getElementById('frm_reservation');
	if (!form) return;

	// Run the form's native validation (the date is required) before confirming.
	if (typeof form.reportValidity === 'function' && !form.reportValidity()) return;

	var dateEl = form.querySelector('[name="visit_scheddate"]');
	var timeEl = form.querySelector('[name="visit_schedtime"]');
	document.getElementById('reserveConfirmDate').textContent = (dateEl && dateEl.value) ? dateEl.value : '—';
	document.getElementById('reserveConfirmTime').textContent = (timeEl && timeEl.value) ? timeEl.value : '—';

	$("#reserveConfirmModal").modal('show');
}

function submitReservation(btn)
{
	if (btn) { btn.disabled = true; }   // prevent double-submit
	document.getElementById('frm_reservation').submit();
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

// Save / unsave this record in the patron's list (POST /search/saved toggles it).
function toggleFdSave(btn)
{
	if (btn.getAttribute('aria-busy') === 'true') return;
	btn.setAttribute('aria-busy', 'true');
	var token = (document.querySelector('meta[name="csrf_token"]') || {}).content || '';
	fetch(@json(url('/search/saved')), {
		method: 'POST',
		headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
		body: 'catalog_id=' + encodeURIComponent(btn.getAttribute('data-fd-save'))
	})
	.then(function (r) {
		if (r.status === 401 || r.status === 419) { location.href = @json(route('login')); throw new Error('auth'); }
		if (!r.ok) throw new Error('failed');
		return r.json();
	})
	.then(function (data) {
		btn.classList.toggle('is-saved', !!data.saved);
		btn.setAttribute('aria-pressed', data.saved ? 'true' : 'false');
		btn.title = data.saved ? 'Saved — click to remove from your list' : 'Save to your list';
		btn.querySelector('.fd-save-label').textContent = data.saved ? 'Saved' : 'Save';
	})
	.catch(function (err) {
		if (err.message === 'auth') return;
		var label = btn.querySelector('.fd-save-label');
		label.textContent = 'Try again';
		setTimeout(function () { label.textContent = btn.classList.contains('is-saved') ? 'Saved' : 'Save'; }, 1600);
	})
	.finally(function () { btn.removeAttribute('aria-busy'); });
}

function shareRecord(btn)
{
	var url = btn.getAttribute('data-share-url');
	var title = btn.getAttribute('data-share-title') || document.title;

	// Native share sheet when the browser supports it (secure context only).
	if (navigator.share && window.isSecureContext) {
		navigator.share({ title: title, url: url }).catch(function(){});
		return;
	}

	// Otherwise copy the link. navigator.clipboard needs https, so fall back to execCommand.
	function flash(){
		var original = btn.getAttribute('data-label') || btn.innerHTML;
		btn.setAttribute('data-label', original);
		btn.innerHTML = 'Link copied!';
		setTimeout(function(){ btn.innerHTML = btn.getAttribute('data-label'); }, 1400);
	}
	function legacyCopy(){
		var ta = document.createElement('textarea');
		ta.value = url;
		ta.style.position = 'fixed';
		ta.style.left = '-9999px';
		document.body.appendChild(ta);
		ta.focus();
		ta.select();
		try { document.execCommand('copy'); flash(); } catch(e) {}
		document.body.removeChild(ta);
	}
	if (navigator.clipboard && window.isSecureContext) {
		navigator.clipboard.writeText(url).then(flash).catch(legacyCopy);
	} else {
		legacyCopy();
	}
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
