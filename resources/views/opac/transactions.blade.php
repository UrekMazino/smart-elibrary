@extends('opac.layout.master')

@section('robots', 'noindex, nofollow')

@section('content')

	<style>
		/* Transactions — compact, minimalist cards */
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
		.tx-status { display: inline-block; font-size: 11px; font-weight: 700; letter-spacing: .2px; padding: 4px 11px; border-radius: 20px; white-space: nowrap; }
		.tx-status.s-pending  { background: #eef0f2; color: #555; }
		.tx-status.s-approved { background: #e3f5ef; color: #087f5b; }
		.tx-status.s-checkout { background: #fff4e5; color: #b26a00; }
		.tx-status.s-declined { background: #fdecea; color: #c0392b; }
		.tx-status.s-complete { background: #e7f5f3; color: #00897b; }
		.tx-updated { display: inline-block; font-size: 10.5px; font-weight: 700; letter-spacing: .2px; color: #fff; background: #e5484d; border-radius: 20px; padding: 2px 8px; margin-right: 6px; }
		.tx-msg-btn { display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 600; color: #fff; background: #00857a; border: 0; border-radius: 7px; padding: 7px 14px; cursor: pointer; transition: background .15s; white-space: nowrap; }
		.tx-msg-btn:hover { background: #006f65; }
		.tx-msg-btn .tx-unread { background: #e5484d; color: #fff; font-size: 10px; font-weight: 700; border-radius: 20px; padding: 1px 6px; line-height: 1.4; }
		.tx-empty { text-align: center; color: #8a94a6; padding: 48px 16px; background: #fff; border: 1px dashed #dfe3e8; border-radius: 10px; }
		@media (max-width: 576px) {
			.tx-card { flex-wrap: wrap; }
			.tx-aside { width: 100%; flex-direction: row; align-items: center; justify-content: space-between; }
		}
	</style>

	<div class="container margin_60 tx-page">
		<div class="row">
			<div class="col-9">
				<div class="tx-head">
					<h1>Transactions</h1>
					<p>Your reservations and borrowed materials.</p>
				</div>

				@if($flag)
					@foreach($list AS $lists)
						@php
							$holdings  = getPublicationDetail($lists->catalog_id);
							$cover     = coverBase($lists->catalog_id) ?: 'frontpages/no-preview.jpg';
							$year      = trim(strip_tags((string) ($holdings['pub_year'] ?? '')));
							$authors   = trim(strip_tags((string) ($holdings['joint_authors'] ?? '')));
							$statusMap = [
								1 => ['Pending', 's-pending'],
								2 => ['Approved', 's-approved'],
								3 => ['Checked out' . ($lists->due_date ? ' · due ' . \Carbon\Carbon::parse($lists->due_date)->format('M d, Y') : ''), 's-checkout'],
								4 => ['Declined', 's-declined'],
								5 => ['Complete (Returned)', 's-complete'],
							];
							$st       = $statusMap[$lists->status] ?? ['—', 's-pending'];
							$rcUnread = $lists->reservation_code ? ($unread[$lists->reservation_code] ?? 0) : 0;
						@endphp
						<div class="tx-card">
							<div class="tx-cover">
								<img src="{{ url('/' . $cover) }}" alt="cover">
							</div>
							<div class="tx-body">
								<h3 class="tx-title"><a href="{{ recordUrl($lists->catalog_id, $holdings['title_statement']) }}">{{ $holdings['title_statement'] }}</a></h3>
								@if($authors !== '')
									<div class="tx-authors">{{ $holdings['joint_authors'] }}</div>
								@endif
								<div class="tx-meta">
									<span class="rating"><i data-star="{{ starRatingAve($lists->catalog_id) }}"></i></span>
									@if($year !== '')<span>Published {{ $year }}</span>@endif
								</div>
							</div>
							<div class="tx-aside">
								{{-- Changed by the library since the patron last opened this page. --}}
								<span>@if(in_array($lists->id, $updated ?? []))<span class="tx-updated">Updated</span>@endif<span class="tx-status {{ $st[1] }}">{{ $st[0] }}</span></span>
								@if($lists->reservation_code)
									<button type="button" class="tx-msg-btn" onclick="openResChat('{{ $lists->reservation_code }}')">
										<i class="pe-7s-chat"></i> Messages
										@if($rcUnread > 0)<span class="tx-unread" id="resbadge-{{ $lists->reservation_code }}">{{ $rcUnread }}</span>@endif
									</button>
								@endif
							</div>
						</div>
					@endforeach
				@else
					<div class="tx-empty">You have no transactions yet.</div>
				@endif
			</div>
			@include('opac.side')
		</div>
	</div>

	<!-- Reservation messages modal -->
	<div class="modal fade" id="resChatModal" tabindex="-1" role="dialog">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" style="margin:0;">Reservation messages <small class="text-muted" id="resChatCode"></small></h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div id="resChatThread" style="max-height:320px;overflow-y:auto;padding:4px;margin-bottom:12px;"></div>
					<textarea class="form-control" id="resChatMessage" rows="3" placeholder="Type your reply to the library..."></textarea>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
					<button type="button" class="btn btn-success" onclick="sendResChat()">Send</button>
				</div>
			</div>
		</div>
	</div>
@endsection

@section('js')
<script nonce="{{ cspNonce() }}">
var resChatCode = null;
var resChatJsonUrl = '{{ url("/patron/reservation/chat-json") }}';
var resChatReplyUrl = '{{ url("/patron/reservation/chat/reply") }}';
var resChatToken = '{{ csrf_token() }}';

function openResChat(code) {
	resChatCode = code;
	$('#resChatCode').text('· ' + code);
	$('#resChatMessage').val('');
	loadResChat();
	$('#resChatModal').modal('show');
	// Opening the thread clears the unread badge for this reservation.
	$('#resbadge-' + code).remove();
}

function loadResChat() {
	$.getJSON(resChatJsonUrl + '/' + encodeURIComponent(resChatCode), function (rows) {
		var box = $('#resChatThread').empty();
		if (!rows.length) {
			box.append('<p class="text-muted" style="text-align:center;margin:0;">No messages yet. You can write to the library below.</p>');
			return;
		}
		$.each(rows, function (i, obj) {
			var mine = (obj.sender_role === 'patron');
			var who = mine ? 'You' : 'Library';
			var align = mine ? 'right' : 'left';
			var bg = mine ? 'background:#00857a;color:#fff;' : 'background:#f0f2f5;color:#222;';
			box.append(
				'<div style="text-align:' + align + ';margin:8px 0;">' +
					'<div style="display:inline-block;max-width:80%;text-align:left;border-radius:10px;padding:8px 12px;' + bg + '">' +
						'<div>' + $('<div>').text(obj.message).html() + '</div>' +
						'<small style="opacity:.85;">' + who + ' · ' + obj.created_at + '</small>' +
					'</div>' +
				'</div>'
			);
		});
		box.scrollTop(box[0].scrollHeight);
	});
}

function sendResChat() {
	var msg = $('#resChatMessage').val().trim();
	if (!msg) return;
	$.ajax({
		type: 'POST',
		url: resChatReplyUrl,
		data: { _token: resChatToken, reservation_code: resChatCode, message: msg },
		dataType: 'json',
		success: function () { $('#resChatMessage').val(''); loadResChat(); },
		error: function (xhr) {
			var m = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : 'Could not send your message. Please try again.';
			if (window.Swal) { Swal.fire('Error', m, 'error'); } else { alert(m); }
		}
	});
}
</script>

@endsection
