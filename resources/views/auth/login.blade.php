@extends('opac.layout.master')

@section('robots', 'noindex, nofollow')

@section('css')
<style>
	#login .form-check-input:checked { background-color: #00a496; border-color: #00a496; }
</style>
@endsection


@section('content')
        <div class="bg_color_2">
            <div class="container margin_60_35">
                <div id="login">
                    <center><h3><img src="{{ asset('opac/img/elibrary-06.png') }}" alt="" width="200"class="img-fluid"></h3></center>
                    <div class="box_form">
                        {{-- Why the session ended: a session limit (EnforceSessionLimits) or a password change (AuthenticateSession). --}}
                        @if (session('signed_out'))
                            <div class="alert alert-info" role="status">{{ session('signed_out') }}</div>
                        @endif
                        {{-- After a reset link: "Your password has been reset." --}}
                        @if (session('status'))
                            <div class="alert alert-success" role="status">{{ session('status') }}</div>
                        @endif
                        {{-- "Google sign-in was cancelled." (GoogleSignInController) --}}
                        @if (session('notice'))
                            <div class="alert alert-info" role="status">{{ session('notice') }}</div>
                        @endif
                        {{-- Wrong email or password, too many tries (LoginRequest), a banned account. --}}
                        @if ($errors->any())
                            <div class="alert alert-danger" role="alert" id="login-errors">
                                @foreach ($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif
                        @include('auth.partials.google-button', ['hint' => 'For patrons. Staff sign in with their email and password.', 'or' => 'or sign in with your email'])
                        <form method="POST" action="{{ route('login') }}">
                        @csrf
                            <div class="form-group">
                                <input type="email" class="form-control" name="email" value="{{ old('email') }}" placeholder="Your email address"
                                       autocomplete="username" @if ($errors->any()) aria-describedby="login-errors" @endif>
                            </div>
                            <div class="form-group">
                                <input type="password" class="form-control" placeholder="Your password" name="password" id="password"
                                       autocomplete="current-password" @if ($errors->any()) aria-describedby="login-errors" @endif>
                            </div>
                            {{-- Patrons only: LoginRequest ignores it for staff, who keep their limits. --}}
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember" aria-describedby="remember-hint">
                                <label class="form-check-label" for="remember">Keep me signed in on this device ({{ \App\Services\SessionLimits::duration(config('auth.guards.web.remember')) }})</label>
                                <small class="form-text text-muted d-block mt-0" id="remember-hint">Not on a shared computer. Staff accounts can’t stay signed in.</small>
                            </div>
                            <a href="#0"><small>Forgot password?</small></a>
                            <div class="form-group text-center add_top_20">
                                <input class="btn_1 medium" type="submit" value="Login">
                            </div>
                        </form>

                        <p class="text-center">Do not have an account yet? <a href="{{ url('register') }}"><strong>Register now!</strong></a></p>
                    </div>
                    
                </div>
                <!-- /login -->
            </div>
        </div>
@endsection

@section('js')
@if (session('account_banned'))
<script nonce="{{ cspNonce() }}" type="text/javascript">
// Banned accounts cannot log in (AuthenticatedSessionController::store). Remind the patron of
// the account's status, and that the ban is lifted only after they email the system admin.
(function () {
	var adminEmail = @json(config('app.admin_email'));
	var accountEmail = @json(old('email', ''));
	var mailto = 'mailto:' + adminEmail +
		'?subject=' + encodeURIComponent('Request to lift account ban') +
		'&body=' + encodeURIComponent('Hello,\n\nI would like to request that the ban on my eLibrary account be lifted.\n\nAccount email: ' + accountEmail + '\n\nThank you.');

	if (window.Swal) {
		Swal.fire({
			icon: 'error',
			title: 'Account banned',
			html: 'Your account has been <strong>banned</strong> by the library administrator, so you cannot log in.<br><br>' +
			      'If you want the ban lifted, please email the system administrator first at:<br>' +
			      '<a href="' + mailto + '"><strong>' + adminEmail + '</strong></a>',
			showCancelButton: true,
			confirmButtonText: 'Email the administrator',
			cancelButtonText: 'Close',
			confirmButtonColor: '#00857a'
		}).then(function (res) {
			if (res.isConfirmed) { window.location.href = mailto; }
		});
	} else {
		alert('Your account has been banned, so you cannot log in. If you want the ban lifted, please email the system administrator first at ' + adminEmail + '.');
	}
})();
</script>
@endif
@endsection