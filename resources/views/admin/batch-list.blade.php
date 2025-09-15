@extends("admin.layouts.master")

@section('css')
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/css/dataTables.bootstrap5.css') }}">
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/css/buttons.bootstrap5.min.css') }}">
  
    <style>
      table td {
        word-break: break-word;
        vertical-align: top;
        white-space: normal !important;
      }
      .width45 {
            width: 30% !important;
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
          Admin / Batch List
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="clearfix mb-3">
                        <div class="pull-left">
                            <h6 class="card-title">List of Import Batches</h6>
                        </div>
                        <div class="pull-right">
                            <a href="{{ url('/admin/create-batch/') }}" class="btn btn-info" style="color:white;">+ New Upload</a>
                            
                          </div>      
                    </div>

                    <div class="table-responsive">
                        <table id="tbl" class="table">
                          <thead>
                            <tr>
                              <th>ID</th>
                              <th>Initial # of Records</th>
                              <th>Current # of Records</th>
                              <th>Status</th>
                              <th>Date imported</th>
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

<form method="post" action="{{ url('/admin/batch-publish') }}" id="toggle-publish-frm">
	@csrf
  <input type="hidden" name="catalog_import_id" id="catalog_import_id">
  <input type="hidden" name="action" id="action">
</form>


<form method="post" action="{{ url('/admin/view-batch') }}" id="viewRecordFrm">
	@csrf
	<input type="hidden" name="batch_id" id="batch_id">
</form>

<form method="post" action="" id="frm_delete">
	@csrf
  @method('DELETE')
</form>

@endsection

@section('js')
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/js/dataTables.bootstrap5.min.js') }}"></script>

<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/buttons.bootstrap5.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/buttons.colVis.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/buttons.html5.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/buttons.print.min.js') }}"></script>

<script>

$(document).ready(function(){

// DataTable
$('#tbl').DataTable({
    processing: true,
    serverSide: true,
    ajax: '{{ url("/admin/batch-list-json") }}',
    columns: [
        { data: 'id' },
        { data: 'init_no_of_records' },
        { data: 'current_no_of_records' },
        { data: 'status' },
        {
          data: 'created_at',
          render: function (data, type, row, meta) {
              if (type === 'display' || type === 'filter') {
                  // Parse the date string
                  var dateParts = data.split('-');
                  var formattedDate = dateParts[1] + '/' + dateParts[2].substr(0, 2) + '/' + dateParts[0].substr(2);

                  return formattedDate;
              }
              return data;
          }
        },

        { 
          "data": function (row, type, val, meta) {
              return '' +
                  '<div class="btn-group" role="group">' +
                      '<button id="btnGroupDrop1" type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
                          'Action' +
                      '</button>' +
                      '<div class="dropdown-menu" aria-labelledby="btnGroupDrop1">' +
                          '<a class="dropdown-item" href="#" onclick="viewRecord(' + row.id + ')">View Details</a>' +
                          @can("publish-hold.catalog") '<a class="dropdown-item" href="#" onclick="togglePublishFrm(' + row.id + ',\'1\')">Publish All</a>' + @endcan
                          @can("publish-hold.catalog") '<a class="dropdown-item" href="#" onclick="togglePublishFrm(' + row.id + ',\'2\')">Hold All</a>' + @endcan
                          @can("delete.catalog.import")'<a class="dropdown-item" href="#" onclick="actionFrm(' + row.id + ',\'delete\')">Delete Batch</a>' + @endcan
                          // '<a class="dropdown-item" href="#" onclick="downloadMarcFile(' + row.id + ')">Download MARC File</a>' +
                      '</div>' +
                  '</div>';
          }
        },
    ]
});

});

function togglePublishFrm(id, action)
{

  
  let text = "";
  if(action == 1) {
      text = "This will publish all the records in this batch to OPAC view.";
  } else if(action == 2) {
      text = "This will hold all the records in this batch from OPAC view."
  }

  Swal.fire({
      title: 'Are you sure?',
      text: text,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonClass: 'me-2',
      confirmButtonText: 'Yes',
      cancelButtonText: 'No',
      reverseButtons: true
  }).then((result) => {
    if (result.value) {
      $("#modalSaving").modal("toggle");

      let act = "{{ route('delete-batch', ['id' => ':id']) }}".replace(':id', id);
      $("#action").val(action);
      $("#catalog_import_id").val(id);
      $("#toggle-publish-frm").submit();

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

function viewRecord(id, title)
{
  $("#batch_id").val(id);
  $("#title").val(title);
  $("#viewRecordFrm").submit();
}

function copyFrm(id)
{
  $("#copy_catalog_id").val(id);
  $("#frm_add_copy").submit();
}


function actionFrm(id,action)
{
  $("#action_catalog_id").val(id);
  Swal.fire({
      title: 'Are you sure?',
      text: "This will also delete all the records in this batch.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonClass: 'me-2',
      confirmButtonText: 'Yes',
      cancelButtonText: 'No',
      reverseButtons: true
    }).then((result) => {
      if (result.value) {
        $("#modalSaving").modal("toggle");

        let act = "{{ route('delete-batch', ['id' => ':id']) }}".replace(':id', id);

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

</script>
@endsection