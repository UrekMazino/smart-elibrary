@extends("admin.layouts.master")

@section('content')

		<div class="page-content">

			<nav class="page-breadcrumb">
				<ol class="breadcrumb">
				</ol>
			</nav>
			<div class="row">
				<div class="col-lg-2 col-md-2 col-sm-2"></div>
				<div class="col-lg-8 col-md-8 col-sm-8 stretch-card">
					<div class="card">
						<div class="card-body">
							<h6 class="card-title">Your Details</h6>
							<form method="POST" id="admin-profile-frm" enctype="multipart/form-data" action="{{ url('/admin/profile/update') }}">
									{{ csrf_field() }}
									<div class="row">
										<div class="col-6">
											<div class="mb-3">
												<label class="form-label">Full Name</label>
												<input type="text" name="name" class="form-control required" placeholder="Enter full name" value="{{ Auth::user()->name }}">
											</div>
										</div><!-- Col -->
										<div class="col-6">
											<div class="mb-3">
												<label class="form-label">Contact number</label>
												<input type="text" name="contactnum" class="form-control required" placeholder="Enter contact number" value="{{ Auth::user()->contactnum }}">
											</div>
										</div><!-- Col -->
									</div><!-- Row -->
									<div class="row">
										<div class="col-6">
											<div>
												<label class="form-label">Birthdate</label>
												<input type="text" name="birthdate" class="form-control datepicker required" value="{{ Auth::user()->birthdate }}">
											</div>	
										</div><!-- Col -->
										<div class="col-6">
											<div class="mb-3">
												<label class="form-label">Patron Type</label>
												<select class="form-control" name="patron_type_id" id="patrontypes_id" required>
													<option selected disabled>Select Type</option>
													@foreach (getPatronType() as $patronType)
														<option value="{{$patronType->id}}" {{ $patronType->id == Auth::user()->patrontypes_id ? 'selected' : '' }}>{{$patronType->types}}</option>
													@endforeach
												</select>
											</div>
										</div><!-- Col -->
									</div><!-- Row -->
									<div class="row">
										<div class="col-6">
											<div class="mb-3">
												<label class="form-label">Email address</label>
												<input id="email" type="email" name="email" class="form-control required" placeholder="Enter email" value="{{ Auth::user()->email }}">
											</div>
										</div><!-- Col -->
										<div class="col-6">
											<div class="mb-3">
												<label class="form-label">Affliation/Agency</label>
												<input type="text" name="affiliation" class="form-control required" placeholder="Enter affliation/agency" value="{{ Auth::user()->affiliation }}">
											</div>
										</div><!-- Col -->
									</div><!-- Row -->
									<div class="row">
										<div class="col-6">
											<div class="form-group">
												<label>Sex</label>
												<br>
												<div class="form-check form-check-inline">
													<input class="form-check-input" type="radio" name="sex" id="inlineRadio1" value="Male">
													<label class="form-check-label text-black" for="inlineRadio1">Male</label>
												</div>
												<div class="form-check form-check-inline">
													<input class="form-check-input" type="radio" name="sex" id="inlineRadio2" value="Female">
													<label class="form-check-label text-black" for="inlineRadio2">Female</label>
												</div>
											</div>
										</div>
										<div class="col-6">
											<div class="form-group">
												<label>Region</label>
												<select class="form-control required" name="region_id" id="region_id">
													<option selected disabled>Select Region</option>
													@foreach (getRegion() as $item)
														<option value="{{$item->regCode}}" {{ $item->regCode == Auth::user()->region_id ? 'selected' : '' }} >{{$item->desc}}</option>
													@endforeach
												</select>
											</div>
										</div>
									</div>
									<div class="row mb-4">
										<div class="col-12">
											<div class="form-group">
												<label>Complete Address</label>
												<input type="text" id="address" name="address" class="form-control required" value="{{ Auth::user()->location }}" >
											</div>
										</div>
									</div>
									<div class="row mb-4">
										<div class="col-12 mb-2">
										
											<div class="form_title">
												<h6 class="card-title">Security</h6>
												<button type="button" class="btn btn-danger btn-sm" onclick="showChangePasswordModal()">Change Password</button>
											</div>
										</div>
									</div>
							</form>
							<div class="row">
								<div class="col-12 mb-2 text-center">
									<span><a class="btn btn-info btn-lg" type="button" onclick="submitForm()">Save Changes</a></span>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

		</div>
		@include('admin.modals.change-password-modal')
@endsection

@section('js')
		<script>
			$("input[name=sex][value={{ $user->sex }}]").prop('checked', true);

			function submitForm()
			{

				let allFieldsFilled = true;

				// Iterate through each required input
				$(".required").each(function() {
					let fieldValue = $(this).val();
					// Check if the field is empty
					if (!fieldValue.trim()) {
						
						allFieldsFilled = false;
						return false; // Break out of the loop early if any field is empty
					}
				});

				if (allFieldsFilled) {
					checkEmail();
				} else {
					showSwal("custom-position", "Please fill in all required fields.", "error");
				}
				
			}

			function checkEmail()
			{
				uid = "{{ $user->id }}";
				
				uemail = $("#email").val();

				$.ajax({
					type: 'GET',
					url: "{{ url('/admin/profile/validate-email') }}", // Include a trailing slash
					dataType: 'json',
					data: {
						user_id: uid,
						user_email: uemail
					},
					success: function(res) {
						if(res.result) {
							$("#admin-profile-frm").submit();
						} 

						if(!res.result) {
							showSwal("custom-position", res.message, "error");
						}
					},
					error: function(e) {
						// Handle the error case
						console.log("Something is wrong!");
					}
				});

			}
			
			function showChangePasswordModal()
			{
				$('#changePasswordModal').modal('show');
    			$('#changePasswordModal').fadeIn();
			}

			function showSwal(type, message, icon) {
				if (type === 'custom-position') {
					Swal.fire({
					position: 'top-center',
					icon: icon,
					title: message,
					showConfirmButton: false,
					timer: 1500
					})
				} 
			}

			$(document).ready(function(){

				$('#password_confirmation').on('keyup', function () {
					let password = $('#password').val();
					let confirm_password = $(this).val();

					if (password === confirm_password) {
						$('#password, #password_confirmation').css('border-color', 'green');
						$('#change-password-btn').prop('disabled', false); 
					} else {
						$('#password, #password_confirmation').css('border-color', 'red');
						$('#change-password-btn').prop('disabled', true);
					}
				});

				$('.datepicker').datepicker({
					format: 'mm/dd/yyyy',
				});

			});
		</script>
		
		@if (isset($error))
			<script>
				$(document).ready(function(){
					showSwal("custom-position", "{{ $error }}", "error");
				});
			</script>
		@endif
		@if (isset($success))
			<script>
				$(document).ready(function(){
					showSwal("custom-position", "{{ $success }}", "success");
				});
			</script>
		@endif
@endsection