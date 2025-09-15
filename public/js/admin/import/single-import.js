$(document).ready(function(){
   let data =  JSON.parse($('#fieldData').val());
   let flag = $('#importFlag').val();
   let fields = data.fields; 
    if(typeof fields !== 'undefined' && fields.length)
    {
        if(fields.length > 0 && flag == 1) {
    
            let arrayData =  [];
            $('#template-div').empty();
            $("#accessionNumber").removeAttr("required");
            for (let i = 0; i < fields.length; i++) {
                    
                // let arrayData =  JSON.parse($('#fieldData').val());
                let subFieldArr = [];

                let mainRow = $('<div>').addClass('row mb-3');

                let firstInnerContainer = $('<div>').addClass('col col-lg-12 subfield-parent');

                let labelElement = $('<label>').attr('for', 'fieldName').addClass('form-label in-b mr strong').text(fields[i].tag.code + ' - ' + fields[i].tag.description);

                // Append label and icon1 to the first inner container
                firstInnerContainer.append(labelElement);

                // Create the second inner container div with class "block mb-2"
                let secondInnerContainer = $('<div>').addClass('block mb-2');

                let thirdInnerContainer = $('<div>').addClass('mb-2 additional-subfield').attr('id', i+1);
                let tag_code = fields[i].tag.code;
                let tagFilter = tag_code == "001" || tag_code == "003" || tag_code == "005" || tag_code == "006" || tag_code == "007";
                    if (tag_code == "LDR" || tag_code == "008") {
                        // Create a button to trigger the modal
                        let showDataButton = $('<button>')
                            .addClass('btn btn-primary')
                            .attr('type', 'button')
                            .text('Show Data');
                    
                        // Append the button to the first inner container
                        firstInnerContainer.append(showDataButton);
                    
                        // Define the modal content as a table
                        let modalContent = $('<table>')
                            .addClass('table')
                            .append(
                                $('<thead>').append(
                                    $('<tr>').append(
                                        $('<th>').text('Sub-index'),
                                        $('<th>').text('ID'),
                                        $('<th>').text('Code'),
                                        $('<th>').text('Description'),
                                        $('<th>').text('Value')
                                    )
                                ),
                                $('<tbody>')
                            );
                    
                        // Create a row for each subfield
                        for (let x = 0; x < fields[i].subfields.length; x++) {
                            let subFieldCode = fields[i].subfields[x].code;
                            let subFieldDescription = fields[i].subfields[x].description;
                            let subFieldId = fields[i].subfields[x].id;
                            let subFieldValue = fields[i].subfields[x].default;
                            if(subFieldCode == 'LDR$06') {

                                // Get the select element by its ID
                                let selectElement = document.getElementById('materialTypeSelect');

                                // Loop through the options in the select element
                                for (let i = 0; i < selectElement.options.length; i++) {
                                    let option = selectElement.options[i];
                                    // Check if the option's class attribute contains the desired class name
                                    if (option.classList.contains(subFieldValue)) {
                                        // Set the matched option as selected
                                        option.selected = true;
                                        // Prevent any interactions with the select element
                                        selectElement.addEventListener('click', function (e) {
                                            e.preventDefault();
                                            e.stopPropagation();
                                        });
                                        break; // Exit the loop once a match is found
                                    }
                                }    
                            }

                            subFieldArr.push({
                                'sub-index': x + 1,
                                'id': subFieldId,
                                'code': subFieldCode,
                                'description': subFieldDescription,
                                'value': subFieldValue
                            });
                            // Create a table row
                            let tableRow = $('<tr>');
                    
                            // Add data to the table cells
                            tableRow.append(
                                $('<td>').text(x + 1),
                                $('<td>').text(subFieldId),
                                $('<td>').text(subFieldCode.replace("LDR$", "")),
                                $('<td>').text(subFieldDescription),
                                $('<td>').append(
                                    $('<input>')
                                        .addClass('form-control in-b mr5')
                                        .attr({
                                            'id': x + 1,
                                            'type': 'text',
                                            'placeholder': 'Enter value',
                                            'value': subFieldValue == "Enter value" ? "" : subFieldValue,
                                            'required': fields[i].tag.isRequired == 1 ? 'required' : "",
                                            'disabled': true,
                                        })
                                )
                            );
                    
                            // Append the table row to the modal content body
                            modalContent.find('tbody').append(tableRow);
                        }
                    
                        // Create the modal dialog
                        let modalDialog = $('<div>')
                            .addClass('modal-dialog modal-lg')
                            .append(
                                $('<div>')
                                    .addClass('modal-content')
                                    .append(
                                        $('<div>')
                                            .addClass('modal-header')
                                            .append(
                                                $('<h5>')
                                                    .addClass('modal-title')
                                                    .text(tag_code +' - '+ fields[i].tag.description)
                                            ),
                                        $('<div>')
                                            .addClass('modal-body')
                                            .append(modalContent)
                                    )
                            );
                    
                        // Create the modal
                        let modal = $('<div>')
                            .addClass('modal fade')
                            .attr('id', 'showModal' + tag_code)
                            .append(modalDialog);
                    
                        // Append the modal to the main container
                        mainRow.append(modal);
                    
                        // Handle the button click event to show the modal
                        showDataButton.click(function () {
                            $('#showModal' + tag_code).modal('show');
                        });
                    } 
                    else 
                    {
                        
                        if(!tagFilter) {
                            // Create the second label element for "IND"
                            let indLabel = $('<label>').attr('for', 'fieldName').addClass('form-label in-b mr strong').text('Indicator');
                            // Create the select element with options
                            let indicator1Element = $('<select>').addClass('form-select in-b width25 mr5').attr('id', 'indicator1');
                            // Append the initial disabled option
                            indicator1Element.append(
                                $('<option>').attr('selected', 'selected').text(fields[i].indicators[0].indicator).val(fields[i].indicators[0].id)
                            );
                            // Create the select element with options
                            let indicator2Element = $('<select>').addClass('form-select in-b width25 mr5').attr('id', 'indicator2');
                            // Append the initial disabled option
                            indicator2Element.append(
                                $('<option>').attr('selected', 'selected').text(fields[i].indicators[1].indicator).val(fields[i].indicators[1].id)
                            );
                            // Append the label, input elements, form-check div to the second inner container
                            secondInnerContainer.append(indLabel, indicator1Element, indicator2Element);
                        }
                        
                        for (let x = 0; x < fields[i].subfields.length; x++) {
                            // Create the new row-like div
                            let subfieldRow = $('<div>').addClass('subfield-row');
                            let subFieldCode = fields[i].subfields[x].code;
                            let subFieldDescription = fields[i].subfields[x].description;
                            let subFieldId = fields[i].subfields[x].id;
                            let subFieldValue = fields[i].subfields[x].default;
                            // subFieldArr[i] = { 'sub-index': x + 1, 'id': subFieldId, 'code':subFieldCode, 'description':subFieldDescription, 'value':subFieldValue };
                            subFieldArr.push({
                                'sub-index': x + 1,
                                'id': subFieldId,
                                'code': subFieldCode,
                                'description': subFieldDescription,
                                'value': subFieldValue
                            });

                            // Create the select element with options
                            let selectElement = $('<select>').addClass('form-select in-b width25 mr5').attr('id', 'subfield');

                            // Append the initial disabled option
                            selectElement.append(
                                $('<option>').attr('selected', 'selected').val(subFieldId).text(subFieldCode+' - '+subFieldDescription)
                            );
                    
                            // Create the input element for "default_value"
                            let defaultValueInput = $('<input>').attr({
                                'id': x+1,
                                'class': 'form-control in-b width25 mr5',
                                'type': 'text',
                                'placeholder': 'Enter value',
                                'value': subFieldValue == "Enter value" ? "" : subFieldValue,
                                'required': fields[i].tag.isRequired == 1 ? 'required' : "",
                                'disabled': tagFilter ? true : false,
                            });

                            defaultValueInput.on('keyup', function(event) {
                                event.preventDefault();

                                let arrayOfData = JSON.parse($('#fieldData').val());

                                let currentValue = $(this).val();

                                // Get the ID of the current input
                                let currentInputId = $(this).attr('id');

                                // Get the ID of the .additional-subfield parent
                                let parentSubfieldId = $(this).closest('.additional-subfield').attr('id');

                                let selectedData = arrayOfData.find(function(item) {
                                    return item.marctag.index == parentSubfieldId;
                                });

                                // Loop through the subfield array and update input values
                                $.each(selectedData.subfield, function(index, subfield) {
                                    if(subfield['sub-index'] == currentInputId) {
                                        subfield.value = currentValue;
                                    }
                                });

                                // Find the index of the object with the matching id in arrayOfIds
                                let indexToUpdate = arrayOfData.findIndex(function (item) {
                                    return item.marctag.index == parentSubfieldId;
                                });

                                // Update the subfield array for the matching object
                                if (indexToUpdate !== -1) {
                                    arrayOfData[indexToUpdate].subfield.findIndex(function (item) { 
                                        if(item['sub-index'] == currentInputId) {
                                            item.value = currentValue; 
                                        }
                                    });
                                }

                                let stringData = JSON.stringify(arrayOfData);

                                $('#fieldData').val(stringData);
                                // console.log('arrayOfData:', $('#fieldData').val());
                            
                            });
                
                            // Append the elements to the subfieldRow
                            subfieldRow.append(selectElement, defaultValueInput);
                            // Append the subfieldRow to the thirdInnerContainer
                            thirdInnerContainer.append(subfieldRow);
                        }
                    }

                // Append the select element and default value input to the first inner container
                firstInnerContainer.append(secondInnerContainer, thirdInnerContainer);
                // Append the first inner container to the main row
                mainRow.append(firstInnerContainer);
                // Append the main row to the existing "main-el" div
                $('#template-div').append(mainRow);

                // Add Feather icons functionality
                feather.replace();

                let ind1PartArr = fields[i].indicators[0].indicator.split('-');
                let firstPart = $.trim(ind1PartArr[0]);
                    
                let ind2PartArr = fields[i].indicators[1].indicator.split('-');
                let secondtPart = $.trim(ind2PartArr[0]);
            
                // Data preaparation
                let jsonString = {
                    "marctag": {
                        'index': i + 1,
                        'id': fields[i].tag.id, 
                        'code': fields[i].tag.code, 
                        'description': fields[i].tag.description,
                    }, 
                    "subfield": subFieldArr,
                    "indicator":[
                        {'id': fields[i].indicators[0].id, 'indicator': firstPart},
                        {'id': fields[i].indicators[1].id, 'indicator': secondtPart}
                    ],
                };

                arrayData.push(jsonString);
            }

            let stringData = JSON.stringify(arrayData);
        
            $('#fieldData').val(stringData);

        }
    }
});

function showImportModal(action = 'create') {

    if (action == 'edit') {
        $('#import-action').val('edit');
    }
    $('#showImportModal').modal('show');
    $('#showImportModal').fadeIn();
}
