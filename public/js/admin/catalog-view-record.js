
let rowCounter = 0; 
$(document).ready(function(){
    // console.log(JSON.parse($('#record').val()));
    let field_data = JSON.parse($('#fields_data').val());

    let record = JSON.parse($('#record').val());
    let materialName = $('#material_name').val();
    let createdBy = $('#created_by').val();
    let updatedBy = $('#updated_by').val();
    let title = $('#hidden-title').val();
    let isEven = false;

    // FULL RECORD TAB
    let fullRecordDiv = $('#full-record');

    field_data.forEach(function(entry) {
        let rowDiv = $('<div class="row mb-1"></div>');

        let rowClass = isEven ? 'even-row' : 'odd-row';

        let description = entry.marctag.description;
        rowDiv.addClass(rowClass);

        let col1Div = $('<div class="p-2 col-lg-4 col-md-4 col-sm-12 text-left"></div>');
        if (typeof description !== 'string') {
            description = description.toString();
        }
        col1Div.html('<strong>' + description.toUpperCase()  + '</strong>');
        rowDiv.append(col1Div);

        let col2Div = $('<div class="p-2 col-lg-8 col-md-8 col-sm-12 text-left"></div>');

        col2Div.css('background-color', '#b0f0f0');
        
        let subfieldValues = entry.subfield.map(function(subfield) {
            return subfield.value;
        }).join(' ');

        col2Div.text(subfieldValues);
        rowDiv.append(col2Div);

        fullRecordDiv.append(rowDiv);

        isEven = !isEven;
    });


    // MARC RECORD TAB
    let marcRecordDiv = $('#marc-record');

    field_data.forEach(function(entry) {
        // console.log('Entry', entry);
        let rowDiv = $('<div class="row mb-1"></div>');

        let rowClass = isEven ? 'even-row' : 'odd-row';
        rowDiv.addClass(rowClass);

        let col1Div = $('<div class="p-2 col-lg-1 col-md-1 col-sm-12 text-left"></div>');
        col1Div.html('<strong>' + entry.marctag.code.toUpperCase()  + '</strong>')
        rowDiv.append(col1Div);

        let col2Div = $('<div class="p-2 col-lg-1 col-md-1 col-sm-12 text-left"></div>');

        let indicatorValues = entry.indicator.map(function(i) {
            let value = i.indicator;
            // console.log(value);
            
            return value == 'None' || value == '# - Undefined'  ? "N" : value;
        }).join('');

        col2Div.text(indicatorValues);
        rowDiv.append(col2Div);

        let col3Div = $('<div class="p-2 col-lg-10 col-md-10 col-sm-12 text-left"></div>');
        col3Div.css('background-color', '#b0f0f0');
        let subfieldValues = entry.subfield.map(function(subfield) {
            let code = subfield.code;
            return code.substr(code.length - 2) + subfield.value;
        }).join(' ');

        col3Div.text(subfieldValues);
        rowDiv.append(col3Div);

        marcRecordDiv.append(rowDiv);
        isEven = !isEven;
    });





    // RECORD HISTORY TAB
    let recordHistDiv = $('#record-hist');

    let created_at = new Date(record.created_at);
    let updated_at = new Date(record.updated_at);

    let options = { year: 'numeric', month: 'long', day: 'numeric', hour: 'numeric', minute: 'numeric', second: 'numeric', timeZoneName: 'short' };

    let createdAtFormatted = created_at.toLocaleString('en-US', { ...options, timeZone: 'Asia/Manila' });
    let updatedAtFormatted = updated_at.toLocaleString('en-US', { ...options, timeZone: 'Asia/Manila' });
    let historyData = [
        { 
            'field' : 'Accession Number',
            'value' : record.accession_number
        },
        { 
            'field' : 'Material Type',
            'value' : materialName
        },
        { 
            'field' : 'Classification System',
            'value' : 'Library of Congress'
        },
        { 
            'field' : 'AANR',
            'value' : record.is_aanr == 1 ? 'Yes' : 'No'
        },
        { 
            'field' : 'OPAC Access',
            'value' : record.is_published == 1 ? 'Yes' : 'No'
        },
        { 
            'field' : 'Created By',
            'value' : createdBy
        },
        { 
            'field' : 'Created At',
            'value' : createdAtFormatted
        },
        { 
            'field' : 'Updated By',
            'value' : updatedBy
        },
        { 
            'field' : 'Updated At',
            'value' : updatedAtFormatted
        },
    ];


    historyData.forEach(function(entry) {

        let rowDiv = $('<div class="row mb-1"></div>');

        let rowClass = isEven ? 'even-row' : 'odd-row';
        rowDiv.addClass(rowClass);

        let col1Div = $('<div class="p-2 col-lg-4 col-md-4 col-sm-12 text-left"></div>');
        col1Div.html('<strong>' + entry.field  + '</strong>');
        rowDiv.append(col1Div);

        let col2Div = $('<div class="p-2 col-lg-8 col-md-8 col-sm-12 text-left"></div>');

        let subfieldValues = entry.value;

        col2Div.text(subfieldValues);
        rowDiv.append(col2Div);

        recordHistDiv.append(rowDiv);

        isEven = !isEven;
    });


    // COPIES TAB
    

        // Add Row button click event
        $("#addRowButton").click(function () {
        rowCounter++;

        let newRow = $("#first-row").clone();

        newRow.find("input").each(function () {
            var oldId = $(this).attr("id");
            var newId = oldId + "_" + rowCounter;
            $(this).attr("id", newId);
            $(this).val(""); // Clear input values in the new row
        });

        // Add a delete button to the new row
        newRow.find(".delete-row").click(function () {
            newRow.remove(); // Remove the current row when delete is clicked
        });

        newRow.appendTo("#dynamic-rows-container").show();


    });

        $("#first-row .delete-row").click(function () {
        $(this).closest(".row").remove();
    });

});


function showAddCopyModal() {
    $('#addCopydModal').modal('show');
    $('#addCopydModal').fadeIn();
}

function showEditCopyModal(id) {
    $.ajax({
        type: 'GET',
        url: '/admin/getcopy-details-json/' + id,
        dataType: 'json',
        success: function(res) {

            $("#editCirculationNumber").val(res.circulation_number);
            $("#editSource").val(res.source);
            $("#editLocation").val(res.location);
            $("#editStatus").val(res.status);
            $("#editCopyId").val(res.copy_id);
       
        //   $('#loadingOverlay').hide();
        //   $('#loadingSpinnerContainer').hide();
        },
        error: function(xhr, status, error) {
            console.error("Something's wrong!");
        }
      }); 

    $('#editCopydModal').modal('show');
    $('#editCopydModal').fadeIn();
}