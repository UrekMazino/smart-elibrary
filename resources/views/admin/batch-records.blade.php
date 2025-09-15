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

      #loadingSpinnerContainer {
          position: fixed; 
          top: 50%; 
          left: 50%; 
          transform: translate(-50%, -50%); 
          z-index: 9999; 
          display: none;
      }

      #loadingSpinner {
          display: inline-block;
      }
      #loadingOverlay {
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent black background */
          z-index: 9998; /* Place it below the loading spinner */
          display: none; /* Initially hide it */
      }
    </style>
@endsection

@section('content')
<div class="page-content">
    <div id="loadingOverlay"></div> 
    <div id="loadingSpinnerContainer">
        <div id="loadingSpinner" class="text-center">
            @php for($i = 0; $i < 3; $i++ ) { @endphp
            <div class="spinner-grow text-info" style="width: 2rem; height: 2rem;" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            @php } @endphp
        </div>
    </div>
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/batch-list') }}">Batch list</a></li>
            <li class="breadcrumb-item active" aria-current="page">View Batch Details</li>
			<li class="breadcrumb-item active" aria-current="page">Batch ID: {{ $id }}</li>
            <li class="breadcrumb-item active" aria-current="page">Records</li>
        </ol>
    </nav>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-baseline mb-4 mb-md-3">
                    <h6 class="card-title mb-0">CATALOG</h6>
                  </div>
                  <div class="d-flex justify-content-center align-items-baseline mb-4 mb-md-3">
                    <button type="button" class="btn p-2 btn-info btn-sm mb-0" onclick="searchTitle()">
                      <i class="icon-lg pb-3px text-white" data-feather="search"> </i> Title &nbsp&nbsp
                    </button>

                    <input type="text" class="form-control p-2 width45 mb-0" id="searchTitle" name="search_title" autocomplete="off" placeholder="Search for Title...">
                  </div>

                    <div class="table-responsive">
                        <table id="tbl" class="table">
                          <thead>
                            <tr>
                              <th style="width: 10%">Index</th>
                              <th style="width: 25%">Title</th>
                              <th>Material Type</th>
                              <th>PUBLISHER</th>
                              <th>AUTHOR</th>
                              <th>Publication Year</th>
                              <th style="width: 10%" align="center"></th>
                            </tr>
                          </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<input type="hidden" value="{{$id}}" id="batch-id">
<form method="post" action="{{ url('/admin/view-record') }}" id="viewRecordFrm">
	@csrf
	<input type="hidden" name="catalog_id" id="catalog_id">
  <input type="hidden" name="title" id="title">
</form>

<form method="post" action="" id="frm_delete">
	@csrf
  @method('DELETE')
</form>

@endsection

@section('js')
{{-- <script src="{{ asset('admin/nobleui/template/assets/vendors/datatables.net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('admin/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.js') }}"></script> --}}

<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/js/dataTables.bootstrap5.min.js') }}"></script>

<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/buttons.bootstrap5.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/buttons.colVis.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/buttons.html5.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/buttons.print.min.js') }}"></script>

<script>

$(document).ready(function(){
    let batchId = $('#batch-id');
    // DataTable
    $('#tbl').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ url("/admin/batch-records-json/". $id) }}',
        columns: [
            { data: 'id' },
            { 
              "data": function ( row, type, val, meta ) {
                //  let titleFormatted = row.title_statement + " (No PDF)";
                return row.title_statement;
              }
            },
            { data: 'material_type' },
            { 
              "data": function ( row, type, val, meta ) {
                  let maxLength = 50;

                  if(row.publisher == null) {
                    return 'N/A';
                  } else {
                    let publisher = row.publisher;
                    if (publisher.length > maxLength) {

                      let truncatedText = publisher.substring(0, maxLength) + '...';
                      return truncatedText;
                    } else {
                      return publisher;
                    }
                  }
              }
            },
            { 
              "data": function ( row, type, val, meta ) {
                  let maxLength = 50;

                  if(row.authors == null) {
                    return 'N/A';
                  } else {
                    let authors = row.authors;
                    if (authors.length > maxLength) {

                      let truncatedText = authors.substring(0, maxLength) + '...';
                      return truncatedText;
                    } else {
                      return authors;
                    }
                  }
                   
              }
            },
            {
              "data": function (row, type, val, meta) {
                // Check if pub_year is null or undefined
                if (row.pub_year == null) {
                  return 'N/A';
                } else {
                  // Remove square brackets and double quotes using regex
                  let cleanPubYear = row.pub_year.replace(/[\[\]"'.]+/g, '');

                  return cleanPubYear;
                }
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
                              '<a class="dropdown-item" href="#" onclick="viewRecord(' + row.id + ', \'' + row.title_statement + '\')">View Details</a>' +
                              @can("publish-hold.catalog") '<a class="dropdown-item" href="#" onclick="holdMaterial(' + row.id + ')">Hold</a>' + @endcan
                              @can("edit.catalog") '<a class="dropdown-item" href="#" onclick="actionFrm(' + row.id + ',\'edit\')">Edit</a>' + @endcan
                              @can("delete.catalog") '<a class="dropdown-item" href="#" onclick="actionFrm(' + row.id + ',\'delete\')">Delete</a>' + @endcan
                              // '<a class="dropdown-item" href="#" onclick="downloadMarcFile(' + row.id + ')">Download MARC File</a>' +
                          '</div>' +
                      '</div>';
              }
            },
        ]
    });

});

function searchTitle()
{
  $('#loadingOverlay').show();
  $('#loadingSpinnerContainer').show();
  let titleSearch = $('#searchTitle').val();
  $.ajax({
        type: 'GET',
        url: '{{ url("/admin/record-list-json") }}',
        data: {
            "search_title": titleSearch,
        },
        
        dataType: 'json',
        success: function(res) {
          let searchResultArr = $.map(res.aaData, function(value, key) {
              return [value];
          });
           // Prepare the data in the expected format for DataTables
          let data = searchResultArr.map(record => ({
            "id": record.id,
            "title": record.title,
            "material_type": record.material_type,
            "is_aanr": record.is_aanr == 1 ? 'Yes' : 'No',
            "date_created": record.date_created,
          }));
          let table = $('#tbl').DataTable();
          table.destroy();
          table = $('#tbl').DataTable({
            data: data,
            columns: [
              { data: 'id' },
              { data: 'title_statement' },
              { data: 'material_type' },
              { 
                "data": function ( row, type, val, meta ) {
                    let maxLength = 50;

                    if(row.publisher == null) {
                      return 'N/A';
                    } else {
                      let publisher = row.publisher;
                      if (publisher.length > maxLength) {

                        let truncatedText = publisher.substring(0, maxLength) + '...';
                        return truncatedText;
                      } else {
                        return publisher;
                      }
                    }
                }
              },
              { 
                "data": function ( row, type, val, meta ) {
                    let maxLength = 50;

                    if(row.authors == null) {
                      return 'N/A';
                    } else {
                      let authors = row.authors;
                      if (authors.length > maxLength) {

                        let truncatedText = authors.substring(0, maxLength) + '...';
                        return truncatedText;
                      } else {
                        return authors;
                      }
                    }
                    
                }
              },
              {
                "data": function (row, type, val, meta) {
                  // Check if pub_year is null or undefined
                  if (row.pub_year == null) {
                    return 'N/A';
                  } else {
                    // Remove square brackets and double quotes using regex
                    let cleanPubYear = row.pub_year.replace(/[\[\]"'.]+/g, '');

                    return cleanPubYear;
                  }
                }
              },
              { 
                "data": function ( row, type, val, meta ) {
                    return '<div class="btn-group" role="group"><button id="btnGroupDrop1" type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button><div class="dropdown-menu" aria-labelledby="btnGroupDrop1"><a class="dropdown-item" href="#" onclick="viewRecord('+row.id+', \'' + row.title_statement + '\')">View Details</a><a class="dropdown-item" href="#" onclick="holdMaterial('+row.id+')">Hold</a><a class="dropdown-item" href="#" onclick="actionFrm('+row.id+',\'edit\')">Edit</a><a class="dropdown-item" href="#" onclick="actionFrm('+row.id+',\'delete\')">Delete</a><a class="dropdown-item" href="#" onclick="downloadMarcFile('+row.id+')">Download MARC File</a></div></div>'
                }
              
              },
            ],
          });
          $('#loadingOverlay').hide();
          $('#loadingSpinnerContainer').hide();
        },
        error: function(e) {
            // Handle the error case
            console.log("Something's wrong!");
            $('#loadingOverlay').hide();
            $('#loadingSpinnerContainer').hide();
        }
      }); 
}

function viewRecord(id, title)
{
  $("#catalog_id").val(id);
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
      text: "",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonClass: 'me-2',
      confirmButtonText: 'Yes',
      cancelButtonText: 'No',
      reverseButtons: true
    }).then((result) => {
      if (result.value) {
        $("#modalSaving").modal("toggle");

        if(action == 'edit') {
          let act = "{{ route('edit-record', ['id' => ':id']) }}".replace(':id', id);
          window.location.href = act;
        }

        if(action == 'delete') {
          let act = "{{ route('delete-record', ['id' => ':id']) }}".replace(':id', id);

          $("#frm_delete").prop('action',act);
          $("#frm_delete").submit();
        }

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