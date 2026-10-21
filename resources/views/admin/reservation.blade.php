@extends("admin.layouts.master")

@section('css')
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.css') }}">
    <style>
    table td {
      word-break: break-word;
      vertical-align: top;
      white-space: normal !important;
    }
    /* Keep the action buttons on one line (the global white-space:normal above
       otherwise wraps the Reply icon above its label). */
    #tbl td:last-child { white-space: nowrap !important; }
    #tbl td .btn-group { flex-wrap: nowrap; }
    #tbl td .btn { white-space: nowrap; }
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
                              <th style="width: 22%">Material Title</th>
                              <th style="width: 14%">Patron</th>
                              <th>Email</th>
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

    <!-- Reservation chat modal -->
    <div class="modal fade" id="reservationChatModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Message patron <small class="text-muted" id="chat-res-code"></small></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div id="reservation-chat-thread" style="max-height:340px;overflow-y:auto;padding:4px;"></div>
            @can('manage.circulation.reservation')
            <label class="form-label">Your message</label>
            <textarea class="form-control" id="reservation-chat-message" rows="3" placeholder="Type your message to the patron..."></textarea>
            @else
            <p class="text-muted small mb-0 mt-2"><i data-feather="lock" class="icon-sm me-1"></i>Your role can read these messages but not reply (needs <em>Manage Reservations</em>).</p>
            @endcan
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            @can('manage.circulation.reservation')
            <button type="button" class="btn btn-primary" onclick="sendReservationChat()"><i data-feather="send" class="icon-sm me-1"></i>Send</button>
            @endcan
          </div>
        </div>
      </div>
    </div>

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
  let reservationChatJsonUrl = '{{ url("/admin/reservation/chat-json") }}';
  let reservationChatReplyUrl = '{{ url("/admin/reservation/chat/reply") }}';
  let toggleFlagUrl = '{{ url("/admin/user/toggle-flag") }}';
  let csrfToken = '{{ csrf_token() }}';
  let adminUserId = {{ Auth::id() }};
  // Permission flags — hide actions the current admin can't perform (route also enforces).
  let canManageReservation = @json(auth()->user()->can('manage.circulation.reservation'));
  let canManagePatronStatus = @json(auth()->user()->can('manage.patron.status'));
</script>
<script src="{{ asset('js/admin/reservation.js') }}?v={{ filemtime(public_path('js/admin/reservation.js')) }}"></script>
<script src="{{ asset('js/admin/view-modals.js') }}"></script>
<script>
$(document).ready(function() {
    @if(session('message'))
        timerNoReloadModal(message, header);
    @endif
    if (window.feather) feather.replace();

    // After reading a thread (which marks patron messages seen), refresh the table so
    // the per-row unread badge clears.
    $('#reservationChatModal').on('hidden.bs.modal', function () {
        if ($.fn.DataTable.isDataTable('#tbl')) { $('#tbl').DataTable().ajax.reload(null, false); }
    });
});

// ---- Reservation chat (admin -> patron, per reservation) ----
var currentChatCode = null;

function openReservationChat(reservationCode) {
    currentChatCode = reservationCode;
    $('#chat-res-code').text('· ' + reservationCode);
    $('#reservation-chat-message').val('');
    loadReservationChat();
    $('#reservationChatModal').modal('show');
}

function loadReservationChat() {
    $.getJSON(reservationChatJsonUrl + '/' + encodeURIComponent(currentChatCode), function (rows) {
        var box = $('#reservation-chat-thread').empty();
        if (!rows.length) {
            box.append('<p class="text-muted text-center small mb-0">No messages yet. Start the conversation below.</p>');
            return;
        }
        $.each(rows, function (i, obj) {
            var mine = (obj.sender_role === 'admin');
            var name = obj.sender_name || (mine ? 'Admin' : 'Patron');
            var side = mine ? 'text-end' : 'text-start';
            var bubble = mine ? 'background:#0d6efd;color:#fff;' : 'background:#f4f6f9;color:#222;';
            box.append(
                '<div class="' + side + '" style="margin:8px 0;">' +
                  '<div style="display:inline-block;max-width:80%;text-align:left;border-radius:10px;padding:8px 12px;' + bubble + '">' +
                    '<div>' + $('<div>').text(obj.message).html() + '</div>' +
                    '<small style="opacity:.8;">' + $('<div>').text(name + ' · ' + obj.created_at).html() + '</small>' +
                  '</div>' +
                '</div>'
            );
        });
        box.scrollTop(box[0].scrollHeight);
    });
}

function sendReservationChat() {
    var msg = $('#reservation-chat-message').val().trim();
    if (!msg) return;
    $.ajax({
        type: 'POST',
        url: reservationChatReplyUrl,
        data: { _token: csrfToken, reservation_code: currentChatCode, message: msg },
        dataType: 'json',
        success: function () { $('#reservation-chat-message').val(''); loadReservationChat(); },
        error: function () { Swal.fire('Error', 'Could not send the message. Please try again.', 'error'); }
    });
}

// ---- Ban / Block a patron ----
function toggleUserFlag(id, flag) {
    var isBan = flag === 'banned';
    Swal.fire({
      title: 'Are you sure?',
      html: isBan
              ? 'A <strong>banned</strong> account cannot log in and cannot use the Read feature.'
              : 'A <strong>blocked</strong> account cannot use chat / inquiry.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes',
      cancelButtonText: 'No',
      reverseButtons: true
    }).then(function (result) {
      if (!result.value) return;
      $.ajax({
        type: 'POST',
        url: toggleFlagUrl,
        data: { _token: csrfToken, user_id: id, flag: flag },
        dataType: 'json',
        success: function () { $('#tbl').DataTable().ajax.reload(null, false); },
        error: function () { Swal.fire('Error', 'Could not update the account. Please try again.', 'error'); }
      });
    });
}
</script>
@endsection