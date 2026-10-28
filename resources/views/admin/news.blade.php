@extends("admin.layouts.master")

@section('css')
	<link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/css/dataTables.bootstrap5.css') }}">
	<link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/css/buttons.bootstrap5.min.css') }}">
	<style>
		.admin-page-head { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:10px; margin-bottom:18px; }
		.admin-page-head .apt { margin:0; font-size:1rem; font-weight:600; }
		.admin-page-head .aph-desc { margin:2px 0 0; font-size:0.8125rem; color:var(--ui-muted, #8a94a6); }
		table td { word-break:break-word; vertical-align:top; white-space:normal !important; }
		.news-headline-now { display:flex; align-items:center; gap:6px; margin:-6px 0 14px; font-size:0.8125rem; color:var(--ui-muted, #8a94a6); }
		.news-headline-now strong { color:var(--bs-body-color, inherit); }
		.btn-headline { white-space:nowrap; }
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
							<p class="aph-desc">Featured articles are shown in the NEWS section of the library home page. Pick one as the headline to lead it; otherwise the latest featured article leads.</p>
						</div>
						<a href="{{ url('/admin/news/add') }}" class="btn btn-primary">
							<i data-feather="file-plus" class="icon-sm me-1"></i> Add News Article
						</a>
					</div>
					<p class="news-headline-now">
						<i data-feather="star" class="icon-sm"></i>
						<span>Headline on the home page:</span>
						<strong id="headlineNow">{{ $headline ? $headline->news_title : 'Automatic (the latest featured article)' }}</strong>
					</p>

					<div class="table-responsive">
						<table id="tbl" class="table table-hover align-middle">
							<thead>
								<tr>
									<th style="width:34%">Title</th>
									<th>Headline</th>
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
<script nonce="{{ cspNonce() }}">
	const editUrlBase = '{{ url("/admin/news/edit") }}';
	const deleteUrlBase = '{{ url("/admin/news/delete") }}';
	const csrfToken = '{{ csrf_token() }}';
	const headlineUrlBase = '{{ url("/admin/news/headline") }}';

	$(document).ready(function () {
		const table = $('#tbl').DataTable({
			processing: true,
			serverSide: true,
			order: [[4, 'desc']],
			ajax: '{{ url("/admin/news/json") }}',
			columns: [
				{ data: 'news_title', render: $.fn.dataTable.render.text() },
				// One headline at a time: it leads the home page's NEWS. Only featured articles can be it.
				{ data: 'is_headline', orderable: false, searchable: false, render: function (d, type, row) {
					if (String(row.featured).toLowerCase() !== 'yes') {
						return '<span class="text-muted small" title="Only featured articles are shown on the home page">—</span>';
					}
					return d
						? `<button type="button" class="btn btn-sm btn-warning btn-headline" data-headline="${row.id}" aria-pressed="true" title="Leads the NEWS section. Click to go back to the latest featured article.">&#9733; Headline</button>`
						: `<button type="button" class="btn btn-sm btn-outline-secondary btn-headline" data-headline="${row.id}" aria-pressed="false">Make headline</button>`;
				} },
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
									<form method="POST" action="${deleteUrl}" data-confirm-title="Delete this news article?" data-confirm="This cannot be undone.">
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

		// Make headline / clear it. A refused session or permission is reported by the layout's ajaxError.
		$('#tbl').on('click', '[data-headline]', function () {
			const btn = this;
			btn.disabled = true;
			$.ajax({ url: headlineUrlBase + '/' + btn.dataset.headline, method: 'POST', dataType: 'json', headers: { 'X-CSRF-TOKEN': csrfToken } })
				.done(function (r) {
					document.getElementById('headlineNow').textContent = r.headline ? r.headline.news_title : 'Automatic (the latest featured article)';
					table.ajax.reload(null, false);
				})
				.fail(function (xhr) {
					if (xhr.status === 422 && window.Swal) Swal.fire({ icon: 'info', title: 'Not featured', text: xhr.responseJSON.message });
				})
				.always(function () { btn.disabled = false; });
		});
	});
</script>
@endsection
