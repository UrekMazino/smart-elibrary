@extends("admin.layouts.master")

@section('css')
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.css') }}">
    <style>
      /* Compact, readable people table — same treatment as the catalog lists. */
      #tbl { font-size: 13px; width: 100% !important; }
      #tbl thead th {
        font-size: 11px; text-transform: uppercase; letter-spacing: .4px;
        color: #7987a1; white-space: nowrap; padding: 9px 10px;
        border-bottom: 2px solid #eef0f2; vertical-align: middle;
      }
      #tbl tbody td { padding: 8px 10px; vertical-align: middle !important; white-space: normal; }
      #tbl tbody tr:hover { background: #f7f9fc; }

      /* Name cell: initials avatar + name */
      .u-cell { display: flex; align-items: center; gap: 9px; }
      .u-avatar {
        flex: 0 0 30px; width: 30px; height: 30px; border-radius: 50%;
        background: #e4e9f2; color: #4a5a78; font-size: 11.5px; font-weight: 700;
        display: flex; align-items: center; justify-content: center; letter-spacing: .3px;
      }
      .u-name { font-weight: 600; color: #22283b; }
      .u-sub { color: #a3adc2; font-size: 11.5px; }

      /* `#tbl` prefix beats the template's `.badge { color:#fff }` (same specificity otherwise). */
      #tbl .role-pill {
        background: #e8eefc; color: #33509c; font-weight: 600; font-size: 11.5px;
        cursor: pointer; margin: 1px 3px 1px 0;
      }
      #tbl .role-pill:hover { background: #d7e2fb; }
      #tbl .role-none { background: #f4f5f7; color: #a3adc2; font-weight: 500; font-size: 11.5px; }

      .cell-muted { color: #7987a1; }
      .date-main { color: #33415c; } .date-time { color: #a3adc2; font-size: 11.5px; }

      /* Permission list inside the modal — two columns, easy to scan. */
      #permissionList { columns: 2; column-gap: 24px; margin: 0; padding-left: 18px; }
      #permissionList li { text-align: left; margin-bottom: 4px; font-size: 12.5px; break-inside: avoid; }
    </style>
@endsection

@section('content')
<div class="page-content">

    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
                        <div>
                            <h6 class="card-title mb-0">ADMINISTRATORS</h6>
                        </div>
                        <a href="{{ route('admin.create') }}" class="btn btn-primary btn-sm">
                            <i data-feather="user-plus" class="icon-sm me-1"></i>Add New Admin
                        </a>
                    </div>
                    <p class="text-muted mb-3" style="font-size: 12.5px;">
                        Staff accounts and the role each one is assigned. Click a role to see exactly
                        what it can do; use <strong>Action</strong> to change a role, reset a password,
                        or remove an account.
                    </p>

                    <div class="table-responsive">
                        <table id="tbl" class="table table-hover table-sm align-middle mb-0">
                          <thead>
                            <tr>
                              <th>Name</th>
                              <th style="width: 150px">Contact No.</th>
                              <th style="width: 230px">Email</th>
                              <th style="width: 200px">Role</th>
                              <th style="width: 130px">Registered</th>
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
@include('admin.modals.edit-admin-role-modal')

@include('admin.modals.view-rolepermissions-modal')
<form method="post" action="{{ url('/admin/patron-action') }}" id="frm" enctype="multipart/form-data">
  @csrf
  <input type="hidden" name="user_id" id="user_id">
  <input type="hidden" name="action" value="">
</form>
@endsection

@section('js')
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.js') }}"></script>
<script>

function escapeHtml(text) {
  return (text == null ? '' : String(text))
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;').replace(/`/g, '&#96;');
}

// "Joe Cris Viar" -> "JV"
function initials(name) {
  var parts = String(name || '?').trim().split(/\s+/);
  return ((parts[0] || '?')[0] + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase();
}

$(document).ready(function(){

$('#tbl').DataTable({
    processing: true,
    serverSide: true,
    autoWidth: false,
    pageLength: 25,
    order: [[0, 'asc']],            // default: Name A -> Z
    ajax: '{{ url("/superadmin/administrators/json") }}',
    language: { search: '', searchPlaceholder: 'Search name, email, contact…' },
    columns: [
        { data: 'name',
          render: function (d, type, row) {
            if (type !== 'display') return d;
            return '<div class="u-cell">' +
                     '<span class="u-avatar">' + escapeHtml(initials(d)) + '</span>' +
                     '<span><span class="u-name">' + escapeHtml(d) + '</span>' +
                       (row.location ? '<br><span class="u-sub">' + escapeHtml(row.location) + '</span>' : '') +
                     '</span>' +
                   '</div>';
          } },
        { data: 'contactnum', className: 'cell-muted text-nowrap',
          render: function (d, type) {
            if (type !== 'display') return d;
            return d ? escapeHtml(d) : '<span class="text-muted small">—</span>';
          } },
        { data: 'email', className: 'cell-muted',
          render: function (d, type) { return type === 'display' ? escapeHtml(d) : d; } },
        // Roles are rendered, not sorted (they don't live on the users table).
        { data: 'roles', orderable: false,
          render: function (roles, type) {
            if (type !== 'display') return (roles || []).join(', ');
            if (!roles || !roles.length) {
              return '<span class="badge role-none rounded-pill">No role assigned</span>';
            }
            // The row index is enough to find the row again on click — no data smuggled
            // through an onclick string (names/roles with quotes used to break it).
            return roles.map(function (r) {
              return '<span class="badge role-pill rounded-pill" data-role="' + escapeHtml(r) + '" ' +
                     'title="Click to view permissions">' + escapeHtml(r) + '</span>';
            }).join('');
          } },
        { data: 'created_at', className: 'text-nowrap',
          render: function (d, type) {
            if (type !== 'display') return d;
            var parts = String(d || '').split(' ');
            return '<span class="date-main">' + escapeHtml(parts[0] || '') + '</span>' +
                   (parts[1] ? '<br><span class="date-time">' + escapeHtml(parts[1].substring(0,5)) + '</span>' : '');
          } },
        { data: null, orderable: false, className: 'text-center',
          render: function (d, type, row) {
              return '<div class="btn-group btn-group-sm" role="group">' +
                       '<button type="button" class="btn btn-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>' +
                       '<div class="dropdown-menu dropdown-menu-end">' +
                         '<a class="dropdown-item" href="#" onclick="showEditAdminRoleModal(' + row.id + ')">Change Role</a>' +
                         '<a class="dropdown-item" href="#" onclick="generateLibraryCode(' + row.id + ')">Generate Library Code</a>' +
                         '<a class="dropdown-item" href="#" onclick="sendFrm(\'frm\',\'reset\',' + row.id + ')">Reset Password</a>' +
                         '<div class="dropdown-divider"></div>' +
                         '<a class="dropdown-item text-danger" href="#" onclick="sendFrm(\'frm\',\'delete\',' + row.id + ')">Delete</a>' +
                       '</div>' +
                     '</div>';
          } },
    ]
});

// Click a role pill -> permissions modal. Delegated so it survives redraws, and the row's
// data comes from the DataTables API rather than from interpolated HTML.
$('#tbl').on('click', '.role-pill', function () {
    var row = $('#tbl').DataTable().row($(this).closest('tr')).data();
    if (row) showPermissions(row.permissions, $(this).data('role'), row.name);
});

if (window.feather) feather.replace();

});


function sendFrm(frm,type,id = null)
{
    if(type == 'delete')
    {
      msg = "";
      $('#action').val('delete');
    }
    else
    {
      msg = "Default password is : `admin`";
      $('#action').val('reset');
    }

    Swal.fire({
      title: 'Are you sure?',
      text: msg,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonClass: 'me-2',
      confirmButtonText: 'Yes',
      cancelButtonText: 'No',
      reverseButtons: true
    }).then((result) => {
      if (result.value) {
        $("#user_id").val(id);
        $('#'+frm).submit();
      } else if (
        // Read more about handling dismissals
        result.dismiss === Swal.DismissReason.cancel
      ) {
        Swal.fire(
          'Cancelled',
          'Action Canceled!',
          'error'
        )
      }
    })
}

function showEditAdminRoleModal(id)
{
    $("#user-id").val(id);

    // Pre-select the admin's current role; fall back to the placeholder when they have none.
    var row = $('#tbl').DataTable().rows().data().toArray().find(function (r) { return r.id == id; });
    var roleId = row && row.role_ids && row.role_ids.length ? row.role_ids[0] : 0;
    $('#role-list').val(roleId);

    $('#editAdminRoleModal').modal('show');
}

function generateLibraryCode(id)
{
  Swal.fire({
      title: 'Are you sure?',
      text: 'This will generate a new library code for this user.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonClass: 'me-2',
      confirmButtonText: 'Yes',
      cancelButtonText: 'No',
      reverseButtons: true
    }).then((result) => {
      if (result.value) {

        // Make the AJAX call to the URL
        $.ajax({
          type: 'GET',
          url: '{{ url("/admin/update-librarycode") }}',
          data: {
              "id": id
          },
          dataType: 'json',
          success: function(res) {

          },
          error: function(e) {
              // Handle the error case
          }
        });

      } else if (
        // Read more about handling dismissals
        result.dismiss === Swal.DismissReason.cancel
      ) {
        Swal.fire(
          'Cancelled',
          'No library code generated.',
          'error'
        )
      }
    })
}

// permissions: array of display names for the role(s) this admin holds.
function showPermissions(permissions, role, title) {
  $('#viewRolePermissionModalTitle').text(String(title).toUpperCase() + ' — ' + String(role).toUpperCase());

  var list = permissions || [];
  $('#viewRolePermissionModalTitle2').html('<strong>PERMISSION(S)</strong> <span class="text-muted">(' + list.length + ')</span>');

  var $ol = $('#permissionList').empty();
  if (!list.length) {
    $ol.append('<li class="text-muted">This role has no permissions assigned.</li>');
  } else {
    list.forEach(function (permission) { $ol.append('<li>' + escapeHtml(permission) + '</li>'); });
  }

  $('#viewRolePermissionModal').modal('show');
  $('#viewRolePermissionModal').fadeIn();
}

</script>
@endsection
