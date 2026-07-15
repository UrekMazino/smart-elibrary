@extends("admin.layouts.master")

@section('css')
    <style>
      .ac-head { display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:10px; margin-bottom:18px; }
      .ac-head h4 { font-weight:700; color:#22283b; margin:0; }
      .ac-head p  { color:#8a94a6; margin:2px 0 0; font-size:13px; }
      .page-content .card { border:1px solid #eef0f4; border-radius:12px; box-shadow:0 1px 2px rgba(20,30,60,.04); }
      .mono { font-family:ui-monospace,Menlo,Consolas,monospace; font-size:11px; color:#a3adc2; }

      /* Roles list */
      table.roles-tbl { font-size:13px; width:100%; }
      table.roles-tbl thead th { font-size:11px; text-transform:uppercase; letter-spacing:.4px; color:#7987a1; padding:9px 10px; border-bottom:2px solid #eef0f2; white-space:nowrap; }
      table.roles-tbl tbody td { padding:9px 10px; vertical-align:middle; border-bottom:1px solid #f2f4f8; }
      .role-name { font-weight:600; color:#22283b; }
      .pill { display:inline-block; min-width:26px; text-align:center; background:#eef1f8; color:#4a5a78; border-radius:20px; padding:2px 9px; font-size:12px; font-weight:600; }

      /* Access matrix */
      .matrix-hint { color:#8a94a6; font-size:12.5px; margin:0 0 14px; }
      .matrix-wrap { overflow:auto; max-height:70vh; border:1px solid #eef0f4; border-radius:10px; }
      table.matrix { border-collapse:separate; border-spacing:0; width:100%; font-size:13px; }
      table.matrix th, table.matrix td { padding:8px 12px; border-bottom:1px solid #eef0f4; }
      table.matrix thead th { position:sticky; top:0; z-index:2; background:#fff; font-size:11px; text-transform:uppercase; letter-spacing:.3px; color:#7987a1; border-bottom:2px solid #eef0f2; }
      table.matrix thead th.role-col { text-align:center; min-width:96px; }
      table.matrix thead th.role-col small { display:block; font-weight:400; text-transform:none; letter-spacing:0; }
      table.matrix th.perm-col, table.matrix td.perm-cell { position:sticky; left:0; z-index:1; background:#fff; text-align:left; min-width:290px; box-shadow:1px 0 0 #eef0f4; }
      table.matrix thead th.perm-col { z-index:3; }
      .perm-name { font-weight:600; color:#22283b; display:block; line-height:1.3; }
      tr.mx-group td { background:#f4f6fb; font-weight:700; font-size:11px; text-transform:uppercase; letter-spacing:.5px; color:#5a6a85; padding:6px 12px; position:sticky; left:0; }
      table.matrix tbody tr:hover td { background:#f9fbfe; }
      table.matrix tbody tr:hover td.perm-cell { background:#f9fbfe; }
      .mx-cell { text-align:center; }
      .mx-toggle { display:inline-block; width:20px; height:20px; border-radius:5px; border:1.5px solid #cfd6e4; background:#fff; cursor:pointer; position:relative; transition:background .12s, border-color .12s; vertical-align:middle; }
      .mx-toggle:hover { border-color:#00a496; }
      .mx-toggle.on { background:#00a496; border-color:#00a496; }
      .mx-toggle.on::after { content:''; position:absolute; left:6px; top:2px; width:5px; height:10px; border:solid #fff; border-width:0 2px 2px 0; transform:rotate(45deg); }
      .mx-toggle.busy { opacity:.4; pointer-events:none; }
    </style>
@endsection

@section('content')
<div class="page-content">

    <div class="ac-head">
      <div>
        <h4>Roles &amp; Access</h4>
        <p>Define roles, then grant or revoke permissions in the matrix — changes save instantly.</p>
      </div>
      <button class="btn btn-primary btn-sm" onclick="viewCreateRoleModal()"><i data-feather="plus" class="icon-sm me-1"></i>New role</button>
    </div>

    {{-- ===== Roles (metadata) ===== --}}
    <div class="card mb-3">
      <div class="card-body">
        <h6 class="card-title">Roles</h6>
        <div class="table-responsive">
          <table class="roles-tbl">
            <thead>
              <tr>
                <th>Role</th>
                <th>Description</th>
                <th class="text-center" style="width:110px">Permissions</th>
                <th class="text-center" style="width:80px">Users</th>
                <th class="text-center" style="width:90px">Action</th>
              </tr>
            </thead>
            <tbody>
              @foreach($roles as $role)
                <tr>
                  <td><span class="role-name">{{ $role->display_name ?: $role->name }}</span><br><span class="mono">{{ $role->name }}</span></td>
                  <td class="text-muted">{{ $role->description ?: '—' }}</td>
                  <td class="text-center"><span class="pill" id="role-perm-count-{{ $role->id }}">{{ count($rolePerms[$role->id]) }}</span></td>
                  <td class="text-center">{{ $userCounts[$role->id] }}</td>
                  <td class="text-center">
                    <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-outline-info btn-sm edit-role-btn"
                        data-id="{{ $role->id }}" data-name="{{ $role->name }}"
                        data-display="{{ $role->display_name }}" data-desc="{{ $role->description }}">Edit</button>
                      <button type="button" class="btn btn-outline-danger btn-sm delete-role-btn"
                        data-id="{{ $role->id }}" data-name="{{ $role->display_name ?: $role->name }}">Delete</button>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- ===== Access matrix ===== --}}
    <div class="card">
      <div class="card-body">
        <h6 class="card-title">Access Matrix</h6>
        <p class="matrix-hint">Tick a cell to grant a permission to a role, untick to revoke. The <strong>developer</strong> superuser always has full access and isn't listed.</p>
        <div class="matrix-wrap">
          <table class="matrix">
            <thead>
              <tr>
                <th class="perm-col">Permission</th>
                @foreach($roles as $role)
                  <th class="role-col">{{ $role->display_name ?: $role->name }}<small class="mono">{{ $role->name }}</small></th>
                @endforeach
              </tr>
            </thead>
            <tbody>
              @foreach($modules as $module => $perms)
                <tr class="mx-group"><td colspan="{{ count($roles) + 1 }}">{{ $module }}</td></tr>
                @foreach($perms as $perm)
                  <tr>
                    <td class="perm-cell">
                      <span class="perm-name">{{ $perm->display_name ?: $perm->name }}</span>
                      <span class="mono">{{ $perm->name }}</span>
                    </td>
                    @foreach($roles as $role)
                      @php $on = in_array($perm->id, $rolePerms[$role->id]); @endphp
                      <td class="mx-cell">
                        <span class="mx-toggle {{ $on ? 'on' : '' }}" data-role="{{ $role->id }}" data-perm="{{ $perm->id }}" role="button" tabindex="0" aria-pressed="{{ $on ? 'true' : 'false' }}" title="{{ ($role->display_name ?: $role->name) }} · {{ $perm->display_name ?: $perm->name }}"></span>
                      </td>
                    @endforeach
                  </tr>
                @endforeach
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
</div>

@include('admin.modals.add-role-modal')
@include('admin.modals.edit-role-modal')
<form method="post" action="" id="frm_delete">
    @csrf
    @method('DELETE')
</form>
@endsection

@section('js')
<script>
$(function () {
    if (window.feather) feather.replace();

    // ---- Access matrix: grant/revoke a (role, permission) on click ----
    $(document).on('click', '.mx-toggle', function () {
        var $t = $(this);
        if ($t.hasClass('busy')) return;
        var roleId = $t.data('role'), permId = $t.data('perm');
        $t.addClass('busy');
        $.ajax({
            type: 'POST',
            url: "{{ route('admin.role_toggle_permission') }}",
            data: { _token: "{{ csrf_token() }}", role_id: roleId, permission_id: permId },
            dataType: 'json'
        }).done(function (res) {
            $t.toggleClass('on', !!res.granted).attr('aria-pressed', res.granted ? 'true' : 'false');
            $('#role-perm-count-' + roleId).text(res.permCount);
        }).fail(function () {
            Swal.fire('Error', 'Could not update the permission. Please try again.', 'error');
        }).always(function () {
            $t.removeClass('busy');
        });
    });
    // keyboard toggle (space/enter)
    $(document).on('keydown', '.mx-toggle', function (e) {
        if (e.key === ' ' || e.key === 'Enter') { e.preventDefault(); $(this).trigger('click'); }
    });

    // ---- Edit role (metadata only) ----
    $(document).on('click', '.edit-role-btn', function () {
        var d = $(this).data();
        $('#roleIdInput').val(d.id);
        $('#roleNameInput').val(d.name);
        $('#editDisplayNameInput').val(d.display || '');
        $('#editDescriptionInput').val(d.desc || '');
        if (window.feather) feather.replace();
        $('#editRoledModal').modal('show');
    });

    // ---- Delete role ----
    $(document).on('click', '.delete-role-btn', function () {
        var id = $(this).data('id'), name = $(this).data('name');
        Swal.fire({
            title: 'Delete "' + name + '" role?',
            text: 'Users with this role lose its access.',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Yes, delete', cancelButtonText: 'No', reverseButtons: true
        }).then(function (result) {
            if (!result.value) return;
            var act = "{{ route('admin.delete_role', ['id' => ':id']) }}".replace(':id', id);
            $('#frm_delete').prop('action', act).submit();
        });
    });
});

function viewCreateRoleModal() {
    $('#addRoledModal').modal('show');
}

function showSwal(type, message, icon) {
    Swal.fire({ position: 'top-end', toast: true, icon: icon, title: message, showConfirmButton: false, timer: 2000 });
}
</script>
@if (session('error'))   <script>$(function(){ showSwal('t', "{{ session('error') }}", 'error'); });</script> @endif
@if (session('success')) <script>$(function(){ showSwal('t', "{{ session('success') }}", 'success'); });</script> @endif
@endsection
