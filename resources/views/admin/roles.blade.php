@extends("admin.layouts.master")

@section('css')
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
            Admin / Roles
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="clearfix mb-3">
                        <div class="pull-left">
                            <h6 class="card-title">Roles</h6>
                        </div>
                        <div class="pull-right">
                            <a href="#" class="btn btn-primary" style="color:white;" onclick="viewCreateRoleModal()">+ Role</a>
                            
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
                          @foreach($roles as $index => $role)
                            <tr>
                                <td>{{ $index+1 }}</td>
                                <td id="td-role-name-{{ $role->id }}">{{ $role->name }}</td>
                                <td id="td-display-name-{{ $role->id }}">{{ $role->display_name }}</td>
                                <td id="td-description-{{ $role->id }}">{{ $role->description }}</td>
                                <th>
                                    <div class="btn-group" role="group">
                                        <button id="btnGroupDrop1" type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>
                                        <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                            <a class="dropdown-item" href="#" onclick="editRole({{$role->id}})">Edit</a>
                                            <a class="dropdown-item" href="#" onclick="deleteRole({{$role->id}})">Delete</a>
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
@include('admin.modals.add-role-modal')
@include('admin.modals.edit-role-modal')
<form method="post" action="" id="frm_delete">
	@csrf
  @method('DELETE')
</form>

@endsection

@section('js')
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  

    $(function() {
        'use strict';

        $(function() {
            $('#tbl').DataTable();
        });

    }); 

    function viewCreateRoleModal()
    {
        $('#addRoledModal').modal('show');
        $('#addRoledModal').fadeIn();
    }

    function editRole(id)
    {
        let roleName = $('#td-role-name-'+id).text();
        let displayName = $('#td-display-name-'+id).text();
        let description = $('#td-description-'+id).text();
        
        $('#roleNameInput').val(roleName);
        $('#editDisplayNameInput').val(displayName);
        $('#editDescriptionInput').val(description);
        
        $('#roleIdInput').val(id);
        
        $('#editRoledModal').modal('show');
        $('#editRoledModal').fadeIn();


        $.ajax({
        type: 'GET',
        url: '{{ route("admin.role_permissions") }}',
        data: {
            "role_id": id,
        },
        
        dataType: 'json',
        success: function(res) {
          let searchResultArr = $.map(res.aaData, function(value, key) {
              return [value];
          });

        // Prepare the data in the expected format for DataTables
          let data = searchResultArr.map(record => ({
            "id": record.id,
            "name": record.name,
            "display_name": record.display_name,
            "description": record.description,
          }));
          let table = $('#permissionTbl').DataTable();
          table.destroy();
          table = $('#permissionTbl').DataTable({
            data: data,
            columns: [
                { data: 'id' },
                { data: 'name' },
                { data: 'display_name' },
                { data: 'description' },
                { 
                    data: function ( row, type, val, meta ) {
                        return '<a class="btn btn-danger btn-sm" href="#" onclick="removePermission(' + row.id + ',\'' + row.name + '\',\'delete\')">Remove</a>';
                    }
                },
            ],
          });
        },
        error: function(e) {
            // Handle the error case
            console.log("Something's wrong!");
        }
      });



    }

    function deleteRole(id)
    {
        let roleName = $('#td-role-name-'+id).text();

        Swal.fire({
            title: 'Delete ' + roleName + ' role?',
            text: "All users associated with this role will become unauthorized.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonClass: 'me-2',
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
            reverseButtons: true
            }).then((result) => {
            if (result.value) {
                $("#modalSaving").modal("toggle");

                let act = "{{ route('admin.delete_role', ['id' => ':id']) }}".replace(':id', id);

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

    function addPermission() {
        let selectedPermission = $('#permission-list').val();
        let selectedPermissionName = $('#permission-list option:selected').text();
        if(selectedPermission == 0) {
            showSwal('custom-position', 'Please select a permission to add!', 'error');
        } else {

            Swal.fire({
            title: 'Are you sure to add "<em>' + selectedPermissionName +'</em>" role?',
            text: "",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonClass: 'me-2',
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
            reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    let role_id = $('#roleIdInput').val();

                    $.ajax({
                        type: 'GET',
                        url: '{{ route("admin.role_add_permissions") }}',
                        data: {
                            "_token": "{{ csrf_token() }}",
                            "role_id": role_id,
                            "permission_id": selectedPermission,
                        },
                        
                        dataType: 'json',
                        success: function(res) {
                            let searchResultArr = $.map(res.aaData, function(value, key) {
                                return [value];
                            });

                            // Prepare the data in the expected format for DataTables
                            let data = searchResultArr.map(record => ({
                                "id": record.id,
                                "name": record.name,
                                "display_name": record.display_name,
                                "description": record.description,
                            }));
                            let table = $('#permissionTbl').DataTable();
                            table.destroy();
                            table = $('#permissionTbl').DataTable({
                                data: data,
                                columns: [
                                    { data: 'id' },
                                    { data: 'name' },
                                    { data: 'display_name' },
                                    { data: 'description' },
                                    { 
                                        data: function ( row, type, val, meta ) {
                                            return '<a class="btn btn-danger btn-sm" href="#" onclick="removePermission(' + row.id + ',\'' + row.name + '\',\'delete\')">Remove</a>';
                                        }
                                    },
                                ],
                            });
                        },
                        error: function(e, xhr, status, error) {
                            // Handle the error case
                            console.log("Something's wrong!");
                        }
                    }); 

                } else if (
                    // Read more about handling dismissals
                    result.dismiss === Swal.DismissReason.cancel
                ) {
                    Swal.fire(
                    'Cancelled',
                    'Role not added!',
                    'error'
                    )
                }
            })

        }

    }

    function removePermission(permissionId, permissionName, action) {
 
        Swal.fire({
        title: 'Are you sure to remove "<em>' + permissionName +'</em>" permission?',
        text: "",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonClass: 'me-2',
        confirmButtonText: 'Yes',
        cancelButtonText: 'No',
        reverseButtons: true
        }).then((result) => {
            if (result.value) {
                let role_id = $('#roleIdInput').val();

                $.ajax({
                    type: 'GET',
                    url: '{{ route("admin.role_remove_permission") }}',
                    data: {
                        "_token": "{{ csrf_token() }}",
                        "role_id": role_id,
                        "permission_id": permissionId,
                        "permission_name": permissionName,
                    },
                    
                    dataType: 'json',
                    success: function(res) {
                        let searchResultArr = $.map(res.aaData, function(value, key) {
                            return [value];
                        });

                        // Prepare the data in the expected format for DataTables
                        let data = searchResultArr.map(record => ({
                            "id": record.id,
                            "name": record.name,
                            "display_name": record.display_name,
                            "description": record.description,
                        }));
                        let table = $('#permissionTbl').DataTable();
                        table.destroy();
                        table = $('#permissionTbl').DataTable({
                            data: data,
                            columns: [
                                { data: 'id' },
                                { data: 'name' },
                                { data: 'display_name' },
                                { data: 'description' },
                                { 
                                    data: function ( row, type, val, meta ) {
                                        return '<a class="btn btn-danger btn-sm" href="#" onclick="removePermission(' + row.id + ',\'' + row.name + '\',\'delete\')">Remove</a>';
                                    }
                                },
                            ],
                        });
                    },
                    error: function(e, xhr, status, error) {
                        // Handle the error case
                        console.log("Something's wrong!");
                    }
                }); 

            } else if (
                // Read more about handling dismissals
                result.dismiss === Swal.DismissReason.cancel
            ) {
                Swal.fire(
                'Cancelled',
                'Role not added!',
                'error'
                )
            }
        })

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