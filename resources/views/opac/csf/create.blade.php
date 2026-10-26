@extends('opac.layout.master')

@section('robots', 'noindex, nofollow')


@section('content')

		<div class="container margin_60_35">
			<div class="row justify-content-center">
				<div class="col-lg-8">
					<div class="box_general_3 write_review">

						<form method="POST" action="{{ url('/csf/store') }}">
							@csrf
							<h1 class="text-center">CUSTOMER SATISFACTION FEEDBACK</h1>

							<div class="form-group">
								<div class="text-center">
									<label><h6>How well did we serve you? Please check </h6><i style="color:red; font-size:12px;">(O-Outstanding; VS-Very Satisfactory; S-Satisfactory; US-Unsatisfactory; P-Poor)</i></label>
								</div>

								<table class="table table-bordered">
									<thead>
										<tr>
											<th class="text-center"><b>SERVICE QUALITY DIMENSION</b></th>
											<th class="text-center">O <span style="font-size:10px;">(5)</span></th>
											<th class="text-center">VS <span style="font-size:10px;">(4)</span></th>
											<th class="text-center">S <span style="font-size:10px;">(3)</span></th>
											<th class="text-center">US <span style="font-size:10px;">(2)</span></th>
											<th class="text-center">P <span style="font-size:10px;">(1)</span></th>
										</tr>
									</thead>
									<tbody>
										<tr>
											<td scope="row" style="width: 35%">
												<b>Responsiveness</b> – system design, layout, and functionality respond to the needs and capabilities of the device it is viewed on.</td>
											<td style="width: 5% !important" align="center">
												<label>
													<input type="checkbox" class="radio" value="5" name="rating1" />
												</label>
											</td>
											<td style="width: 5% !important" align="center">
												<label>
													<input type="checkbox" class="radio" value="4" name="rating1" />
												</label>
											</td>
											<td style="width: 5% !important" align="center">
												<label>
													<input type="checkbox" class="radio" value="3" name="rating1" />
												</label>
											</td>
											<td style="width: 5% !important" align="center">
												<label>
													<input type="checkbox" class="radio" value="2" name="rating1" />
												</label>
											</td>
											<td style="width: 5% !important" align="center">
												<label>
													<input type="checkbox" class="radio" value="1" name="rating1" />
												</label>
											</td>
										</tr>
										<tr>
											<td scope="row" style="width: 35%">
												<b>Reliability (Quality)</b> – information provided by the system is timely and relevant</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="5" name="rating2" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="4" name="rating2" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="3" name="rating2" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="2" name="rating2" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="1" name="rating2" />
												</label>
											</td>
										</tr>
										<tr>
											<td scope="row" style="width: 35%">
												<b>Access &amp; Facilities</b> – system can be easily navigated with minimal to no assistance</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="5" name="rating3" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="4" name="rating3" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="3" name="rating3" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="2" name="rating3" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="1" name="rating3" />
												</label>
											</td>
										</tr>
										<tr>
											<td scope="row" style="width: 35%">
												<b>Communication</b> – system provides venue for communication between user and service provider; content can be shared across social media and other communication channels.</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="5" name="rating4" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="4" name="rating4" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="3" name="rating4" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="2" name="rating4" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="1" name="rating4" />
												</label>
											</td>
										</tr>
										<tr>
											<td scope="row" style="width: 35%">
												<b>Integrity</b> – the honesty and truthfulness of the knowledge and information carried by the system as well as the agency’s commitment to data security and preservation, in accordance with Republic Act 10173 or Data Privacy Act of 2012.</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="5" name="rating5" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="4" name="rating5" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="3" name="rating5" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="2" name="rating5" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="1" name="rating5" />
												</label>
											</td>
										</tr>
										<tr>
											<td scope="row" style="width: 35%">
												<b>Assurance</b> – the system can be referred to as informative, useful and recommendable to other users</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="5" name="rating6" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="4" name="rating6" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="3" name="rating6" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="2" name="rating6" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="1" name="rating6" />
												</label>
											</td>
										</tr>
										<tr>
											<td scope="row" style="width: 35%">
												<b>Outcome</b> – customer expectations are met through the use of the system.</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="5" name="rating7" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="4" name="rating7" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="3" name="rating7" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="2" name="rating7" />
												</label>
											</td>
											<td align="center">
												<label>
													<input type="checkbox" class="radio" value="1" name="rating7" />
												</label>
											</td>
										</tr>
									</tbody>
								</table>
							</div>


							<div class="form-group">
								<label>Remark/s (especially for ‘Unsatisfactory’ and ‘Poor’ rating):</label>
								<input class="form-control" type="text" name="comment1" placeholder="Write your remark/s here ...">
							</div>
							<div class="form-group">
								<label>Please give us specific comments/suggestions on how we can further improve our services:</label>
								<input class="form-control" type="text" name="comment2" placeholder="Write your comments/suggestions here ...">
							</div>
							<div class="form-group">
								<label>General comments:</label>
								<textarea class="form-control" style="height: 180px;" name="comment3" placeholder="Write your general comments here ..."></textarea>
							</div>
							<div class="form-group text-center">
								<button type="submit" class="btn_1">Submit feedback</button>
							</div>
						</form>
					</div>
				</div>
			</div>
			<!-- /row -->
		</div>
		<!-- /container -->
	</main>
	<!-- /main -->
	
@endsection

@section('js')
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
	<script nonce="{{ cspNonce() }}">
		$("input:checkbox").on('click', function() {
		var $box = $(this);
		if ($box.is(":checked")) {
			var group = "input:checkbox[name='" + $box.attr("name") + "']";
			$(group).prop("checked", false);
			$box.prop("checked", true);
		} else {
			$box.prop("checked", false);
		}
		});
	</script>
@endsection