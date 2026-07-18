
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

/* =====================================================================
 * Add Tag — add an arbitrary MARC field to the record (mirrors the edit
 * page's feature). Added fields use the SAME field_data shape the template
 * loader produces:
 *   { marctag:{index,id,code,description},
 *     indicator:[{id,indicator},{id,indicator}],
 *     subfield:[{'sub-index',id,code,description,value}] }
 * Fields are matched by marctag.index (unique per field), so the same tag
 * can appear more than once (e.g. several 650 subjects).
 * ===================================================================== */

function fdLoad()  { try { return JSON.parse($('#fieldData').val() || '[]'); } catch (e) { return []; } }
function fdSave(a) { $('#fieldData').val(JSON.stringify(a)); }
function fdFind(a, index) { return a.find(function (f) { return f.marctag.index == index; }); }

// LDR first, then alphabetical by tag code — matches the edit page's ordering.
function fdSort(a) {
    return a.sort(function (x, y) {
        if (x.marctag.code === 'LDR') return -1;
        if (y.marctag.code === 'LDR') return 1;
        return String(x.marctag.code).toUpperCase().localeCompare(String(y.marctag.code).toUpperCase());
    });
}

function addTag(id) {
    $('#viewAddFieldModal').modal('hide');

    $.ajax({
        type: 'GET', url: getMarcDetailsUrl, data: { marctag_id: id }, dataType: 'json',
        success: function (res) {
            let arr = fdLoad();
            let nextIndex = arr.length
                ? Math.max.apply(null, arr.map(function (f) { return f.marctag.index; })) + 1
                : 1;

            arr.push({
                marctag:   { index: nextIndex, id: res.id, code: res.code, description: res.description },
                indicator: [ { id: null, indicator: 'None' }, { id: null, indicator: 'None' } ],
                subfield:  [ { 'sub-index': 1, id: null, code: null, description: null, value: null } ]
            });
            fdSave(fdSort(arr));

            renderAddedTag(res, nextIndex);
        },
        error: function () { console.log("Couldn't load the tag details."); }
    });
}

function renderAddedTag(res, index) {
    let mainRow = $('<div>').addClass('row mb-3').attr('data-index', index);
    let parent  = $('<div>').addClass('col col-lg-12 subfield-parent');

    let label = $('<label>').addClass('form-label in-b mr strong').text(res.code + ' - ' + res.description);
    let removeTagIcon = $('<i>').addClass('sm-font danger-clr in-b').attr({
        'data-bs-toggle': 'tooltip', 'data-bs-placement': 'top', 'title': 'Remove Tag',
        'data-feather': 'minus-square', 'onclick': 'removeTag(this,' + index + ')'
    });
    parent.append(label, ' ', removeTagIcon);

    let indBlock = $('<div>').addClass('block mb-2');
    let indLabel = $('<label>').addClass('form-label in-b mr strong').text('Indicator');
    let addSubIcon = $('<i>').addClass('sm-font success-clr in-b').attr({
        'data-bs-toggle': 'tooltip', 'data-bs-placement': 'top', 'title': 'Add Subfield',
        'data-feather': 'plus-square', 'onclick': 'addSubfield(this,' + index + ')'
    });
    indBlock.append(indLabel,
        buildIndicatorSelect(index, 0, res.first_indictors),
        buildIndicatorSelect(index, 1, res.second_indicators),
        addSubIcon);

    // The first subfield is not removable — a field must keep at least one.
    let subWrap = $('<div>').addClass('mb-2 additional-subfield-wrap');
    subWrap.append(buildSubfieldRow(index, 1, res.subfields, false));

    parent.append(indBlock, subWrap);
    mainRow.append(parent);
    $('#template-div').prepend(mainRow);   // added tags sit at the top
    feather.replace();
}

function buildIndicatorSelect(index, pos, indicators) {
    let sel = $('<select>').addClass('form-select in-b width25 mr5');
    sel.append($('<option>').attr('selected', 'selected').val('').text('None'));
    if (Array.isArray(indicators)) {
        indicators.forEach(function (ind) {
            if (ind && ind.id != null) sel.append($('<option>').val(ind.id).text(ind.indicator));
        });
    }
    sel.on('change', function () {
        let arr = fdLoad(), f = fdFind(arr, index);
        if (!f) return;
        f.indicator[pos] = { id: $(this).val() || null, indicator: $(this).find('option:selected').text() };
        fdSave(arr);
    });
    return sel;
}

function buildSubfieldRow(index, subIndex, subfields, removable) {
    let row = $('<div>').addClass('subfield-row mb-1').attr('data-sub-index', subIndex);

    let removeIcon = $('<i>').addClass('sm-font mr in-b ' + (removable ? 'danger-clr' : 'dull-clr')).attr({
        'data-bs-toggle': 'tooltip', 'data-bs-placement': 'top', 'title': 'Remove Subfield',
        'data-feather': 'minus-square'
    });
    if (removable) removeIcon.attr('onclick', 'removeSubfield(this,' + index + ',' + subIndex + ')');

    let sel = $('<select>').addClass('form-select in-b width25 mr5');
    sel.append($('<option>').attr({ selected: 'selected', disabled: 'disabled' }).text('Select Subfield'));
    (subfields || []).forEach(function (sf) {
        sel.append($('<option>').val(sf.id).text(sf.code + ' - ' + sf.description));
    });
    sel.on('change', function () {
        let arr = fdLoad(), f = fdFind(arr, index);
        if (!f) return;
        let sf = f.subfield.find(function (s) { return s['sub-index'] == subIndex; });
        if (!sf) return;
        let text = $(this).find('option:selected').text();
        sf.id = parseInt($(this).val(), 10);
        sf.code = text.split(' - ')[0];
        sf.description = text.split(' - ').slice(1).join(' - ');
        fdSave(arr);
    });

    let input = $('<input>').addClass('form-control in-b width25 mr5')
        .attr({ type: 'text', placeholder: 'Enter value' });
    input.on('keyup', function () {
        let arr = fdLoad(), f = fdFind(arr, index);
        if (!f) return;
        let sf = f.subfield.find(function (s) { return s['sub-index'] == subIndex; });
        if (!sf) return;
        sf.value = $(this).val();
        fdSave(arr);
    });

    row.append(removeIcon, sel, input);
    return row;
}

function addSubfield(iconElement, index) {
    let cur = fdFind(fdLoad(), index);
    if (!cur) return;

    $.ajax({
        type: 'GET', url: getMarcDetailsUrl, data: { marctag_id: cur.marctag.id }, dataType: 'json',
        success: function (res) {
            let arr = fdLoad(), f = fdFind(arr, index);
            if (!f) return;
            let last = f.subfield.length ? f.subfield[f.subfield.length - 1]['sub-index'] : 0;
            let newSub = last + 1;
            f.subfield.push({ 'sub-index': newSub, id: null, code: null, description: null, value: null });
            fdSave(arr);

            $(iconElement).closest('.subfield-parent').find('.additional-subfield-wrap')
                .append(buildSubfieldRow(index, newSub, res.subfields, true));
            feather.replace();
        },
        error: function () { console.log("Couldn't load the tag details."); }
    });
}

function removeTag(iconElement, index) {
    fdSave(fdLoad().filter(function (f) { return f.marctag.index != index; }));
    $(iconElement).closest('.row.mb-3').fadeOut(200, function () { $(this).remove(); });
}

function removeSubfield(iconElement, index, subIndex) {
    let arr = fdLoad(), f = fdFind(arr, index);
    if (f) {
        f.subfield = f.subfield.filter(function (s) { return s['sub-index'] != subIndex; });
        fdSave(arr);
    }
    $(iconElement).closest('.subfield-row').fadeOut(200, function () { $(this).remove(); });
}