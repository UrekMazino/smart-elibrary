@extends("admin.layouts.master")

@section('css')
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/css/dataTables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/css/buttons.bootstrap5.min.css') }}">

    <style>
      /* Compact, readable batch table */
      #tbl { font-size: 0.8125rem; width: 100% !important; }
      #tbl thead th {
        font-size: 0.6875rem; text-transform: uppercase; letter-spacing: .4px;
        color: #7987a1; white-space: nowrap; padding: 9px 10px;
        border-bottom: 2px solid #eef0f2; vertical-align: middle;
      }
      #tbl tbody td { padding: 7px 10px; vertical-align: middle !important; white-space: normal; }
      #tbl tbody tr { cursor: pointer; }
      #tbl tbody tr:hover { background: #f7f9fc; }
      .bl-id { color: #a3adc2; font-size: 0.75rem; }
      .bl-records { font-variant-numeric: tabular-nums; white-space: nowrap; }
      .bl-records .sep { color: #c4ccd8; margin: 0 4px; }
      /* Import report badges: records skipped / flagged for review at import. */
      .bl-issue { display: inline-block; margin-left: 6px; padding: 1px 7px; border-radius: 10px; font-size: 0.6875rem; font-weight: 600; text-decoration: none; }
      .bl-issue.skip { background: #fdecea; color: #b42318; }
      .bl-issue.review { background: #fff4e5; color: #a15c00; }
      .bl-issue:hover { filter: brightness(.95); }
      /* #tbl prefix beats the template's `.badge { color:#fff }`. */
      .bl-subject { font-weight: 600; color: #22283b; overflow-wrap: anywhere; }
      .bl-comment {
        display: inline-block; max-width: 460px; overflow: hidden; text-overflow: ellipsis;
        white-space: nowrap; vertical-align: middle; color: #5a6a85;
      }
      @media (max-width: 1200px) { .bl-comment { max-width: 260px; } }
      @media (max-width: 768px)  { .bl-comment { max-width: 150px; } }

      .pull-right { float: right !important; }
      .pull-left  { float: left !important; }
      .clearfix::after { content: ""; clear: both; display: table; }
    </style>
@endsection

@section('content')
<div class="page-content">

    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
          Admin / Batch List
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="clearfix mb-3">
                        <div class="pull-left">
                            <h6 class="card-title">List of Import Batches</h6>
                        </div>
                        <div class="pull-right">
                            @can('create.catalog.import')<a href="{{ url('/admin/create-batch/') }}" class="btn btn-info" style="color:white;">+ New Upload</a>@endcan
                          </div>
                    </div>

                    <div class="table-responsive">
                        <table id="tbl" class="table table-hover table-sm align-middle mb-0">
                          <thead>
                            <tr>
                              <th style="width: 60px">ID</th>
                              <th style="width: 230px">Initial / Current</th>
                              <th style="width: 30%">Subject</th>
                              <th>Comment</th>
                              <th style="width: 120px">Date Imported</th>
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

<form method="post" action="{{ url('/admin/batch-publish') }}" id="toggle-publish-frm">
	@csrf
  <input type="hidden" name="catalog_import_id" id="catalog_import_id">
  <input type="hidden" name="action" id="action">
</form>


<form method="post" action="{{ url('/admin/view-batch') }}" id="viewRecordFrm">
	@csrf
	<input type="hidden" name="batch_id" id="batch_id">
</form>

<form method="post" action="" id="frm_delete">
	@csrf
  @method('DELETE')
</form>

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

$(document).ready(function(){

    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }

    function actionMenu(row) {
      return '<div class="btn-group btn-group-sm" role="group">' +
          '<button type="button" class="btn btn-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>' +
          '<div class="dropdown-menu dropdown-menu-end">' +
              '<a class="dropdown-item" href="#" onclick="viewRecord(' + row.id + ')">View Details</a>' +
              '<a class="dropdown-item" href="{{ url("/admin/batch-report") }}/' + encodeURIComponent(row.id) + '">Import report</a>' +
              @can("publish-hold.catalog") '<a class="dropdown-item" href="#" onclick="togglePublishFrm(' + row.id + ',\'1\')">Publish All</a>' + @endcan
              @can("publish-hold.catalog") '<a class="dropdown-item" href="#" onclick="togglePublishFrm(' + row.id + ',\'2\')">Hold All</a>' + @endcan
              @can("delete.catalog.import") '<a class="dropdown-item text-danger" href="#" onclick="actionFrm(' + row.id + ',\'delete\')">Delete Batch</a>' + @endcan
          '</div>' +
      '</div>';
    }

    // DataTable — default order: newest import first (Date Imported desc)
    $('#tbl').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        order: [[4, 'desc']],
        ajax: '{{ url("/admin/batch-list-json") }}',
        columns: [
            { data: 'id', className: 'bl-id text-nowrap',
              render: function (d, type) { return type === 'display' ? '#' + esc(d) : d; } },
            { data: null, orderable: false, className: 'bl-records',
              render: function (d, type, row) {
                var init = row.init_no_of_records, cur = row.current_no_of_records;
                var curCls = (String(cur) !== String(init)) ? 'text-danger fw-semibold' : 'text-body';
                var report = '{{ url("/admin/batch-report") }}/' + encodeURIComponent(row.id);
                return '<span class="text-muted">' + esc(init) + '</span>' +
                       '<span class="sep">/</span>' +
                       '<span class="' + curCls + '">' + esc(cur) + '</span>' +
                       (row.skipped > 0 ? '<a class="bl-issue skip" href="' + report + '" title="Not imported: see the import report">' + esc(row.skipped) + ' skipped</a>' : '') +
                       (row.review_open > 0 ? '<a class="bl-issue review" href="' + report + '" title="Imported but flagged: see the import report">' + esc(row.review_open) + ' to review</a>' : '');
              } },
            { data: 'subject',
              render: function (d, type) {
                if (type !== 'display') return d;
                return d ? '<span class="bl-subject">' + esc(d) + '</span>' : '<span class="text-muted small">&mdash;</span>';
              } },
            { data: 'comment',
              render: function (d, type) {
                if (type !== 'display') return d;
                if (!d) return '<span class="text-muted small">&mdash;</span>';
                return '<span class="bl-comment" title="' + esc(d) + '">' + esc(d) + '</span>';
              } },
            { data: 'created_at', className: 'text-nowrap',
              render: function (data, type, row) {
                if (type === 'display' || type === 'filter') {
                    if (!data) return '';
                    var p = data.split('-');
                    return p[1] + '/' + p[2].substr(0, 2) + '/' + p[0].substr(2);
                }
                return data;
              } },
            { data: null, orderable: false, className: 'text-center',
              render: function (d, type, row) { return actionMenu(row); } },
        ]
    });

    // Whole row clickable -> View batch details. Ignore clicks on the Action menu.
    $('#tbl').on('click', 'tbody tr', function (e) {
        if ($(e.target).closest('a, button, .btn-group, .dropdown-menu').length) return;
        var data = $('#tbl').DataTable().row(this).data();
        if (data && data.id != null) { viewRecord(data.id); }
    });

});

function togglePublishFrm(id, action)
{
  let text = "";
  if(action == 1) {
      text = "This will publish all the records in this batch to OPAC view.";
  } else if(action == 2) {
      text = "This will hold all the records in this batch from OPAC view."
  }

  Swal.fire({
      title: 'Are you sure?',
      text: text,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonClass: 'me-2',
      confirmButtonText: 'Yes',
      cancelButtonText: 'No',
      reverseButtons: true
  }).then((result) => {
    if (result.value) {
      appBusy.show();

      let act = "{{ route('delete-batch', ['id' => ':id']) }}".replace(':id', id);
      $("#action").val(action);
      $("#catalog_import_id").val(id);
      $("#toggle-publish-frm").submit();

    } else if (
      result.dismiss === Swal.DismissReason.cancel
    ) {
      Swal.fire(
        'Cancelled',
        'Your data is safe!',
        'error'
      )
    }
  })

}

function viewRecord(id, title)
{
  $("#batch_id").val(id);
  $("#title").val(title);
  $("#viewRecordFrm").submit();
}

function copyFrm(id)
{
  $("#copy_catalog_id").val(id);
  $("#frm_add_copy").submit();
}


function actionFrm(id,action)
{
  $("#action_catalog_id").val(id);
  Swal.fire({
      title: 'Are you sure?',
      text: "This will also delete all the records in this batch.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonClass: 'me-2',
      confirmButtonText: 'Yes',
      cancelButtonText: 'No',
      reverseButtons: true
    }).then((result) => {
      if (result.value) {
        appBusy.show();

        let act = "{{ route('delete-batch', ['id' => ':id']) }}".replace(':id', id);

        $("#frm_delete").prop('action',act);
        $("#frm_delete").submit();

      } else if (
        result.dismiss === Swal.DismissReason.cancel
      ) {
        Swal.fire(
          'Cancelled',
          'Your data is safe!',
          'error'
        )
      }
    })

}

</script>
@if(session('success'))
<script>
  // Result of a Batch Import (with the per-material breakdown). When records were skipped or
  // flagged, offer the import report.
  $(function () {
    var report = @json(session('import_report') ? route('batch-report', ['id' => session('import_report')]) : null);
    Swal.fire({
      icon: report ? 'warning' : 'success',
      title: report ? 'Batch imported, with issues' : 'Batch imported',
      text: @json(session('success')),
      showCancelButton: !!report,
      confirmButtonText: report ? 'Open import report' : 'OK',
      cancelButtonText: 'Close',
      reverseButtons: true
    }).then(function (r) { if (report && r.isConfirmed) window.location = report; });
  });
</script>
@endif
@endsection
