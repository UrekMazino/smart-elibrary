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

    @include('admin.partials.breadcrumb', ['page' => 'history'])

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-secondary mb-1">HISTORY</h6>
                    <p class="text-muted mb-3" style="font-size: 0.78125rem;">Every reservation and loan, whatever its status, newest first. Click a material, patron or code to see its details.</p>
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
<script src="https://cdn.jsdelivr.net/npm/moment@2.31.0/min/moment.min.js"></script>
<script src="{{ asset('js/admin/view-modals.js') }}"></script>
<script nonce="{{ cspNonce() }}"> 
  let viewMaterialUrl = '{{ url("/admin/reservation/material-json") }}';
  let viewPatronUrl = '{{ url("/admin/reservation/patron-json") }}';
  let viewTransactionUrl = '{{ url("/admin/reservation/transaction-json") }}';
  let viewReservationUrl = '{{ url("/admin/reservation/view-json") }}';
</script>
<script nonce="{{ cspNonce() }}">
// Values go into the table as text: patron names and titles are not trusted markup.
function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
}
// Buttons call their function through data-call / data-args (AdminActions in the admin layout), not
// onclick=: the Content Security Policy blocks inline handlers. str() keeps an id a string, as before.
function str(s) { return s == null ? '' : String(s); }
AdminActions.allow(['viewMaterial', 'viewPatron', 'viewTransaction', 'viewReservation']);

$('#tbl').DataTable({
    processing: true,
    serverSide: true,
    order: [[5, 'desc']],   // newest transactions first; click any header to sort by it
    ajax: '{{ url("/admin/borrowed/json") }}',
    columns: [
        { data: 'title_statement',
            render: function(data, type, row) {
                return '<a href="#" ' + AdminActions.attrs('viewMaterial', [str(row.catalog_id), str(row.circulation_number), 0]) + '>' + esc(data) + '</a>';
            }
        
        },
        { data: 'patron_name',
            render: function(data, type, row) {
                return '<a href="#" ' + AdminActions.attrs('viewPatron', [str(row.user_id)]) + '>' + esc(data) + '</a>';
            }
        },
        { data: 'transaction_code',
            render: function(data, type, row) {
                return '<a href="#" ' + AdminActions.attrs('viewTransaction', [str(row.transaction_code)]) + '>' + esc(data) + '</a>';
            } 
        },
       
       
        { data: 'reservation_code',
            render: function(data, type, row) {
                if(data == 'Walk-in') {
                  return esc(data);
                } else {
                  return '<a href="#" ' + AdminActions.attrs('viewReservation', [str(row.reservation_code)]) + '>' + esc(data) + '</a>';
                }

            } },
        { data: 'status', render: function (d, type) { return type === 'display' ? esc(d) : d; } },
        { data: 'created_at',
            render: function(data, type, row) {
              let humanReadableDate = moment(data).format('MMMM Do, YYYY h:mm:ss A');
              return humanReadableDate;
            } },
    ]
});


</script>
@endsection