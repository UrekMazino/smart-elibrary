@extends("admin.layouts.master")

@section('css')
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.css') }}">
    <style>
      table td {
        word-break: break-word;
        vertical-align: top;
        white-space: normal !important;
      }

      .pull-right {
          float: right!important;
      }

      .pull-left {
          float: left!important;
      }

      .clearfix::after {
          content: "";
          clear: both;
          display: table;
      }
    </style>
@endsection

@section('content')
<div class="page-content">

    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            Admin / Permission
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="clearfix mb-3">
                        <div class="pull-left">
                            <h6 class="card-title">Permissions</h6>
                        </div>
                        <div class="pull-right">
                            @hasrole('developer') <a href="#" class="btn btn-info" style="color:white;" onclick="viewCreatePermissionModal()">+ Permission</a> @endhasrole
                          </div>      
                    </div>
                    <div class="table-responsive">
                        <table id="tbl" class="table">
                          <thead>
                            <tr>
                              <th>Id</th>
                              <th>Name</th>
                              <th>Display Name</th>
                              <th>Description</th>
                              <th>Action</th>
                            </tr>
                          </thead>
                          @foreach($permissions as $index => $permission)
                            <tr>
                                <td>{{ $index+1 }}</td>
                                <td id="td-name-{{ $permission->id }}">{{ $permission->name }}</td>
                                <td id="td-display-name-{{ $permission->id }}">{{ $permission->display_name }}</td>
                                <td id="td-description-{{ $permission->id }}">{{ $permission->description }}</td>
                                <th>
                                    <div class="btn-group" role="group">
                                        <button id="btnGroupDrop1" type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>
                                        <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                            <a class="dropdown-item" href="#" onclick="editPermission({{$permission->id}})">Edit</a>
                                            @hasrole('developer') <a class="dropdown-item" href="#" onclick="deletePermission({{$permission->id}})">Delete</a> @endhasrole
                                        </div>
                                    </div>
                                </th>
                            </tr>
                          @endforeach
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@include('admin.modals.add-permission-modal')
@include('admin.modals.edit-permission-modal')
<form method="post" action="" id="frm_delete">
	@csrf
  @method('DELETE')
</form>

@endsection

@section('js')
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.js') }}"></script>
<script>
  

    $(function() {
        'use strict';

        $(function() {
            $('#tbl').DataTable();
        });

    }); 

    function viewCreatePermissionModal()
    {
        $('#addPermissionModal').modal('show');
        $('#addPermissionModal').fadeIn();
    }

    function editPermission(id)
    {
        let name = $('#td-name-'+id).text();
        let displayName = $('#td-display-name-'+id).text();
        let description = $('#td-description-'+id).text();
        
        $('#editNameInput').val(name);
        $('#editDisplayNameInput').val(displayName);
        $('#editDescriptionInput').val(description);
        $('#permissionIdInput').val(id);
        
        $('#editPermissionModal').modal('show');
        $('#editPermissionModal').fadeIn();
    }

    function deletePermission(id)
    {
        let permissionName = $('#td-permission-name-'+id).text();

        Swal.fire({
            title: 'Delete ' + permissionName + ' permission?',
            text: "All users associated with this permission will be detached.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonClass: 'me-2',
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
            reverseButtons: true
            }).then((result) => {
            if (result.value) {
                $("#modalSaving").modal("toggle");

                let act = "{{ route('admin.delete_permission', ['id' => ':id']) }}".replace(':id', id);

                $("#frm_delete").prop('action',act);
                $("#frm_delete").submit();

            } else if (
                // Read more about handling dismissals
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

    function showSwal(type, message, icon) {
        if (type === 'custom-position') {
            Swal.fire({
            position: 'top-center',
            icon: icon,
            title: message,
            showConfirmButton: false,
            timer: 1500
            })
        } 
    }
</script>
@if (session('error'))
    <script>
        $(document).ready(function(){
            showSwal("custom-position", "{{ session('error') }}", "error");
        });
    </script>
@endif
@if (session('success'))
    <script>
        $(document).ready(function(){
            showSwal("custom-position", "{{ session('success') }}", "success");
        });
    </script>
@endif
@endsection