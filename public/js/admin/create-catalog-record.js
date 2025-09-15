
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
            // console.log(res);
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
                        console.log('arrayOfData:', $('#fieldData').val());
                      
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
            console.log('arrayOfData:', $('#fieldData').val());
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