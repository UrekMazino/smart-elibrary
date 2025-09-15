@extends("admin.layouts.master")

@section('css')
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

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

      div.dataTables_wrapper div.dataTables_filter input {
          /* margin-left: 0.5em; */
          display: inline-block;
          width: 400px;
          text-align: center;
          margin-inline: 45px;
          border-color: #eee;
          border-style: solid;
          border-width: 2px;
      }
      div.dataTables_wrapper div.dataTables_filter label {
          font-weight: 600;
          text-transform: uppercase;
          text-align: center;
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
                  <div class="clearfix">
                    <div class="pull-left">
                      <h6 class="card-title">MARC Templates</h6>
                    </div>
                    <div class="pull-right">
                    <a href="{{ url('/admin/marc-templates/create') }}" class="btn btn-primary">+ New MARC Template</a>
                    </div>      
                  </div>
                    
                    <div class="table-responsive mt-5">
                        <table id="tbl" class="table">
                          <thead>
                            <tr>
                              <th style="width:20%">ID</th>
                              <th>Name</th>
                              <th>Action</th>
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
      ajax: '{{ url("/admin/marc-templates/json") }}',
      columns: [
          { data: 'id' },
          { data: 'name' },
          { 
            data: function(row, type, val, meta) {
                return '<div class="btn-group text-center" role="group">' +
                    '<button id="btnGroupDrop1" type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button>' +
                    '<div class="dropdown-menu" aria-labelledby="btnGroupDrop1">' +
                    @can("edit.config.template")'<a class="dropdown-item" href="#" data-edit-url="{{ url("/admin/marc-templates/edit?template_id=") }}' + row.id + '">Edit</a>' +@endcan
                    @can("delete.config.template")'<button class="dropdown-item delete-btn" data-template-id="' + row.id + '">Delete</button>' +@endcan
                    '</div>' +
                    '</div>';
            }
          
          },
      ]
  });

  @can("delete.config.template")
    $('#tbl').on('click', '.dropdown-item.delete-btn', function() {
          let templateId = $(this).data('template-id');
          if (confirm('Are you sure you want to delete this template?')) {

            $.ajax({
              url: 'marc-templates/delete/' + templateId,
              type: 'DELETE',
              headers: {
                  'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
              },
              dataType: 'json', // Use JSON for response
              success: function(response) {
                  if (response.success) {
                      successSwal();
                      location.reload();
                      // Delete was successful, update the DataTable or take other actions
                  } else {
                      // Delete failed, show an error message or take appropriate actions
                      failedSwal ();
                      location.reload();
                  }
              },
              error: function(xhr, status, error) {
                  // Handle errors here
              }
          });

          }
      });
    @endcan
    @can("edit.config.template")
      $('#tbl').on('click', '.dropdown-item[data-edit-url]', function() {
          let editUrl = $(this).data('edit-url');
          // Redirect to the edit URL
          window.location.href = editUrl;
      });
    @endcan
});


function successSwal() {
  const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      });
      
      Toast.fire({
        icon: 'success',
        title: 'Template deleted successfully!'
      })
}

function failedSwal() {
  const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      });
      
      Toast.fire({
        icon: 'danger',
        title: 'Something is wrong!'
      })
}
</script>
@endsection