$(document).ready(function(){
    let toEditfields = JSON.parse($('#fieldIds').val());
    // console.log(toEditfields);
    for(let a = 0; a < toEditfields.length; a++){
        addTag(toEditfields[a].id, toEditfields, a);
    }
});