function copyToClipBoard() {
    var copyText = document.getElementById("transactionCodeInput");
    var text = copyText ? copyText.value : '';

    function showCopied() {
        var popup = $('#popup-message');
        popup.css('display', 'block');
        setTimeout(function () { popup.css('display', 'none'); }, 2000); // hide after 2s
    }

    // navigator.clipboard only works in a secure context (https/localhost); this app runs
    // over http, and the source input is disabled, so fall back to a temp textarea + execCommand.
    function fallbackCopy() {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.top = '-9999px';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        try { document.execCommand('copy'); showCopied(); }
        catch (e) { window.prompt('Copy this code:', text); }
        document.body.removeChild(ta);
    }

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(showCopied, fallbackCopy);
    } else {
        fallbackCopy();
    }
}

function applyAddedTerm() {
    let modalFineAmount = $('#input-other-fine-amount').val();
    let parsedModalFineAmount = parseFloat(modalFineAmount);

    let modalWaveAmount = $('#input-waive-amount').val();
    let parsedModalWaveAmount = parseFloat(modalWaveAmount);

    let modalFineNote = $('#textarea-fine-note').val();
    let modalFineDescriptionValue = $('#fine-description').val();
    let modalFineDescriptionText = $('#fine-description').find('option:selected').text();

    $('#input-fine-note').val(modalFineNote);

    // let totalFine = $('#total-fine').text();

    // totalFine = parseFloat(totalFine);
    let overdueFine = $("#overdue-fine").text().trim();
            
    let match = overdueFine.match(/[0-9]+\.[0-9]+/);
    
    overdueFine = match ? match[0] : "0.00";

    let overdueFineParsed = parseFloat(overdueFine);

    let totalFine = (overdueFineParsed + parsedModalFineAmount) - parsedModalWaveAmount;

    $('#other-fine').text('₱ ' + parsedModalFineAmount.toLocaleString());
    $('#waive-amount').text('-₱ ' + parsedModalWaveAmount.toLocaleString());
    $('#label-fine-desc').text(modalFineDescriptionText).css('font-size', '13px');
    $('#total-fine').text('₱ ' + totalFine.toLocaleString());

    $('#hidden-total-fine').val(totalFine);
    $('#hidden-overdue-fine').val(overdueFineParsed);
    $('#hidden-waive-amount').val(parsedModalWaveAmount);
    $('#hidden-other-fine').val(parsedModalFineAmount);
    $('#hidden-other-fine-desc').val(modalFineDescriptionText);
    $('#hidden-circulation-fine-id').val(modalFineDescriptionValue);
}

function checkinTransaction()
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
        $('#check-in-form').submit();
      } else if (
        // Read more about handling dismissals
        result.dismiss === Swal.DismissReason.cancel
      ) {
        Swal.fire(
          'Cancelled',
          'Check-in cancelled',
          'error'
        )
      }
    })
}

function checkoutTransaction()
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
        $('#check-out-form').submit();
      } else if (
        // Read more about handling dismissals
        result.dismiss === Swal.DismissReason.cancel
      ) {
        Swal.fire(
          'Cancelled',
          'Check-out cancelled',
          'error'
        )
      }
    })
}

function timerModal(title, message)
{
  let timerInterval
  Swal.fire({
    title: title,
    html: message,
    timer: 2000,
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
      refreshPage();
    }
  })
}

function timerNoReloadModal(title, message)
{
  let timerInterval
  Swal.fire({
    title: title,
    html: message,
    timer: 2000,
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

function searchCheckout() {

  var transactionCode = $('#input-search-checkout').val();
  // Make the AJAX call to the URL
  $.ajax({
  type: 'POST',
  url: searchCheckoutUrl,
  data: {
      "_token": csrf, 
      "search_transaction": transactionCode 
  },
  dataType: 'json',
  success: function(res) {
    if(res.transaction_status_order_no == 5) {
      $('#checkout-title').text('TRANSACTION IS CLOSED!').css('color', 'red');
      timerModal('Transaction is close!', 'Search on check-in to view details.');
     
    } else if (res.transaction_status_order_no == 6) {
      $('#checkout-title').text('TRANSACTION NOT FOUND!').css('color', 'red');
      timerModal('Transaction not found!', 'Check your transaction code if correct.');
     
    } else { 
      // Material
      $('#input-title').val(res.title);
      $('#input-mat-type').val(res.material_type);
      $('#input-circulation-no').val(res.circulation_number);
      if(res.status == 1 || res.status == 2) {
        $('#input-status').val(res.status_name).css('color', 'green');                    
      } else {
        $('#input-status').val(res.status_name).css('color', 'red');
      }
      $('#input-cost').val(res.cost.toFixed(2));

      // Patron
      $('#inputName').val(res.name);
      $('#inputEmail').val(res.email);
      $('#inputContactNo').val(res.contactnum);
      $('#inputTransactions').val(res.total_transaction);
      $('#inputCheckouts').val(res.total_checkout);
      $('#inputOverdue').val(res.total_overdue);
      $('#fineRatePerHr').val(res.overdue_fine_rate);
      $('#input-due-date').val(res.due_date);

      const daysAllowedInput = document.querySelector('input[placeholder="No. days"]');
      const dueDate = new Date(res.due_date);
      const currentDate = new Date();
      const daysAllowed = Math.floor((dueDate.getTime() - currentDate.getTime()) / (24 * 60 * 60 * 1000));
      daysAllowedInput.value = daysAllowed;

      if(res.due_date == 'Not found') {
        $('#checkout-title').text('TRANSACTION NOT FOUND!');
      } else {   
        if(res.transaction_status_order_no == 3) {       
          $('#checkout-title').text('UPDATE CHECK-OUT');  
          $('#check-out-button').text('Update');
          $('#check-out-form').attr('action', updateCheckout);
        }

        if(res.transaction_status_order_no == 2) {       
          $('#checkout-title').text('CHECK-OUT RESERVATION');  
          $('#check-out-button').text('Check-out');
          $('#check-out-form').attr('action', updateCheckout);
        }
        if(res.transaction_status_order_no == 2 || res.transaction_status_order_no == 3) {
          $('#check-out-button').removeAttr("disabled");
        }
        if(res.transaction_status_order_no == 1) {       
          $('#checkout-title').text('FOR APPROVAL');  
          $('#check-out-button').text('For Approval');
          $('#check-out-button').attr("disabled", "disabled");
          $('#check-out-form').attr('action', '#');
        }


        $('#hidden-checkout-transaction-code').val(res.transaction_code);
        $('#hidden-circulation-number').val(res.circulation_number);
        $('#hidden-catalog-id').val(res.catalog_id);
        $('#hidden-patron-library-code').val(res.library_code);
        $('#hidden-user-id').val(res.user_id);
        $('#hidden-copy-id').val(res.copy_id);
        
      }
    }
  },
  error: function(e) {
      // Handle the error case
      console.log(e);
  }
  });
}

$(document).ready(function() {
    // Add click event listener to the search icon
    $('#find-material').click(function() {
        // Get the entered circulation number
        var circulationNumber = $('#findCirculationNo').val();

        Swal.fire({
          title: 'Loading',
          text: 'Please wait...',
          allowOutsideClick: false,
          showConfirmButton: false,
          willOpen: () => {
              Swal.showLoading();
          }
        });

        // Make the AJAX call to the URL
        $.ajax({
        type: 'POST',
        url: findMaterialUrl,
        data: {
            "_token": csrf, 
            "circulation_number": circulationNumber 
        },
        dataType: 'json',
        success: function(response) {
          
            Swal.close();
            // Set the returned value as the value for the input fields
            $('#input-title').val(response.title);
            $('#input-mat-type').val(response.material_type);
            $('#input-circulation-no').val(response.circulation_number);
            if(response.status == 1) {
                $('#input-status').val(response.status_name).css('color', 'green');
                $('#check-out-button').removeAttr("disabled");
                $('#checkout-title').text('ADD NEW CHECK-OUT');
                $('#check-out-form').attr('action', storeCheckout);
                $('#check-out-button').text('Check-out');                                  
            } else {
              $('#check-out-button').val("disabled");
                $('#input-status').val(response.status_name).css('color', 'red');
                $('#check-out-button').attr('disabled', 'disabled');
                $('#check-out-button').text('Check-out');
                timerNoReloadModal('Material is currently unavailable.','Search available material to be able to checkout.');
            }
            $('#input-cost').val(response.cost.toFixed(2));
            $('#hidden-circulation-number').val(response.circulation_number);
            $('#hidden-catalog-id').val(response.catalog_id);
            $('#hidden-copy-id').val(response.copy_id);
           
        },
        error: function(e) {
            // Handle the error case
            console.log(e.responseJSON);
            Swal.close();
        }
        });
    });

    $('#find-patron').click(function() {

        var libraryCode = $('#findLibraryCode').val();

        Swal.fire({
          title: 'Loading',
          text: 'Please wait...',
          allowOutsideClick: false,
          showConfirmButton: false,
          willOpen: () => {
              Swal.showLoading();
          }
        });

        // Make the AJAX call to the URL
        $.ajax({
        type: 'POST',
        url: findPatronUrl,
        data: {
            "_token": csrf, 
            "library_code": libraryCode 
        },
        dataType: 'json',
        success: function(response) {
            $('#inputName').val(response.name);
            $('#inputEmail').val(response.email);
            $('#inputContactNo').val(response.contactnum);
            $('#inputTransactions').val(response.total_transaction);
            $('#inputCheckouts').val(response.total_checkout);
            $('#inputOverdue').val((response.total_overdue));
            $('#hidden-patron-library-code').val(response.library_code);
            $('#hidden-user-id').val(response.user_id);

            $('#checkout-title').text('ADD NEW CHECK-OUT');
            $('#check-out-form').attr('action', storeCheckout);
            $('#check-out-button').text('Check-out');  
            Swal.close();
            
        },
        error: function(e) {
            // Handle the error case
            console.log('Somethings wrong!');
            Swal.close();
        }
        });
    });

    $('#search-transaction').click(function() {

        var transactionCode = $('#input-search-transaction').val();
        // Make the AJAX call to the URL
        $.ajax({
        type: 'POST',
        url: searchTransactionUrl,
        data: {
            "_token": csrf, 
            "search_transaction": transactionCode 
        },
        dataType: 'json',
        success: function(response) {

            if(response.transaction_code == 'Not found'){
              timerNoReloadModal('Transaction not found!', 'Please check your transaction code if correct.');
            }
            $('#checkin-inputName').val(response.name);
            $('#hidden-library-code').val(response.library_code);
            $('#checkin-inputEmail').val(response.email);
            $('#checkin-inputContactNo').val(response.contactnum);
            $('#checkin-input-total-transaction').val(response.total_transaction);
            $('#checkin-input-active-checkout').val(response.total_checkout);
            $('#checkin-input-active-overdue').val(response.total_overdue);
            $('#checkin-input-title').val(response.title);
            $('#checkin-input-mat-type').val(response.material_type);
            $('#checkin-input-circulation-no').val(response.circulation_number);
            $('#checkin-input-status').val(response.status_name);
            if(response.status == 1) {
              $('#checkin-input-status').val(response.status_name).css('color', 'green');                    
            } else {
              $('#checkin-input-status').val(response.status_name).css('color', 'red');
            }

            $('#checkin-input-cost').val(response.cost);
            $('#checkin-arrangement').val(response.reservation_code);
            $('#checkin-duedate').val(response.due_date);
            $('#checkin-released-date').val(response.released_date);
            $('#checkin-released-by').val(response.released_by);
            $('#checkin-transaction-code').val(response.transaction_code);
            $('#checkin-transaction-status').val(response.transaction_status);
            
            if(response.transaction_status_order_no == 3) {
              $("#check-in-btn").removeAttr("disabled");
              $("#add-fine-btn").removeAttr("disabled");
            }
            
            let released_date = new Date(response.released_date);
            let due_date = new Date(response.due_date);
            let daysAllowed = Math.floor((due_date.getTime() - released_date.getTime()) / (24 * 60 * 60 * 1000));
            let hoursAllowed = Math.floor((due_date.getTime() - released_date.getTime()) / (60 * 60 * 1000));
            
            if(daysAllowed <= 0) {
              $('#checkin-days-allowed').val(Math.abs(hoursAllowed));
              $('#label-days-allowed').text('Hours Allowed');
              
            } else {
              $('#checkin-days-allowed').val(daysAllowed);
              $('#label-days-allowed').text('Days Allowed');
            }

            let odfr = parseFloat(response.overdue_fine_rate).toFixed(2);
            let ovfa =  parseFloat(response.overdue_fine_amount).toFixed(2);
            let tfp = parseFloat(response.total_fine_paid).toFixed(2);
            let wa =  parseFloat(response.waived_amount).toFixed(2);
            let ofa = parseFloat(response.other_fine_amount).toFixed(2);
            $('#total-hrs-overdue').text(response.overdue_total_hours);
            $('#overdue-fine-rate').text('₱ ' + odfr);
            $('#overdue-fine').text('₱ ' + ovfa);
            $('#total-fine').text('₱ ' + tfp);
            $('#waive-amount').text('₱ -' + wa);
            $("#other-fine").text('₱ ' + ofa);
            $('#label-fine-desc').text(response.other_fine_description).css('font-size', '13px');
            $("#fine-description option").each(function() {
              if ($(this).val() == response.circulation_fine_id) {
                $("#fine-description ").val(response.circulation_fine_id);
              }
            });

            $("#textarea-fine-note").val(response.fine_note);
            $("#input-fine-note").val(response.fine_note);
            $("#input-waive-amount").val(response.waived_amount);
            $("#input-other-fine-amount").val(response.other_fine_amount);
            
            $('#hidden-transaction-code').val(response.transaction_code);
            $('#hidden-circulation-code').val(response.circulation_number);
            $('#hidden-total-hrs-overdue').val(response.overdue_total_hours);
            $('#hidden-overdue-fine').val(response.overdue_fine_amount);
            $('#hidden-waive-amount').val(response.waived_amount);
            $('#hidden-other-fine').val(response.other_fine_amount);
            $('#hidden-total-fine').val(response.total_fine_paid);
        },
        error: function(e) {
            // Handle the error case
            console.log(e);
        }
        });
    });

    let currentDate = new Date();
    let timeZoneOffset = currentDate.getTimezoneOffset() * 60000; // Get the time zone offset in milliseconds

    let localDate = new Date(currentDate.getTime() - timeZoneOffset);
    let localDateString = localDate.toJSON().slice(0, 16); // Format the date string as needed

    $("#input-due-date").val(localDateString);


    // Get the form elements
    const daysAllowedInput = document.querySelector('input[placeholder="No. days"]');
    const dueDateInput = document.querySelector('input[type="datetime-local"]');

    // Add event listeners to the inputs
    daysAllowedInput.addEventListener('input', adjustDueDate);
    dueDateInput.addEventListener('change', adjustDaysAllowed);

    // Function to adjust the due date based on days allowed
    function adjustDueDate() {
    const daysAllowed = parseInt(daysAllowedInput.value);

    if (daysAllowed) {
        const currentDate = new Date();
        const dueDate = new Date(currentDate.getTime() + (daysAllowed * 24 * 60 * 60 * 1000) + (overdueHours * 60 * 60 * 1000));
        dueDateInput.value = formatDate(dueDate);
    }
    }

    // Function to adjust the days allowed based on the due date
    function adjustDaysAllowed() {
    const dueDate = new Date(dueDateInput.value);
    const currentDate = new Date();
    const daysAllowed = Math.floor((dueDate.getTime() - currentDate.getTime()) / (24 * 60 * 60 * 1000));
    daysAllowedInput.value = daysAllowed;
    }

    // Function to format the date in the format expected by the datetime-local input
    function formatDate(date) {
    const year = date.getFullYear();
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const day = date.getDate().toString().padStart(2, '0');
    const hours = date.getHours().toString().padStart(2, '0');
    const minutes = date.getMinutes().toString().padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
    }
});