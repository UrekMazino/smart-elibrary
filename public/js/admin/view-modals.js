  function viewMaterial(catalog_id, circulation_number, id) {
    $(document).ready(function() {
      $('.approve-reservations').each(function() {
        if (!this.disabled) {
          this.disabled = true; 
        }
      });
      // Add onChange event to the select element
      $('#material-circulation-numbers').change(function() {
          // Get the selected option value
          var selectedValue = $(this).val();
          $('#circulation-number').val(selectedValue);
          $('#approve-reservation-'+id).removeAttr('disabled'); 
          id = 'clear';
      });

    });
    // Make the AJAX call to the URL
    $.ajax({
      type: 'GET',
      url: viewMaterialUrl,
      data: {
          "catalog_id": catalog_id,
          "circulation_number": circulation_number,
      },
      dataType: 'json',
      success: function(res) {
        $('#viewMaterialModal').modal('show');
        $('#viewMaterialModal').fadeIn();
  
        $('#material-title').val(res.title);
        $('#material-catalog-id').val(res.catalog_id);
        $('#material-status').val(res.status);
        $('#material-cost').val(res.cost);
        $('#material-acquisition-mode').val(res.acquisition_mode);
        $('#material-source').val(res.source);

        if(res.circulation_number == 'null') {
            if(id == 0){
              $('#material-circulation-numbers').hide();
              $('#material-circulation-number').val("No selected circulation no. yet.");
              $('#material-circulation-number').show();
            } else {
              $('#material-circulation-numbers').show();
              $('#material-circulation-number').hide();
            }
            // Get a reference to the select element
            var selectElement = $('#material-circulation-numbers');
            // Clear the current options, except for the first option
            selectElement.find('option:not(:first)').remove();
            // Make the first option selected
            selectElement.val(selectElement.find('option:first').val());
            
            if(res.circulation_numbers.length > 0) {
              res.circulation_numbers.forEach(function(item) {
                // Create an option element
                var option = $('<option></option>');         
                // Set the value and text of the option element
                option.val(item.circulation_number);
                option.text(item.circulation_number);
                // Append the option element to the select element
                $('#material-circulation-numbers').append(option);
              });
            } else {
              var option = $('<option></option>');         
              // Set the value and text of the option element
              option.val('No available copy!');
              option.text('No available copy!');
              option.attr('disabled','disabled');
              $('#material-circulation-numbers').append(option);
            }
        } else {
          $('#material-circulation-number').val(res.circulation_number);
          $('#material-circulation-numbers').hide();
          $('#material-circulation-number').show();
        }
        
      },
      error: function(e) {
          // Handle the error case
          console.log('Somethings wrong!');
      }
    });

  }
  
  function viewPatron(user_id) {
  
    // Make the AJAX call to the URL
    $.ajax({
      type: 'GET',
      url: viewPatronUrl,
      data: {
          "user_id": user_id 
      },
      dataType: 'json',
      success: function(res) {
        $('#viewPatronModal').modal('show');
        $('#viewPatronModal').fadeIn();
  
        $('#patron-name').val(res.name);
        $('#patron-email').val(res.email);
        $('#patron-contact-number').val(res.contact_no);
        $('#patron-birthdate').val(res.birthdate);
        $('#patron-library-code').val(res.library_code);
        $('#patron-sex').val(res.sex);
        $('#patron-position').val(res.position);
        $('#patron-location').val(res.location);
      },
      error: function(e) {
          // Handle the error case
          console.log('Somethings wrong!');
      }
    });
  }

  function viewTransaction(transaction_code) {
  
    // Make the AJAX call to the URL
    $.ajax({
      type: 'GET',
      url: viewTransactionUrl,
      data: {
          "transaction_code": transaction_code 
      },
      dataType: 'json',
      success: function(res) {
        $('#viewTransactionModal').modal('show');
        $('#viewTransactionModal').fadeIn();
  
        if(res.status == 'Declined') {
          $('.modal-trans-result, .modal-approved-result, .modal-checkin-result').hide();
          $('.modal-declined-result').show();
        } else if(res.status == 'For Approval') {
          $('.modal-trans-result, .modal-declined-result, .modal-checkin-result, .modal-approved-result').hide();
        } else if(res.status == 'Approved') {
          $('.modal-trans-result, .modal-declined-result, .modal-checkin-result').hide();
          $('.modal-approved-result').show();
        } else if(res.status == 'Open') {
          $('.modal-declined-result, .modal-checkin-result').hide();
          $('.modal-trans-result, .modal-approved-result').show();
        } else {
          $('.modal-trans-result, .modal-approved-result, .modal-checkin-result').show();
          $('.modal-declined-result').hide();
        }
        if(res.reservation_code == 'N/A') {
          $('.modal-approved-result').hide();
        }
        $('#transaction-code').val(res.transaction_code);
        $('#transaction-status').val(res.status);
        $('#transaction-due-date').val(res.due_date);
        $('#transaction-overdue-fine-rate').val(res.overdue_fine_rate);
        $('#transaction-waived-amount').val(res.waived_amount);
        $('#transaction-total-fine').val(res.total_fine);
        $('#transaction-total-fine-paid').val(res.total_fine_paid);
        $('#transaction-approved-at').val(res.approved_at);
        $('#transaction-approved-by').val(res.approved_by);
        $('#transaction-declined-at').val(res.declined_at);
        $('#transaction-declined-by').val(res.declined_by);
        $('#transaction-released-at').val(res.released_at);
        $('#transaction-released-by').val(res.released_by);
        $('#transaction-received-at').val(res.received_at);
        $('#transaction-received-by').val(res.received_by);
      },
      error: function(e) {
          // Handle the error case
          console.log('Somethings wrong!');
      }
    });
  }

  function viewReservation(reservation_code) {
      // Make the AJAX call to the URL
    $.ajax({
      type: 'GET',
      url: viewReservationUrl,
      data: {
          "reservation_code": reservation_code 
      },
      dataType: 'json',
      success: function(res) {
        $('#viewReservationModal').modal('show');
        $('#viewReservationModal').fadeIn();
  
        $('#reservation-code').val(res.reservation_code);
        $('#reservation-visit-date').val(res.visit_date);
        $('#reservation-visit-time').val(res.visit_time);
       
      },
      error: function(e) {
          // Handle the error case
          console.log('Somethings wrong!');
      }
    });
  }