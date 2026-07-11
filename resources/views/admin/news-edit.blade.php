@extends("admin.layouts.master")

@section('css')
    <!-- Plugin css for this page -->
	<link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/simplemde/simplemde.min.css') }}">
	<!-- End plugin css for this page -->
@endsection

@section('content')
<div class="page-content">

    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-baseline mb-4 mb-md-3">
                        <h6 class="card-title mb-0">EDIT NEWS ARTICLE</h6>

                        <button type="submit" class="btn p-2 btn-primary" onclick="saveArticle()"al()">
                          <i class="icon-lg pb-3px text-white" data-feather="save"> </i> SAVE &nbsp&nbsp
                        </button>
                      </div>
                    <form method="post" action="{{ url('/admin/news/update', $article->id) }}" id="frm_news" enctype="multipart/form-data">
                      @csrf
                      @method('PUT')

                    <div class="mb-3">
                        <label for="news_title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="news_title" name="news_title" autocomplete="off" value="{{ old('news_title', $article->news_title ?? '') }}">
                        @error('news_title')
                          <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3 form-check">
                      <input type="checkbox" class="form-check-input" id="is_featured" name="is_featured" {{ old('news_url', $article->featured == 'Yes' ? 'checked' : '') }} >
                      <label class="form-check-label" for="is_featured">Featured</label>
                    </div>
                    <div class="mb-3">
                      <label for="news_url" class="form-label">Redirect URL</label>
                      <input type="text" class="form-control" id="news_url" name="news_url" autocomplete="off" value="{{ old('news_url', $article->redirect_url ?? '') }}" placeholder="e.g https://fprdi.dost.gov.ph/">
                    </div>
                    <div class="mb-3 row">
                      <div class="col-md-6 col-sm-12">
                          <label for="news_cover" class="form-label">Cover photo</label>
                          <input class="form-control" value="{{ $article->news_cover }}" type="file" id="news_cover" name="news_cover" onchange="previewImage(event)" accept="image/jpeg,image/png,image/webp">
                          @error('news_cover')
                            <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                      </div>
                      <div class="col-md-6 col-sm-12">
                          <label for="cover_preview" class="form-label">Cover Preview</label>
                          <img id="cover_preview" class="img-fluid" src="@if($article->news_cover) {{ asset($article->news_cover) }} @else {{ asset('assets/images/placeholder.jpg') }} @endif" alt="Cover Preview" style="max-height: 200px; display: block; margin-top: 10px; border-radius: 10px;">

                      </div>
                    </div>

                    <label for="news_body" class="form-label">Body</label>
                    <textarea class="form-control" name="news_body" id="tinymceExample" rows="10">{{ $article->news_body }}</textarea>
                    @error('news_body')
                      <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    </form>
                    <br>
                </div>
            </div>
        </div>
    </div>
</div>



@endsection

@section('js')
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/tinymce/tinymce.min.js') }}"></script>

<script>
  $(function() {
    'use strict';

    //Tinymce editor
    if ($("#tinymceExample").length) {
      tinymce.init({
        selector: '#tinymceExample',
        height: 400,
        default_text_color: 'red',
        plugins: [
          'advlist autolink lists link image charmap print preview hr anchor pagebreak',
          'searchreplace wordcount visualblocks visualchars code fullscreen',
        ],
        toolbar1: 'undo redo | insert | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
        toolbar2: 'print preview media | forecolor backcolor emoticons | codesample help',
        image_advtab: true,
        templates: [{
            title: 'Test template 1',
            content: 'Test 1'
          },
          {
            title: 'Test template 2',
            content: 'Test 2'
          }
        ],
        content_css: []
      });
    }

  });

  function previewImage(event) {
      let reader = new FileReader();
      reader.onload = function() {
          let output = document.getElementById('cover_preview');
          output.src = reader.result;
      }
      reader.readAsDataURL(event.target.files[0]);
  }
</script>

<script>

function saveArticle()
{
    Swal.fire({
      title: 'Are you sure?',
      text: "You won't be able to revert this!",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonClass: 'me-2',
      confirmButtonText: 'Yes',
      cancelButtonText: 'No',
      reverseButtons: true
    }).then((result) => {
      if (result.value) {
        $("#modalSaving").modal("toggle");
        $('#frm_news').submit();
      } else if (
        // Read more about handling dismissals
        result.dismiss === Swal.DismissReason.cancel
      ) {
        Swal.fire(
          'Cancelled',
          'Your news article is safe :)',
          'error'
        )
      }
    })
}
</script>
@endsection
