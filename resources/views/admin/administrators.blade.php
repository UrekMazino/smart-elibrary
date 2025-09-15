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

      #permissionList li {
          /* list-style-type: none; Remove default list item marker */
          text-align: left;      /* Align text to the left */
          margin-bottom: 5px;    /* Add some margin between list items */
      }
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
                    <div class="clearfix mb-3">
                        <div class="pull-left">
                            <h6 class="card-title">ADMINISTRATORS</h6>
                        </div>
                        <div class="pull-right">
                            <a href="{{ route('admin.create') }}" class="btn btn-info" style="color:white;">+ Add New Admin</a>
                            
                          </div>      
                    </div>
                
                    <div class="table-responsive">
                        <table id="tbl" class="table">
                          <thead>
                            <tr>
                              <th style="width:20%">Name</th>
                              <th>Contact No.</th>
                              <th>Email</th>
                              <th>Roles</th>
                              <th>Date Registered</th>
                              <th></th>
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
$(document).ready(function(){

// DataTable
$('#tbl').DataTable({
    processing: true,
    serverSide: true,
    ajax: '{{ url("/superadmin/administrators/json") }}',
    columns: [
        { data: 'name' },
        { data: 'contactnum' },
        { data: 'email' },
        { 
          "data": function (row, type, val, meta) {
              let rolesHtml = '';
              let uniqueCounter = meta.row; // Using the row index as a counter

              // Assuming row.roles is an array of roles
              row.roles.forEach(role => {
                  // Generating a unique ID using the counter
                  let roleId = 'role_' + uniqueCounter;

                  rolesHtml += '<a href="#" data-role-id="' + roleId + '" onclick="showPermissions(\'' + row.permissions + '\', \'' + role + '\', \'' + row.name + '\')">' + role + '</a>';
                  
                  uniqueCounter++;
              });

              return rolesHtml;
          }
        
        },
        { data: 'created_at'},
        { 
          "data": function ( row, type, val, meta ) {
              return '<div class="btn-group" role="group"><button id="btnGroupDrop1" type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button><div class="dropdown-menu" aria-labelledby="btnGroupDrop1"><a class="dropdown-item" href="#" onclick="showEditAdminRoleModal('+row.id+')">Change Role</a><a class="dropdown-item" href="#" onclick="generateLibraryCode('+row.id+')">Generate Library Code</a><a class="dropdown-item" href="#" onclick="sendFrm(\'frm\',\'reset\','+row.id+')">Reset Password</a><a class="dropdown-item" href="#" onclick="sendFrm(\'frm\',\'delete\','+row.id+')">Delete</a></div></div>'
          }
        
        },
    ]
});

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
    $('#editAdminRoleModal').modal('show');
    $('#editAdminRoleModal').fadeIn();
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

function viewDetails(id)
{
  console.log(id);
}

function showPermissions(permissions, role, title) {
  $('#viewRolePermissionModalTitle').text(title.toUpperCase() + ' - ' + role.toUpperCase());
  $('#viewRolePermissionModalTitle2').html('<strong>PERMISSION(S)</strong>'); 
  
  // Split the string into an array
  let permissionsArray = permissions.split(',');

  // Clear existing content of the ordered list
  $('#permissionList').empty();
  // Create an ordered list and append list items
  let orderedList = $('#permissionList');
    permissionsArray.forEach(function(permission) {
        orderedList.append('<li>' + permission + '</li>');
    });


    $('#viewRolePermissionModal').modal('show');
    $('#viewRolePermissionModal').fadeIn();
}

</script>
@endsection