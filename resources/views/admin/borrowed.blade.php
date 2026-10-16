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
                    <h6 class="card-title text-secondary mb-1">TRANSACTIONS</h6>
                    <p class="text-muted mb-3" style="font-size: 12.5px;">Every check-out / check-in, newest first. Click a material, patron or code to see its details.</p>
                    <div class="table-responsive">
                        <table id="tbl" class="table">
                          <thead>
                            <tr>
                              <th style="width: 30%">Material</th>
                              <th style="width: 20%">Patron</th>
                              <th style="width: 15%">Transaction Code</th>
                              <th style="width: 15%">Reservation Code</th>
                              <th style="width: 10%">Status</th>
                              <th style="width: 10%">Date Created</th>
                              
                            </tr>
                          </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--View material Modal -->
@include('admin.modals.view-material-modal')
  
<!--View patron Modal -->
@include('admin.modals.view-patron-modal')

<!--View transaction Modal -->
@include('admin.modals.view-transaction-modal')

<!--View reservation Modal -->
@include('admin.modals.view-reservation-modal')

@endsection

@section('js')
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="{{ asset('js/admin/view-modals.js') }}"></script>
<script> 
  let viewMaterialUrl = '{{ url("/admin/reservation/material-json") }}';
  let viewPatronUrl = '{{ url("/admin/reservation/patron-json") }}';
  let viewTransactionUrl = '{{ url("/admin/reservation/transaction-json") }}';
  let viewReservationUrl = '{{ url("/admin/reservation/view-json") }}';
</script>
<script>
$('#tbl').DataTable({
    processing: true,
    serverSide: true,
    order: [[5, 'desc']],   // newest transactions first; click any header to sort by it
    ajax: '{{ url("/admin/borrowed/json") }}',
    columns: [
        { data: 'title_statement',
            render: function(data, type, row) {
                return '<a href="#" onclick="viewMaterial(\'' + row.catalog_id + '\', \'' + row.circulation_number + '\', 0)" >' + data + '</a>';
            }
        
        },
        { data: 'patron_name',
            render: function(data, type, row) {
                return '<a href="#" onclick="viewPatron( \''+row.user_id+'\')" >' + data + '</a>';
            }
        },
        { data: 'transaction_code',
            render: function(data, type, row) {
                return '<a href="#" onclick="viewTransaction( \''+row.transaction_code+'\')" >' + data + '</a>';
            } 
        },
       
       
        { data: 'reservation_code',
            render: function(data, type, row) {
                if(data == 'Walk-in') {
                  return data;
                } else {
                  return '<a href="#" onclick="viewReservation( \''+row.reservation_code+'\')" >' + data + '</a>';
                }

            } },
        { data: 'status' },
        { data: 'created_at',
            render: function(data, type, row) {
              let humanReadableDate = moment(data).format('MMMM Do, YYYY h:mm:ss A');
              return humanReadableDate;
            } },
    ]
});


</script>
@endsection