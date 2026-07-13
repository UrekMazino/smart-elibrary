@extends('opac.layout.master')
@section('content')


		<div class="container margin_60">
			<div class="main_title">
				<h1>Transactions</h1>
				{{-- <p>Usu habeo equidem sanctus no. Suas summo id sed, erat erant oporteat cu pri. In eum omnes molestie. Sed ad debet scaevola, ne mel.</p> --}}
			</div>
			<div class="row">
				<div class="col-9">
					@if($flag)
					@foreach($list AS $lists)
					<?php
					// dd($lists->catalog_id);
						$holdings = getPublicationDetail($lists->catalog_id);
					?>
									<div class="strip_list wow fadeIn">
										<figure>
												{!! getFrontpage($lists->catalog_id) !!}
										</figure>
										<h3>{!! $holdings['title_statement'] !!}</h3>
										<span class="text-muted"><br>{!! $holdings['joint_authors'] !!}</span><br>

										Published Date: {!! pubYear($holdings['pub_year']) !!} <br>
										
				
										
										<div class="rating">
											<i data-star="{{ $ave = starRatingAve($lists->catalog_id) }}"></i>
										</div>
										@if($lists->status == 1)
											<p align='right'><span class='badge bg-secondary'> Pending </span></p>
										@elseif($lists->status == 2)
											<p align='right'><span class='badge bg-success'>Approved </span></p>
										@elseif($lists->status == 3)
											<p align='right'><span class='badge bg-warning'>Checkout - Due Date: {{ $lists->due_date  }}  </span></p>
										@elseif($lists->status == 4)
											<p align='right'><span class='badge bg-danger'>Declined </span></p>
										@elseif($lists->status == 5)
											<p align='right'><span class='badge bg-primary'>Complete ( Returned )</span></p>

										@endif
										@if($lists->reservation_code)
											@php $rcUnread = $unread[$lists->reservation_code] ?? 0; @endphp
											<p align='right' style="margin-top:6px;">
												<button type="button" class="btn btn-sm" style="background:#00a496;color:#fff;border:0;" onclick="openResChat('{{ $lists->reservation_code }}')">
													<i class="pe-7s-chat"></i> Messages
													@if($rcUnread > 0)<span class="badge bg-danger" id="resbadge-{{ $lists->reservation_code }}">{{ $rcUnread }}</span>@endif
												</button>
											</p>
										@endif
									</div>
					@endforeach
					@else
					<div class="strip_list wow fadeIn">
						<div class="row">
							<div class="col-12">
								<p align='center'>
									No borrowed materials..
								</p>
							</div>
						</div>
					</div>
					@endif
					</div>
				@include('opac.side')
			</div>
				<!-- /col -->
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
<script>
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
			var bg = mine ? 'background:#00a496;color:#fff;' : 'background:#f0f2f5;color:#222;';
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