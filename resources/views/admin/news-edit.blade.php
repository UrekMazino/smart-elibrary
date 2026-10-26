@extends("admin.layouts.master")

@section('css')
	<style>
		.admin-page-head { display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:10px; margin-bottom:18px; }
		.admin-page-head .apt { margin:0; font-size:1rem; font-weight:600; }
		.admin-page-head .aph-desc { margin:2px 0 0; font-size:0.8125rem; color:var(--ui-muted, #8a94a6); }
		#cover_preview { max-height:220px; width:100%; object-fit:cover; border-radius:8px; border:1px solid var(--ui-border, #e6e9ef); background:var(--ui-surface-2, #f6f8fa); }
	</style>
@endsection

@section('content')
<div class="page-content">
	<nav class="page-breadcrumb">
		<ol class="breadcrumb">
			<li class="breadcrumb-item"><a href="{{ url('/admin/dashboard') }}">Home</a></li>
			<li class="breadcrumb-item"><a href="{{ url('/admin/news') }}">News Articles</a></li>
			<li class="breadcrumb-item active" aria-current="page">Edit Article</li>
		</ol>
	</nav>

	<div class="admin-page-head">
		<div>
			<h6 class="apt">Edit Article</h6>
			<p class="aph-desc">Update this news article.</p>
		</div>
		<div class="d-flex gap-2">
			<a href="{{ url('/admin/news') }}" class="btn btn-outline-secondary"><i data-feather="arrow-left" class="icon-sm me-1"></i>Back</a>
			<button type="button" class="btn btn-primary" onclick="saveArticle()"><i data-feather="save" class="icon-sm me-1"></i>Save Changes</button>
		</div>
	</div>

	<form method="post" action="{{ url('/admin/news/update', $article->id) }}" id="frm_news" enctype="multipart/form-data">
		@csrf
		@method('PUT')
		<div class="row">
			<div class="col-lg-8">
				<div class="card mb-3">
					<div class="card-body">
						<div class="mb-3">
							<label for="news_title" class="form-label">Title</label>
							<input type="text" class="form-control @error('news_title') is-invalid @enderror" id="news_title" name="news_title" autocomplete="off" value="{{ old('news_title', $article->news_title ?? '') }}">
							@error('news_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
						</div>
						<div class="mb-3">
							<label for="news_url" class="form-label">Redirect URL <span class="text-muted">(optional)</span></label>
							<input type="text" class="form-control" id="news_url" name="news_url" autocomplete="off" value="{{ old('news_url', $article->redirect_url ?? '') }}" placeholder="e.g. https://fprdi.dost.gov.ph/">
							<small class="text-muted">Where clicking the article sends the reader. Leave blank to use the article body.</small>
						</div>
						<div>
							<label for="news_body" class="form-label">Body</label>
							<textarea class="form-control @error('news_body') is-invalid @enderror" name="news_body" id="tinymceExample" rows="12">{{ cleanNewsHtml(old('news_body', $article->news_body)) }}</textarea>
							@error('news_body')<div class="invalid-feedback">{{ $message }}</div>@enderror
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-4">
				<div class="card mb-3">
					<div class="card-body">
						<h6 class="card-title">Options</h6>
						<div class="form-check form-switch">
							<input type="checkbox" class="form-check-input" role="switch" id="is_featured" name="is_featured" @checked(old('is_featured', ($article->featured ?? '') == 'Yes'))>
							<label class="form-check-label" for="is_featured">Feature on home page</label>
						</div>
					</div>
				</div>
				<div class="card mb-3">
					<div class="card-body">
						<h6 class="card-title">Cover photo</h6>
						<img id="cover_preview" src="@if($article->news_cover){{ asset($article->news_cover) }}@else{{ asset('assets/images/placeholder.jpg') }}@endif" alt="Cover preview" class="mb-2">
						<input class="form-control @error('news_cover') is-invalid @enderror" type="file" id="news_cover" name="news_cover" onchange="previewImage(event)" accept="image/jpeg,image/png,image/webp">
						<small class="text-muted">Leave empty to keep the current cover. JPG, PNG or WEBP.</small>
						@error('news_cover')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
					</div>
				</div>
			</div>
		</div>
	</form>
</div>
@endsection

@section('js')
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/tinymce/tinymce.min.js') }}"></script>
<script nonce="{{ cspNonce() }}">
	$(function () {
		'use strict';
		if (window.feather) feather.replace();
		if ($("#tinymceExample").length) {
			tinymce.init({
				selector: '#tinymceExample',
				height: 420,
				menubar: false,
				plugins: 'advlist autolink lists link image charmap preview anchor pagebreak searchreplace wordcount visualblocks visualchars code fullscreen',   // one string: in an array it was read as one plugin name, so none loaded
				toolbar: 'undo redo | styles | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | code fullscreen',
				image_advtab: true,
				content_css: []
			});
		}
	});

	function previewImage(event) {
		var input = event.target;
		if (input.files && input.files[0]) {
			var reader = new FileReader();
			reader.onload = function (e) { document.getElementById('cover_preview').src = e.target.result; };
			reader.readAsDataURL(input.files[0]);
		}
	}

	function saveArticle() {
		Swal.fire({
			title: 'Save changes?',
			icon: 'question',
			showCancelButton: true,
			confirmButtonText: 'Yes, save',
			cancelButtonText: 'Cancel',
			reverseButtons: true
		}).then((result) => {
			if (result.value) { $('#frm_news').submit(); }
		});
	}
</script>
@endsection
