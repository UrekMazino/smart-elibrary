$(".slct2").select2();

function removeFromList(obj)
{
    $(obj).closest("tr").remove();
}

function addCopy() {
    let newRow = $("<tr>");
  
    // Add a new input field for 'accession_number'
    newRow.append("<td><input type='text' class='form-control input-sm' name='accession_number[]'></td>");
    newRow.append("<td><input type='text' class='form-control input-sm' name='circulation_number[]'></td>");
    newRow.append("<td><input type='text' class='form-control input-sm' name='location[]'></td>");
    newRow.append("<td><input type='text' class='form-control input-sm' name='source[]'></td>");
    let selectElement = $("<select>").addClass("form-control input-sm").attr("name", "status[]").attr("required", true);
    // Add the default option to the select element
    selectElement.append("<option value='' selected disabled>--- Select Status ---</option>");

    // Add options from the getStatus array
    for (let i = 0; i < getStatus.length; i++) {
        selectElement.append("<option value='" + getStatus[i].order_no + "'>" + getStatus[i].name + "</option>");
    }
    let newCol= $("<td>");
    // Append the select element to the table row
    newCol.append(selectElement);
    newRow.append(newCol);
    newRow.append("<td align='center'><button class='btn btn-danger btn-sm' onclick='removeFromList(this)'><i class='mdi mdi-delete-forever'></i></button></td>");
   
    // Append the new row to the table
    $("#tbl_copy").append(newRow);
}

function addList(id,title,material,author)
{
    $("#tbl_list").append("<tr><td><input type='hidden' name='catalog_id[]' value='"+id+"'>"+title+"</td><td>"+material+"</td><td>"+author+"</td><td align='center'><a href='#' style='color:red;' onclick='removeFromList(this)'><b>remove</b></a></td></tr>");
}
