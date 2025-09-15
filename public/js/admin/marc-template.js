function showAddTagModal() {
    $('#viewAddTagModal').modal('show');
    $('#viewAddTagModal').fadeIn();
}

// -------------------------->This<-------------------------------|
// -------------------------->is the<-----------------------------|
// -------------------------->removeTag()<------------------------|
// -------------------------->function<---------------------------|

function removeTag(iconElement, tag_id_to_remove) {

    let arrayIds =  JSON.parse($('#fieldIds').val());

    let filteredIds = arrayIds.filter(function(item) {
        return item.id !== tag_id_to_remove;
      });
    let stringIds = JSON.stringify(filteredIds);
    $('#fieldIds').val(stringIds);

    // Get the closest ancestor div with class "row mb-5"
    let rowElement = $(iconElement).closest('.row.mb-5');

    // Fade out the row element over 500 milliseconds and then remove it
    rowElement.fadeOut(200, function() {
      $(this).remove();
    });
}

// -------------------------->This<-------------------------------|
// -------------------------->is the<-----------------------------|
// -------------------------->removeSubfield()<-------------------|
// -------------------------->function<---------------------------|

function removeSubfield(iconElement, tagid_where_to_remove) {

    let subfieldId =  $(iconElement).closest('.additional-subfield').attr('id').split('-')[1];

    let arrayOfIds = JSON.parse($('#fieldIds').val());

    let selectedData = arrayOfIds.find(function(item) {
        return item.id == tagid_where_to_remove;
    });

    selectedData.subfield[subfieldId] = null;
    selectedData.default[subfieldId] = null;

        // Find the index of the object with the matching id in arrayOfIds
    let indexToUpdate = arrayOfIds.findIndex(function (item) {
        return item.id === selectedData.id;
    });

    // Update the subfield array for the matching object
    if (indexToUpdate !== -1) {
        arrayOfIds[indexToUpdate].subfield = selectedData.subfield;
    }
    let stringIds = JSON.stringify(arrayOfIds);

    $('#fieldIds').val(stringIds);
    
    let rowElement = $(iconElement).closest('.additional-subfield');

    rowElement.fadeOut(200, function() {
        $(this).hide();
    });
}

// -------------------------->This<-------------------------------|
// -------------------------->is the<-----------------------------|
// -------------------------->addSubfield()<----------------------|
// -------------------------->function<---------------------------|

function addSubfield(iconElement, tag_id_to_add) {
    $.ajax({
        type: 'GET',
        url: getMarcDetailsUrl,
        data: {
            "marctag_id": tag_id_to_add,
        },
        
        dataType: 'json',
        success: function(res) {        
            let subfields = res.subfields;
            let tag_id = res.id;
            let rowElement = $(iconElement).closest('.subfield-parent');
            let childCount = rowElement.children().length - 3;
            let addtionalContainer = $('<div>').addClass('mb-2 additional-subfield').attr('id', 'subfield-'+ childCount);
            let removeSubfieldIcon = $('<i>').addClass('sm-font danger-clr mr in-b').attr({
                'data-bs-toggle': 'tooltip',
                'data-bs-placement': 'top',
                'title': 'Remove Subfield',
                'data-feather': 'minus-square',
                'onclick': 'removeSubfield(this,'+res.id+')',
            });
        
            // Create the input element for "default_value"
            let defaultValueInput = $('<input>').attr({
            'id': 'defaultValue-'+childCount,
            'class': 'form-control in-b width25 mr5',
            'type': 'text',
            'placeholder': 'Default Value'
            });


            // This part
            // upon subfield creation
            // create a temporary null value
            let arrayOfIds = JSON.parse($('#fieldIds').val());
            let selectedData = arrayOfIds.find(function(item) {
                return item.id == tag_id;
            });
 
            selectedData.default[childCount] = null;
            let indexToUpdate = arrayOfIds.findIndex(function (item) {
                return item.id === selectedData.id;
            });
            
            // Update the subfield array for the matching object
            if (indexToUpdate !== -1) {
                arrayOfIds[indexToUpdate].default = selectedData.default;
            }
            let stringIds = JSON.stringify(arrayOfIds);

            $('#fieldIds').val(stringIds);
            
            
            
            
            defaultValueInput.on('keyup', function(event) {
                let defaultInput = $(this);
          
                let arrayOfIds = JSON.parse($('#fieldIds').val());
                // Variable to store the timeout reference
                let typingTimer;
   
                // Time in milliseconds to wait after user stops typing
                const delayTime = 0;
                
                // Clear the previous timeout
                clearTimeout(typingTimer);
            
                // Start a new timeout
                typingTimer = setTimeout(function() {
                
                    // Get the current value of the input field
                    let currentValue = defaultInput.val();
                    
                    let selectedData = arrayOfIds.find(function(item) {
                        return item.id == tag_id;
                    });

                    // Update the "subfield" array in the selectedData with the selected value
                    selectedData.default[childCount] = currentValue;
  
                    let indexToUpdate = arrayOfIds.findIndex(function (item) {
                        return item.id === selectedData.id;
                    });
                    
                    // Update the subfield array for the matching object
                    if (indexToUpdate !== -1) {
                        arrayOfIds[indexToUpdate].default = selectedData.default;
                    }
                    let stringIds = JSON.stringify(arrayOfIds);

                    $('#fieldIds').val(stringIds);

                }, delayTime);
            });


            // Create the select element with options
            let selectElement = $('<select>').addClass('form-select in-b width25 mr5');

            // Append the initial disabled option
            selectElement.append(
                $('<option>').attr('selected', 'selected').attr('disabled', 'disabled').text('Please Select Subfield')
            );
              
            if(subfields.length > 0) {   
                // Loop through the subfields array and append options dynamically
                for (let i = 0; i < subfields.length; i++) {
                    let optionText = subfields[i].code + ' - ' + subfields[i].description;
                    let optionValue = subfields[i].id;
                    selectElement.append($('<option>').text(optionText).val(optionValue));
                }
            
            }

            
            // Add the "change" event listener to the select element
            selectElement.on('change', function() {
                            
                let selectedValue = $(this).val();

                let subfieldId =  $(this).closest('.additional-subfield').attr('id').split('-')[1];

                let arrayOfIds = JSON.parse($('#fieldIds').val());

         
                // Get the corresponding data object based on the selectedValue's id
                let selectedData = arrayOfIds.find(function(item) {
                    return item.id == tag_id;
                });

                // Update the "subfield" array in the selectedData with the selected value
                selectedData.subfield[subfieldId] = selectedValue;

                 // Find the index of the object with the matching id in arrayOfIds
                 let indexToUpdate = arrayOfIds.findIndex(function (item) {
                    return item.id === selectedData.id;
                });
                
                // Update the subfield array for the matching object
                if (indexToUpdate !== -1) {
                    arrayOfIds[indexToUpdate].subfield = selectedData.subfield;
                }
                let stringIds = JSON.stringify(arrayOfIds);

                $('#fieldIds').val(stringIds);
            });

            addtionalContainer.append(removeSubfieldIcon, selectElement, defaultValueInput);

            rowElement.append(addtionalContainer);

            feather.replace();
        
        },
        error: function(e) {
            // Handle the error case
            console.log('Somethings wrong!');
        }
      });   

   
}

// -------------------------->This<-------------------------------|
// -------------------------->is the<-----------------------------|
// -------------------------->addTag()<---------------------------|
// -------------------------->function<---------------------------|

function addTag(id, toEditfields = [], a) {

    if (toEditfields.length == 0 || typeof toEditfields[a] === 'undefined') {
        let arrayIds =  JSON.parse($('#fieldIds').val());

        let jsonString = '{"id": '+ id +', "subfield": [], "default":[], "indicator":["0","0"], "required":0}';

        // Parse the JSON string into a JavaScript object
        let jsonObject = JSON.parse(jsonString);

        arrayIds.push(jsonObject);

        let stringIds = JSON.stringify(arrayIds);

        $('#fieldIds').val(stringIds);
 
    }
    $('#viewAddTagModal').modal('hide');
    // AJAX CALL
    $.ajax({
        type: 'GET',
        url: getMarcDetailsUrl,
        data: {
            "marctag_id": id,
        },
        dataType: 'json',
        success: function(res) {     
            let tag_id = res.id;   
            let subfields = res.subfields;
            let first_indictors = res.first_indictors;
            let second_indicators = res.second_indicators;

            // Create the main row div with class "row mb-5"
            let mainRow = $('<div>').addClass('row mb-5');

            // Create the first inner container div with class "col col-lg-12"
            let firstInnerContainer = $('<div>').addClass('col col-lg-12 subfield-parent');

            // Create the label element
            let labelElement = $('<label>').attr('for', 'fieldName').addClass('form-label in-b mr').text(res.code + ' - ' + res.description);

            // Create the first icon element for "Remove Tag"
            let removeTagIcon1 = $('<i>').addClass('sm-font danger-clr').attr({
            'data-bs-toggle': 'tooltip',
            'data-bs-placement': 'top',
            'title': 'Remove Tag',
            'data-feather': 'minus-square',
            'onclick': 'removeTag(this,'+res.id+')',
            });

            // Append label and icon1 to the first inner container
            firstInnerContainer.append(labelElement, removeTagIcon1);

            // Create the second inner container div with class "block mb-2"
            let secondInnerContainer = $('<div>').addClass('block mb-2');

            // Create the second label element for "IND"
            let indLabel = $('<label>').attr('for', 'fieldName').addClass('form-label in-b mr').text('IND');

            // Create the select element with options
            let indicator1Element = $('<select>').addClass('form-select in-b width8 mr5').attr('id', 'indicator1');

            // Append the initial disabled option
            indicator1Element.append(
                toEditfields.length > 0 ? $('<option>').text('None').val(0) : $('<option>').attr('selected', 'selected').text('None').val(0)
            );

            if(first_indictors[0].length != 0) {   
                // Loop through the subfields array and append options dynamically
                for (let i = 0; i < first_indictors.length; i++) {
                    
                    let optionText = first_indictors[i].indicator;
                    
                    let optionValue = first_indictors[i].id;
                    
                    if (toEditfields.length > 0 && typeof toEditfields[a] !== 'undefined') {
                        
                        if(toEditfields[a].indicator[0] == optionValue) {

                            indicator1Element.append($('<option>').attr('selected', 'selected').text(optionText).val(optionValue));

                        } else {
                            indicator1Element.append($('<option>').text(optionText).val(optionValue));
                        }
                    } else {
                        indicator1Element.append($('<option>').text(optionText).val(optionValue));
                    }
                    
                }
            }

            // Add the "change" event listener to the select element
            indicator1Element.on('change', function() {
                
                let selectedValue = $(this).val();
                let arrayOfIds = JSON.parse($('#fieldIds').val());

                // Get the corresponding data object based on the selectedValue's id
                let selectedData = arrayOfIds.find(function(item) {
                    return item.id == tag_id;
                });

                // Update the "indicator" array in the selectedData with the selected value
                selectedData.indicator[0] = selectedValue;

                // Find the index of the object with the matching id in arrayOfIds
                let indexToUpdate = arrayOfIds.findIndex(function (item) {
                    return item.id === selectedData.id;
                });
                
                // Update the indicator array for the matching object
                if (indexToUpdate !== -1) {
                    arrayOfIds[indexToUpdate].indicator = selectedData.indicator;
                }
                let stringIds = JSON.stringify(arrayOfIds);

                $('#fieldIds').val(stringIds);

            });


            // Create the select element with options
            let indicator2Element = $('<select>').addClass('form-select in-b width8 mr5').attr('id', 'indicator2');

            // Append the initial disabled option
            indicator2Element.append(
                toEditfields.length > 0 ? $('<option>').text('None').val(0) : $('<option>').attr('selected', 'selected').text('None').val(0)
            );
            
            if(second_indicators[0].length != 0) {   
                // Loop through the second_indicators array and append options dynamically
                for (let i = 0; i < second_indicators.length; i++) {
                    let optionText = second_indicators[i].indicator;
                    let optionValue = second_indicators[i].id;


                    if (toEditfields.length > 0 && typeof toEditfields[a] !== 'undefined') {

                        if(toEditfields[a].indicator[1] == optionValue) {

                            indicator2Element.append($('<option>').attr('selected', 'selected').text(optionText).val(optionValue));

                        } else {
                            indicator2Element.append($('<option>').text(optionText).val(optionValue));
                        }
                    } else {
                        indicator2Element.append($('<option>').text(optionText).val(optionValue));
                    }
                }
            }

            // Add the "change" event listener to the select element
            indicator2Element.on('change', function() {
                
                let selectedValue = $(this).val();
                let arrayOfIds = JSON.parse($('#fieldIds').val());

                // Get the corresponding data object based on the selectedValue's id
                let selectedData = arrayOfIds.find(function(item) {
                    return item.id == tag_id;
                });

                // Update the "indicator" array in the selectedData with the selected value
                selectedData.indicator[1] = selectedValue;

                // Find the index of the object with the matching id in arrayOfIds
                let indexToUpdate = arrayOfIds.findIndex(function (item) {
                    return item.id === selectedData.id;
                });
                
                // Update the indicator array for the matching object
                if (indexToUpdate !== -1) {
                    arrayOfIds[indexToUpdate].indicator = selectedData.indicator;
                }
                let stringIds = JSON.stringify(arrayOfIds);

                $('#fieldIds').val(stringIds);
            });

            // Create the form-check div for "Required" checkbox
            let formCheckDiv = $('<div>').addClass('form-check in-b mr');

            // Create the label for "Required" checkbox
            let requiredLabel = $('<label>').addClass('form-check-label').attr('for', 'isRequired').text('Required');

            let isChecked = false;
            
            if (toEditfields.length > 0 && typeof toEditfields[a] !== 'undefined') {
                isChecked = toEditfields[a].required === 1 ? true : false;
            }
            // Create the "Required" checkbox
            let requiredCheckbox = $('<input>').attr({
            'type': 'checkbox',
            'class': 'form-check-input',
            'id': 'isRequired',
            'checked': isChecked,
            });

            // Add onclick event handler
            requiredCheckbox.on('click', function() {
                    let arrayOfIds = JSON.parse($('#fieldIds').val());
                    
                    // Get the corresponding data object based on the selectedValue's id
                    let selectedData = arrayOfIds.find(function(item) {
                        return item.id == tag_id;
                    });

                    // Update the "indicator" array in the selectedData with the selected value
                    selectedData.required = this.checked ? 1 : 0;

                    // Find the index of the object with the matching id in arrayOfIds
                    let indexToUpdate = arrayOfIds.findIndex(function (item) {
                        return item.id === selectedData.id;
                    });

                    // Update the required variable for the matching object
                    if (indexToUpdate !== -1) {
                        arrayOfIds[indexToUpdate].required = selectedData.required;
                    }

                    let stringIds = JSON.stringify(arrayOfIds);

                    $('#fieldIds').val(stringIds);

            });

            // Append the label and checkbox to the form-check div
            formCheckDiv.append(requiredLabel, requiredCheckbox);

            // Create the icon element for "Add Subfield"
            let addSubfieldIcon = $('<i>').addClass('sm-font success-clr').attr({
            'data-bs-toggle': 'tooltip',
            'data-bs-placement': 'top',
            'title': 'Add Subfield',
            'data-feather': 'plus-square',
            'onclick': 'addSubfield(this,'+res.id+')',
            });

            // Append the label, input elements, form-check div, and addSubfieldIcon to the second inner container
            secondInnerContainer.append(indLabel, indicator1Element, indicator2Element, formCheckDiv, addSubfieldIcon);

            if(toEditfields.length > 0 && typeof toEditfields[a] !== 'undefined') {
               
                let thirdInnerContainer = $('<div>').addClass('mb-2 additional-subfield');

                for(let b = 0; b < toEditfields[a].subfield.length; b++ ) {

                    let rowDiv = $('<div>').addClass('mb-2 block');
                    
                    thirdInnerContainer.attr('id', 'subfield-'+ b);

                    let removeSubfieldIcon = $('<i>').addClass('sm-font ' + (b == 0 ? 'dull-clr' : 'danger-clr') + ' mr in-b').attr({
                        'data-bs-toggle': 'tooltip',
                        'data-bs-placement': 'top',
                        'title': 'Remove Subfield',
                        'data-feather': 'minus-square',
                        'onclick': b == 0 ? "" : 'removeSubfield(this,'+toEditfields[a].id+')',
                    });


                    // Create the input element for "default_value"
                    let defaultValueInput = $('<input>').attr({
                        'id': 'defaultValue-'+b,
                        'class': 'form-control in-b width25 mr5',
                        'type': 'text',
                        'placeholder': 'Default Value',
                        'value': toEditfields[a].default[b],
                    });
        
                    defaultValueInput.on('keyup', function(event) {
                        let defaultInput = $(this);
                        
                        let arrayOfIds = JSON.parse($('#fieldIds').val());
                        // Variable to store the timeout reference
                        let typingTimer;
        
                        // Time in milliseconds to wait after user stops typing
                        const delayTime = 0;
                        
                        // Clear the previous timeout
                        clearTimeout(typingTimer);
                    
                        // Start a new timeout
                        typingTimer = setTimeout(function() {
                        
                        // Get the current value of the input field
                        let currentValue = defaultInput.val();
                        
                        let selectedData = arrayOfIds.find(function(item) {
                            return item.id == tag_id;
                        });
        
                        // Update the "subfield" array in the selectedData with the selected value
                        selectedData.default[b] = currentValue;
        
                        let indexToUpdate = arrayOfIds.findIndex(function (item) {
                            return item.id === selectedData.id;
                        });
                        
                        // Update the subfield array for the matching object
                        if (indexToUpdate !== -1) {
                            arrayOfIds[indexToUpdate].default = selectedData.default;
                        }
                        let stringIds = JSON.stringify(arrayOfIds);
        
                        $('#fieldIds').val(stringIds);
        
                        }, delayTime);
                    });
        
        
                    // Create the select element with options
                    let selectElement = $('<select>').addClass('form-select in-b width25 mr5');
        
                    // Append the initial disabled option
                    selectElement.append(
                        (toEditfields[a].subfield.length > 0) ? $('<option>').attr('disabled', 'disabled').text('Please Select Subfield') : $('<option>').attr('selected', 'selected').attr('disabled', 'disabled').text('Please Select Subfield')
                    );
                    
                    if(subfields.length > 0) {   
                        // Loop through the subfields array and append options dynamically
                        for (let i = 0; i < subfields.length; i++) {
                            let optionText = subfields[i].code + ' - ' + subfields[i].description;
                            let optionValue = subfields[i].id;

                            if(toEditfields[a].subfield.length > 0) {
                                if(toEditfields[a].subfield[b] == optionValue){
                                    selectElement.append($('<option>').attr('selected', 'selected').text(optionText).val(optionValue));
                                }
                                selectElement.append($('<option>').text(optionText).val(optionValue));

                            } else {
                                selectElement.append($('<option>').text(optionText).val(optionValue));
                            }
                            
                        }
                    
                    }
        
                    // Add the "change" event listener to the select element
                    selectElement.on('change', function() {
                                    
                        let selectedValue = $(this).val();
        
                        let subfieldId =  $(this).closest('.additional-subfield').attr('id').split('-')[1];
        
                        let arrayOfIds = JSON.parse($('#fieldIds').val());
                
                        // Get the corresponding data object based on the selectedValue's id
                        let selectedData = arrayOfIds.find(function(item) {
                            return item.id == tag_id;
                        });
        
                        // Update the "subfield" array in the selectedData with the selected value
                        selectedData.subfield[subfieldId] = selectedValue;
        
                        // Find the index of the object with the matching id in arrayOfIds
                        let indexToUpdate = arrayOfIds.findIndex(function (item) {
                            return item.id === selectedData.id;
                        });
                        
                        // Update the subfield array for the matching object
                        if (indexToUpdate !== -1) {
                            arrayOfIds[indexToUpdate].subfield = selectedData.subfield;
                        }
                        let stringIds = JSON.stringify(arrayOfIds);
        
                        $('#fieldIds').val(stringIds);
        
                    });
                    rowDiv.append(removeSubfieldIcon, selectElement, defaultValueInput);
                    thirdInnerContainer.append(rowDiv);
                    
                 
        
                }

                // Append the select element and default value input to the first inner container
                firstInnerContainer.append(secondInnerContainer, thirdInnerContainer);

                // Append the first inner container to the main row
                mainRow.append(firstInnerContainer);
                // Append the main row to the existing "main-el" div
                $('#main-el').append(mainRow);

                // Add Feather icons functionality
                feather.replace();

            } else {

                let thirdInnerContainer = $('<div>').addClass('mb-2 additional-subfield').attr('id', 'subfield-0');

                // Create the third icon element for "Remove Subfield"
                let removeSubfieldIcon = $('<i>').addClass('sm-font dull-clr mr').attr({
                'data-bs-toggle': 'tooltip',
                'data-bs-placement': 'top',
                'title': 'Remove Subfield',
                'data-feather': 'minus-square',
                });
        
                // Create the select element with options
                let selectElement = $('<select>').addClass('form-select in-b width25 mr5').attr('id', 'subfield');

                // Append the initial disabled option
                selectElement.append(
                    $('<option>').attr('selected', 'selected').attr('disabled', 'disabled').text('Please Select Subfield')
                );
                if(subfields.length > 0) {   
                    // Loop through the subfields array and append options dynamically
                    for (let i = 0; i < subfields.length; i++) {
                        let optionText = subfields[i].code + ' - ' + subfields[i].description;
                        let optionValue = subfields[i].id;
                        selectElement.append($('<option>').text(optionText).val(optionValue));
                    }
                
                }

                // Add the "change" event listener to the select element
                selectElement.on('change', function() {
                    
                    let selectedValue = $(this).val();
                    let arrayOfIds = JSON.parse($('#fieldIds').val());

                    // Get the corresponding data object based on the selectedValue's id
                    let selectedData = arrayOfIds.find(function(item) {
                        return item.id == tag_id;
                    });

                    // Update the "subfield" array in the selectedData with the selected value
                    selectedData.subfield[0] = selectedValue;

                    // Find the index of the object with the matching id in arrayOfIds
                    let indexToUpdate = arrayOfIds.findIndex(function (item) {
                        return item.id === selectedData.id;
                    });
                    
                    // Update the subfield array for the matching object
                    if (indexToUpdate !== -1) {
                        arrayOfIds[indexToUpdate].subfield = selectedData.subfield;
                    }
                    let stringIds = JSON.stringify(arrayOfIds);

                    $('#fieldIds').val(stringIds);
                });


                // Create the input element for "default_value"
                let defaultValueInput = $('<input>').attr({
                'id': 'defaultValue-0',
                'class': 'form-control in-b width25 mr5',
                'type': 'text',
                'placeholder': 'Default Value',
                });

                defaultValueInput.on('keyup', function(event) {
                    let defaultInput = $(this).val();
                    
                    let arrayOfIds = JSON.parse($('#fieldIds').val());
                    // Variable to store the timeout reference
                    let typingTimer;
    
                    // Time in milliseconds to wait after user stops typing
                    const delayTime = 0;
                    
                    // Clear the previous timeout
                    clearTimeout(typingTimer);
                
                    // Start a new timeout
                    typingTimer = setTimeout(function() {
                    
                    // Get the current value of the input field
                    let currentValue = defaultInput;
                    
                    let selectedData = arrayOfIds.find(function(item) {
                        return item.id == tag_id;
                    });

                    // Update the "subfield" array in the selectedData with the selected value
                    selectedData.default[0] = currentValue;

                    let indexToUpdate = arrayOfIds.findIndex(function (item) {
                        return item.id === selectedData.id;
                    });
                    
                    // Update the subfield array for the matching object
                    if (indexToUpdate !== -1) {
                        arrayOfIds[indexToUpdate].default = selectedData.default;
                    }
                    let stringIds = JSON.stringify(arrayOfIds);

                    $('#fieldIds').val(stringIds);

                    }, delayTime);
                });
                            
                thirdInnerContainer.append(removeSubfieldIcon, selectElement, defaultValueInput);
        
                // Append the select element and default value input to the first inner container
                firstInnerContainer.append(secondInnerContainer, thirdInnerContainer);
                // Append the first inner container to the main row
                mainRow.append(firstInnerContainer);
                // Append the main row to the existing "main-el" div
                $('#main-el').append(mainRow);

                // Add Feather icons functionality
                feather.replace();
            }
        },
        error: function(e) {
            // Handle the error case
            console.log('Somethings wrong!');
        }
      });   
}