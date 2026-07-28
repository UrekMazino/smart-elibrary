@extends('opac.layout.master')

@section('robots', 'noindex, follow')


@section('content')
<div id="error_page">
			<div class="container">
				<div class="row justify-content-center text-center">
					<div class="col-xl-7 col-lg-9">
						<h2>Ooooopsss <i class="icon_error-triangle_alt"></i></h2>
						<p>{{ $message }}</p>
						<form>
							<div class="search_bar_error">
								<input type="text" class="form-control" placeholder="What are you looking for?">
								<input type="submit" value="Search">
							</div>
						</form>
					</div>
				</div>
				<!-- /row -->
			</div>
			<!-- /container -->
		</div>
		<!-- /error_page -->
@endsection

@section('js')

@endsection