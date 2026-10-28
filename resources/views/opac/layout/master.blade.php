<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="author" content="FPRDI">
	<meta name="csrf_token" content="{{ csrf_token() }}">

	{{-- Per-page SEO (OPAC_SEO_PLAN.md workstream A). Pages override these via @section;
	     the defaults below are what every page used to emit unconditionally. --}}
	<title>@yield('meta_title', 'DOST-PCAARRD eLibrary')</title>
	<meta name="description" content="@yield('meta_description', 'Search the DOST-PCAARRD eLibrary for agriculture, aquatic and natural resources (AANR) research materials.')">
	{{-- The admin "discourage search engines" switch OVERRIDES the page's own value: while it is on,
	     nothing is indexable, whatever an individual page asks for. --}}
	@if(settingEnabled('seo_discourage_indexing', false))
		<meta name="robots" content="noindex, nofollow">
	@else
		<meta name="robots" content="@yield('robots', 'index, follow')">
	@endif
	@if(setting('seo_verification_google'))
		<meta name="google-site-verification" content="{{ setting('seo_verification_google') }}">
	@endif
	@hasSection('canonical')
		<link rel="canonical" href="@yield('canonical')">
	@endif

	{{-- Link previews (social/chat). og:image only when the page supplies one. --}}
	<meta property="og:site_name" content="DOST-PCAARRD eLibrary">
	<meta property="og:type" content="@yield('og_type', 'website')">
	<meta property="og:title" content="@yield('meta_title', 'DOST-PCAARRD eLibrary')">
	<meta property="og:description" content="@yield('meta_description', 'Search the DOST-PCAARRD eLibrary for agriculture, aquatic and natural resources (AANR) research materials.')">
	<meta property="og:url" content="@yield('canonical', url()->current())">
	@hasSection('og_image')
		<meta property="og:image" content="@yield('og_image')">
		<meta name="twitter:card" content="summary_large_image">
	@else
		<meta name="twitter:card" content="summary">
	@endif

	{{-- schema.org structured data (workstream B). Pushed as raw <script nonce="{{ cspNonce() }}"> blocks — @yield would
	     HTML-escape the JSON. Safe to emit raw: seoJsonLdEncode() uses JSON_HEX_TAG. --}}
	@stack('json_ld')

	<!-- Favicons-->
	<link rel="shortcut icon" href="{{ asset('assets/images/elibrary-02.png') }}">
	<link rel="apple-touch-icon" type="image/x-icon" href="{{ asset('opac/img/apple-touch-icon-57x57-precomposed.png') }}">
	<link rel="apple-touch-icon" type="image/x-icon" sizes="72x72" href="{{ asset('opac/img/apple-touch-icon-72x72-precomposed.png') }}">
	<link rel="apple-touch-icon" type="image/x-icon" sizes="114x114" href="{{ asset('opac/img/apple-touch-icon-114x114-precomposed.png') }}">
	<link rel="apple-touch-icon" type="image/x-icon" sizes="144x144" href="{{ asset('opac/img/apple-touch-icon-144x144-precomposed.png') }}">

	<!-- DataTables-->
	<!-- <link href="{{ asset('opac/DataTables/Bulma-0.9.2/css/bulma.min.css') }}" rel="stylesheet"> -->
	<link href="{{ asset('opac/DataTables/DataTables-1.12.1/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet">


    <!-- GOOGLE WEB FONT -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

	<!-- BASE CSS -->
	<link href="{{ asset('opac/css/bootstrap.min.css') }}" rel="stylesheet">
	<link href="{{ asset('opac/css/style.css') }}" rel="stylesheet">
	<link href="{{ asset('opac/css/menu.css') }}" rel="stylesheet">
	<link href="{{ asset('opac/css/vendors.css') }}" rel="stylesheet">
	<link href="{{ asset('opac/css/icon_fonts/css/all_icons_min.css') }}" rel="stylesheet">

	<link href="{{ asset('opac/css/blog.css') }}" rel="stylesheet">

	<!-- YOUR CUSTOM CSS -->
	<link href="{{ asset('opac/css/custom.css') }}" rel="stylesheet">
	<link href="{{ asset('opac/css/stars.css') }}" rel="stylesheet">



	@yield('css')

</head>

<body>

	<div class="layer"></div>
	<!-- Mobile menu overlay mask -->

	<div id="preloader">
		<div data-loader="circle-side"></div>
	</div>
	<!-- End Preload -->

	<header class="header_sticky">
		<div class="container">
			<div class="row">
				<div class="col-lg-3 col-6">
					<div id="logo_home">
						<h1><a href="{{ url('/home') }}" title="Home">eLibrary</a></h1>
					</div>
				</div>
				<nav class="col-lg-9 col-6">
					<a class="cmn-toggle-switch cmn-toggle-switch__htx open_close" href="#0"><span>Menu mobile</span></a>
					<div class="main-menu">
						<ul>
							{{-- Optional breadcrumb before the bell / account menu (desktop only), e.g. the search
							     page's "Home › Search Results". Pages without the section keep the header as is. --}}
							@hasSection('header_breadcrumb')
							<li class="hdr-crumb">@yield('header_breadcrumb')</li>
							<style>
								.main-menu > ul > li.hdr-crumb { vertical-align: top; padding: 6px 16px 17px 8px; margin-right: 8px; border-right: 1px solid #e3e8eb; font-size: 14px; color: #6b7680; }
								.main-menu > ul > li.hdr-crumb a { display: inline; padding: 0; color: #00756b; font-weight: 500; }
								.main-menu > ul > li.hdr-crumb a:hover { color: #e74e84; background: none; text-decoration: underline; }
								.main-menu > ul > li.hdr-crumb [aria-hidden] { margin: 0 6px; }
								@media (max-width: 991px) { .main-menu > ul > li.hdr-crumb { display: none !important; } }
							</style>
							@endif
							@auth
							@if(Auth::user()->usertype === 'Patron')
							<li class="nfy-bell-li" style="position:relative; list-style:none;">
								@php $nfyCount = notifications()['count']; @endphp
								<a href="#0" class="nfy-bell-toggle" style="position:relative; display:inline-block;">
									<i class="pe-7s-bell" style="font-size:28px; vertical-align:middle;"></i>
									@if($nfyCount > 0)<span class="nfy-badge">{{ $nfyCount > 9 ? '9+' : $nfyCount }}</span>@endif
								</a>
								<div class="nfy-pop">
									@include('partials.notifications')
								</div>
							</li>
							<style>
								.nfy-pop { display:none; position:absolute; right:0; top:100%; z-index:1050; background:#fff; border:1px solid #e5e8ee; border-radius:12px; box-shadow:0 12px 32px rgba(20,30,60,.16); overflow:hidden; margin-top:8px; }
								.nfy-pop.open { display:block; }
								/* The theme's menu links (.main-menu ul li a: display:block, padding:10px; on phones
								   padding:15px !important) stack each item's icon above its text. Keep the panel's row layout. */
								.main-menu .nfy-pop a.nfy-item { display:flex; padding:11px 16px !important; }
							</style>
							<script nonce="{{ cspNonce() }}">
							document.addEventListener('click', function (e) {
								var pop = document.querySelector('.nfy-pop'); if (!pop) return;
								if (e.target.closest('.nfy-bell-toggle')) { e.preventDefault(); pop.classList.toggle('open'); }
								else if (!e.target.closest('.nfy-pop')) { pop.classList.remove('open'); }
							});
							</script>
							@endif
							@if(Auth::user()->isStaff())
							{{-- Staff browsing the OPAC from the admin: no patron menu (profile, reservations,
							     inquiries, reviews), just the way back to the admin page they came from. --}}
							<li class="acct-li acct-li-register"><a href="{{ route('admin.return') }}" class="acct-register">Return to admin</a></li>
							@else
							<li class="submenu">
								<a href="#0" class="show-submenu">Welcome, {{Auth::user()->name}} <i class="icon-down-open-mini"></i></a>
								<ul>
									<li><a href="{{ url('/home') }}">Home</a></li>
									<li><a href="{{ url('/patron/profile') }}">Profile</a></li>
									<li><a href="{{ url('/patron/transactions') }}">Transactions {!! getNotif('reservation') !!}</a></li>
									<li><a href="{{ url('/patron/inquiries') }}">Inquiries {!! getNotif('inquiry') !!}</a></li>
									<li><a href="{{ url('/patron/to-review') }}">To Review {!! getNotif('review') !!}</a></li>
									<form method="POST" id="frmLogout" action="{{ route('logout') }}">
		                                @csrf
		                            </form>
									<li data-logout><a href="#">Logout</a></li>
								</ul>

							</li>
							@endif
							@else
							{{-- Guests: "Log in" as a plain link for returning members, "Register" as the one filled
							     button for newcomers. In the phone menu they become two full-width buttons, Register first. --}}
							<li class="acct-li acct-li-login"><a href="{{ route('login') }}" class="acct-login">Log in</a></li>
							<li class="acct-li acct-li-register"><a href="{{ route('register') }}" class="acct-register">Register</a></li>
							@endauth
							{{-- Account buttons: guests' "Log in" / "Register", staff's "Return to admin". --}}
							<style>
								.main-menu > ul > li.acct-li { vertical-align: top; }
								/* 30px lines keep the header as tall as the old icon "Login" (and the patrons' bell). */
								.main-menu > ul > li > a.acct-login { color: #333; line-height: 30px; }
								.main-menu > ul > li > a.acct-login:hover { color: #00756b; }
								.main-menu > ul > li > a.acct-register { margin-left: 6px; padding: 0 18px; line-height: 30px; border-radius: 999px; background: #00857a; color: #fff; font-weight: 600; transition: background .15s ease; }
								.main-menu > ul > li:hover > a.acct-register { background: #006f65; color: #fff; }
								.main-menu > ul > li > a.acct-login:focus-visible, .main-menu > ul > li > a.acct-register:focus-visible { outline: 2px solid #00a496; outline-offset: 2px; }
								@media (max-width: 991px) {
									.main-menu > ul:has(> li.acct-li) { display: flex; flex-direction: column; gap: 10px; padding: 16px 15px; }
									.main-menu > ul > li.acct-li { border: 0; }
									.main-menu > ul > li.acct-li-register { order: -1; }
									.main-menu > ul > li > a.acct-login, .main-menu > ul > li > a.acct-register { margin: 0; padding: 11px 15px !important; line-height: 1.4; border-radius: 8px; text-align: center; font-size: 15px; font-weight: 600; }
									.main-menu > ul > li > a.acct-login { border: 1px solid #00857a; color: #00756b !important; }
									.main-menu > ul > li > a.acct-login:hover { background-color: #eef8f6; color: #00756b !important; }
									.main-menu > ul > li > a.acct-register { background: #00857a; color: #fff !important; }
									.main-menu > ul > li > a.acct-register:hover { background-color: #006f65; color: #fff !important; }
								}
							</style>
						</ul>
						@unless(Auth::check() && Auth::user()->isStaff()) {!! getNotif('overall') !!} @endunless
					</div>
					<!-- /main-menu -->

				</nav>
			</div>
		</div>
		<!-- /container -->
	</header>
	<!-- /header -->

	<main>
	@yield('content')

	</main>
	<!-- /main content -->
	<footer>
		<div class="container py-5">
			<div class="row gy-4 text-center text-md-start">
				<!-- Local Resources -->
				<div class="col-lg-3 col-md-6">
					<h5>Local Resources</h5>
					<ul class="links list-unstyled">
						<li><a href="https://www.da.gov.ph/" target="_blank">Department of Agriculture</a></li>
						<li><a href="http://www.bsu.edu.ph/shamag#:~:text=Shamag%2C%20an%20Ibaloi%20term%20that,communication%20tool%20among%20BSU%20constituents." target="_blank">Benguet State University</a></li>
						<li><a href="https://www.pids.gov.ph/publications" target="_blank">Philippine Institute for Development Studies</a></li>
						<li><a href="https://fprdi.dost.gov.ph/22-publications" target="_blank">DOST-FPRDI</a></li>
					</ul>
				</div>

				<!-- International Resources -->
				<div class="col-lg-5 col-md-6">
					<h5>International Resources</h5>
					<ul class="links list-unstyled">
						<li><a href="https://www.tappi.org/" target="_blank">Technical Association of the Pulp and Paper Industry (TAPPI)</a></li>
						<li><a href="https://www.workinagriculture.com/" target="_blank">International Association on Agricultural Work (IAWA)</a></li>
						<li><a href="https://forestprod.org/" target="_blank">Forest Products Society (FPS)</a></li>
						<li><a href="https://www.iufro.org/" target="_blank">International Union of Forest Research Organizations (IUFRO)</a></li>
						<li><a href="https://www.apafri.org/" target="_blank">Asia Pacific Association of Forestry Research Institutions (APAFRI)</a></li>
						<li><a href="https://www.pcaarrd.dost.gov.ph/index.php/staarrdec/" target="_blank">STAARRDEC</a></li>
					</ul>
				</div>

				<!-- Contact Us -->
				<div class="col-lg-4 col-md-12">
					<h5>Contact with Us</h5>
					<ul class="contacts list-unstyled">
						<li><a href="tel://495362377"><i class="icon_mobile"></i> Telephone Number: (49) 536 2377</a></li>
						<li><a href="mailto:info@fprdi.dost.gov.ph"><i class="icon_mail_alt"></i> Email: info@fprdi.dost.gov.ph</a></li>
						<li><a href="https://maps.app.goo.gl/HvsNhRhJ3UYLXZV4A"><i class="icon-address"></i> Location: Domingo Lantican Ave, UPCFNR Campus, UPLB, College, Laguna 4031</a></li>
					</ul>
					<div class="follow_us mt-3">
						<h5>Follow us</h5>
						<ul class="list-inline">
							<li class="list-inline-item"><a href="https://www.facebook.com/dostfprdi" target="_blank"><i class="social_facebook"></i></a></li>
							<li class="list-inline-item"><a href="https://www.youtube.com/channel/UCaNndZCAc6qej8OVYSCnz1w" target="_blank"><i class="social_youtube"></i></a></li>
							<li class="list-inline-item"><a href="https://www.instagram.com/dostfprdi" target="_blank"><i class="social_instagram"></i></a></li>
						</ul>
					</div>
				</div>
			</div>

			<hr class="my-4">

			<!-- Footer bottom -->
			<div class="row">
				<div class="col-12 text-center">
					<div id="copy">© 2023 DOST-PCAARRD eLibrary · <a href="#" data-bs-toggle="modal" data-bs-target="#modalPrivacy">Privacy</a></div>
				</div>
			</div>
		</div>
	</footer>

	<!--/footer-->

	<!-- <div id="toTop"></div> -->
	<!-- Back to top button -->

	<div class="modal" id="modalTerms" tabindex="-1">
	  <div class="modal-dialog">
	    <div class="modal-content">
	      <div class="modal-header">
	        <h5 class="modal-title text-info">Terms and conditions</h5>
	        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
	      </div>
	      <div class="modal-body">
	        <p>
				{{-- Content Here --}}
			</p>
	      </div>
	    </div>
	  </div>
	</div>

	<div class="modal" id="modalPrivacy" tabindex="-1">
	  <div class="modal-dialog">
	    <div class="modal-content">
	      <div class="modal-header">
	        <h5 class="modal-title text-info">Privacy</h5>
	        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
	      </div>
	      <div class="modal-body">
			{{-- Keep in step with docs/implemented/SECURITY_NOTES.md §6. The agency's full notice (what accounts hold,
			     retention, the Data Protection Officer's contact, data subject rights) is still to be written with its DPO. --}}
			<h6>Your visit and the library's statistics</h6>
			<p>When you open a material, the eLibrary uses your network (IP) address and browser to recognise a repeat
				visit, so a reader is counted once a day in the list of most viewed materials and automated traffic is
				slowed down. If you are not signed in, your views are kept with a code made from them (not the address
				or the browser themselves), and your address is kept separately so the library's system administrators
				can look into misuse of the statistics. Both are deleted after 90 days. They are not added to any account
				or shared outside the agency.</p>
			<p>Downloads and ratings are counted once per account.</p>
			@if (\App\Rules\HumanCheck::enabled() || \App\Services\GoogleSignIn::enabled())
				<h6>Signing up and signing in</h6>
				@if (\App\Rules\HumanCheck::enabled())
					<p>The sign-up form checks that a person, not a program, is signing up, with Cloudflare Turnstile: your
						browser connects to Cloudflare for the check, under Cloudflare's privacy policy. The eLibrary keeps
						nothing from it.</p>
				@endif
				@if (\App\Services\GoogleSignIn::enabled())
					<p>If you choose “Continue with Google”, Google tells the eLibrary your name and email address, and that
						Google has confirmed the address. The eLibrary keeps your Google account's ID to recognise you next
						time. It gets no access to your Google account, contacts or files.</p>
				@endif
			@endif
			<p class="mb-0">Questions about your personal data: <a href="mailto:{{ config('app.admin_email') }}">{{ config('app.admin_email') }}</a>.</p>
	      </div>
	    </div>
	  </div>
	</div>


	<div class="modal fade" id="inquireReplyModal" tabindex="-1" role="dialog">
		<div class="modal-dialog" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h4 class="title" id="smallModalLabel">Inquiry on this material</h4>
				</div>
				<form method="post" action="{{ url('/inquiry/reply') }}" id="frm_inquiry_reply">
				@csrf
				<input type='hidden' name='catalog_id_inquiry_reply' id='catalog_id_inquiry_reply'>
				<div class="modal-body">

					<div id='inquiry_message'>

					</div>
					<span>Message : </span>
					<textarea class='form-control' name="inquiry_message_reply"></textarea>
				</div>
				<div class="modal-footer">
					<button type="submit" class="btn btn-info">Submit</button>
					<button type="button" class="btn btn-danger" data-bs-dismiss="modal">CLOSE</button>
				</div>
				</form>
			</div>
		</div>
	</div>



	<!-- COMMON SCRIPTS -->
	<script src="{{ asset('opac/js/jquery-3.6.0.min.js') }}"></script>
	<script src="{{ asset('opac/js/common_scripts.min.js') }}"></script>
	<script src="{{ asset('opac/js/functions.js') }}"></script>

	<!-- DATATABLE SCRIPTS -->
	<!-- <script src="{{ asset('opac/DataTables/datatables.min.js') }}"></script> -->
	<script src="{{ asset('opac/DataTables/DataTables-1.12.1/js/jquery.dataTables.min.js') }}"></script>
	<script src="{{ asset('opac/DataTables/DataTables-1.12.1/js/dataTables.bootstrap5.min.js') }}"></script>

	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.26.25"></script>
	<script nonce="{{ cspNonce() }}">
		// While a popup is open SweetAlert hides the page's scrollbar and pads <body> by its width.
		// These pages scroll on <html>, so the scrollbar stays and the padding only shifted the page
		// 15px to the left (a gap at the right of the header). No padding for any popup.
		if (window.Swal) window.Swal = Swal.mixin({ scrollbarPadding: false });
	</script>
	@auth
		@if(Auth::user()->usertype === 'Patron')
			@include('opac.partials.quick-review')
		@endif
	@endauth
	<!-- CHATBOT -->
	{{-- <script src="https://account.snatchbot.me/script.js"></script><script nonce="{{ cspNonce() }}">window.sntchChat.Init(257292)</script>  --}}
	{{-- Ask the library: the chat button at the lower right (General inquiries; guests answered by email). --}}
	@include('opac.partials.chat-widget')
	{{-- Staff browsing the OPAC: the warning 2 minutes before a session limit signs them out. --}}
	@include('partials.session-timeout')

	{{-- Logout and the "back" breadcrumb, as listeners rather than onclick= (the Content Security Policy). --}}
	<script nonce="{{ cspNonce() }}">
		document.addEventListener('click', function (e) {
			if (!e.target.closest('[data-logout]')) return;
			e.preventDefault();
			document.getElementById('frmLogout').submit();
		});
		// A "back" breadcrumb (e.g. Search Results) returns to the search the visitor came from, if any.
		document.addEventListener('click', function (e) {
			if (!e.target.closest('[data-crumb-back]') || document.referrer.indexOf('/results') === -1) return;
			e.preventDefault();
			history.back();
		});
	</script>
	@yield('js')


</body>
</html>
