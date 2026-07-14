@extends('opac.layout.master')
@section('content')

	<style>
		/* To review — compact, minimalist cards (uniform with Transactions) */
		.tx-page { min-height: calc(100vh - 320px); }
		.tx-head { margin-bottom: 22px; }
		.tx-head h1 { font-size: 30px; font-weight: 700; color: #222; margin: 0 0 4px; }
		.tx-head p { color: #8a94a6; font-size: 14px; margin: 0; }
		.tx-card { display: flex; align-items: stretch; gap: 16px; background: #fff; border: 1px solid #eef0f2; border-radius: 10px; padding: 14px 16px; margin-bottom: 12px; box-shadow: 0 1px 2px rgba(0,0,0,.04); transition: box-shadow .15s, border-color .15s; }
		.tx-card:hover { box-shadow: 0 5px 16px rgba(0,0,0,.08); border-color: #d7ece9; }
		.tx-cover { flex: 0 0 auto; }
		.tx-cover img { width: 70px; height: 98px; object-fit: cover; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,.18); display: block; background: #f0f2f5; }
		.tx-body { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; justify-content: center; }
		.tx-title { margin: 0 0 4px; font-size: 15px; font-weight: 600; line-height: 1.35; color: #222; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
		.tx-title a { color: inherit; text-decoration: none; }
		.tx-title a:hover { color: #00a496; }
		.tx-authors { font-size: 12.5px; color: #6b7280; margin-bottom: 8px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
		.tx-meta { display: flex; align-items: center; flex-wrap: wrap; gap: 6px 14px; font-size: 12px; color: #8a94a6; }
		.tx-meta .rating { line-height: 1; }
		.tx-aside { flex: 0 0 auto; display: flex; flex-direction: column; align-items: flex-end; justify-content: center; gap: 10px; }
		.tx-review-btn { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 600; color: #fff; background: #00a496; border: 0; border-radius: 7px; padding: 8px 16px; cursor: pointer; transition: background .15s; white-space: nowrap; }
		.tx-review-btn:hover { background: #00897b; }
		.tx-empty { text-align: center; color: #8a94a6; padding: 48px 16px; background: #fff; border: 1px dashed #dfe3e8; border-radius: 10px; }
		.tx-pagination { margin-top: 18px; }
		@media (max-width: 576px) {
			.tx-card { flex-wrap: wrap; }
			.tx-aside { width: 100%; flex-direction: row; align-items: center; justify-content: flex-end; }
		}
	</style>

	<div class="container margin_60 tx-page">
		<div class="row">
			<div class="col-9">
				<div class="tx-head">
					<h1>To Review</h1>
					<p>Share your feedback on materials you've downloaded.</p>
				</div>

				@if($flag)
					@foreach($list AS $lists)
						@php
							$holdings = getPublicationDetail($lists->catalog_id);
							$cover    = coverBase($lists->catalog_id) ?: 'frontpages/no-preview.jpg';
							$year     = trim(strip_tags((string) ($holdings['pub_year'] ?? '')));
							$authors  = trim(strip_tags((string) ($holdings['joint_authors'] ?? '')));
						@endphp
						<div class="tx-card">
							<div class="tx-cover">
								<img src="{{ url('/' . $cover) }}" alt="cover">
							</div>
							<div class="tx-body">
								<h3 class="tx-title"><a href="{{ url('/full-description/' . $lists->catalog_id) }}">{!! $holdings['title_statement'] !!}</a></h3>
								@if($authors !== '')
									<div class="tx-authors">{!! $holdings['joint_authors'] !!}</div>
								@endif
								<div class="tx-meta">
									<span class="rating"><i data-star="{{ starRatingAve($lists->catalog_id) }}"></i></span>
									@if($year !== '')<span>Published {{ $year }}</span>@endif
								</div>
							</div>
							<div class="tx-aside">
								<button type="button" class="tx-review-btn" onclick="toReview('{{ $lists->catalog_id }}')">
									<i class="pe-7s-star"></i> Write a review
								</button>
							</div>
						</div>
					@endforeach

					<div class="tx-pagination">{{ $list->links('vendor.pagination.default') }}</div>
				@else
					<div class="tx-empty">You have no materials to review.</div>
				@endif
			</div>
			@include('opac.side')
		</div>
	</div>

	<form method="post" action="{{ url('/review') }}" id="frm_review">
		@csrf
		<input type="hidden" class="form-control" name="catalog_id" id="catalog_id" value="">
	</form>
@endsection

@section('js')
<script type="text/javascript">
function toReview(catalog_id) {
	$("#catalog_id").val(catalog_id);
	$("#frm_review").submit();
}
</script>

@endsection
