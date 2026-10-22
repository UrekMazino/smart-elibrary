@extends("admin.layouts.master")

@section('css')
    <style>
      .ac-head { display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:10px; margin-bottom:18px; }
      .ac-head h4 { font-weight:700; color:var(--ui-text, #22283b); margin:0; }
      .ac-head p  { color:var(--ui-muted, #8a94a6); margin:2px 0 0; font-size:0.8125rem; }
      .page-content .card { border:1px solid var(--ui-border, #eef0f4); border-radius:12px; box-shadow:0 1px 2px rgba(20,30,60,.04); }
      .mono { font-family:ui-monospace,Menlo,Consolas,monospace; font-size:0.6875rem; color:var(--ui-muted-2, #a3adc2); }
      .pill { display:inline-block; min-width:24px; text-align:center; background:var(--ui-surface-2, #eef1f8); color:var(--ui-text-2, #4a5a78); border-radius:20px; padding:2px 9px; font-size:0.75rem; font-weight:600; }

      .perm-mod-head { display:flex; align-items:center; gap:10px; margin-bottom:6px; }
      .perm-mod-head h6 { font-size:0.75rem; text-transform:uppercase; letter-spacing:.6px; color:#00a496; font-weight:700; }
      .perm-mod-head .count { font-size:0.6875rem; color:var(--ui-muted, #9aa4b8); }
      table.perm-tbl { font-size:0.8125rem; width:100%; }
      table.perm-tbl thead th { font-size:0.6875rem; text-transform:uppercase; letter-spacing:.4px; color:var(--ui-muted, #7987a1); padding:8px 10px; border-bottom:2px solid var(--ui-border, #eef0f2); white-space:nowrap; }
      table.perm-tbl tbody td { padding:8px 10px; vertical-align:middle; border-bottom:1px solid var(--ui-border, #f2f4f8); }
      table.perm-tbl tbody tr:hover td { background:var(--ui-surface-2, #f9fbfe); }
      .perm-name { font-weight:600; color:var(--ui-text, #22283b); }
    </style>
@endsection

@section('content')
<div class="page-content">
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('accounts') }}">Accounts</a></li>
            <li class="breadcrumb-item active" aria-current="page">Permissions</li>
        </ol>
    </nav>

    <div class="ac-head">
      <div>
        <h4>Permissions</h4>
        <p>The individual capabilities you assign to roles, grouped by module. Manage who gets them in <a href="{{ route('admin.list_roles') }}">Roles &amp; Access</a>.</p>
      </div>
      @hasrole('developer')
        <button class="btn btn-primary btn-sm" onclick="viewCreatePermissionModal()"><i data-feather="plus" class="icon-sm me-1"></i>New permission</button>
      @endhasrole
    </div>

    @foreach($modules as $module => $perms)
      <div class="card mb-3">
        <div class="card-body">
          <div class="perm-mod-head">
            <h6 class="mb-0">{{ $module }}</h6>
            <span class="count">{{ count($perms) }} permission{{ count($perms) == 1 ? '' : 's' }}</span>
          </div>
          <div class="table-responsive">
            <table class="perm-tbl">
              <thead>
                <tr>
                  <th>Permission</th>
                  <th>Description</th>
                  <th class="text-center" style="width:80px">Roles</th>
                  <th class="text-center" style="width:90px">Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach($perms as $perm)
                  <tr>
                    <td><span class="perm-name">{{ $perm->display_name ?: $perm->name }}</span><br><span class="mono">{{ $perm->name }}</span></td>
                    <td class="text-muted">{{ $perm->description ?: '—' }}</td>
                    <td class="text-center"><span class="pill">{{ $roleCounts[$perm->id] ?? 0 }}</span></td>
                    <td class="text-center">
                      <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-info btn-sm edit-perm-btn"
                          data-id="{{ $perm->id }}" data-name="{{ $perm->name }}"
                          data-display="{{ $perm->display_name }}" data-desc="{{ $perm->description }}">Edit</button>
                        @hasrole('developer')
                          @if(in_array($perm->id, $inUse))
                            <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Used by the app — can't be deleted">Delete</button>
                          @else
                            <button type="button" class="btn btn-outline-danger btn-sm delete-perm-btn"
                              data-id="{{ $perm->id }}" data-name="{{ $perm->display_name ?: $perm->name }}">Delete</button>
                          @endif
                        @endhasrole
                      </div>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    @endforeach
</div>

@include('admin.modals.add-permission-modal')
@include('admin.modals.edit-permission-modal')
<form method="post" action="" id="frm_delete">
    @csrf
    @method('DELETE')
</form>
@endsection

@section('js')
<script>
$(function () {
    if (window.feather) feather.replace();

    $(document).on('click', '.edit-perm-btn', function () {
        var d = $(this).data();
        $('#permissionIdInput').val(d.id);
        $('#editNameInput').val(d.name);
        $('#editDisplayNameInput').val(d.display || '');
        $('#editDescriptionInput').val(d.desc || '');
        $('#editPermissionModal').modal('show');
    });

    $(document).on('click', '.delete-perm-btn', function () {
        var id = $(this).data('id'), name = $(this).data('name');
        Swal.fire({
            title: 'Delete "' + name + '" permission?',
            text: 'It will be removed from every role that has it.',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Yes, delete', cancelButtonText: 'No', reverseButtons: true
        }).then(function (result) {
            if (!result.value) return;
            var act = "{{ route('admin.delete_permission', ['id' => ':id']) }}".replace(':id', id);
            $('#frm_delete').prop('action', act).submit();
        });
    });
});

function viewCreatePermissionModal() {
    $('#addPermissionModal').modal('show');
}

function showSwal(type, message, icon) {
    Swal.fire({ position: 'top-end', toast: true, icon: icon, title: message, showConfirmButton: false, timer: 2000 });
}
</script>
@if (session('error'))   <script>$(function(){ Swal.fire({ icon: 'error', title: 'Not done', text: @json(session('error')) }); });</script> @endif
@if (session('success')) <script>$(function(){ showSwal('t', "{{ session('success') }}", 'success'); });</script> @endif
@if ($errors->any())     <script>$(function(){ Swal.fire({ icon: 'error', title: 'Not saved', text: @json($errors->first()) }); });</script> @endif
@endsection
