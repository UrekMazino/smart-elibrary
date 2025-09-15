@extends("admin.layouts.master")

@section('css')
    {{-- <link rel="stylesheet" href="{{ asset('admin/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.css') }}"> --}}
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/css/dataTables.bootstrap5.css') }}">

    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/css/buttons.bootstrap5.min.css') }}">

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
                  <div class="d-flex justify-content-between align-items-baseline mb-4 mb-md-3">
                    <h6 class="card-title mb-0">NEWS ARTICLE</h6>
                    <a href="{{ url('/admin/news/add') }}" class="btn p-2 btn-primary" type="button">
                      <i class="icon-lg pb-3px text-white" data-feather="file-plus"> </i> ADD NEWS ARTICLE
                    </a>
                  </div>

                    <div class="table-responsive">
                        <table id="tbl" class="table">
                          <thead>
                            <tr>
                              <th style="width: 30%">Title</th>
                              <th>Featured</th>
                              <th>Created By</th>
                              <th>Created At</th>
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
  const editUrlBase = '{{ url("/admin/news/edit") }}';
  const deleteUrlBase = '{{ url("/admin/news/delete") }}';
  const csrfToken = '{{ csrf_token() }}';
</script>
<script>
$(document).ready(function(){

// DataTable
$('#tbl').DataTable({
    processing: true,
    serverSide: true,
    ajax: '{{ url("/admin/news/json") }}',
    columns: [
        { data: 'news_title' },
        { data: 'featured' },
        { data: 'created_by' },
        { "data": function ( row, type, val, meta ) {
            let isoDate = row.created_at;
            let date = new Date(isoDate);
            let formattedDate = date.toLocaleString();
            return formattedDate;
          } 
        },
        { 
            "data": function (row, type, val, meta) {
                // Assuming `row.id` contains the ID of the news item
                let editUrl = editUrlBase + '/' + row.id; 
                let deleteUrl = deleteUrlBase + '/' + row.id;

                // Create the form using Laravel Collective's syntax
                let deleteForm = `
                    <form method="POST" action="${deleteUrl}" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this item?');">
                        <input type="hidden" name="_method" value="DELETE">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <button type="submit" class="dropdown-item">Delete</button>
                    </form>`;

                return `
                    <center>
                        <div class="btn-group" role="group">
                            <button id="btnGroupDrop1" type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                Action
                            </button>
                            <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
                                <a class="dropdown-item" href="${editUrl}">Edit</a>
                                ${deleteForm}
                            </div>
                        </div>
                    </center>`;
            }
        }

    ]
});

});
</script>
@endsection