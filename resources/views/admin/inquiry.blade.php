@extends("admin.layouts.master")

@section('css')
	<link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.css') }}">
	<style>
		.admin-page-head { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:10px; margin-bottom:18px; }
		.admin-page-head .apt { margin:0; font-size:16px; font-weight:600; }
		.admin-page-head .aph-desc { margin:2px 0 0; font-size:13px; color:#8a94a6; }
		.inq-msg { max-width:340px; }
		table td { word-break:break-word; vertical-align:top; white-space:normal !important; }
		/* Keep the action buttons on one line (the global white-space:normal above was
		   wrapping the Reply icon above its label once the split caret was added). */
		#tbl td:last-child { white-space:nowrap !important; }
		#tbl td .btn-group { flex-wrap:nowrap; }
		#tbl td .btn { white-space:nowrap; }

		.chat-thread { max-height:340px; overflow-y:auto; padding:4px; }
		.container-msg-reply { border:1px solid #e2e6ec; background-color:#f4f6f9; border-radius:10px; padding:10px 14px; margin:8px 0; }
		.container-msg-reply p { margin:0 0 4px; }
		.container-msg-reply.darker { border-color:#0d6efd; background-color:#0d6efd; color:#fff; }
		.time-right { display:block; text-align:right; color:rgba(255,255,255,.85); font-size:11px; }
		.time-left  { display:block; text-align:left;  color:#8a94a6; font-size:11px; }

		.reply-dot { position:absolute; top:-3px; right:-3px; width:10px; height:10px; background:#ff3366; border-radius:50%; border:2px solid #fff; }
		/* Messenger-style "Seen by" admin avatars (stacked, hover for name). */
		.seen-row { display:flex; align-items:center; justify-content:flex-end; margin-top:6px; padding-right:2px; }
		.seen-lbl { font-size:11px; color:#8a94a6; margin-right:6px; }
		.seen-ava { width:22px; height:22px; border-radius:50%; background:#6571ff; color:#fff; font-size:9.5px; font-weight:700; display:inline-flex; align-items:center; justify-content:center; box-shadow:0 0 0 2px #fff; cursor:default; margin-left:-6px; }
		.seen-ava:first-of-type { margin-left:0; }
	</style>
@endsection

@section('content')
<div class="page-content">
	<nav class="page-breadcrumb">
		<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="{{ url('/admin/dashboard') }}">Home</a></li>
			<li class="breadcrumb-item active" aria-current="page">Inquiries</li>
		</ol>
	</nav>

	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<div class="admin-page-head">
						<div>
							<h6 class="apt">Inquiries</h6>
							<p class="aph-desc">Questions patrons submitted about materials — reply to registered users to keep the conversation going.</p>
						</div>
						<span class="badge bg-primary rounded-pill" style="font-size:13px;">{{ count($list) }} total</span>
					</div>

					<div class="table-responsive">
						<table id="tbl" class="table table-hover align-middle">
							<thead>
								<tr>
									<th>Subject</th>
									<th>Material Title</th>
									<th>User</th>
									<th>Contact No.</th>
									<th>Email</th>
									<th>Date</th>
									<th class="text-center">Action</th>
								</tr>
							</thead>
							<tbody>
								@foreach($list AS $lists)
									@php $uf = $userFlags[$lists->user_id] ?? null; @endphp
									<tr>
										<td><div class="inq-msg">{{ $lists->subject }}</div></td>
										@php $fullTitle = trim(strip_tags((string) getTitle($lists->catalog_id))); @endphp
										<td><span title="{{ $fullTitle }}">{{ \Illuminate\Support\Str::limit($fullTitle, 55) }}</span></td>
										<td>
											{{ $lists->fullname }}
											@if($uf && $uf->is_banned)<span class="badge bg-danger ms-1">Banned</span>@endif
											@if($uf && $uf->is_blocked)<span class="badge bg-warning text-dark ms-1">Blocked</span>@endif
										</td>
										<td>{{ $lists->contactnum }}</td>
										<td>{{ $lists->email }}</td>
										<td data-order="{{ strtotime($lists->created_at) }}">{{ \Carbon\Carbon::parse($lists->created_at)->format('M d, Y g:i A') }}</td>
										<td class="text-center">
											<div class="btn-group">
												<button class="btn btn-primary btn-sm position-relative" data-subject="{{ $lists->subject }}" data-message="{{ $lists->message }}" onclick="replyInquiry(this,{{ $lists->id }},{{ Auth::user()->id }})">@if(isset($unread[$lists->id]))<span class="reply-dot" title="Unseen message"></span>@endif<i data-feather="corner-up-left" class="icon-sm me-1"></i>Reply</button>
												@if($lists->user_id)
													<button type="button" class="btn btn-primary btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false"><span class="visually-hidden">Manage</span></button>
													<div class="dropdown-menu dropdown-menu-end">
														<h6 class="dropdown-header">Manage account</h6>
														<a class="dropdown-item text-warning" href="#" onclick="toggleUserFlag({{ $lists->user_id }},'blocked');return false;">{{ $uf && $uf->is_blocked ? 'Unblock account' : 'Block account' }}</a>
														<a class="dropdown-item text-danger" href="#" onclick="toggleUserFlag({{ $lists->user_id }},'banned');return false;">{{ $uf && $uf->is_banned ? 'Unban account' : 'Ban account' }}</a>
													</div>
												@endif
											</div>
										</td>
									</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="inquireReplyModal" tabindex="-1" role="dialog" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title">Reply to inquiry</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
			</div>
			<form method="post" action="{{ url('/inquiry/reply') }}" id="frm_inquiry_reply">
				@csrf
				<input type="hidden" name="catalog_id_inquiry_reply" id="catalog_id_inquiry_reply">
				<div class="modal-body">
					<div class="chat-thread mb-3" id="inquiry_message"></div>
					<label class="form-label">Your reply</label>
					<textarea class="form-control" name="inquiry_message_reply" rows="3" placeholder="Type your reply..."></textarea>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
					<button type="submit" class="btn btn-primary"><i data-feather="send" class="icon-sm me-1"></i>Send reply</button>
				</div>
			</form>
		</div>
	</div>
</div>
@endsection

@section('js')
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.js') }}"></script>
<script>
	function replyInquiry(btn, id, userid) {
		if (btn) $(btn).find('.reply-dot').remove();
		$("#inquireReplyModal").modal('show');
		$("#catalog_id_inquiry_reply").val(id);

		var esc = function (s) { return $('<div>').text(s == null ? '' : s).html(); };
		var subject = btn && btn.getAttribute('data-subject');
		var message = btn && btn.getAttribute('data-message');

		var box = $("#inquiry_message").empty();
		// The reply thread only holds replies, so show the patron's original inquiry
		// (subject + message) at the top for full context.
		box.append('<div class="container-msg-reply"><p><span class="text-muted small">Subject</span><br><strong>' + esc(subject) + '</strong></p><p>' + esc(message) + '</p><span class="time-left"><small>Original inquiry</small></span></div>');

		$.getJSON("{{ url('inquiry/json/') }}/" + id).done(function (datajson) {
			jQuery.each(datajson, function (i, obj) {
				if (userid == obj.replied_by) {
					box.append('<div class="container-msg-reply darker"><p>' + esc(obj.replied_msg) + '</p><span class="time-right"><small>' + esc(obj.replied_by_name) + ' | ' + esc(obj.thread_date) + '</small></span></div>');
				} else {
					box.append('<div class="container-msg-reply"><p>' + esc(obj.replied_msg) + '</p><span class="time-left"><small>' + esc(obj.replied_by_name) + ' | ' + esc(obj.thread_date) + '</small></span></div>');
				}
			});

			// Mark this admin as having read the thread (POST -> CSRF-protected, no
			// side effects on a GET), then load the seen receipts so this admin appears.
			$.ajax({
				type: 'POST',
				url: "{{ url('inquiry/mark-seen') }}/" + id,
				data: { _token: "{{ csrf_token() }}" },
				dataType: 'json'
			}).always(function () {
				$.getJSON("{{ url('inquiry/seen-receipts/') }}/" + id).done(function (res) {
					$('#inquiry_seen_row').remove();
					if (res.seens && res.seens.length) {
						var av = res.seens.map(function (s) {
							return '<span class="seen-ava" title="Seen by ' + esc(s.name) + ' &middot; ' + esc(s.seen_at) + '">' + esc(s.initials) + '</span>';
						}).join('');
						box.append('<div id="inquiry_seen_row" class="seen-row"><span class="seen-lbl">Seen by</span>' + av + '</div>');
					}
				});
			});
		});
	}

	$(function () {
		'use strict';
		if (window.feather) feather.replace();
		$('#tbl').DataTable({ order: [[5, 'desc']], columnDefs: [{ orderable: false, targets: 6 }] });
	});

	// Ban/Block a patron: banned = no login + no Read; blocked = no chat/inquiry.
	function toggleUserFlag(id, flag) {
		var isBan = flag === 'banned';
		Swal.fire({
			title: 'Are you sure?',
			html: isBan
					? 'A <strong>banned</strong> account cannot log in and cannot use the Read feature.'
					: 'A <strong>blocked</strong> account cannot use chat / inquiry.',
			icon: 'warning',
			showCancelButton: true,
			confirmButtonText: 'Yes',
			cancelButtonText: 'No',
			reverseButtons: true
		}).then(function (result) {
			if (!result.value) return;
			$.ajax({
				type: 'POST',
				url: '{{ url("/admin/user/toggle-flag") }}',
				data: { _token: '{{ csrf_token() }}', user_id: id, flag: flag },
				dataType: 'json',
				success: function () { location.reload(); },
				error: function () { Swal.fire('Error', 'Could not update the account. Please try again.', 'error'); }
			});
		});
	}
</script>
@endsection
