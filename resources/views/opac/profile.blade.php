@extends('opac.layout.master')

@section('content')
<div class="container margin_60">
	<div class="main_title">
		<h1>Profile</h1>
	</div>

	<form method="POST" id="profile_frm" enctype="multipart/form-data" action="{{ route('update.patron.profile', ['id' => Auth::user()->id]) }}">
		{{ csrf_field() }}

		<div class="row">
			<div class="col-6">
				<div class="mb-3">
					<label class="form-label">Full Name</label>
					<input type="text" name="name" class="form-control required" placeholder="Enter full name" value="{{ $user->name }}">
				</div>
			</div>
			<div class="col-6">
				<div class="mb-3">
					<label class="form-label">Contact number</label>
					<input type="text" name="contactnum" class="form-control required" placeholder="Enter contact number" value="{{ $user->contactnum }}">
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-6">
				<div class="mb-3">
					<label class="form-label">Birthdate</label>
					<input type="date" name="birthdate" class="form-control datepicker required" value="{{ $user->birthdate }}">
				</div>	
			</div>
			<div class="col-6">
				<div class="mb-3">
					<label class="form-label">Patron Type</label>
					<select class="form-control" name="patron_type_id" id="patrontypes_id" required>
						<option selected disabled>Select Type</option>
						@foreach (getPatronType() as $patronType)
							<option value="{{ $patronType->id }}" {{ $patronType->id == $user->patrontypes_id ? 'selected' : '' }}>
								{{ $patronType->types }}
							</option>
						@endforeach
					</select>
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-6">
				<div class="mb-3">
					<label class="form-label">Email address</label>
					<input id="email" type="email" name="email" class="form-control required" placeholder="Enter email" value="{{ $user->email }}">
				</div>
			</div>
			<div class="col-6">
				<div class="mb-3">
					<label class="form-label">Affiliation/Agency</label>
					<input type="text" name="affiliation" class="form-control required" placeholder="Enter affiliation/agency" value="{{ $user->affiliation }}">
				</div>
			</div>
		</div>

		<div class="row">
			<div class="col-6">
				<div class="form-group">
					<label>Sex</label><br>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="sex" id="inlineRadio1" value="Male" {{ $user->sex == 'Male' ? 'checked' : '' }}>
						<label class="form-check-label text-black" for="inlineRadio1">Male</label>
					</div>
					<div class="form-check form-check-inline">
						<input class="form-check-input" type="radio" name="sex" id="inlineRadio2" value="Female" {{ $user->sex == 'Female' ? 'checked' : '' }}>
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
							<option value="{{ $item->regCode }}" {{ $item->regCode == $user->region_id ? 'selected' : '' }}>
								{{ $item->desc }}
							</option>
						@endforeach
					</select>
				</div>
			</div>
		</div>

		<div class="row mb-4">
			<div class="col-12">
				<div class="form-group">
					<label>Complete Address</label>
					<input type="text" id="address" name="address" class="form-control required" value="{{ $user->location }}">
				</div>
			</div>
		</div>


	</form>

	<!-- Submit button -->
	<div id="policy">
		<center>
			<span><button type="button" class="btn btn-danger btn-sm" onclick="showChangePasswordModal()">Change Password</button></span>
			<span><a class="btn_1 btn-lg" onclick="submitForm()">Save Changes</a></span>
		</center>
	</div>
</div>
@include('opac.modals.change-password-modal')
@endsection


@section('js')
		<script>
			$("#region_id").val("{{ Auth::user()->region_id }}");
			$("#patrontypes_id").val("{{ Auth::user()->patrontypes_id }}");
			$("input[name=sex][value={{ Auth::user()->sex }}]").prop('checked', true);

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
					showSwal("Please fill in all required fields.", "error");
				}
			}

			function checkEmail()
			{
				uemail = $("#email").val();

				$.ajax({
					type: 'GET',
					url: "{{ url('/patron/profile/validate-email/') }}", // Include a trailing slash
					dataType: 'json',
					data: {
						user_email: uemail
					},
					success: function(res) {
						
						if(res.result) {
							$("#profile_frm").submit();
						} else {
							console.log(res.message);
							showSwal(res.message, "error");
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

			function showSwal(message, icon) {
				Swal.fire({
					position: 'center',
					icon: icon,
					title: message,
					showConfirmButton: false,
					timer: 1500
				})
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

			});
		</script>
		@if (isset($error))
			<script>
				$(document).ready(function(){
					showSwal("{{ $error }}", "error");
				});
			</script>
		@endif
		@if (isset($success))
			<script>
				$(document).ready(function(){
					showSwal("{{ $success }}", "success");
				});
			</script>
		@endif
@endsection