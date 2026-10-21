@extends('opac.layout.master')

@section('robots', 'noindex, nofollow')

@section('css')
<style>
	/* Inquiries — compact, minimalist cards (uniform with Transactions / To Review) */
	.tx-page { min-height: calc(100vh - 320px); }
	.tx-head { margin-bottom: 22px; }
	.tx-head h1 { font-size: 30px; font-weight: 700; color: #222; margin: 0 0 4px; }
	.tx-head p { color: #8a94a6; font-size: 14px; margin: 0; }
	.tx-card { display: flex; align-items: stretch; gap: 16px; background: #fff; border: 1px solid #eef0f2; border-radius: 10px; padding: 14px 16px; margin-bottom: 12px; box-shadow: 0 1px 2px rgba(0,0,0,.04); transition: box-shadow .15s, border-color .15s; }
	.tx-card:hover { box-shadow: 0 5px 16px rgba(0,0,0,.08); border-color: #d7ece9; }
	.tx-cover { flex: 0 0 auto; }
	.tx-cover img { width: 70px; height: 98px; object-fit: cover; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,.18); display: block; background: #f0f2f5; }
	.tx-body { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; justify-content: center; }
	.tx-title { margin: 0 0 5px; font-size: 15px; font-weight: 600; line-height: 1.35; color: #222; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
	.tx-title a { color: inherit; text-decoration: none; }
	.tx-title a:hover { color: #00a496; }
	.tx-msg { font-size: 13px; color: #556; margin-bottom: 8px; line-height: 1.45; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
	.tx-msg .q { color: #00a496; font-weight: 700; margin-right: 4px; }
	.tx-meta { display: flex; align-items: center; flex-wrap: wrap; gap: 6px 14px; font-size: 12px; color: #8a94a6; }
	.tx-aside { flex: 0 0 auto; display: flex; flex-direction: column; align-items: flex-end; justify-content: center; gap: 10px; }
	.tx-msg-btn { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 600; color: #fff; background: #00857a; border: 0; border-radius: 7px; padding: 8px 16px; cursor: pointer; transition: background .15s; white-space: nowrap; }
	.tx-msg-btn:hover { background: #006f65; }
	.tx-msg-btn .tx-unread { background: #e5484d; color: #fff; font-size: 10px; font-weight: 700; border-radius: 20px; padding: 1px 6px; line-height: 1.4; }
	.tx-empty { text-align: center; color: #8a94a6; padding: 48px 16px; background: #fff; border: 1px dashed #dfe3e8; border-radius: 10px; }
	.tx-pagination { margin-top: 18px; }
	@media (max-width: 576px) {
		.tx-card { flex-wrap: wrap; }
		.tx-aside { width: 100%; flex-direction: row; align-items: center; justify-content: flex-end; }
	}

	/* Thread bubbles inside the shared inquiry modal */
	.container-msg-reply { border: 1px solid #e2e6ec; background-color: #f4f6f9; color: #222; border-radius: 10px; padding: 10px 14px; margin: 8px 0; }
	.container-msg-reply p { margin: 0 0 4px; }
	.container-msg-reply.darker { border-color: #00857a; background-color: #00857a; color: #fff; }
	.seen-row-patron { display: flex; justify-content: flex-end; margin-top: 4px; padding-right: 2px; }
	.seen-ava-lib { width: 22px; height: 22px; border-radius: 50%; background: #00857a; color: #fff; font-size: 10px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; box-shadow: 0 0 0 2px #fff; cursor: default; }
	.time-right { display: block; text-align: right; color: rgba(255,255,255,.85); font-size: 11px; }
	.time-left  { display: block; text-align: left;  color: #8a94a6; font-size: 11px; }
</style>
@endsection
@section('content')

	<div class="container margin_60 tx-page">
		<div class="row">
			<div class="col-9">
				<div class="tx-head">
					<h1>Inquiries</h1>
					<p>Questions you've sent about materials, and the library's replies.</p>
				</div>

				@if($flag)
					@foreach($list AS $lists)
						@php
							$holdings = getPublicationDetail($lists->catalog_id);
							$cover    = coverBase($lists->catalog_id) ?: 'frontpages/no-preview.jpg';
						@endphp
						<div class="tx-card">
							<div class="tx-cover">
								<img src="{{ url('/' . $cover) }}" alt="cover">
							</div>
							<div class="tx-body">
								<h3 class="tx-title"><a href="{{ recordUrl($lists->catalog_id, $holdings['title_statement']) }}">{!! $holdings['title_statement'] !!}</a></h3>
								<div class="tx-msg"><span class="q">Q:</span>{{ $lists->message }}</div>
								<div class="tx-meta">
									<span>{{ \Carbon\Carbon::parse($lists->created_at)->format('M d, Y') }}</span>
									<span>{{ $lists->total_msg }} {{ $lists->total_msg == 1 ? 'reply' : 'replies' }}</span>
								</div>
							</div>
							<div class="tx-aside">
								<button type="button" class="tx-msg-btn" onclick="showThread({{ $lists->id }}, {{ Auth::user()->id }})">
									<i class="pe-7s-chat"></i> Messages
									@if($lists->new_reply > 0)<span class="tx-unread">{{ $lists->new_reply }}</span>@endif
								</button>
							</div>
						</div>
					@endforeach

					<div class="tx-pagination">{{ $list->links('vendor.pagination.default') }}</div>
				@else
					<div class="tx-empty">You have no inquiries yet.</div>
				@endif
			</div>
			@include('opac.side')
		</div>
	</div>

@endsection

@section('js')
<script type="text/javascript">
function showThread(id, userid) {
	var esc = function (s) { return $('<div>').text(s == null ? '' : s).html(); };
	$("#catalog_id_inquiry_reply").val(id);

	$.getJSON("{{ url('inquiry/json/') }}/" + id, function (datajson) {
	}).done(function (datajson) {
		$("#inquiry_message").text('');
		jQuery.each(datajson, function (i, obj) {
			// Patron perspective: your own messages show "You"; every library/admin reply
			// shows "Library" (the specific admin's name is only revealed in the admin UI).
			if (userid == obj.replied_by) {
				$("#inquiry_message").append('<div class="container-msg-reply darker"><p>' + esc(obj.replied_msg) + '</p><span class="time-right"><small>You | ' + obj.thread_date + '</small></span></div>');
			} else {
				$("#inquiry_message").append('<div class="container-msg-reply"><p>' + esc(obj.replied_msg) + '</p><span class="time-left"><small>Library | ' + obj.thread_date + '</small></span></div>');
			}
		});

		// Show a single "Seen by Library" avatar when the library has read your
		// latest message — one avatar regardless of how many admins actually saw it.
		$.getJSON("{{ url('inquiry/seen-receipts/') }}/" + id).done(function (res) {
			$('#inquiry_seen_row').remove();
			if (res.lastFromPatron && res.anySeen) {
				$("#inquiry_message").append('<div id="inquiry_seen_row" class="seen-row-patron"><span class="seen-ava-lib" title="Seen by Library">L</span></div>');
			}
		});
	}).fail(function (datajson) {
	});

	// Mark the library's replies as seen (POST -> CSRF-protected, not a GET side effect).
	$.ajax({
		type: 'POST',
		url: "{{ url('inquiry/mark-seen') }}/" + id,
		data: { _token: "{{ csrf_token() }}" },
		dataType: 'json'
	});

	$("#inquireReplyModal").modal('toggle');
}
</script>

@endsection
