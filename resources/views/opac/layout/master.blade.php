<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="description" content="Find ANNR materials online">
	<meta name="author" content="FPRDI">
	<meta name="csrf_token" content="{{ csrf_token() }}">

	<title>DOST-PCAARRD eLibrary</title>

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
							@auth
							<li class="submenu">
								<a href="#0" class="show-submenu">Welcome, {{Auth::user()->name}} <i class="icon-down-open-mini"></i></a>
								<ul>
									<li><a href="{{ url('/home') }}">Home</a></li>
									<li><a href="{{ url('/patron/profile') }}">My Profile</a></li>
									<li><a href="{{ url('/patron/transactions') }}">My Transactions</a></li>
									<li><a href="{{ url('/patron/inquiries') }}">Inquiries {!! getNotif('inquiry') !!}</a></li>
									<li><a href="{{ url('/patron/to-review') }}">To Review {!! getNotif('review') !!}</a></li>
									<form method="POST" id="frmLogout" action="{{ route('logout') }}">
		                                @csrf
		                            </form>
									<li onclick="event.preventDefault();
                                                getElementById('frmLogout').submit();"><a href="#">Logout</a></li>
								</ul>

							</li>
							@else
							<li class="submenu">
								<a href="{{ url('login') }}"><i class="pe-7s-user" style="font-size: 30px !important;vertical-align: middle;"></i>&nbsp&nbspLogin</a>
							</li>
							@endauth
						</ul>
						{!! getNotif('overall') !!}
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
					<div id="copy">© 2023 DOST-PCAARRD eLibrary</div>
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
	        <p>
				{{-- Content Here --}}
			</p>
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
	
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<!-- CHATBOT -->
	{{-- <script src="https://account.snatchbot.me/script.js"></script><script>window.sntchChat.Init(257292)</script>  --}}

	@yield('js')

	 
</body>
</html>