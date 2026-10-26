@extends('opac.layout.master')

@section('robots', 'noindex, nofollow')


@section('css')
<style>
	/* Profile — minimalist, uniform with Transactions / Inquiries / To Review */
	.tx-page { min-height: calc(100vh - 320px); }
	.tx-head { margin-bottom: 22px; }
	.tx-head h1 { font-size: 30px; font-weight: 700; color: #222; margin: 0 0 4px; }
	.tx-head p { color: #8a94a6; font-size: 14px; margin: 0; }

	.pf-card { background: #fff; border: 1px solid #eef0f2; border-radius: 10px; box-shadow: 0 1px 2px rgba(0,0,0,.04); overflow: hidden; }
	.pf-identity { display: flex; align-items: center; gap: 16px; padding: 20px 22px; background: linear-gradient(90deg, #e7f5f3, #f4faf9); border-bottom: 1px solid #eef0f2; }
	.pf-avatar { flex: 0 0 auto; width: 56px; height: 56px; border-radius: 50%; background: #00857a; color: #fff; font-size: 20px; font-weight: 700; display: flex; align-items: center; justify-content: center; letter-spacing: .5px; }
	.pf-name { font-size: 17px; font-weight: 700; color: #222; line-height: 1.2; }
	.pf-sub { font-size: 13px; color: #6b7280; margin-top: 3px; }
	.pf-body { padding: 22px; }
	.pf-card .form-label, .pf-card label { font-size: 12.5px; font-weight: 600; color: #55606b; margin-bottom: 5px; }
	.pf-card .form-control { border: 1px solid #dfe3e8; border-radius: 7px; padding: 9px 12px; font-size: 14px; color: #333; background: #fff; }
	.pf-card .form-control:focus { border-color: #00a496; box-shadow: 0 0 0 3px rgba(0,164,150,.12); outline: none; }
	.pf-card .form-check-input:checked { background-color: #00a496; border-color: #00a496; }
	.pf-actions { display: flex; justify-content: flex-end; align-items: center; gap: 10px; padding: 16px 22px; border-top: 1px solid #eef0f2; background: #fbfcfd; }
	.pf-btn { border: 0; border-radius: 7px; padding: 9px 20px; font-size: 13.5px; font-weight: 600; cursor: pointer; transition: background .15s, border-color .15s, color .15s; }
	.pf-btn-save { background: #00857a; color: #fff; }
	.pf-btn-save:hover { background: #006f65; color: #fff; }
	.pf-btn-pass { background: #fff; color: #c0392b; border: 1px solid #f0c8c2; }
	.pf-btn-pass:hover { background: #fdecea; border-color: #e2b3ab; }

	/* Change-password modal — matches the card above it */
	.pw-modal { border: 0; border-radius: 10px; }
	.pw-modal .modal-header, .pw-modal .modal-footer { border-color: #eef0f2; }
	.pw-modal .modal-title { font-size: 16px; font-weight: 700; color: #222; }
	.pw-modal .form-label { font-size: 12.5px; font-weight: 600; color: #55606b; margin-bottom: 5px; }
	.pw-modal .form-control { border: 1px solid #dfe3e8; border-radius: 7px; padding: 9px 12px; font-size: 14px; }
	.pw-modal .form-control:focus { border-color: #00a496; box-shadow: 0 0 0 3px rgba(0,164,150,.12); outline: none; }
	.pw-modal .form-control.is-invalid { border-color: #c0392b; }
	.pw-hint { display: block; font-size: 11.5px; color: #a3adc2; margin-top: 4px; }
	.pw-hint.ok { color: #00a496; }
	.pw-hint.bad { color: #c0392b; }
	.pw-error { display: block; font-size: 11.5px; color: #c0392b; margin-top: 4px; }
</style>
@endsection

@section('content')
	<div class="container margin_60 tx-page">
		<div class="row">
			<div class="col-9">
				<div class="tx-head">
					<h1>Profile</h1>
					<p>Manage your account details.</p>
				</div>

				@php
					$parts = preg_split('/\s+/', trim((string) $user->name));
					$initials = strtoupper(mb_substr($parts[0] ?? 'U', 0, 1) . (isset($parts[1]) ? mb_substr($parts[1], 0, 1) : ''));
				@endphp

				<div class="pf-card">
					<div class="pf-identity">
						<div class="pf-avatar">{{ $initials ?: 'U' }}</div>
						<div>
							<div class="pf-name">{{ $user->name }}</div>
							<div class="pf-sub">{{ $user->email }}@if($user->library_code) &nbsp;·&nbsp; {{ $user->library_code }}@endif</div>
						</div>
					</div>

					<form method="POST" id="profile_frm" enctype="multipart/form-data" action="{{ route('update.patron.profile', ['id' => Auth::user()->id]) }}">
						{{ csrf_field() }}
						<div class="pf-body">
							<div class="row">
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Full Name</label>
										<input type="text" name="name" class="form-control required" placeholder="Enter full name" value="{{ $user->name }}">
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Contact number</label>
										<input type="text" name="contactnum" class="form-control required" placeholder="Enter contact number" value="{{ $user->contactnum }}">
									</div>
								</div>
							</div>

							<div class="row">
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Birthdate</label>
										<input type="date" name="birthdate" class="form-control datepicker required" value="{{ $user->birthdate }}">
									</div>
								</div>
								<div class="col-md-6">
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
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Email address</label>
										<input id="email" type="email" name="email" class="form-control required" placeholder="Enter email" value="{{ $user->email }}">
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Affiliation/Agency</label>
										<input type="text" name="affiliation" class="form-control required" placeholder="Enter affiliation/agency" value="{{ $user->affiliation }}">
									</div>
								</div>
							</div>

							<div class="row">
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label d-block">Sex</label>
										<div class="form-check form-check-inline">
											<input class="form-check-input" type="radio" name="sex" id="inlineRadio1" value="Male" {{ $user->sex == 'Male' ? 'checked' : '' }}>
											<label class="form-check-label" for="inlineRadio1">Male</label>
										</div>
										<div class="form-check form-check-inline">
											<input class="form-check-input" type="radio" name="sex" id="inlineRadio2" value="Female" {{ $user->sex == 'Female' ? 'checked' : '' }}>
											<label class="form-check-label" for="inlineRadio2">Female</label>
										</div>
									</div>
								</div>
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Region</label>
										<select class="form-control required" name="region_id" id="region_id">
											<option selected disabled>Select Region</option>
											@foreach (getRegion() as $item)
												<option value="{{ $item->id }}" {{ $item->id == $user->region_id ? 'selected' : '' }}>
													{{ $item->desc }}
												</option>
											@endforeach
										</select>
									</div>
								</div>
							</div>

							<div class="row">
								<div class="col-12">
									<div class="mb-1">
										<label class="form-label">Complete Address</label>
										<input type="text" id="address" name="address" class="form-control required" value="{{ $user->location }}">
									</div>
								</div>
							</div>
						</div>
					</form>

					<div class="pf-actions">
						<button type="button" class="pf-btn pf-btn-pass" onclick="showChangePasswordModal()">Change Password</button>
						<button type="button" class="pf-btn pf-btn-save" onclick="submitForm()">Save Changes</button>
					</div>
				</div>
			</div>
			@include('opac.side')
		</div>
	</div>
@include('opac.modals.change-password-modal')
@endsection


@section('js')
		<script nonce="{{ cspNonce() }}">
			$("#region_id").val("{{ Auth::user()->region_id }}");
			$("#patrontypes_id").val("{{ Auth::user()->patrontypes_id }}");
			// Quoted: an unset sex produced [value=], an invalid selector that threw.
			$('input[name=sex][value="{{ Auth::user()->sex }}"]').prop('checked', true);

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
				// Live match hint. The Update button is NOT gated on this — the server is
				// what enforces it, and a disabled button left people stuck with no reason.
				function checkMatch() {
					var pw = $('#password').val(), confirmPw = $('#password_confirmation').val();
					var $note = $('#password-match');
					if (!confirmPw) { $note.text('').removeClass('ok bad'); return; }
					if (pw === confirmPw) $note.text('Passwords match.').addClass('ok').removeClass('bad');
					else                  $note.text('Passwords do not match.').addClass('bad').removeClass('ok');
				}
				$('#password, #password_confirmation').on('keyup', checkMatch);

				// Re-open the modal when the password form came back with errors.
				@if($errors->updatePassword->any())
					$('#changePasswordModal').modal('show');
				@endif
			});
		</script>
		@if (session('success'))
			<script nonce="{{ cspNonce() }}">
				$(document).ready(function(){
					showSwal("{{ session('success') }}", "success");
				});
			</script>
		@endif
		@if ($errors->getBag('default')->any())
			<script nonce="{{ cspNonce() }}">
				$(document).ready(function(){
					showSwal("{{ $errors->getBag('default')->first() }}", "error");
				});
			</script>
		@endif
@endsection
