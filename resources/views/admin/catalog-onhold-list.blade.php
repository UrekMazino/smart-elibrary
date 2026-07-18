@extends("admin.layouts.master")

@section('css')
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/css/dataTables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/css/buttons.bootstrap5.min.css') }}">

    <style>
      /* Compact, readable catalog table */
      #tbl { font-size: 13px; width: 100% !important; }
      #tbl thead th {
        font-size: 11px; text-transform: uppercase; letter-spacing: .4px;
        color: #7987a1; white-space: nowrap; padding: 9px 10px;
        border-bottom: 2px solid #eef0f2; vertical-align: middle;
      }
      #tbl tbody td { padding: 6px 10px; vertical-align: middle !important; white-space: normal; }
      #tbl tbody tr { cursor: pointer; }
      #tbl tbody tr:hover { background: #f7f9fc; }
      .tbl-id { color: #a3adc2; font-size: 12px; }
      .tbl-title {
        font-weight: 600; color: #22283b; display: inline-block; max-width: 420px;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: middle;
      }
      .tbl-authors {
        color: #7987a1; display: inline-block; max-width: 220px;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: middle;
      }
      /* #tbl prefix beats the template's `.badge { color:#fff }` (same specificity otherwise). */
      #tbl .badge-type {
        background: #e4e9f2; color: #33415c; font-weight: 600;
        font-size: 11.5px; letter-spacing: .2px;
      }
      @media (max-width: 1200px) { .tbl-title { max-width: 260px; } .tbl-authors { max-width: 150px; } }
      @media (max-width: 768px)  { .tbl-title { max-width: 170px; } .tbl-authors { max-width: 110px; } }

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
                  <div class="d-flex justify-content-between align-items-baseline mb-3">
                    <h6 class="card-title mb-0">CATALOG ONHOLD</h6>
                    <div class="btn-group btn-group-sm me-2">
                      <button class="btn btn-outline-info btn-sm" onclick="publishAllOnhold()">Publish All</button>
                      <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown" aria-expanded="false">Export MARC</button>
                      <ul class="dropdown-menu" aria-labelledby="exportDropdown">
                          <li><h6 class="dropdown-header">On-hold list</h6></li>
                          <li><a class="dropdown-item" href="{{ route('export-records', ['format' => 'mrc', 'status' => 'onhold']) }}">MARC21 binary (.mrc)</a></li>
                          <li><a class="dropdown-item" href="{{ route('export-records', ['format' => 'xml', 'status' => 'onhold']) }}">MARCXML (.xml)</a></li>
                          <li><hr class="dropdown-divider"></li>
                          <li><h6 class="dropdown-header">Entire catalog</h6></li>
                          <li><a class="dropdown-item" href="{{ route('export-records', ['format' => 'mrc']) }}">MARC21 binary (.mrc)</a></li>
                          <li><a class="dropdown-item" href="{{ route('export-records', ['format' => 'xml']) }}">MARCXML (.xml)</a></li>
                      </ul>
                      <button class="btn btn-outline-info btn-sm dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                         Resource Filter
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="filterDropdown">
                          <li><button class="dropdown-item filter-btn active" id="all" type="button" onclick="refreshPage()">All</button></li>
                          <li><button class="dropdown-item filter-btn" id="fulltext-only" type="button">w/ Fulltext</button></li>
                          <li><button class="dropdown-item filter-btn" id="coverphoto-only" type="button">w/ Coverphoto</button></li>
                          <li><button class="dropdown-item filter-btn" id="both" type="button">w/ Fulltext & Coverphoto Only</button></li>
                          <li><button class="dropdown-item filter-btn" id="without-fulltext" type="button">w/o Fulltext</button></li>
                          <li><button class="dropdown-item filter-btn" id="without-coverphoto" type="button">w/o Coverphoto</button></li>
                          <li><button class="dropdown-item filter-btn" id="without-both" type="button">w/o Fulltext & Coverphoto</button></li>
                      </ul>
                  </div>

                  </div>
                  <div class="table-responsive">
                      <table id="tbl" class="table table-hover table-sm align-middle mb-0">
                        <thead>
                          <tr>
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

<script>

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

    function actionMenu(row) {
      var escapedTitle = escapeHtml(row.title_statement);
      return '<div class="btn-group btn-group-sm" role="group">' +
          '<button type="button" class="btn btn-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>' +
          '<div class="dropdown-menu dropdown-menu-end">' +
              '<a class="dropdown-item" href="#" onclick="viewRecord(' + row.id + ', \'' + escapedTitle + '\')">View Details</a>' +
              @can("publish-hold.catalog") '<a class="dropdown-item" href="#" onclick="publishMaterial(' + row.id + ')">Add to Record list</a>' + @endcan
              @can("edit.catalog") '<a class="dropdown-item" href="#" onclick="actionFrm(' + row.id + ',\'edit\')">Edit</a>' + @endcan
              @can("delete.catalog") '<a class="dropdown-item" href="#" onclick="actionFrm(' + row.id + ',\'delete\')">Delete</a>' + @endcan
          '</div>' +
      '</div>';
    }

    // Shared column definition, used by both the server-side table and the
    // client-side (filtered) rebuild so both look and sort identically.
    function makeColumns() {
      return [
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
          render: function (d, type) { return type === 'display' ? (d == 1 ? OK_BADGE : NO_BADGE) : d; } },
        { data: null, orderable: false, className: 'text-center',
          render: function (d, type, row) { return actionMenu(row); } },
      ];
    }

    $('.filter-btn').on('click', function() {
      $('.filter-btn').removeClass('active');
      $(this).addClass('active');
      $('#loadingOverlay').show();
      $('#loadingSpinnerContainer').show();

      let selectedFilter = $(this).attr("id");
      $.ajax({
        type: 'GET',
        url: '{{ url("/admin/onhold-list-json") }}',
        data: {
            "filter_search": selectedFilter,
        },
        dataType: 'json',
        success: function(res) {
          let data = $.map(res.aaData, function(value) { return value; });
          let table = $('#tbl').DataTable();
          table.destroy();
          $('#tbl').DataTable({
            data: data,
            autoWidth: false,
            pageLength: 25,
            order: [[1, 'asc']],
            columns: makeColumns(),
          });
          $('#loadingOverlay').hide();
          $('#loadingSpinnerContainer').hide();
        },
        error: function(e) {
            console.log("Something's wrong!");
            $('#loadingOverlay').hide();
            $('#loadingSpinnerContainer').hide();
        }
      });
    });

    // Main table: server-side, default sort = Title (A → Z), other columns sortable.
    $('#tbl').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        pageLength: 25,
        order: [[1, 'asc']],
        ajax: '{{ url("/admin/onhold-list-json") }}',
        columns: makeColumns(),
        language: { search: '', searchPlaceholder: 'Search title, author, type, year…' }
    });

    // Whole row clickable -> View Details. Ignore clicks on the Action menu / any control.
    $('#tbl').on('click', 'tbody tr', function (e) {
        if ($(e.target).closest('a, button, .btn-group, .dropdown-menu').length) return;
        var data = $('#tbl').DataTable().row(this).data();
        if (data && data.id != null) { viewRecord(data.id, data.title_statement); }
    });

});

function refreshPage() {
    location.reload();
}

function refreshAjax() {
    let table = $('#tbl').DataTable();
    table.ajax.reload(null, false); // Reload the table data without resetting the pagination
}

function publishAllOnhold()
{
  Swal.fire({
      title: 'Are you sure?',
      text: "All items in the On-Hold list will be published.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonClass: 'me-2',
      confirmButtonText: 'Yes',
      cancelButtonText: 'No',
      reverseButtons: true
    }).then((result) => {
      if (result.value) {
        $("#modalSaving").modal("toggle");
        $.ajax({
          url: "{{ url('/admin/publish-all-onhold') }}",
          method: "POST",
          data: {
            _token: "{{ csrf_token() }}"
          },
          success: function (response) {
            Swal.fire({
                title: 'Success',
                text: 'The record has been updated successfully!',
                icon: 'success',
                timer: 1000,
                showConfirmButton: false
            });
            $("#modalSaving").modal("toggle");
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
      } else if (
        result.dismiss === Swal.DismissReason.cancel
      ) {
        Swal.fire(
          'Cancelled',
          'No changes made!',
          'error'
        )
      }
    })
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
        $("#modalSaving").modal("toggle");

        if(action == 'edit') {
          let act = "{{ route('edit-record', ['id' => ':id']) }}".replace(':id', id);
          window.location.href = act;
        }

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
