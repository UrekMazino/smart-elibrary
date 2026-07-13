$('#tbl').DataTable({
    processing: true,
    serverSide: true,
    ajax: reservationsUrl,
    drawCallback: function () { if (window.feather) feather.replace(); },
    columns: [
        {
            data: 'title_statement',
            render: function(data, type, row) {
                return '<a href="#" onclick="viewMaterial(\'' + row.catalog_id + '\', \'' + row.circulation_number + '\', ' + row.id + ')">' + data + '</a>';
            }

        },
        {
            data: 'requested_by',
            render: function(data, type, row) {
                var badges = '';
                if (row.is_banned == 1)  badges += ' <span class="badge bg-danger">Banned</span>';
                if (row.is_blocked == 1) badges += ' <span class="badge bg-warning text-dark">Blocked</span>';
                return '<a href="#" onclick="viewPatron( \''+row.user_id+'\')">' + data + '</a>' + badges;
            }
        },
        { data: 'email' },
        { data: 'status'},
        { data: 'reservation_code'},
        { data: 'date_of_visit'},
        {
          "data": function (row, type, val, meta) {
              var actions = '';
              if (row.status == 'For Approval') {
                  actions =
                      '<button type="button" class="btn btn-info btn-xs approve-reservations" aria-haspopup="true" aria-expanded="false" id="approve-reservation-' + row.id + '" disabled onclick="submitConfirmation(1, \'' + row.reservation_code + '\')">Approve</button>' +
                      '<button type="button" class="btn btn-danger btn-xs" aria-haspopup="true" aria-expanded="false" onclick="submitConfirmation(2,\'' + row.reservation_code + '\')">Decline</button>';
              } else if (row.status == 'Approved') {
                  actions =
                      '<button type="button" class="btn btn-primary btn-xs" aria-haspopup="true" aria-expanded="false" onclick="submitConfirmation(3,\'' + row.reservation_code + '\', \'' + row.circulation_number + '\')">Check-out</button>' +
                      '<button type="button" class="btn btn-danger btn-xs" aria-haspopup="true" aria-expanded="false" onclick="submitConfirmation(4,\'' + row.reservation_code + '\', \'' + row.circulation_number + '\')">Revert</button>';
              }

              // Chat button (per-reservation admin<->patron thread)
              var chat = '<button type="button" class="btn btn-outline-primary btn-xs" title="Message patron" onclick="openReservationChat(\'' + row.reservation_code + '\')"><i data-feather="message-square"></i></button>';

              // Manage account (ban/block) — only when we know the patron
              var manage = '';
              if (row.user_id) {
                  var blockLabel = row.is_blocked == 1 ? 'Unblock account' : 'Block account';
                  var banLabel = row.is_banned == 1 ? 'Unban account' : 'Ban account';
                  manage =
                      '<div class="btn-group" role="group">' +
                        '<button type="button" class="btn btn-secondary btn-xs dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Manage</button>' +
                        '<div class="dropdown-menu dropdown-menu-end">' +
                          '<a class="dropdown-item text-warning" href="#" onclick="toggleUserFlag(' + row.user_id + ',\'blocked\');return false;">' + blockLabel + '</a>' +
                          '<a class="dropdown-item text-danger" href="#" onclick="toggleUserFlag(' + row.user_id + ',\'banned\');return false;">' + banLabel + '</a>' +
                        '</div>' +
                      '</div>';
              }

              return '<div class="btn-group" role="group">' + actions + chat + '</div> ' + manage;
          }
        },

    ]
});

function timerNoReloadModal(message, header)
{
  let timerInterval
  Swal.fire({
    title: header,
    html: message,
    timer: 1000,
    timerProgressBar: true,
    didOpen: () => {
      $("#overlay").show();
      Swal.showLoading()
      timerInterval = setInterval(() => {
        const content = Swal.getHtmlContainer()
        if (content) {
          const b = content.querySelector('b')
          if (b) {
            b.textContent = Swal.getTimerLeft()
          }
        }
      }, 100)
    },
    willClose: () => {
      clearInterval(timerInterval)
    }
  }).then((result) => {
    /* Read more about handling dismissals below */
    if (result.dismiss === Swal.DismissReason.timer) {
      $("#overlay").hide();
    }
  })
}

function submitConfirmation(action, reservation_code, circulation_number = null)
{
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
        $("#reservation-action").val(action);
        $("#reservation-code").val(reservation_code);
        if(circulation_number != null) { 
          $("#circulation-number").val(circulation_number);
        }
        $('#frm').submit();
      } else if (
        // Read more about handling dismissals
        result.dismiss === Swal.DismissReason.cancel
      ) {
        Swal.fire(
          'Cancelled',
          'Action cancelled',
          'error'
        )
      }
    })
}

