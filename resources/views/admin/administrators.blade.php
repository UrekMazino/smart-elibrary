@extends("admin.layouts.master")

@section('css')
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.css') }}">
    <style>
      /* Compact, readable people table — same treatment as the catalog lists. */
      #tbl { font-size: 0.8125rem; width: 100% !important; }
      #tbl thead th {
        font-size: 0.6875rem; text-transform: uppercase; letter-spacing: .4px;
        color: var(--ui-muted, #7987a1); white-space: nowrap; padding: 9px 10px;
        border-bottom: 2px solid var(--ui-border, #eef0f2); vertical-align: middle;
      }
      #tbl tbody td { padding: 8px 10px; vertical-align: middle !important; white-space: normal; }
      #tbl tbody tr:hover { background: var(--ui-surface-2, #f7f9fc); }

      /* Name cell: initials avatar + name */
      .u-cell { display: flex; align-items: center; gap: 9px; }
      .u-avatar {
        flex: 0 0 30px; width: 30px; height: 30px; border-radius: 50%;
        background: var(--ui-surface-2, #e4e9f2); color: var(--ui-text-2, #4a5a78); font-size: 0.71875rem; font-weight: 700;
        display: flex; align-items: center; justify-content: center; letter-spacing: .3px;
      }
      .u-name { font-weight: 600; color: var(--ui-text, #22283b); }
      .u-sub { color: var(--ui-muted-2, #a3adc2); font-size: 0.71875rem; }

      /* `#tbl` prefix beats the template's `.badge { color:#fff }` (same specificity otherwise). */
      #tbl .role-pill {
        background: var(--ui-navy-soft, #e8eefc); color: var(--ui-navy, #33509c); font-weight: 600; font-size: 0.71875rem;
        cursor: pointer; margin: 1px 3px 1px 0;
      }
      #tbl .role-pill:hover { background: var(--ui-navy-soft, #d7e2fb); }
      #tbl .role-none { background: var(--ui-surface-2, #f4f5f7); color: var(--ui-muted-2, #a3adc2); font-weight: 500; font-size: 0.71875rem; }

      .cell-muted { color: var(--ui-muted, #7987a1); }
      .date-main { color: var(--ui-text, #33415c); } .date-time { color: var(--ui-muted-2, #a3adc2); font-size: 0.71875rem; }

      /* Permission list inside the modal — two columns, easy to scan. */
      #permissionList { columns: 2; column-gap: 24px; margin: 0; padding-left: 18px; }
      #permissionList li { text-align: left; margin-bottom: 4px; font-size: 0.78125rem; break-inside: avoid; }
    </style>
@endsection

@section('content')
<div class="page-content">

    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('accounts') }}">Accounts</a></li>
            <li class="breadcrumb-item active" aria-current="page">Administrators</li>
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
                    <p class="text-muted mb-3" style="font-size: 0.78125rem;">
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
              // Your own account is managed from Profile; developer accounts only by a developer.
              if (row.is_self) return '<span class="badge bg-light text-muted" title="Manage your own account from Profile">You</span>';
              if (!row.manageable) return '<span class="text-muted small" title="Only a developer can manage this account">—</span>';
              return '<div class="btn-group btn-group-sm" role="group">' +
                       '<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>' +
                       '<div class="dropdown-menu dropdown-menu-end">' +
                         '<a class="dropdown-item" href="#" onclick="showEditAdminRoleModal(' + row.id + ')">Change Role</a>' +
                         '<a class="dropdown-item" href="#" data-act="library-code" data-id="' + row.id + '">Generate Library Code</a>' +
                         '<a class="dropdown-item" href="#" data-act="reset-password" data-id="' + row.id + '">Reset Password</a>' +
                         '<div class="dropdown-divider"></div>' +
                         '<a class="dropdown-item text-danger" href="#" data-act="delete" data-id="' + row.id + '" data-name="' + escapeHtml(row.name) + '">Delete</a>' +
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


// ---- Administrator account actions (Generate Library Code / Reset Password / Delete) ----
var ADMIN_ACTIONS = {
  'library-code': {
    title: 'Generate a new library code?', text: 'Their current library code will stop working.',
    confirm: 'Generate', method: 'POST', url: function (id) { return '{{ url("/superadmin/admin") }}/' + id + '/library-code'; },
    done: function (res) { showSecret(res.message, 'Library code', res.library_code); }
  },
  'reset-password': {
    title: 'Reset this administrator’s password?', text: 'A new temporary password is created and shown once, for you to pass on.',
    confirm: 'Reset password', method: 'POST', url: function (id) { return '{{ url("/superadmin/admin") }}/' + id + '/reset-password'; },
    done: function (res) { showSecret(res.message, 'Temporary password', res.password); }
  },
  'delete': {
    title: 'Delete this administrator?', text: 'They lose access to the admin panel immediately.',
    confirm: 'Delete', danger: true, method: 'DELETE', url: function (id) { return '{{ url("/superadmin/admin") }}/' + id; },
    done: function (res) { Swal.fire({ icon: 'success', title: 'Deleted', text: res.message, timer: 2200, showConfirmButton: false }); }
  }
};

$(document).on('click', '#tbl [data-act]', function (e) {
  e.preventDefault();
  var act = ADMIN_ACTIONS[$(this).data('act')], id = $(this).data('id'), name = $(this).data('name');
  Swal.fire({
    title: act.title,
    html: (name ? '<strong>' + escapeHtml(name) + '</strong><br>' : '') + act.text,
    icon: 'warning', showCancelButton: true, reverseButtons: true, focusCancel: !!act.danger,
    confirmButtonText: act.confirm, cancelButtonText: 'Cancel',
    confirmButtonColor: act.danger ? '#dc3545' : undefined,
    showLoaderOnConfirm: true,
    preConfirm: function () {
      return $.ajax({ type: act.method, url: act.url(id), data: { _token: '{{ csrf_token() }}' }, dataType: 'json' })
        .catch(function (xhr) {
          Swal.showValidationMessage((xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.');
        });
    },
    allowOutsideClick: function () { return !Swal.isLoading(); }
  }).then(function (result) {
    if (!result.isConfirmed || !result.value) return;
    $('#tbl').DataTable().ajax.reload(null, false);
    act.done(result.value);
  });
});

// Show a one-time value (temporary password / library code) with a copy button.
function showSecret(message, label, value) {
  Swal.fire({
    icon: 'success', title: message,
    html: '<div class="text-muted small mb-1">' + escapeHtml(label) + '</div>' +
          '<code style="font-size:1.125rem;letter-spacing:1px;user-select:all;">' + escapeHtml(value) + '</code>',
    showCancelButton: true, confirmButtonText: 'Copy', cancelButtonText: 'Close', cancelButtonColor: '#6c757d', reverseButtons: true
  }).then(function (r) {
    if (r.isConfirmed && navigator.clipboard) navigator.clipboard.writeText(value);
  });
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


// Result of Change Role (a normal form post) comes back as a flash message.
$(function () {
  @if (session('success')) Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: @json(session('success')), showConfirmButton: false, timer: 2600 }); @endif
  @if (session('error'))   Swal.fire({ icon: 'error', title: 'Not changed', text: @json(session('error')) }); @endif
  @if ($errors->any())     Swal.fire({ icon: 'error', title: 'Not changed', text: @json($errors->first()) }); @endif
});
</script>
@endsection
