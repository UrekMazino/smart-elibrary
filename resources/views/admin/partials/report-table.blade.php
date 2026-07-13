@php $count = is_countable($list) ? count($list) : 0; @endphp
@isset($preview)
	<div class="d-flex align-items-center mb-3">
		<span class="badge bg-primary" style="font-size:13px;">{{ number_format($count) }}</span>
		<span class="ms-2 text-muted">record{{ $count == 1 ? '' : 's' }} found</span>
	</div>
@endisset

@if($count == 0)
	<div class="alert alert-info mb-0">No records found for the selected filter.</div>
@else
<div class="table-responsive">
<table class="table table-striped table-hover mb-0" id="tbl">
	@if($table == 1)
		<thead><tr><th>#</th><th>Title</th><th>Authors</th><th>Material Type</th><th>Call No.</th><th>General Notes</th><th>Source</th></tr></thead>
		<tbody>
			@foreach($list as $i => $r)
				<tr><td>{{ $i+1 }}</td><td>{{ formatTitle1($r->title_statement) }}</td><td>{{ $r->joint_authors }}</td><td>{{ $r->material }}</td><td>{{ $r->call_number }}</td><td>{{ $r->general_note }}</td><td>{{ $r->consortia }}</td></tr>
			@endforeach
		</tbody>
	@elseif($table == 2)
		<thead><tr><th>#</th><th>Name</th><th>Username</th><th>Contact No.</th><th>Email</th><th>Location</th><th>Date Registered</th></tr></thead>
		<tbody>
			@foreach($list as $i => $r)
				<tr><td>{{ $i+1 }}</td><td>{{ $r->name }}</td><td>{{ $r->username }}</td><td>{{ $r->contactnum }}</td><td>{{ $r->email }}</td><td>{{ $r->location }}</td><td>{{ $r->created_at }}</td></tr>
			@endforeach
		</tbody>
	@elseif($table == 3)
		<thead><tr><th>#</th><th>Transaction</th><th>Patron</th><th>Title</th><th>Circulation No.</th><th>Status</th><th>Date</th></tr></thead>
		<tbody>
			@foreach($list as $i => $r)
				<tr>
					<td>{{ $i+1 }}</td>
					<td>{{ $r->transaction_code }}</td>
					<td>{{ $r->user_name }}</td>
					<td>{{ $r->res_title ? formatTitle1($r->res_title) : ($r->copy_id ? getTitleFromHoldingCopy($r->copy_id) : '') }}</td>
					<td>{{ $r->circulation_number }}</td>
					<td>{{ $r->status_name }}</td>
					<td>{{ $r->created_at }}</td>
				</tr>
			@endforeach
		</tbody>
	@elseif($table == 4)
		<thead><tr><th>#</th><th>Inquiry</th><th>Material</th><th>Name</th><th>Contact No.</th><th>Email</th><th>Date</th></tr></thead>
		<tbody>
			@foreach($list as $i => $r)
				<tr><td>{{ $i+1 }}</td><td>{{ $r->message }}</td><td>{{ $r->title_statement ? formatTitle1($r->title_statement) : getTitle($r->catalog_id) }}</td><td>{{ $r->fullname }}</td><td>{{ $r->contactnum }}</td><td>{{ $r->email }}</td><td>{{ $r->created_at }}</td></tr>
			@endforeach
		</tbody>
	@elseif($table == 5)
		<thead><tr><th>#</th><th>User</th><th>Responsiveness</th><th>Reliability</th><th>Access &amp; Facilities</th><th>Communication</th><th>Integrity</th><th>Assurance</th><th>Outcome</th><th>Remarks</th><th>Suggestions</th><th>Comments</th><th>Date</th></tr></thead>
		<tbody>
			@foreach($list as $i => $r)
				<tr>
					<td>{{ $i+1 }}</td><td>{{ $r->user_id }}</td>
					<td>{{ $ratings[$r->rating1] ?? '' }}</td><td>{{ $ratings[$r->rating2] ?? '' }}</td><td>{{ $ratings[$r->rating3] ?? '' }}</td><td>{{ $ratings[$r->rating4] ?? '' }}</td><td>{{ $ratings[$r->rating5] ?? '' }}</td><td>{{ $ratings[$r->rating6] ?? '' }}</td><td>{{ $ratings[$r->rating7] ?? '' }}</td>
					<td>{{ $r->comment1 }}</td><td>{{ $r->comment2 }}</td><td>{{ $r->comment3 }}</td><td>{{ $r->created_at }}</td>
				</tr>
			@endforeach
		</tbody>
	@elseif($table == 6)
		<thead><tr><th>#</th><th>Catalog ID</th><th>Material</th><th>User</th><th>Review Status</th><th>Date Downloaded</th></tr></thead>
		<tbody>
			@foreach($list as $i => $r)
				<tr><td>{{ $i+1 }}</td><td>{{ $r->catalog_id }}</td><td>{{ getTitle($r->catalog_id) }}</td><td>{{ getPatronInfo($r->user_id,'name') }}</td><td>{{ $r->to_review ? 'Reviewed on '.\Carbon\Carbon::parse($r->to_review)->format('M d, Y') : 'Not yet reviewed' }}</td><td>{{ $r->created_at }}</td></tr>
			@endforeach
		</tbody>
	@elseif($table == 7)
		<thead><tr><th>#</th><th>Title</th><th>Authors</th><th>Material Type</th><th>Call No.</th><th>Date Uploaded</th></tr></thead>
		<tbody>
			@foreach($list as $i => $r)
				<tr><td>{{ $i+1 }}</td><td>{{ formatTitle1($r->title_statement) }}</td><td>{{ $r->joint_authors }}</td><td>{{ $r->material }}</td><td>{{ $r->call_number }}</td><td>{{ $r->created_at }}</td></tr>
			@endforeach
		</tbody>
	@endif
</table>
</div>
@endif
