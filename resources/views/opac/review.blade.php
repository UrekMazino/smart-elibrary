@extends('opac.layout.master')

@section('content')
<form method="POST" action="{{ url('/review/submit') }}" id="frm_review">
@csrf
<input type="hidden" name="catalog_id" value="{{ $catalog_id }}">
<input type="hidden" name="download_id" value="{{ $download_id }}">
<div class="container margin_60_35">
			<div class="row justify-content-center">
				<div class="col-lg-8">
					<div class="box_general_3 write_review">
						<h1><small class="text-success">Write a review for</small><br/> <span class="text-default">{!! getTitle($catalog_id) !!}</span></h1>
						<div class="rating_submit">
							<div class="form-group">
							<label class="d-block">Overall rating</label>
							<span class="rating">
								<input type="radio" class="rating-input" id="5_star" name="rating_input" value="5"><label for="5_star" class="rating-star"></label>
								<input type="radio" class="rating-input" id="4_star" name="rating_input" value="4"><label for="4_star" class="rating-star"></label>
								<input type="radio" class="rating-input" id="3_star" name="rating_input" value="3"><label for="3_star" class="rating-star"></label>
								<input type="radio" class="rating-input" id="2_star" name="rating_input" value="2"><label for="2_star" class="rating-star"></label>
								<input type="radio" class="rating-input" id="1_star" name="rating_input" value="1"><label for="1_star" class="rating-star"></label>
							</span>
							</div>
						</div>
						<div class="form-group">
							<label>Your review</label>
							<textarea class="form-control" style="height: 180px;" name="remarks" placeholder="Write your review here ..."></textarea>
						</div>
						<hr>
						<button type="submit" class="btn_1" >Submit review</button>
					</div>
				</div>
			</div>
			<!-- /row -->
		</div>
</form>
@endsection

@section('js')

@endsection