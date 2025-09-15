@extends("admin.layouts.master")

@section('css')
    {{-- <link rel="stylesheet" href="{{ asset('admin/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.css') }}"> --}}
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/css/dataTables.bootstrap5.css') }}">

    <link rel="stylesheet" href="{{ asset('admadmintemplatein/nobleui/template/assets/vendors/DataTables/buttons/css/buttons.bootstrap5.min.css') }}">

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
                    <form method="post" action="{{ url('/admin/patron-borrow') }}" id="frm">
                    @csrf
                    <input type="hidden" name="userid" value="{{getPatronInfo($data['userid'],'id')}}">

                    <div class="d-flex justify-content-between align-items-baseline mb-4 mb-md-3">
                      <h6 class="card-title mb-0">PATRON NAME : {{strtoupper(getPatronInfo($data['userid'],'name'))}}</h6>

                      <button type="submit" class="btn p-2 btn-primary" onclick="saveMaterial()">
                          <i class="icon-lg pb-3px text-white" data-feather="save"> </i> SAVE &nbsp&nbsp
                        </button>
                    </div>

                    <div class="table-responsive">
                        <span>List of Material to Borrow</span>
                        <table id="tbl_list" class="table">
                          <thead>
                            <tr>
                              <th style="width: 35%">Title</th>
                              <th>Material Type</th>
                              <th>Authors</th>
                              <th style="width: 10%" align="center"></th>
                            </tr>
                          </thead>
                         
                        </table>
                      </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-baseline mb-4 mb-md-3">
                    <h6 class="card-title mb-0">CATALOG</h6>
                  </div>

                    <div class="table-responsive">
                        <table id="tbl" class="table">
                          <thead>
                            <tr>
                              <th style="width: 10%">ID</th>
                              <th style="width: 35%">Title</th>
                              <th>Material Type</th>
                              <th>Authors</th>
                              <th style="width: 10%">Copies</th>
                              <th>Created by</th>
                              <th style="width: 10%">Date created</th>
                              <th style="width: 15%" align="center"></th>
                            </tr>
                          </thead>
                         
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



<form method="post" action="{{ url('/admin/edit-acquisition-view') }}" id="frm">
	@csrf
	<input type="hidden" name="catalog_id" id="catalog_id">
</form>

{{-- Form para i-download ng MARC File - Anna --}}
<form method="post" action="{{ url('marcfile/download') }}" id="frm_marcdownload" target="_blank">
	@csrf
	<input type="hidden" name="catalog_id_marc" id="catalog_id_marc">
</form>

{{-- <form method="post" action="{{ url('/admin/delete-acquisition') }}" id="frm_action"> --}}
<form method="post" action="" id="frm_action">
	@csrf
	<input type="hidden" name="action_catalog_id" id="action_catalog_id">
  <input type="hidden" name="catalog_date" id="catalog_date" value="">
  <input type="hidden" name="url" value="{{ url('/admin/catalog') }}">
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
    ajax: '{{ url("/admin/catalog/json") }}',
    columns: [
        { data: 'catalog_id' },
        { data: 'title_statement' },
        { data: 'material' },
        { data: 'joint_authors'},
        { data: 'copies' },
        { data: 'CreatedBy' },
        { 
          "data": function ( row, type, val, meta ) {
            const d = new Date(row.created_at).toLocaleDateString('en-US')
              return d
          }
        
        },
        { 
          "data": function ( row, type, val, meta ) {
                var title_statement = row.title_statement;
                title_statement = title_statement.replace(/'/g, "&#39");
                var joint_authors = row.joint_authors;
                // joint_authors = joint_authors.replace(/'/g, "&#39");
              return "<button class='btn btn-primary' onclick='addList("+row.id+",\""+title_statement+"\",\""+row.material+"\",\""+joint_authors+"\")'>ADD TO LIST</button>"
          }
        
        },
    ]
});

});

function addList(id,title,material,author)
{
    $("#tbl_list").append("<tr><td><input type='hidden' name='catalog_id[]' value='"+id+"'>"+title+"</td><td>"+material+"</td><td>"+author+"</td><td align='center'><a href='#' style='color:red;' onclick='removeFromList(this)'><b>remove</b></a></td></tr>");
}

function removeFromList(obj)
{
    $(obj).closest("tr").remove();
}
</script>
@endsection