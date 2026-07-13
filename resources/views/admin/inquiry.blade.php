@extends("admin.layouts.master")

@section('css')
	<link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.css') }}">
	<style>
		.admin-page-head { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:10px; margin-bottom:18px; }
		.admin-page-head .apt { margin:0; font-size:16px; font-weight:600; }
		.admin-page-head .aph-desc { margin:2px 0 0; font-size:13px; color:#8a94a6; }
		.inq-msg { max-width:340px; }
		table td { word-break:break-word; vertical-align:top; white-space:normal !important; }

		.chat-thread { max-height:340px; overflow-y:auto; padding:4px; }
		.container-msg-reply { border:1px solid #e2e6ec; background-color:#f4f6f9; border-radius:10px; padding:10px 14px; margin:8px 0; }
		.container-msg-reply p { margin:0 0 4px; }
		.container-msg-reply.darker { border-color:#0d6efd; background-color:#0d6efd; color:#fff; }
		.time-right { display:block; text-align:right; color:rgba(255,255,255,.85); font-size:11px; }
		.time-left  { display:block; text-align:left;  color:#8a94a6; font-size:11px; }
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
									<th>Message</th>
									<th>Title</th>
									<th>User</th>
									<th>Contact No.</th>
									<th>Email</th>
									<th class="text-center">Replies</th>
									<th>Date</th>
									<th class="text-center">Action</th>
								</tr>
							</thead>
							<tbody>
								@foreach($list AS $lists)
									<tr>
										<td><div class="inq-msg">{{ $lists->message }}</div></td>
										<td>{!! getTitle($lists->catalog_id) !!}</td>
										<td>{{ $lists->fullname }}</td>
										<td>{{ $lists->contactnum }}</td>
										<td>{{ $lists->email }}</td>
										<td class="text-center">
											@if($lists->total_msg > 0)
												<span class="badge bg-danger rounded-pill">{{ $lists->total_msg }}</span>
											@else
												<span class="text-muted">&mdash;</span>
											@endif
										</td>
										<td data-order="{{ strtotime($lists->created_at) }}">{{ \Carbon\Carbon::parse($lists->created_at)->format('M d, Y g:i A') }}</td>
										<td class="text-center">
											@if($lists->user_id)
												<button class="btn btn-primary btn-sm" onclick="replyInquiry({{ $lists->id }},{{ Auth::user()->id }})"><i data-feather="corner-up-left" class="icon-sm me-1"></i>Reply</button>
											@else
												<span class="badge bg-light text-dark">Guest</span>
											@endif
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
	function replyInquiry(id, userid) {
		$("#inquireReplyModal").modal('show');
		$("#catalog_id_inquiry_reply").val(id);

		$.getJSON("{{ url('inquiry/json/') }}/" + id).done(function (datajson) {
			$("#inquiry_message").empty();
			jQuery.each(datajson, function (i, obj) {
				if (userid == obj.replied_by) {
					$("#inquiry_message").append('<div class="container-msg-reply darker"><p>' + obj.replied_msg + '</p><span class="time-right"><small>' + obj.thread_date + '</small></span></div>');
				} else {
					$("#inquiry_message").append('<div class="container-msg-reply"><p>' + obj.replied_msg + '</p><span class="time-left"><small>' + obj.replied_by_name + ' | ' + obj.thread_date + '</small></span></div>');
				}
			});
		});
	}

	$(function () {
		'use strict';
		if (window.feather) feather.replace();
		$('#tbl').DataTable({ order: [[6, 'desc']], columnDefs: [{ orderable: false, targets: 7 }] });
	});
</script>
@endsection
