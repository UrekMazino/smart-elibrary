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
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-baseline mb-4 mb-md-3">
                    <h6 class="card-title mb-0">CATALOG ONHOLD</h6>
                    <div class="btn-group me-2">
                      <button class="btn btn-outline-info" onclick="publishAllOnhold()">Publish All</button>
                      <button class="btn btn-outline-info dropdown-toggle" type="button" id="filterDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                         Resource Filter
                      </button>
                      <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                          <li><button class="dropdown-item filter-btn active" id="all" type="button" onclick="refreshPage()">All</button></li>
                          <li><button class="dropdown-item filter-btn" id="fulltext-only" type="button">w/ Fulltext</button></li>
                          <li><button class="dropdown-item filter-btn" id="coverphoto-only" type="button">w/ Coverphoto</button></li>
                          <li><button class="dropdown-item filter-btn" id="both" type="button">w/ Fulltext & Coverphoto Only</button></li>
                          <li><button class="dropdown-item filter-btn" id="without-fulltext" type="button">w/o Fulltext</button></li>
                          <li><button class="dropdown-item filter-btn" id="without-coverphoto" type="button">w/o Coverphoto</button></li>
                          <li><button class="dropdown-item filter-btn" id="without-both" type="button">w/o Fulltext & Coverphoto</button></li>
                      </ul>
                  </div>
                  
                  </div>
                  <div class="table-responsive">
                      <table id="tbl" class="table">
                        <thead>
                          <tr>
                            <th style="width: 5%">Index</th>
                            <th>Title</th>
                            <th  style="width: 10%">Material Type</th>
                            <th>Author</th>
                            <th>Publication Year</th>
                            <th style="width: 5%">Fulltext</th>
                            <th style="width: 5%">Coverphoto</th>
                            <th style="width: 5%" align="center">Action</th>
                          </tr>
                        </thead>
                      </table>
                  </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form method="post" action="{{ url('/admin/publish-to-opac') }}" id="publishFrm">
	@csrf
	<input type="hidden" name="catalog_id" id="publish_catalog_id">
</form>

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
    // Function to escape special characters
    function escapeHtml(text) {
      return text.replace(/&/g, '&amp;')
                  .replace(/</g, '&lt;')
                  .replace(/>/g, '&gt;')
                  .replace(/"/g, '&quot;')
                  .replace(/'/g, '&#039;')
                  .replace(/`/g, '&#96;');
    }

    $('.filter-btn').on('click', function() {
      $('.filter-btn').removeClass('active');
      $(this).addClass('active');
      $('#loadingOverlay').show();
      $('#loadingSpinnerContainer').show();

      let selectedFilter = $(this).attr("id");
      $.ajax({
        type: 'GET',
        url: '{{ url("/admin/onhold-list-json") }}',
        data: {
            "filter_search": selectedFilter,
        },
        
        dataType: 'json',
        success: function(res) {
          let searchResultArr = $.map(res.aaData, function(value, key) {
              return [value];
          });
           // Prepare the data in the expected format for DataTables
          let data = searchResultArr.map(record => ({
            "id": record.id,
            "title_statement": record.title_statement,
            "material_type": record.material_type,
            "has_multimedia": record.has_multimedia,
            "has_coverpage": record.has_coverpage,
            "authors": record.authors,
            "pub_year": record.pub_year,
          }));
          let table = $('#tbl').DataTable();
          table.destroy();
          table = $('#tbl').DataTable({
            data: data,
            columns: [
              { data: 'id' },
              { 
                data: 'title_statement',
                orderable: false
              },
              { data: 'material_type' },
              
              { 
                "data": function ( row, type, val, meta ) {
                    return row.authors == null ? 'N/A' : row.authors
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
                  if (type === 'display') {
                      if (row.has_multimedia == 1) {
                          return '<span class="badge rounded-pill border border-success text-success">&#10003;</span>'; // Green checkmark
                      } else {
                          return '<span class="badge rounded-pill border border-danger text-danger">&#10007;</span>'; // Red "X"
                      }
                  }
                  return row.has_multimedia;
                },
                orderable: false
              },
              {
                "data": function (row, type, val, meta) {
                  if (type === 'display') {
                      if (row.has_coverpage == 1) {
                          return '<span class="badge rounded-pill border border-success text-success">&#10003;</span>'; // Green checkmark
                      } else {
                          return '<span class="badge rounded-pill border border-danger text-danger">&#10007;</span>'; // Red "X"
                      }
                  }
                  return row.has_coverpage;
                },
                orderable: false
              },
              { 
                "data": function(row, type, val, meta) {
                  let escapedTitle = escapeHtml(row.title_statement);
                  return '<div class="btn-group" role="group">' +
                      '<button id="btnGroupDrop1" type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
                          'Action' +
                      '</button>' +
                      '<div class="dropdown-menu" aria-labelledby="btnGroupDrop1">' +
                          '<a class="dropdown-item" href="#" onclick="viewRecord(' + row.id + ', \'' + escapedTitle + '\')">View Details</a>' +
                          @can("publish-hold.catalog") '<a class="dropdown-item" href="#" onclick="publishMaterial(' + row.id + ')">Add to Record list</a>' + @endcan
                          @can("edit.catalog") '<a class="dropdown-item" href="#" onclick="actionFrm(' + row.id + ',\'edit\')">Edit</a>' + @endcan
                          @can("delete.catalog") '<a class="dropdown-item" href="#" onclick="actionFrm(' + row.id + ',\'delete\')">Delete</a>' + @endcan
                          // '<a class="dropdown-item" href="#" onclick="downloadMarcFile(' + row.id + ')">Download MARC File</a>' +
                      '</div>' +
                  '</div>';
                },
                orderable: false
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
    });

    // DataTable
    $('#tbl').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ url("/admin/onhold-list-json") }}',
        columns: [
            { data: 'id' },
            { 
              "data": function ( row, type, val, meta ) {
                //  let titleFormatted = row.title_statement + " (No PDF)";
                return row.title_statement;
              },
              orderable: false
            },
            { data: 'material_type' },
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
                   
              },
              orderable: false
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
              },
              orderable: false
            },
            { 
              data: 'has_multimedia',
              render: function(data, type, row) {
                // console.log(row);
                  if (type === 'display') {
                      return data == 1 ? '<span class="badge rounded-pill border border-success text-success">&#10003;</span>' : '<span class="badge rounded-pill border border-danger text-danger">&#10007;</span>';
                  }
                  return data;
              },
              orderable: false
            },
            { 
              data: 'has_coverpage',
              render: function(data, type, row) {
                  if (type === 'display') {
                      return data == 1 ? '<span class="badge rounded-pill border border-success text-success">&#10003;</span>' : '<span class="badge rounded-pill border border-danger text-danger">&#10007;</span>';
                  }
                  return data;
              },
              orderable: false
            },
            { 
              "data": function(row, type, val, meta) {
                let escapedTitle = escapeHtml(row.title_statement);
                return '<div class="btn-group" role="group">' +
                    '<button id="btnGroupDrop1" type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">' +
                        'Action' +
                    '</button>' +
                    '<div class="dropdown-menu" aria-labelledby="btnGroupDrop1">' +
                        '<a class="dropdown-item" href="#" onclick="viewRecord(' + row.id + ', \'' + escapedTitle + '\')">View Details</a>' +
                        @can("publish-hold.catalog") '<a class="dropdown-item" href="#" onclick="publishMaterial(' + row.id + ')">Add to Record list</a>' + @endcan
                        @can("edit.catalog") '<a class="dropdown-item" href="#" onclick="actionFrm(' + row.id + ',\'edit\')">Edit</a>' + @endcan
                        @can("delete.catalog") '<a class="dropdown-item" href="#" onclick="actionFrm(' + row.id + ',\'delete\')">Delete</a>' + @endcan
                        // '<a class="dropdown-item" href="#" onclick="downloadMarcFile(' + row.id + ')">Download MARC File</a>' +
                    '</div>' +
                '</div>';
              },
              orderable: false

            
            },
        ]
    });

});

function refreshPage() {
    location.reload();
}

function refreshAjax() {
    let table = $('#tbl').DataTable();
    table.ajax.reload(null, false); // Reload the table data without resetting the pagination
}

function publishAllOnhold()
{
  Swal.fire({
      title: 'Are you sure?',
      text: "All items in the On-Hold list will be published.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonClass: 'me-2',
      confirmButtonText: 'Yes',
      cancelButtonText: 'No',
      reverseButtons: true
    }).then((result) => {
      if (result.value) {
        $("#modalSaving").modal("toggle");
        $.ajax({
          url: "{{ url('/admin/publish-all-onhold') }}",
          method: "POST",
          data: {
            _token: "{{ csrf_token() }}"
          },
          success: function (response) {
            Swal.fire({
                title: 'Success',
                text: 'The record has been updated successfully!',
                icon: 'success',
                timer: 1000, // Close the modal after 2000 milliseconds (2 seconds)
                showConfirmButton: false // Hide the confirm button
            });
            $("#modalSaving").modal("toggle");
            refreshAjax();
          },
          error: function () {
              Swal.fire(
                  'Error',
                  'An error occurred while updating the record.',
                  'error'
              );
          }
        });
      } else if (
        // Read more about handling dismissals
        result.dismiss === Swal.DismissReason.cancel
      ) {
        Swal.fire(
          'Cancelled',
          'No changes made!',
          'error'
        )
      }
    })
}

function publishMaterial(id)
{
  $("#publish_catalog_id").val(id);
  $.ajax({
  url: $("#publishFrm").attr('action'),
  method: $("#publishFrm").attr('method'),
  data: $("#publishFrm").serialize(),
    success: function (response) {
      Swal.fire({
          title: 'Success',
          text: 'The record has been updated successfully!',
          icon: 'success',
          timer: 1000, // Close the modal after 2000 milliseconds (2 seconds)
          showConfirmButton: false // Hide the confirm button
      });
      refreshAjax();
    },
    error: function () {
        Swal.fire(
            'Error',
            'An error occurred while updating the record.',
            'error'
        );
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