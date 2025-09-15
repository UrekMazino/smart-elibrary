function showAddTagModal() {
    $('#viewAddFieldModal').modal('show');
    $('#viewAddFieldModal').fadeIn();
}

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
                 let tag_code = fields[i].tag.code;
                 let tagFilter = tag_code == "001" || tag_code == "003" || tag_code == "005" || tag_code == "006" || tag_code == "007" || tag_code == "008" || tag_code == "LDR";
                   
                // Create the first icon element for "Remove Tag"
                let removeTagIcon1 = $('<i>').addClass('sm-font danger-clr').attr({
                    'data-bs-toggle': 'tooltip',
                    'data-bs-placement': 'top',
                    'title': 'Remove Tag',
                    'data-feather': 'minus-square',
                    'onclick': 'removeTag(this,'+fields[i].marctag.id+')',
                });

                // Append label and icon1 to the first inner container
                firstInnerContainer.append(labelElement, removeTagIcon1);
                 
                 // Create the second inner container div with class "block mb-2"
                 let secondInnerContainer = $('<div>').addClass('block mb-2');
 
                 let thirdInnerContainer = $('<div>').addClass('mb-2 additional-subfield').attr('id', i+1);

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
                                             'disabled': false,
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

                            // Create the icon element for "Add Subfield"
                            let addSubfieldIcon = $('<i>').addClass('sm-font success-clr').attr({
                            'data-bs-toggle': 'tooltip',
                            'data-bs-placement': 'top',
                            'title': 'Add Subfield',
                            'data-feather': 'plus-square',
                            'onclick': 'addSubfield(this,'+fields[i].marctag.description+')',
                            });

                             // Append the label, input elements, form-check div to the second inner container
                             secondInnerContainer.append(indLabel, indicator1Element, indicator2Element, addSubfieldIcon);
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
                             let removeSubfieldIcon = $('<i>').addClass('sm-font danger-clr mr in-b').attr({
                                'data-bs-toggle': 'tooltip',
                                'data-bs-placement': 'top',
                                'title': 'Remove Subfield',
                                'data-feather': 'minus-square',
                                'onclick': 'removeSubfield(this,'+tag_id+','+newSubIndex+')',
                            });
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
                                 'disabled': false,
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
                             
                             });
                 
                             // Append the elements to the subfieldRow
                             subfieldRow.append(removeSubfieldIcon, selectElement, defaultValueInput);
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
     } else {
        fields = data;
        if(fields.length > 0 && flag == 1) {
            let arrayData =  [];
            $('#template-div').empty();
            $("#accessionNumber").removeAttr("required");
            for (let i = 0; i < fields.length; i++) {
                    
                // let arrayData =  JSON.parse($('#fieldData').val());
                let subFieldArr = [];

                let mainRow = $('<div>').addClass('row mb-3');

                let firstInnerContainer = $('<div>').addClass('col col-lg-12 subfield-parent');

                let labelElement = $('<label>').attr('for', 'fieldName').addClass('form-label in-b mr strong').text(fields[i].marctag.code + ' - ' + fields[i].marctag.description);
                let tag_code = fields[i].marctag.code;
                let tagFilter = tag_code == "001" || tag_code == "003" || tag_code == "005" || tag_code == "006" || tag_code == "007" || tag_code == "008" || tag_code == "LDR";
           
                // Create the first icon element for "Remove Tag"
                let removeTagIcon1 = $('<i>').addClass('sm-font danger-clr').attr({
                    'data-bs-toggle': 'tooltip',
                    'data-bs-placement': 'top',
                    'title': 'Remove Tag',
                    'data-feather': 'minus-square',
                    'onclick': 'removeTag(this,'+fields[i].marctag.id+')',
                });
                // Append label and icon1 to the first inner container
                firstInnerContainer.append(labelElement, removeTagIcon1);
             
                // Create the second inner container div with class "block mb-2"
                let secondInnerContainer = $('<div>').addClass('block mb-2');

                let thirdInnerContainer = $('<div>').addClass('mb-2 additional-subfield').attr('id', i+1);
               
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
                        for (let x = 0; x < fields[i].subfield.length; x++) {
                            let subFieldCode = fields[i].subfield[x].code;
                            let subFieldDescription = fields[i].subfield[x].description;
                            let subFieldId = fields[i].subfield[x].id;
                            let subFieldValue = fields[i].subfield[x].value;
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
                                            'required': fields[i].marctag.isRequired == 1 ? 'required' : "",
                                            'disabled': false,
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
                                                    .text(tag_code +' - '+ fields[i].marctag.description)
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
                                $('<option>').attr('selected', 'selected').text(fields[i].indicator[0].indicator).val(fields[i].indicator[0].id)
                            );
                            // Create the select element with options
                            let indicator2Element = $('<select>').addClass('form-select in-b width25 mr5').attr('id', 'indicator2');
                            // Append the initial disabled option
                            indicator2Element.append(
                                $('<option>').attr('selected', 'selected').text(fields[i].indicator[1].indicator).val(fields[i].indicator[1].id)
                            );

                            // Create the icon element for "Add Subfield"
                            let addSubfieldIcon = $('<i>').addClass('sm-font success-clr').attr({
                                'data-bs-toggle': 'tooltip',
                                'data-bs-placement': 'top',
                                'title': 'Add Subfield',
                                'data-feather': 'plus-square',
                                'onclick': 'addSubfield(this,'+fields[i].marctag.id+')',
                            });

                            // Append the label, input elements, form-check div to the second inner container
                            secondInnerContainer.append(indLabel, indicator1Element, indicator2Element, addSubfieldIcon);
                        }
                        
                        for (let x = 0; x < fields[i].subfield.length; x++) {
                            // Create the new row-like div
                            let subfieldRow = $('<div>').addClass('subfield-row');
                            let subFieldCode = fields[i].subfield[x].code;
                            let subFieldDescription = fields[i].subfield[x].description;
                            let subFieldId = fields[i].subfield[x].id;
                            let subFieldValue = fields[i].subfield[x].value;
                            // subFieldArr[i] = { 'sub-index': x + 1, 'id': subFieldId, 'code':subFieldCode, 'description':subFieldDescription, 'value':subFieldValue };
                            subFieldArr.push({
                                'sub-index': x + 1,
                                'id': subFieldId,
                                'code': subFieldCode,
                                'description': subFieldDescription,
                                'value': subFieldValue
                            });
                            

                            
                            // Determine the new sub-index dynamically
                            let lastSubIndex = fields[i].subfield.length > 0 ? fields[i].subfield[fields[i].subfield.length - 1]['sub-index'] : 0;
                            let newSubIndex = lastSubIndex + (x-2);
                            // Create the select element with options
                            let selectElement = $('<select>').addClass('form-select in-b width25 mr5').attr('id', 'subfield-'+ newSubIndex);

                            // Append the initial disabled option
                            selectElement.append(
                                $('<option>').attr('selected', 'selected').val(subFieldId).text(subFieldCode+' - '+subFieldDescription)
                            );

                            let removeSubfieldIcon = $('<i>').addClass('sm-font danger-clr mr in-b').attr({
                                'data-bs-toggle': 'tooltip',
                                'data-bs-placement': 'top',
                                'title': 'Remove Subfield',
                                'data-feather': 'minus-square',
                                'onclick': 'removeSubfield(this,'+fields[i].marctag.id+','+newSubIndex+')',
                            });
                    
                            // Create the input element for "default_value"
                            let defaultValueInput = $('<input>').attr({
                                'id': x+1,
                                'class': 'form-control in-b width25 mr5',
                                'type': 'text',
                                'placeholder': 'Enter value',
                                'value': subFieldValue == "Enter value" ? "" : subFieldValue,
                                'required': fields[i].marctag.isRequired == 1 ? 'required' : "",
                                'disabled': false,
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
                            
                            });
                
                            // Append the elements to the subfieldRow
                            subfieldRow.append(removeSubfieldIcon, selectElement, defaultValueInput);
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
                
                let firstPart = 'None';
                let secondtPart =  'None';
                let indicator1 =  fields[i].indicator[0].indicator;
                let indicator2 =  fields[i].indicator[1].indicator;

                if(indicator1 != 'N' && indicator1 !=  undefined) {
                    let ind1PartArr = fields[i].indicator[0].indicator.split('-');
                    firstPart = $.trim(ind1PartArr[0]);
                } 

                if(indicator2 != 'N' && indicator2 != undefined) {
                    let ind2PartArr = fields[i].indicator[1].indicator.split('-');
                    secondtPart = $.trim(ind2PartArr[0]);
                }   
                
                
            
                // Data preaparation
                let jsonString = {
                    "marctag": {
                        'index': i + 1,
                        'id': fields[i].marctag.id, 
                        'code': fields[i].marctag.code, 
                        'description': fields[i].marctag.description,
                    }, 
                    "subfield": subFieldArr,
                    "indicator":[
                        {'id': fields[i].indicator[0].id, 'indicator': firstPart},
                        {'id': fields[i].indicator[1].id, 'indicator': secondtPart}
                    ],
                };

                arrayData.push(jsonString);
            }
            // Convert json into string
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
 

$(document).on('change', '#marcTemplateSelect', function(event) {
    event.preventDefault();
  
    // console.log($(this).val());
  
    let arrayData =  [];
   
    let template_id = $(this).val();

    $.ajax({
        type: 'GET',
        url: getTemplateDetailsUrl,
        data: {
            "template_id": template_id,
        },
        
        dataType: 'json',
        success: function(res) {
            
            $('#template-div').empty();
            let fields = res.fields;
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

                let thirdInnerContainer = $('<div>').addClass('mb-2 additional-subfield').attr('id', i+1);
                
                for (let x = 0; x < fields[i].subfields.length; x++) {
                
                     // Create the new row-like div
                    let subfieldRow = $('<div>').addClass('subfield-row');

                    let subFieldCode = fields[i].subfields[x].code;

                    let subFieldDescription = fields[i].subfields[x].description;
                    
                    let subFieldId = fields[i].subfields[x].id;

                    let subFieldValue = fields[i].subfields[x].value;
      
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
                      
                    });
        
                    // Append the elements to the subfieldRow
                    subfieldRow.append(selectElement, defaultValueInput);

                    // Append the subfieldRow to the thirdInnerContainer
                    thirdInnerContainer.append(subfieldRow);
            
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
        },
        error: function(e) {
            // Handle the error case
            console.log("Something's wrong");
        }
      }); 
});


function showAddFieldModal() {
    $('#viewAddFieldModal').modal('show');
    $('#viewAddFieldModal').fadeIn();
}

function addTag(id, toEditfields = [], a) {
    $('#viewAddFieldModal').modal('hide');
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
            let code = res.code;
            let description = res.description;
            let subfields = res.subfields;
            let first_indictors = res.first_indictors;
            let second_indicators = res.second_indicators;

            let arrayIds =  JSON.parse($('#fieldData').val());
            console.log('------------arrayIds----------', arrayIds);
            lastIndex = arrayIds[arrayIds.length - 1].marctag.index;
            let jsonObject = {
                "marctag": {
                    'index': lastIndex + 1,
                    'id': tag_id, 
                    'code': code, 
                    'description': description,
                }, 
                "subfield": [{
                    'code': null,
                    'description': null,
                    'id': null, 
                    'sub-index': 1,
                    'value': null
                }],
                "indicator":[
                    {'code': null, 'indicator': null},
                    {'id': null, 'indicator': null}
                ],
                "required": 0,
            };

            arrayIds.push(jsonObject);
            
            // Sort by "code"
            arrayIds.sort(function(a, b) {
                if (a.marctag.code === "LDR") {
                    return -1; // a should come before b
                }
                if (b.marctag.code === "LDR") {
                    return 1; // b should come before a
                }
                let codeA = a.marctag.code.toUpperCase(); // Ignore upper and lowercase
                let codeB = b.marctag.code.toUpperCase(); // Ignore upper and lowercase
                if (codeA < codeB) {
                    return -1;
                }
                if (codeA > codeB) {
                    return 1;
                }
                // names must be equal
                return 0;
            });

            let stringIds = JSON.stringify(arrayIds);
    
            $('#fieldData').val(stringIds);
            // End of json preparation

            // Create the main row div with class "row mb-5"
            let mainRow = $('<div>').addClass('row mb-3');

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
                let arrayOfIds = JSON.parse($('#fieldData').val());
                console.log('arrayOfIds', arrayOfIds);
                // Get the corresponding data object based on the selectedValue's id
                let selectedData = arrayOfIds.find(function(item) {
                    return item.marctag.id == tag_id;
                });

                // Update the "indicator" array in the selectedData with the selected value
                selectedData.indicator[0] = {id: selectedValue, indicator: $(this).find('option:selected').text()};

                let stringIds = JSON.stringify(arrayOfIds);

                $('#fieldData').val(stringIds);

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
                let arrayOfIds = JSON.parse($('#fieldData').val());

                // Get the corresponding data object based on the selectedValue's id
                let selectedData = arrayOfIds.find(function(item) {
                    return item.marctag.id == tag_id;
                });
                console.log('arrayOfIds', arrayOfIds);
                // Update the "indicator" array in the selectedData with the selected value
                selectedData.indicator[1] = {id: selectedValue, indicator: $(this).find('option:selected').text()};

                let stringIds = JSON.stringify(arrayOfIds);

                $('#fieldData').val(stringIds);
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
                    let arrayOfIds = JSON.parse($('#fieldData').val());

                    // Get the corresponding data object based on the selectedValue's id
                    let selectedData = arrayOfIds.find(function(item) {
                        return item.marctag.id == tag_id;
                    });

                    // Update the "indicator" array in the selectedData with the selected value
                    selectedData.required = this.checked ? 1 : 0;
                    console.log('arrayOfIds', arrayOfIds);
                    // Find the index of the object with the matching id in arrayOfIds

                    let stringIds = JSON.stringify(arrayOfIds);

                    $('#fieldData').val(stringIds);

            });

            // Append the label and checkbox to the form-check div
            formCheckDiv.append(requiredLabel, requiredCheckbox);

            // Create the icon element for "Add Subfield"
            let addSubfieldIcon = $('<i>').addClass('sm-font success-clr').attr({
                'data-bs-toggle': 'tooltip',
                'data-bs-placement': 'top',
                'title': 'Add Subfield',
                'data-feather': 'plus-square',
                'onclick': 'addSubfield(this,'+tag_id+')',
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

                    let selectedData = arrayOfIds.find(function(item) {
                        return item.marctag.id == tag_id;
                    });
        
                    // Determine the new sub-index dynamically
                    let lastSubIndex = selectedData.subfield.length > 0 ? selectedData.subfield[selectedData.subfield.length - 1]['sub-index'] : 0;
                    let newSubIndex = lastSubIndex + 1;

                    // Create the input element for "default_value"
                    let defaultValueInput = $('<input>').attr({
                        'id': 'defaultValue-'+newSubIndex,
                        'class': 'form-control in-b width25 mr5',
                        'type': 'text',
                        'placeholder': 'Enter Value'
                    });
        
                    defaultValueInput.on('keyup', function(event) {
                        event.preventDefault();
                        let defaultInput = $(this);
                        let arrayOfIds = JSON.parse($('#fieldData').val());
                        let typingTimer;
                        const delayTime = 0;
                        clearTimeout(typingTimer);
                        typingTimer = setTimeout(function() {
                            let currentValue = defaultInput.val();
                            let selectedData = arrayOfIds.find(function(item) {
                                return item.marctag.id == tag_id;
                            });
                            
                            let selectedSubfield = selectedData.subfield.find(function(item) {
                                return item['sub-index'] == newSubIndex;
                            });
                            selectedSubfield.value = currentValue;
                            let stringIds = JSON.stringify(arrayOfIds);
                            $('#fieldData').val(stringIds);
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
        
                        let arrayOfIds = JSON.parse($('#fieldData').val());
                
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
        
                        $('#fieldData').val(stringIds);
        
                    });
                    rowDiv.append(removeSubfieldIcon, selectElement, defaultValueInput);
                    thirdInnerContainer.append(rowDiv);
                    
                 
        
                }

                // Append the select element and default value input to the first inner container
                firstInnerContainer.append(secondInnerContainer, thirdInnerContainer);

                // Append the first inner container to the main row
                mainRow.append(firstInnerContainer);
                // Append the main row to the existing "main-el" div
                $('#template-div').prepend(mainRow); // Prepend to place at the top

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
                let arrayOfIds = JSON.parse($('#fieldData').val());
                let selectedData = arrayOfIds.find(function(item) {
                    return item.marctag.id == tag_id;
                });

                // Determine the new sub-index dynamically
                let lastSubIndex = selectedData.subfield.length > 0 ? selectedData.subfield[selectedData.subfield.length - 1]['sub-index'] : 0;
                let newSubIndex = lastSubIndex + 1;
                // Create the select element with options
                let selectElement = $('<select>').addClass('form-select in-b width25 mr5').attr('id', 'subfield-'+ newSubIndex);

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
                    let arrayOfIds = JSON.parse($('#fieldData').val());

                    // Get the corresponding data object based on the selectedValue's id
                    let selectedData = arrayOfIds.find(function(item) {
                        return item.marctag.id == tag_id;
                    });

                    // Update the "subfield" array in the selectedData with the selected value
                    selectedData.subfield[0] = {
                        id: selectedValue, 
                        code: $(this).find('option:selected').text().split(' - ')[0], 
                        description: $(this).find('option:selected').text().split(' - ')[1], 
                        'sub-index': 1,
                        value: selectedData.subfield[0].value
                    };
                    console.log('arrayOfIds', arrayOfIds);  
                    let stringIds = JSON.stringify(arrayOfIds);
                    $('#fieldData').val(stringIds);

                });

                // Create the input element for "default_value"
                let defaultValueInput = $('<input>').attr({
                    'id': 'defaultValue-1',
                    'class': 'form-control in-b width25 mr5',
                    'type': 'text',
                    'placeholder': 'Enter Value'
                });
    
                defaultValueInput.on('keyup', function(event) {
                    event.preventDefault();
                    let defaultInput = $(this);
                    let arrayOfIds = JSON.parse($('#fieldData').val());
                    let typingTimer;
                    const delayTime = 0;
                    clearTimeout(typingTimer);
                    typingTimer = setTimeout(function() {
                        let currentValue = defaultInput.val();
                        let selectedData = arrayOfIds.find(function(item) {
                            return item.marctag.id == tag_id;
                        });
                        // console.log('newSubIndex', newSubIndex);
                        console.log('selectedData', selectedData);

                        let selectedSubfield = selectedData.subfield.find(function(item) {
                            return item['sub-index'] == 1;
                        });

                        selectedSubfield.value = currentValue;
                        let stringIds = JSON.stringify(arrayOfIds);
                        $('#fieldData').val(stringIds);
                    }, delayTime);
                });
                            
                thirdInnerContainer.append(removeSubfieldIcon, selectElement, defaultValueInput);
        
                // Append the select element and default value input to the first inner container
                firstInnerContainer.append(secondInnerContainer, thirdInnerContainer);
                // Append the first inner container to the main row
                mainRow.append(firstInnerContainer);
                // Append the main row to the existing "main-el" div
                $('#template-div').prepend(mainRow); // Prepend to place at the top

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


function removeTag(iconElement, tag_id_to_remove) {
    let arrayIds =  JSON.parse($('#fieldData').val());

    let filteredIds = arrayIds.filter(function(item) {
        return item.marctag.id != tag_id_to_remove;
      });

    let stringIds = JSON.stringify(filteredIds);
    $('#fieldData').val(stringIds);

    // Get the closest ancestor div with class "row mb-5"
    let rowElement = $(iconElement).closest('.row.mb-3');

    // Fade out the row element over 500 milliseconds and then remove it
    rowElement.fadeOut(200, function() {
      $(this).remove();
    });

    console.log($('#fieldData').val());
}

function removeSubfield(iconElement, tagid_where_to_remove, subfield_index) {
    console.log('subfield_index',subfield_index);
    let arrayOfIds = JSON.parse($('#fieldData').val());
    console.log('--------------arrayOfIds--------------', arrayOfIds);
    console.log('--------------subfield_index--------------', subfield_index);  
    console.log('--------------tagid_where_to_remove--------------', tagid_where_to_remove);
    arrayOfIds.find(function(item) {
        if(item.marctag.id == tagid_where_to_remove) {
            item.subfield = item.subfield.filter(function(subfield) {
                return subfield['sub-index'] != subfield_index;
            });
        }
    });

    let stringIds = JSON.stringify(arrayOfIds);
    $('#fieldData').val(stringIds);
    let rowElement = $(iconElement).closest('.subfield-row');

    rowElement.fadeOut(200, function() {
        $(this).hide();
    });
}

function addSubfield(iconElement, tag_id_to_add) {
    console.log('tag_id_to_add', tag_id_to_add);
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
            let arrayOfIds = JSON.parse($('#fieldData').val());
            let selectedData = arrayOfIds.find(function(item) {
                return item.marctag.id == tag_id;
            });

            let indexToUpdate = arrayOfIds.findIndex(function (item) {
                return item.marctag.id === selectedData.marctag.id;
            });

            // Determine the new sub-index dynamically
            let lastSubIndex = selectedData.subfield.length > 0 ? selectedData.subfield[selectedData.subfield.length - 1]['sub-index'] : 0;
            let newSubIndex = lastSubIndex + 1;

            let addtionalContainer = $('<div>').addClass('mb-2 additional-subfield').attr('id', 'subfield-'+ newSubIndex);
            let subfieldRow = $('<div>').addClass('subfield-row');
            let removeSubfieldIcon = $('<i>').addClass('sm-font danger-clr mr in-b').attr({
                'data-bs-toggle': 'tooltip',
                'data-bs-placement': 'top',
                'title': 'Remove Subfield',
                'data-feather': 'minus-square',
                'onclick': 'removeSubfield(this,'+tag_id+','+newSubIndex+')',
            });

            // Create the input element for "default_value"
            let defaultValueInput = $('<input>').attr({
                'id': 'defaultValue-'+newSubIndex,
                'class': 'form-control in-b width25 mr5',
                'type': 'text',
                'placeholder': 'Enter Value'
            });

            // json payload
            let newSubfield = {
                "sub-index": newSubIndex,
                "id": null,
                "code": null,
                "description": null,
                "value": null
            };

            // Update the subfield array for the matching object
            if (indexToUpdate !== -1) {
                arrayOfIds[indexToUpdate].subfield.push(newSubfield);
            }

            let stringIds = JSON.stringify(arrayOfIds);
            $('#fieldData').val(stringIds);
  
            defaultValueInput.on('keyup', function(event) {
                event.preventDefault();
                let defaultInput = $(this);
                let arrayOfIds = JSON.parse($('#fieldData').val());
                let typingTimer;
                const delayTime = 0;
                clearTimeout(typingTimer);
                typingTimer = setTimeout(function() {
                    let currentValue = defaultInput.val();
                    let selectedData = arrayOfIds.find(function(item) {
                        return item.marctag.id == tag_id;
                    });
                    
                    let selectedSubfield = selectedData.subfield.find(function(item) {
                        return item['sub-index'] == newSubIndex;
                    });
                    selectedSubfield.value = currentValue;
                    let stringIds = JSON.stringify(arrayOfIds);
                    $('#fieldData').val(stringIds);
                }, delayTime);
            });

            // Create the select element with options
            let selectElement = $('<select>').addClass('form-select in-b width25 mr5');
            selectElement.append(
                $('<option>').attr('selected', 'selected').attr('disabled', 'disabled').text('Please Select Subfield')
            );
            if(subfields.length > 0) {   
                for (let i = 0; i < subfields.length; i++) {
                    let optionText = subfields[i].code + ' - ' + subfields[i].description;
                    let optionValue = subfields[i].id;
                    selectElement.append($('<option>').text(optionText).val(optionValue));
                }
            }

            selectElement.on('change', function() {
                let selectedValue = $(this).val();

                let arrayOfIds = JSON.parse($('#fieldData').val());
                let selectedData = arrayOfIds.find(function(item) {
                    return item.marctag.id == tag_id;
                });
                
                let indexToUpdate = arrayOfIds.findIndex(function (item) {
                    return item.marctag.id === selectedData.marctag.id;
                });
   
                if (indexToUpdate !== -1) {
                    let x = arrayOfIds[indexToUpdate].subfield.find(function(item) {
                        return item['sub-index'] == newSubIndex;
                    });
                    x.id = selectedValue*1;
                    x.code = subfields.find(function(item) {
                        return item.id == selectedValue;
                    }).code;
                    x.description = subfields.find(function(item) {
                        return item.id == selectedValue;
                    }).description;
                }
                let stringIds = JSON.stringify(arrayOfIds);
                $('#fieldData').val(stringIds);
            });
            subfieldRow.append(removeSubfieldIcon, selectElement, defaultValueInput);
            addtionalContainer.append(subfieldRow);
            rowElement.append(addtionalContainer);
            feather.replace();
        },
        error: function(e) {
            console.log('Somethings wrong!');
        }
    });
}

