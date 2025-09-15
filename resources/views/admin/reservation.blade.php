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
                    <h6 class="card-title text-secondary">RESERVATIONS</h6>
                    <div class="table-responsive">
                        <table id="tbl" class="table">
                          <thead>
                            <tr>
                              <th style="width: 25%">Material Title</th>
                              <th style="width: 15%">Patron</th>
                              <th>Status</th>
                              <th>Reservation Code</th>
                              <th>Date and Time of Visit</th>
                              <th></th>
                            </tr>
                          </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--View material Modal -->
    @include('admin.modals.view-material-modal')
      
    <!--View patron Modal -->
    @include('admin.modals.view-patron-modal')


</div>
<form method="post" action="{{ url('/admin/reservation/action') }}" id="frm">
	@csrf
	  <input type="hidden" class="form-control" name="reservation_code" id="reservation-code">
    <input type="hidden" class="form-control" name="circulation_number" id="circulation-number">
    <input type="hidden" class="form-control" name="reservation_action" id="reservation-action">
</form>

@endsection

@section('js')
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.js') }}"></script>
<script> 
  let message = "{{ session('message') }}";
  let header = "{{ session('header') }}";
  let viewMaterialUrl = '{{ url("/admin/reservation/material-json") }}';
  let viewPatronUrl = '{{ url("/admin/reservation/patron-json") }}';
  let reservationsUrl = '{{ url("/admin/reservations/json") }}';
</script>
<script src="{{ asset('js/admin/reservation.js') }}"></script>
<script src="{{ asset('js/admin/view-modals.js') }}"></script>
<script>
$(document).ready(function() {
    @if(session('message'))
        timerNoReloadModal(message, header);
    @endif
});
</script>
@endsection