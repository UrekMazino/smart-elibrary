@extends("admin.layouts.master")

{{-- A batch's records. Same table + filter toolbar as the Record List / On-Hold list (shared
     CatalogingController::catalogListJson), scoped to the batch, plus a Status filter and column
     because a batch holds both published and on-hold records. --}}

@section('css')
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/css/dataTables.bootstrap5.css') }}">
    <style>
      #tbl { font-size: 0.8125rem; width: 100% !important; }
      #tbl thead th { font-size: 0.6875rem; text-transform: uppercase; letter-spacing: .4px; color: #7987a1; white-space: nowrap; padding: 9px 10px; border-bottom: 2px solid #eef0f2; }
      #tbl tbody td { padding: 6px 10px; vertical-align: middle !important; white-space: normal; }
      #tbl tbody tr { cursor: pointer; }
      #tbl tbody tr:hover { background: #f7f9fc; }
      .tbl-id { color: #a3adc2; font-size: 0.75rem; }
      /* Full MARC 245 title (all subfields), wrapped — never cut to one line. */
      .tbl-title { font-weight: 600; color: #22283b; display: block; white-space: normal; overflow-wrap: anywhere; min-width: 220px; }
      .tbl-authors { color: #7987a1; display: inline-block; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: middle; }
      /* #tbl prefix beats the template's `.badge { color:#fff }`. */
      #tbl .badge-type { background: #e4e9f2; color: #33415c; font-weight: 600; font-size: 0.71875rem; letter-spacing: .2px; }
      #tbl .badge-status { font-weight: 600; font-size: 0.6875rem; }
      #tbl .badge-status.pub  { background: #e3f6ef; color: #1d7a55; }
      #tbl .badge-status.hold { background: #fff4e5; color: #a15c00; }
      .bt-meta { font-size: 0.78125rem; color: #7987a1; }
      .bt-meta strong { color: #33415c; font-weight: 600; }
      @media (max-width: 1200px) { .tbl-authors { max-width: 150px; } }
      @media (max-width: 768px)  { .tbl-authors { max-width: 110px; } }
    </style>
@endsection

@section('content')
<div class="page-content">
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/batch-list') }}">Batch list</a></li>
            {{-- View Batch Details is a POST page: the link submits #viewBatchFrm. --}}
            <li class="breadcrumb-item"><a href="#" onclick="document.getElementById('viewBatchFrm').submit(); return false;">Batch #{{ $batch->id }} details</a></li>
            <li class="breadcrumb-item active" aria-current="page">Records</li>
        </ol>
    </nav>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                      <div>
                        <h6 class="card-title mb-1">Batch #{{ $batch->id }} — Records</h6>
                        <div class="bt-meta">
                            <strong>{{ $batch->subject ?: 'No subject' }}</strong>
                            · imported {{ optional($batch->created_at)->format('M j, Y') }}
                            · {{ $filters['statuses']['published'] ?? 0 }} published
                            · {{ $filters['statuses']['onhold'] ?? 0 }} on hold
                            · <a href="{{ route('batch-report', ['id' => $batch->id]) }}">Import report</a>
                        </div>
                      </div>
                      @include('admin.partials.catalog-bulk-toolbar', ['list' => 'batch'])
                    </div>

                    @include('admin.partials.catalog-list-filters', ['filters' => $filters])

                    <div class="table-responsive">
                        <table id="tbl" class="table table-hover table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="bs-cell">@include('admin.partials.catalog-bulk-checkall')</th>
                                    <th style="width: 64px">ID</th>
                                    <th>Title</th>
                                    <th style="width: 120px">Type</th>
                                    <th style="width: 200px">Author</th>
                                    <th style="width: 80px" class="text-center">Year</th>
                                    <th style="width: 90px" class="text-center">Status</th>
                                    <th style="width: 70px" class="text-center">Fulltext</th>
                                    <th style="width: 64px" class="text-center">Cover</th>
                                    <th style="width: 92px" class="text-center">Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form method="post" action="{{ url('/admin/view-record') }}" id="viewRecordFrm">
    @csrf
    <input type="hidden" name="catalog_id" id="catalog_id">
    <input type="hidden" name="title" id="title">
</form>
<form method="post" action="{{ url('/admin/view-batch') }}" id="viewBatchFrm">
    @csrf
    <input type="hidden" name="batch_id" value="{{ $batch->id }}">
</form>
<form method="post" action="" id="frm_delete">
    @csrf
    @method('DELETE')
</form>
@endsection

@section('js')
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/js/dataTables.bootstrap5.min.js') }}"></script>
@include('admin.partials.catalog-list-filters-js')
@include('admin.partials.catalog-bulk-js', ['list' => 'batch', 'batchId' => $batch->id])
<script>
var CSRF = '{{ csrf_token() }}';
var CAN_PUBLISH_HOLD = @json(auth()->user()->can('publish-hold.catalog'));

function escapeHtml(text) {
    return (text == null ? '' : String(text))
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;').replace(/'/g, '&#039;').replace(/`/g, '&#96;');
}
var OK_BADGE = '<span class="badge rounded-pill border border-success text-success">&#10003;</span>';
var NO_BADGE = '<span class="badge rounded-pill border border-danger text-danger">&#10007;</span>';
@include('admin.partials.cover-placeholder-tag-js')

function actionMenu(row) {
    var items = '<a class="dropdown-item" href="#" data-act="view">View Details</a>';
    if (CAN_PUBLISH_HOLD) {
        items += row.is_published == 1
            ? '<a class="dropdown-item" href="#" data-act="hold">Move to On-Hold</a>'
            : '<a class="dropdown-item" href="#" data-act="publish">Publish to OPAC</a>';
    }
    @can("edit.catalog")   items += '<a class="dropdown-item" href="#" data-act="edit">Edit</a>'; @endcan
    @can("delete.catalog") items += '<div class="dropdown-divider"></div><a class="dropdown-item text-danger" href="#" data-act="delete">Delete</a>'; @endcan
    return '<div class="btn-group btn-group-sm" role="group">' +
        '<button type="button" class="btn btn-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="color:#fff;">Action</button>' +
        '<div class="dropdown-menu dropdown-menu-end">' + items + '</div></div>';
}

$(function () {
    var dt = $('#tbl').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        pageLength: 25,
        order: [[2, 'asc']],   // col 0 = checkbox, 1 = ID, 2 = Title
        // Toolbar filters (status / type / author / year / resources) go with every request.
        ajax: { url: '{{ url("/admin/batch-records-json/" . $batch->id) }}', data: clFilters.params },
        language: { search: '', searchPlaceholder: 'Search title, author, type, year…' },
        columns: [
            bulkSelect.column,
            { data: 'id', className: 'tbl-id text-nowrap',
              render: function (d, type) { return type === 'display' ? '#' + escapeHtml(d) : d; } },
            { data: 'title_statement',
              render: function (d, type) { return type === 'display' ? '<span class="tbl-title">' + (d == null ? '' : d) + '</span>' : d; } },
            { data: 'material_type', className: 'text-nowrap',
              render: function (d, type) {
                if (type !== 'display') return d;
                return d ? '<span class="badge badge-type rounded-pill">' + escapeHtml(d) + '</span>' : '<span class="text-muted small">N/A</span>';
              } },
            { data: 'authors',
              render: function (d, type) {
                if (type !== 'display') return d;
                return d ? '<span class="tbl-authors" title="' + d + '">' + d + '</span>' : '<span class="text-muted small">N/A</span>';
              } },
            { data: 'pub_year', className: 'text-center',
              render: function (d, type) {
                if (type !== 'display') return d;
                var y = (d == null ? '' : String(d).replace(/[\[\]"'.]+/g, ''));
                return y !== '' ? y : '<span class="text-muted small">N/A</span>';
              } },
            { data: 'is_published', orderable: false, className: 'text-center',
              render: function (d, type) {
                if (type !== 'display') return d;
                return d == 1 ? '<span class="badge badge-status pub rounded-pill">Published</span>'
                              : '<span class="badge badge-status hold rounded-pill">On hold</span>';
              } },
            { data: 'has_multimedia', orderable: false, className: 'text-center',
              render: function (d, type) { return type === 'display' ? (d == 1 ? OK_BADGE : NO_BADGE) : d; } },
            { data: 'has_coverpage', orderable: false, className: 'text-center',
              render: function (d, type, row) { return type === 'display' ? (d == 1 ? OK_BADGE + (row.cover_placeholder == 1 ? PLACEHOLDER_TAG : '') : NO_BADGE) : d; } },
            { data: null, orderable: false, className: 'text-center',
              render: function (d, type, row) { return actionMenu(row); } },
        ]
    });
    bulkSelect.init(dt);

    // Whole row -> View Details (except clicks on the Action menu / any control).
    $('#tbl').on('click', 'tbody tr', function (e) {
        if ($(e.target).closest('a, button, input, .bs-cell, .btn-group, .dropdown-menu').length) return;
        var row = $('#tbl').DataTable().row(this).data();
        if (row && row.id != null) viewRecord(row.id, row.title_statement);
    });

    // Action menu (row data comes from DataTables, never from strings built into onclick).
    $('#tbl').on('click', '.dropdown-item[data-act]', function (e) {
        e.preventDefault();
        var row = $('#tbl').DataTable().row($(this).closest('tr')).data();
        if (!row) return;
        var act = $(this).data('act');
        if (act === 'view')    return viewRecord(row.id, row.title_statement);
        if (act === 'hold')    return setPublished(row, false);
        if (act === 'publish') return setPublished(row, true);
        if (act === 'edit')    { appBusy.show(); window.location.href = "{{ route('edit-record', ['id' => ':id']) }}".replace(':id', row.id); return; }
        if (act === 'delete')  return deleteRecord(row);
    });
});

// Back / Forward restores this page from cache: refresh, records may have changed meanwhile.
window.addEventListener('pageshow', function (e) {
    if (e.persisted && $.fn.dataTable.isDataTable('#tbl')) $('#tbl').DataTable().ajax.reload(null, false);
});

function viewRecord(id, title) {
    $('#catalog_id').val(id);
    $('#title').val($('<div>').html(title || '').text());   // titles arrive HTML-escaped
    $('#viewRecordFrm').submit();
}

function setPublished(row, publish) {
    $.post(publish ? '{{ url("/admin/publish-to-opac") }}' : '{{ url("/admin/hold-view-from-opac") }}', { _token: CSRF, catalog_id: row.id })
        .done(function () {
            Swal.fire({ toast: true, position: 'top-end', icon: 'success', showConfirmButton: false, timer: 2000,
                        title: publish ? 'Published to the OPAC' : 'Moved to On-Hold' });
            $('#tbl').DataTable().ajax.reload(null, false);
        })
        .fail(function (xhr) {
            if (xhr.status !== 403 && xhr.status !== 419) Swal.fire('Error', 'The record could not be updated. Please try again.', 'error');
        });
}

function deleteRecord(row) {
    Swal.fire({
        title: 'Delete this record?', html: '<strong>' + row.title_statement + '</strong>', icon: 'warning',
        showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#dc3545', cancelButtonText: 'Cancel', reverseButtons: true
    }).then(function (result) {
        if (!result.isConfirmed) return;
        appBusy.show();
        $('#frm_delete').prop('action', "{{ route('delete-record', ['id' => ':id']) }}".replace(':id', row.id)).submit();
    });
}
</script>
@endsection
