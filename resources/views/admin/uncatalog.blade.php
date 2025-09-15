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
                    <h6 class="card-title mb-0">UNCATALOG</h6>
                  </div>

                    <div class="table-responsive">
                        <table id="tbl" class="table">
                          <thead>
                            <tr>
                              <th style="width: 10%">ID</th>
                              <th style="width: 35%">Title</th>
                              <th>Material Type</th>
                              <th>Authors</th>
                              {{-- <th style="width: 10%">Source</th> --}}
                              <th>Created by</th>
                              <th style="width: 10%">Date created</th>
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

<div class="modal fade" id="shareModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="title" id="smallModalLabel">Share material</h4>
      </div>
      <form method="post" action="{{ url('/acquisition/shared') }}" id="frm_share">
      @csrf
      <input type='hidden' name='catalog_id_share' id='catalog_id_share'>
      <div class="modal-body">
        <div class="mb-4">
          <div class="form-check mb-2">
            <input type="checkbox" class="form-check-input" id="consortia-all" checked>
            <label class="form-check-label" for="consortia-all">
              All
            </label>
          </div>
        @foreach(getConsortia() AS $list)
          <div class="form-check mb-2">
            <input type="checkbox" class="form-check-input consortia" id="{{ $list->description }}">
            <label class="form-check-label" for="{{ $list->description }}">
              {{ $list->description }}
            </label>
          </div>
        @endforeach
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-info">Submit</button>
        <button type="button" class="btn btn-danger" data-bs-dismiss="modal">CLOSE</button>
      </div>
      </form>
    </div>
  </div>
</div>

<form method="post" action="{{ url('/admin/edit-acquisition-view') }}" id="frm">
	@csrf
	<input type="hidden" name="catalog_id" id="catalog_id">
</form>

<form method="post" action="" id="frm_action">
	@csrf
	<input type="hidden" name="action_catalog_id" id="action_catalog_id">
  <input type="hidden" name="catalog_date" id="catalog_date" value="{{ date('Y-m-d') }}">
  <input type="hidden" name="url" value="{{ url('/admin/uncat') }}">
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

// DataTable
$('#tbl').DataTable({
    processing: true,
    serverSide: true,
    ajax: '{{ url("/admin/uncatalog/json") }}',
    columns: [
        { data: 'catalog_id' },
        { data: 'title_statement' },
        { data: 'material' },
        { data: 'joint_authors'},
        // { data: 'consortia' },
        { data: 'CreatedBy' },
        { 
          "data": function ( row, type, val, meta ) {
            const d = new Date(row.created_at).toLocaleDateString('en-US')
              return d
          }
        
        },
        { 
          "data": function ( row, type, val, meta ) {
              return '<div class="btn-group" role="group"><button id="btnGroupDrop1" type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button><div class="dropdown-menu" aria-labelledby="btnGroupDrop1"><a class="dropdown-item" href="#" onclick="editFrm('+row.id+')" >Edit</a><a class="dropdown-item" href="#" onclick="actionFrm('+row.id+',\'delete\')">Delete</a><a class="dropdown-item" href="#" onclick="actionFrm('+row.id+',\'catalog\')">Set as Catalog</a><a class="dropdown-item" href="#" onclick="shareMaterial('+row.id+')">Share material</a></div></div>'
          }
        
        },
    ]
});

});

function shareMaterial(id)
{
  $('.consortia').not(this).prop('checked', false);
  $('#consortia-all').prop('checked', true);
  $("#shareModal").modal('toggle');
}

$("#consortia-all").click(function () {
     $('.consortia').not(this).prop('checked', false);
     $(this).prop('checked', true);
 });

 $(".consortia").click(function () {
     $('#consortia-all').prop('checked', false);
 });

 function editFrm(id)
{
  $("#catalog_id").val(id);
  $("#frm").submit();
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

        if(action == 'delete')
        {
          var act = "{{ url('/admin/delete-acquisition') }}";
        }
        else
        {
          var act = "{{ url('/admin/set-as-acquisition') }}";
        }

        $("#frm_action").prop('action',act);
        $("#frm_action").submit();

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
</script>
@endsection