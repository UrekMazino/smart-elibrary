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
              // Approve / Decline (or Check-out / Revert) stay as their own button group.
              var actions = '';
              if (row.status == 'For Approval') {
                  actions =
                      '<div class="btn-group" role="group">' +
                        '<button type="button" class="btn btn-info btn-xs approve-reservations" aria-haspopup="true" aria-expanded="false" id="approve-reservation-' + row.id + '" disabled onclick="submitConfirmation(1, \'' + row.reservation_code + '\')">Approve</button>' +
                        '<button type="button" class="btn btn-danger btn-xs" aria-haspopup="true" aria-expanded="false" onclick="submitConfirmation(2,\'' + row.reservation_code + '\')">Decline</button>' +
                      '</div> ';
              } else if (row.status == 'Approved') {
                  actions =
                      '<div class="btn-group" role="group">' +
                        '<button type="button" class="btn btn-primary btn-xs" aria-haspopup="true" aria-expanded="false" onclick="submitConfirmation(3,\'' + row.reservation_code + '\', \'' + row.circulation_number + '\')">Check-out</button>' +
                        '<button type="button" class="btn btn-danger btn-xs" aria-haspopup="true" aria-expanded="false" onclick="submitConfirmation(4,\'' + row.reservation_code + '\', \'' + row.circulation_number + '\')">Revert</button>' +
                      '</div> ';
              }

              // One combined split button: Reply (open chat) as the primary action, with a
              // caret that opens Manage account (block / ban).
              var manageCaret = '';
              if (row.user_id) {
                  var blockLabel = row.is_blocked == 1 ? 'Unblock account' : 'Block account';
                  var banLabel = row.is_banned == 1 ? 'Unban account' : 'Ban account';
                  manageCaret =
                      '<button type="button" class="btn btn-primary btn-xs dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false"><span class="visually-hidden">Manage</span></button>' +
                      '<div class="dropdown-menu dropdown-menu-end">' +
                        '<h6 class="dropdown-header">Manage account</h6>' +
                        '<a class="dropdown-item text-warning" href="#" onclick="toggleUserFlag(' + row.user_id + ',\'blocked\');return false;">' + blockLabel + '</a>' +
                        '<a class="dropdown-item text-danger" href="#" onclick="toggleUserFlag(' + row.user_id + ',\'banned\');return false;">' + banLabel + '</a>' +
                      '</div>';
              }

              var unseenBadge = (row.unseen_msgs && row.unseen_msgs > 0)
                  ? ' <span class="badge bg-danger" title="Unread patron messages">' + row.unseen_msgs + '</span>' : '';

              // Blocked accounts have the chat feature disabled.
              var replyMain = (row.is_blocked == 1)
                  ? '<button type="button" class="btn btn-primary btn-xs" disabled title="Chat disabled — this account is blocked"><i data-feather="corner-up-left" class="icon-sm me-1"></i>Reply</button>'
                  : '<button type="button" class="btn btn-primary btn-xs" onclick="openReservationChat(\'' + row.reservation_code + '\')"><i data-feather="corner-up-left" class="icon-sm me-1"></i>Reply' + unseenBadge + '</button>';

              var replyBtn =
                  '<div class="btn-group" role="group">' +
                    replyMain +
                    manageCaret +
                  '</div>';

              return actions + replyBtn;
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

