$('#tbl').DataTable({
    processing: true,
    serverSide: true,
    ajax: reservationsUrl,
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
                return '<a href="#" onclick="viewPatron( \''+row.user_id+'\')">' + data + '</a>';
            }
        },
        { data: 'status'},
        { data: 'reservation_code'},
        { data: 'date_of_visit'},
        {
          "data": function (row, type, val, meta) {
              if (row.status == 'For Approval') {
                  return '<div class="btn-group" role="group">' +
                      '<button type="button" class="btn btn-info btn-xs approve-reservations" aria-haspopup="true" aria-expanded="false" id="approve-reservation-' + row.id + '" disabled onclick="submitConfirmation(1, \'' + row.reservation_code + '\')">Approve</button>' +
                      '<button type="button" class="btn btn-danger btn-xs" aria-haspopup="true" aria-expanded="false" onclick="submitConfirmation(2,\'' + row.reservation_code + '\')">Decline</button>' +
                      '</div>';
              }
              if (row.status == 'Approved') {
                  return '<div class="btn-group" role="group">' +
                      '<button type="button" class="btn btn-primary btn-xs" aria-haspopup="true" aria-expanded="false" onclick="submitConfirmation(3,\'' + row.reservation_code + '\', \'' + row.circulation_number + '\')">Check-out</button>' +
                      '<button type="button" class="btn btn-danger btn-xs" aria-haspopup="true" aria-expanded="false" onclick="submitConfirmation(4,\'' + row.reservation_code + '\', \'' + row.circulation_number + '\')">Revert</button>' +
                      '</div>';
              }
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

