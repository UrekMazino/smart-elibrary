@extends('opac.layout.master')
@section('content')

		<div class="container margin_60">
			<div class="row">
				<div class="col-12">
					<div class="box_general_3 cart">
						<div class="form_title">
							<h3>To Review</h3>
						</div>
							<div class="step">
								<table id="example" class="table is-striped" style="width:100%">
								        <thead>
								            <tr>
								                <th>Title</th>
								                <th style="width: 20%;"></th>
								            </tr>
								        </thead>
								        <tbody>
								            @foreach($list AS $lists)
								            	<tr>
								            		<td>
								            			{{ formatTitle1($lists->title_statement) }}
								            		</td>
								            		<td align="center">
								            			<button class="btn_1" onclick="toReview({{ $lists->id }},{{ $lists->catalog_id }})">Submit Review</button>
								            		</td>
								            	</tr>
								            @endforeach
								        </tbody>
								    </table>
							</div>
					</div>
				</div>
			</div>
				<!-- /col -->
		</div>
<form method="post" action="{{ url('/review') }}" id="frm_review">
	@csrf
	<input type="hidden" class="form-control" name="catalog_id" id="catalog_id" value="">
	<input type="hidden" class="form-control" name="download_id" id="download_id" value="">
</form>
@endsection

@section('js')
<script type="text/javascript">
	$(document).ready(function () {
    $('#example').DataTable();
});


function  toReview(id,catalog_id) {
	$("#catalog_id").val(catalog_id);
	$("#download_id").val(id);
	$("#frm_review").submit();
}
</script>

@endsection