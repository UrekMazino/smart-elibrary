@extends("admin.layouts.master")

@section('css')
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.css') }}">
    <style>
      table td {
        word-break: break-word;
        vertical-align: top;
        white-space: normal !important;
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
                    <h6 class="card-title">PATRON</h6>
                    <div class="table-responsive">
                        <table id="tbl" class="table">
                          <thead>
                            <tr>
                              <th style="width:20%">Name</th>
                              <th>Contact No.</th>
                              <th>Email</th>
                              <th>Address</th>
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

<form method="post" action="{{ url('/admin/patron-action') }}" id="frm" enctype="multipart/form-data">
  @csrf
  <input type="hidden" name="patron_id" id="patron_id">
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
    ajax: '{{ url("/admin/patron/json") }}',
    columns: [
        {
          data: 'name',
          render: function (data, type, row) {
              return data + statusBadges(row);
          }
        },
        { data: 'contactnum' },
        { data: 'email' },
        { data: 'location'},
        { data: 'created_at'},
        {
          "data": function ( row, type, val, meta ) {
              var banLabel = row.is_banned == 1 ? 'Unban account' : 'Ban account';
              var blockLabel = row.is_blocked == 1 ? 'Unblock account' : 'Block account';
              return '<div class="btn-group" role="group"><button id="btnGroupDrop1" type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button><div class="dropdown-menu" aria-labelledby="btnGroupDrop1">' +
                  '<a class="dropdown-item" href="#" onclick="generateLibraryCode('+row.id+')">Generate Library Code</a>' +
                  '<a class="dropdown-item" href="#" onclick="sendFrm(\'frm\',\'reset\','+row.id+')">Reset Password</a>' +
                  '<div class="dropdown-divider"></div>' +
                  '<a class="dropdown-item text-warning" href="#" onclick="toggleUserFlag('+row.id+',\'blocked\')">'+blockLabel+'</a>' +
                  '<a class="dropdown-item text-danger" href="#" onclick="toggleUserFlag('+row.id+',\'banned\')">'+banLabel+'</a>' +
                  '<div class="dropdown-divider"></div>' +
                  '<a class="dropdown-item text-danger" href="#" onclick="sendFrm(\'frm\',\'delete\','+row.id+')">Delete</a>' +
                  '</div></div>';
          }

        },
    ]
});

});

// Small status pills shown next to a patron's name.
function statusBadges(row) {
    var out = '';
    if (row.is_banned == 1)  out += ' <span class="badge bg-danger">Banned</span>';
    if (row.is_blocked == 1) out += ' <span class="badge bg-warning text-dark">Blocked</span>';
    return out;
}

// Ban/Block toggle: banned = no login + no Read; blocked = no chat/inquiry.
function toggleUserFlag(id, flag) {
    var isBan = flag === 'banned';
    Swal.fire({
      title: 'Are you sure?',
      html: isBan
              ? 'A <strong>banned</strong> account cannot log in and cannot use the Read feature.'
              : 'A <strong>blocked</strong> account cannot use chat / inquiry.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes',
      cancelButtonText: 'No',
      reverseButtons: true
    }).then(function (result) {
      if (!result.value) return;
      $.ajax({
        type: 'POST',
        url: '{{ url("/admin/user/toggle-flag") }}',
        data: { _token: '{{ csrf_token() }}', user_id: id, flag: flag },
        dataType: 'json',
        success: function (res) {
          $('#tbl').DataTable().ajax.reload(null, false);
          Swal.fire('Done', (flag.charAt(0).toUpperCase()+flag.slice(1)) + (res.value ? ' applied.' : ' removed.'), 'success');
        },
        error: function () { Swal.fire('Error', 'Could not update the account. Please try again.', 'error'); }
      });
    });
}

function sendFrm(frm,type,id = null)
{
    if(type == 'delete')
    {
      msg = "";
      $('#action').val('delete');
    }
    else
    {
      msg = "Default password is : `pcaarrdelibrary`";
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
        $("#patron_id").val(id);
        $('#'+frm).submit();
      } else if (
        // Read more about handling dismissals
        result.dismiss === Swal.DismissReason.cancel
      ) {
        Swal.fire(
          'Cancelled',
          'Your data is safe :)',
          'error'
        )
      }
    })
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
            console.log(res);
          },
          error: function(e) {
              // Handle the error case
              console.log(e);
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
</script>
@endsection