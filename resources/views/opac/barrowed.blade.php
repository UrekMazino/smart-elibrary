@extends('opac.layout.master')
@section('content')


		<div class="container margin_60">
			<div class="main_title">
				<h1>Transactions</h1>
				{{-- <p>Usu habeo equidem sanctus no. Suas summo id sed, erat erant oporteat cu pri. In eum omnes molestie. Sed ad debet scaevola, ne mel.</p> --}}
			</div>
			<div class="row">
				<div class="col-9">
					@if($flag)
					@foreach($list AS $lists)
					<?php
					// dd($lists->catalog_id);
						$holdings = getPublicationDetail($lists->catalog_id);
					?>
									<div class="strip_list wow fadeIn">
										<figure>
												{!! getFrontpage($lists->catalog_id) !!}
										</figure>
										<h3>{!! $holdings['title_statement'] !!}</h3>
										<span class="text-muted"><br>{!! $holdings['joint_authors'] !!}</span><br>

										Published Date: {!! pubYear($holdings['pub_year']) !!} <br>
										
				
										
										<div class="rating">
											<i data-star="{{ $ave = starRatingAve($lists->catalog_id) }}"></i>
										</div>
										@if($lists->status == 1)
											<p align='right'><span class='badge bg-secondary'> Pending </span></p>
										@elseif($lists->status == 2)
											<p align='right'><span class='badge bg-success'>Approved </span></p>
										@elseif($lists->status == 3)
											<p align='right'><span class='badge bg-warning'>Checkout - Due Date: {{ $lists->due_date  }}  </span></p>
										@elseif($lists->status == 4)
											<p align='right'><span class='badge bg-danger'>Declined </span></p>
										@elseif($lists->status == 5)
											<p align='right'><span class='badge bg-primary'>Complete ( Returned )</span></p>	
											
										@endif
									</div>
					@endforeach
					@else
					<div class="strip_list wow fadeIn">
						<div class="row">
							<div class="col-12">
								<p align='center'>
									No borrowed materials..
								</p>
							</div>
						</div>
					</div>
					@endif
					</div>
				@include('opac.side')
			</div>
				<!-- /col -->
		</div>
@endsection

@section('js')
<script>

</script>

@endsection