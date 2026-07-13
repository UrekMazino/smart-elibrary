@extends("admin.layouts.master")

@section('css')
	<link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/css/dataTables.bootstrap5.css') }}">
	<link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/css/buttons.bootstrap5.min.css') }}">
	<style>
		.admin-page-head { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:10px; margin-bottom:18px; }
		.admin-page-head .apt { margin:0; font-size:16px; font-weight:600; }
		.admin-page-head .aph-desc { margin:2px 0 0; font-size:13px; color:#8a94a6; }
		table td { word-break:break-word; vertical-align:top; white-space:normal !important; }
	</style>
@endsection

@section('content')
<div class="page-content">
	<nav class="page-breadcrumb">
		<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="{{ url('/admin/dashboard') }}">Home</a></li>
			<li class="breadcrumb-item active" aria-current="page">News Articles</li>
		</ol>
	</nav>

	<div class="row">
		<div class="col-12">
			<div class="card">
				<div class="card-body">
					<div class="admin-page-head">
						<div>
							<h6 class="apt">News Articles</h6>
							<p class="aph-desc">Announcements and articles shown on the library home page. Feature an article to pin it to the front.</p>
						</div>
						<a href="{{ url('/admin/news/add') }}" class="btn btn-primary">
							<i data-feather="file-plus" class="icon-sm me-1"></i> Add News Article
						</a>
					</div>

					<div class="table-responsive">
						<table id="tbl" class="table table-hover align-middle">
							<thead>
								<tr>
									<th style="width:34%">Title</th>
									<th>Featured</th>
									<th>Created By</th>
									<th>Created At</th>
									<th class="text-center">Action</th>
								</tr>
							</thead>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@endsection

@section('js')
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/buttons.bootstrap5.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/buttons.colVis.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/buttons.html5.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/buttons.print.min.js') }}"></script>
<script>
	const editUrlBase = '{{ url("/admin/news/edit") }}';
	const deleteUrlBase = '{{ url("/admin/news/delete") }}';
	const csrfToken = '{{ csrf_token() }}';

	$(document).ready(function () {
		$('#tbl').DataTable({
			processing: true,
			serverSide: true,
			order: [[3, 'desc']],
			ajax: '{{ url("/admin/news/json") }}',
			columns: [
				{ data: 'news_title' },
				{ data: 'featured', orderable: false, render: function (d) {
					return (String(d).toLowerCase() === 'yes')
						? '<span class="badge bg-success">Featured</span>'
						: '<span class="badge bg-light text-dark">No</span>';
				} },
				{ data: 'created_by' },
				{ data: 'created_at', render: function (d) { return d ? new Date(d).toLocaleString() : ''; } },
				{ data: 'id', orderable: false, searchable: false, render: function (d, type, row) {
					const editUrl = editUrlBase + '/' + row.id;
					const deleteUrl = deleteUrlBase + '/' + row.id;
					return `
						<center>
							<div class="btn-group" role="group">
								<button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Action</button>
								<div class="dropdown-menu dropdown-menu-end">
									<a class="dropdown-item" href="${editUrl}"><i data-feather="edit-2" class="icon-sm me-2"></i>Edit</a>
									<form method="POST" action="${deleteUrl}" onsubmit="return confirm('Delete this news article? This cannot be undone.');">
										<input type="hidden" name="_method" value="DELETE">
										<input type="hidden" name="_token" value="${csrfToken}">
										<button type="submit" class="dropdown-item text-danger"><i data-feather="trash-2" class="icon-sm me-2"></i>Delete</button>
									</form>
								</div>
							</div>
						</center>`;
				} }
			],
			drawCallback: function () { if (window.feather) feather.replace(); }
		});
	});
</script>
@endsection
