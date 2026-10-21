@extends('opac.layout.master')

@section('robots', 'noindex, nofollow')


@section('content')
        <div class="bg_color_2">
            <div class="container margin_60_35">
                <div id="login">
                    <center><h3><img src="{{ asset('opac/img/elibrary-06.png') }}" alt="" width="200"class="img-fluid"></h3></center>
                    <div class="box_form">
                        <form method="POST" action="{{ route('login') }}">
                        @csrf
                            <div class="form-group">
                                <input type="email" class="form-control" name="email" placeholder="Your email address">
                            </div>
                            <div class="form-group">
                                <input type="password" class="form-control" placeholder="Your password" name="password" id="password">
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
<script type="text/javascript">
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