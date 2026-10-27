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
      <div class="col-12 col-xl-12 stretch-card">
        <div class="row flex-grow-1">
          <div class="col-md-4 grid-margin stretch-card">
            <div class="card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-baseline">
                  <h6 class="card-title mb-0">TOTAL HOLDINGS</h6>
                  <div class="dropdown mb-2">
                  </div>
                </div>
                <div class="row">
                  <div class="col-12">
                    <input type="hidden" id="total_copies" value="{{getAllCopies()}}">
                    <h3 style="font-size: 4.375rem">{{ number_format(getAllCopies()) }}</h3>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4 grid-margin stretch-card">
            <div class="card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-baseline">
                  <h6 class="card-title mb-0">ACCOUNTED</h6>
                  <div class="dropdown mb-2">
                    <button class="btn p-0" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      <i class="icon-lg text-muted pb-3px" data-feather="more-horizontal"></i>
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                      <a class="dropdown-item d-flex align-items-center" href="{{url('/admin/inventory/reportpresented')}}" target="_blank"><i class="icon-sm me-2 mdi mdi-file-excel-box"></i> <span class="">Excel File</span></a>

                      <a class="dropdown-item d-flex align-items-center text-danger" href="#" data-call="resetInventory"><i class="icon-sm me-2 mdi mdi-information text-danger"></i> <span class="">Reset</span></a>

                      </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-12">
                    <h3 style="font-size: 4.375rem" id="copypresented">{{ getCopiesPresented() }}</h3>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-4 grid-margin stretch-card">
            <div class="card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-baseline">
                  <h6 class="card-title mb-0 text-danger">NOT ACCOUNTED</h6>
                  <div class="dropdown mb-2">
                    <button class="btn p-0" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      <i class="icon-lg text-muted pb-3px" data-feather="more-horizontal"></i>
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                      <a class="dropdown-item d-flex align-items-center" href="{{url('/admin/inventory/reportmissing')}}" target="_blank"><i class="icon-sm me-2 mdi mdi-file-excel-box"></i> <span class="">Excel File</span></a>
                      </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-12">
                    <h3 class="text-danger" style="font-size: 4.375rem" id="copymissing"></h3>
                    
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row mb-3">
      <div class="col-md-12">
          <div class="card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-baseline mb-4 mb-md-3">
                  <h6 class="card-title mb-0">SCAN BARCODE</h6>
                </div>

                  <div class="table-responsive">
                      <input type="text" class="form-control" name="barcode" id="barcode" placeholder="Scan Barcode Here...">
                  </div>
              </div>
          </div>
      </div>
    </div>
    <div class="row">
      <div class="table-responsive">
          <table id="copies-tbl" class="table">
            <thead>
              <tr>
                <th>ID</th>
                <th style="width: 20%;">CIRCULATION NO.</th>
                <th>STATUS</th>
                <th style="width: 20%;">SOURCE</th>
                <th style="width: 20%;">LOCATION</th>
                <th>ACCOUNTED</th>
              </tr>
            </thead>
          </table>
      </div>
    </div>


</div>

<form method="post" action="{{ url('/admin/inventory/reset') }}" id="frm_reset">
	@csrf
</form>

@endsection

@section('js')
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.js') }}"></script>
<script nonce="{{ cspNonce() }}">
// This page's buttons (data-call; onclick= is blocked by the Content Security Policy).
AdminActions.allow(['resetInventory']);

  
  $(document).ready(function(){

    // DataTable
    $('#copies-tbl').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ url("/admin/inventory-list-json") }}',
        columns: [
            { data: 'id' },
            { 
              "data": function ( row, type, val, meta ) {
                return row.circulation_number;
              }
            },
            { 
              "data": function ( row, type, val, meta ) {
                return row.status_name;
              }
            },
            { 
              "data": function ( row, type, val, meta ) {
                  return row.source;
              }
            },
            { 
              "data": function ( row, type, val, meta ) {
                  return row.location;
              }
            },
            {
              "data": function (row, type, val, meta) {
               return row.presented == null ? 'Unaccounted' : row.presented;
              }
            },
            
        ]
    });
   
    $("#barcode").focus();
    getPresented();

  });

$("#barcode").keypress(function(e) {
    if(e.which == 13) 
        {
          presented(this.value);
        }
    });

function presented(barcode)
  {
    //GET JSON DETAILS
        $.getJSON( "{{ url('admin/inventory/json/') }}/"+barcode, function( datajson ) {

              }).done(function(datajson){
                   if(datajson.status == 'Success')
                   {  
                      getPresented();
                   }
                   else
                   {
                      showMessage(datajson.status);
                   }
                   $("#barcode").val('').focus();
              }).fail(function(datajson){
                    
              });
    $("#scan_product").val('').focus();
  }

function getPresented()
  {
    //GET JSON DETAILS
        $.getJSON( "{{ url('admin/inventory/presented/') }}", function( datajson ) {

              }).done(function(datajson){
                   $("#copypresented").text(datajson.total);
                   var copypres = datajson.total;
                   var copytotal = $("#total_copies").val();
                   var missing = copytotal - copypres;
                   $("#copymissing").text(missing);
                   $('#copymissing').number(true);
                   $('#copypresented').number(true);
              }).fail(function(datajson){
                    
              });
  }

function showMessage(msg)
{
  Swal.fire(msg);
}

function resetInventory()
{
  Swal.fire({
  title: 'Are you sure?',
  text: "You won't be able to revert this!",
  icon: 'warning',
  showCancelButton: true,
  confirmButtonColor: '#3085d6',
  cancelButtonColor: '#d33',
  confirmButtonText: 'Yes, reset scanned materials!'
}).then((result) => {
  if (result.isConfirmed) {
    $("#frm_reset").submit();
  }
})
}


</script>
@endsection