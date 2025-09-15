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
        { data: 'name' },
        { data: 'contactnum' },
        { data: 'email' },
        { data: 'location'},
        { data: 'created_at'},
        { 
          "data": function ( row, type, val, meta ) {
              return '<div class="btn-group" role="group"><button id="btnGroupDrop1" type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button><div class="dropdown-menu" aria-labelledby="btnGroupDrop1"><a class="dropdown-item" href="#" onclick="generateLibraryCode('+row.id+')">Generate Library Code</a><a class="dropdown-item" href="#" onclick="sendFrm(\'frm\',\'reset\','+row.id+')">Reset Password</a><a class="dropdown-item" href="#" onclick="sendFrm(\'frm\',\'delete\','+row.id+')">Delete</a></div></div>'
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