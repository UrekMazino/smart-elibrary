@extends("admin.layouts.master")

@section('css')
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/css/dataTables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/css/buttons.bootstrap5.min.css') }}">

    <style>
      /* Compact, readable catalog table */
      #tbl { font-size: 0.8125rem; width: 100% !important; }
      #tbl thead th {
        font-size: 0.6875rem; text-transform: uppercase; letter-spacing: .4px;
        color: var(--ui-muted, #7987a1); white-space: nowrap; padding: 9px 10px;
        border-bottom: 2px solid var(--ui-border, #eef0f2); vertical-align: middle;
      }
      #tbl tbody td { padding: 6px 10px; vertical-align: middle !important; white-space: normal; }
      #tbl tbody tr { cursor: pointer; }
      #tbl tbody tr:hover { background: var(--ui-surface-2, #f7f9fc); }
      .tbl-id { color: var(--ui-muted-2, #a3adc2); font-size: 0.75rem; }
      /* Full MARC 245 title (all subfields), wrapped — never cut to one line. */
      .tbl-title { font-weight: 600; color: var(--ui-text, #22283b); display: block; white-space: normal; overflow-wrap: anywhere; min-width: 220px; }
      .tbl-authors {
        color: var(--ui-muted, #7987a1); display: inline-block; max-width: 220px;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: middle;
      }
      /* #tbl prefix beats the template's `.badge { color:#fff }` (same specificity otherwise). */
      #tbl .badge-type {
        background: var(--ui-surface-2, #e4e9f2); color: var(--ui-text, #33415c); font-weight: 600;
        font-size: 0.71875rem; letter-spacing: .2px;
      }
      @media (max-width: 1200px) { .tbl-authors { max-width: 150px; } }
      @media (max-width: 768px)  { .tbl-authors { max-width: 110px; } }

      #loadingSpinnerContainer { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 9999; display: none; }
      #loadingSpinner { display: inline-block; }
      #loadingOverlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 9998; display: none; }
    </style>
@endsection

@section('content')
<div class="page-content">
    <div id="loadingOverlay"></div>
    <div id="loadingSpinnerContainer">
        <div id="loadingSpinner" class="text-center">
            @php for($i = 0; $i < 3; $i++ ) { @endphp
            <div class="spinner-grow text-info" style="width: 2rem; height: 2rem;" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            @php } @endphp
        </div>
    </div>
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                      <h6 class="card-title mb-1">On-Hold</h6>
                      <p class="text-muted mb-0" style="font-size: 0.78125rem;">Records not published yet — hidden from the OPAC until you publish them.</p>
                    </div>
                    @include('admin.partials.catalog-bulk-toolbar', ['list' => 'onhold'])

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
                            <th style="width: 220px">Author</th>
                            <th style="width: 90px" class="text-center">Year</th>
                            <th style="width: 72px" class="text-center">Fulltext</th>
                            <th style="width: 78px" class="text-center">Cover</th>
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

<form method="post" action="{{ url('/admin/publish-to-opac') }}" id="publishFrm">
	@csrf
	<input type="hidden" name="catalog_id" id="publish_catalog_id">
</form>

<form method="post" action="{{ url('/admin/view-record') }}" id="viewRecordFrm">
	@csrf
	<input type="hidden" name="catalog_id" id="catalog_id">
  <input type="hidden" name="title" id="title">
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
@include('admin.partials.catalog-list-filters-js')
@include('admin.partials.catalog-bulk-js', ['list' => 'onhold'])

<script nonce="{{ cspNonce() }}">

$(document).ready(function(){

    // Escape for use inside HTML attributes and onclick() strings.
    function escapeHtml(text) {
      return (text == null ? '' : String(text))
                  .replace(/&/g, '&amp;')
                  .replace(/</g, '&lt;')
                  .replace(/>/g, '&gt;')
                  .replace(/"/g, '&quot;')
                  .replace(/'/g, '&#039;')
                  .replace(/`/g, '&#96;');
    }

    var OK_BADGE = '<span class="badge rounded-pill border border-success text-success">&#10003;</span>';
    var NO_BADGE = '<span class="badge rounded-pill border border-danger text-danger">&#10007;</span>';
    @include('admin.partials.cover-placeholder-tag-js')

    // The Action menu's items (data-call; onclick= is blocked by the Content Security Policy).
    AdminActions.allow(['viewRecord', 'holdMaterial', 'publishMaterial', 'actionFrm']);
    function actionMenu(row) {
      return '<div class="btn-group btn-group-sm" role="group">' +
          '<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>' +
          '<div class="dropdown-menu dropdown-menu-end">' +
              '<a class="dropdown-item" href="#" ' + AdminActions.attrs('viewRecord', [row.id, row.title_statement]) + '>View Details</a>' +
              @can("publish-hold.catalog") '<a class="dropdown-item" href="#" ' + AdminActions.attrs('publishMaterial', [row.id]) + '>Add to Record list</a>' + @endcan
              @can("edit.catalog") '<a class="dropdown-item" href="#" ' + AdminActions.attrs('actionFrm', [row.id, 'edit']) + '>Edit</a>' + @endcan
              @can("delete.catalog") '<a class="dropdown-item" href="#" ' + AdminActions.attrs('actionFrm', [row.id, 'delete']) + '>Delete</a>' + @endcan
          '</div>' +
      '</div>';
    }

    // Shared column definition, used by both the server-side table and the
    // client-side (filtered) rebuild so both look and sort identically.
    function makeColumns() {
      return [
        bulkSelect.column,
        { data: 'id', className: 'tbl-id text-nowrap',
          render: function (d, type) { return type === 'display' ? '#' + escapeHtml(d) : d; } },
        { data: 'title_statement',
          render: function (d, type) {
            if (type !== 'display') return d;
            return '<span class="tbl-title" title="' + escapeHtml(d) + '">' + (d == null ? '' : d) + '</span>';
          } },
        { data: 'material_type', className: 'text-nowrap',
          render: function (d, type) {
            if (type !== 'display') return d;
            return d ? '<span class="badge badge-type rounded-pill">' + d + '</span>' : '<span class="text-muted small">N/A</span>';
          } },
        { data: 'authors',
          render: function (d, type) {
            if (type !== 'display') return d;
            if (d == null || d === '') return '<span class="text-muted small">N/A</span>';
            return '<span class="tbl-authors" title="' + escapeHtml(d) + '">' + d + '</span>';
          } },
        { data: 'pub_year', className: 'text-center',
          render: function (d, type) {
            if (type !== 'display') return d;
            var y = (d == null ? '' : String(d).replace(/[\[\]"'.]+/g, ''));
            return y !== '' ? y : '<span class="text-muted small">N/A</span>';
          } },
        { data: 'has_multimedia', orderable: false, className: 'text-center',
          render: function (d, type) { return type === 'display' ? (d == 1 ? OK_BADGE : NO_BADGE) : d; } },
        { data: 'has_coverpage', orderable: false, className: 'text-center',
          render: function (d, type, row) { return type === 'display' ? (d == 1 ? OK_BADGE + (row.cover_placeholder == 1 ? PLACEHOLDER_TAG : '') : NO_BADGE) : d; } },
        { data: null, orderable: false, className: 'text-center',
          render: function (d, type, row) { return actionMenu(row); } },
      ];
    }

    // Main table: server-side, default sort = Title (A → Z), other columns sortable.
    var dt = $('#tbl').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        pageLength: 25,
        order: [[2, 'asc']],   // col 0 = checkbox, 1 = ID, 2 = Title
        // Toolbar filters (type / author / year / resources) are sent with every request.
        ajax: { url: '{{ url("/admin/onhold-list-json") }}', data: clFilters.params },
        columns: makeColumns(),
        language: { search: '', searchPlaceholder: 'Search title, author, type, year…' }
    });
    bulkSelect.init(dt);

    // Whole row clickable -> View Details. Ignore clicks on the Action menu / any control.
    $('#tbl').on('click', 'tbody tr', function (e) {
        if ($(e.target).closest('a, button, input, .bs-cell, .btn-group, .dropdown-menu').length) return;
        var data = $('#tbl').DataTable().row(this).data();
        if (data && data.id != null) { viewRecord(data.id, data.title_statement); }
    });

});

// Back / Forward restores this page from the browser cache: refresh the list, since records
// may have moved between the Record List and On-Hold while you were away.
window.addEventListener('pageshow', function (e) {
  if (!e.persisted || !$.fn.dataTable.isDataTable('#tbl')) return;
  var t = $('#tbl').DataTable();
  if (t.settings()[0].ajax) t.ajax.reload(null, false); else location.reload();
});

function refreshPage() {
    location.reload();
}

function refreshAjax() {
    let table = $('#tbl').DataTable();
    table.ajax.reload(null, false); // Reload the table data without resetting the pagination
}

function publishMaterial(id)
{
  $("#publish_catalog_id").val(id);
  $.ajax({
  url: $("#publishFrm").attr('action'),
  method: $("#publishFrm").attr('method'),
  data: $("#publishFrm").serialize(),
    success: function (response) {
      Swal.fire({
          title: 'Success',
          text: 'The record has been updated successfully!',
          icon: 'success',
          timer: 1000,
          showConfirmButton: false
      });
      refreshAjax();
    },
    error: function () {
        Swal.fire(
            'Error',
            'An error occurred while updating the record.',
            'error'
        );
    }
  });
}

function viewRecord(id, title)
{
  $("#catalog_id").val(id);
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
  // Opening the edit page changes nothing, so it goes straight there; only Delete asks first.
  if(action == 'edit') {
    appBusy.show();
    window.location.href = "{{ route('edit-record', ['id' => ':id']) }}".replace(':id', id);
    return;
  }

  $("#action_catalog_id").val(id);
  Swal.fire({
      title: 'Are you sure?',
      text: "",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonClass: 'me-2',
      confirmButtonText: 'Yes',
      cancelButtonText: 'No',
      reverseButtons: true
    }).then((result) => {
      if (result.value) {
        appBusy.show();

        if(action == 'delete') {
          let act = "{{ route('delete-record', ['id' => ':id']) }}".replace(':id', id);

          $("#frm_delete").prop('action',act);
          $("#frm_delete").submit();
        }

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
@endsection
