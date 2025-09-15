$(function() {

  let imgView = document.querySelector('.img-view');
  let upload = document.querySelector('#coverUpload');
  let coverButton = document.getElementById("cover-photo-btn");

  // on change show image with crop options
  upload.addEventListener('change', function (e) {
    if (e.target.files.length) {
      let file = e.target.files[0];
      let fileType = file.type;

      if(fileType === 'image/gif' || fileType === 'image/jpeg' || fileType === 'image/png') {
        let reader = new FileReader();

        reader.onload = function (event) {
            let imgSrc = event.target.result;
            coverButton.classList.remove("d-none");
            imgView.src = imgSrc;
        };
        reader.readAsDataURL(file);
        $("#cover-photo-img-view").removeAttr("style");
        $("#cover-photo-no-upload").hide();
      } else {
        alert("Selected file type is not supported. Please try again");
        upload.value = '';
        window.location.href = window.location.href.split('#')[0] + "#resources";
      }
    }
  });

});

$(function() {

  let fulltextView = document.querySelector('.fulltext-view');
  let upload = document.querySelector('#fulltextUpload');
  let fulltextButton = document.getElementById("fulltext-btn");

  // on change show image with crop options
  upload.addEventListener('change', function (e) {
    if (e.target.files.length) {
      let file = e.target.files[0];
      let fileType = file.type;

      if(fileType === 'application/pdf') {
        let reader = new FileReader();

        reader.onload = function (event) {
            let imgSrc = event.target.result;
            fulltextButton.classList.remove("d-none");
            fulltextView.src = pdfDefaultThumb;
        };
        reader.readAsDataURL(file);
        $("#full-text-img-view").removeAttr("style");
        $("#full-text-no-upload").hide();

      } else {
        alert("Selected file type is not supported. Please try again");
        upload.value = '';
        window.location.href = window.location.href.split('#')[0] + "#resources";
      }
    }
  });

});