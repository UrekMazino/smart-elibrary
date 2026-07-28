<!DOCTYPE html>
<!--
Template Name: NobleUI - HTML Bootstrap 5 Admin Dashboard Template
Author: NobleUI
Website: https://www.nobleui.com
Portfolio: https://themeforest.net/user/nobleui/portfolio
Contact: nobleui123@gmail.com
Purchase: https://1.envato.market/nobleui_admin
License: For each use you must have a valid license purchased only from above link in order to legally use the theme for your project.
-->
<html lang="en">
<head>
	<meta charset="UTF-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
  <meta name="description" content="Responsive HTML Admin Dashboard Template based on Bootstrap 5">
	<meta name="author" content="NobleUI">
	<meta name="keywords" content="nobleui, bootstrap, bootstrap 5, bootstrap5, admin, dashboard, template, responsive, css, sass, html, theme, front-end, ui kit, web">
  
  <title>DOST-PCAARRD eLibrary</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
  <!-- End fonts -->

	<!-- core:css -->
	<link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/core/core.css') }}">
	<!-- endinject -->

	<!-- Plugin css for this page -->
  <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.css') }}">
	<!-- End plugin css for this page -->

    @yield('css')

	<!-- inject:css -->
  <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/mdi/css/materialdesignicons.min.css') }}">
	<link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/fonts/feather-font/css/iconfont.css') }}">
	<link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/flag-icon-css/css/flag-icon.min.css') }}">
	<!-- endinject -->

  <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/sweetalert2/sweetalert2.min.css') }}">
  <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/select2/select2.min.css') }}">

  <!-- Layout styles -->  
	<link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/css/demo1/style.css') }}">
  <!-- End layout styles -->

  	<!-- Favicons-->
	<link rel="shortcut icon" href="{{ asset('assets/images/elibrary-02.png') }}">
	<link rel="apple-touch-icon" type="image/x-icon" href="{{ asset('opac/img/apple-touch-icon-57x57-precomposed.png') }}">
	<link rel="apple-touch-icon" type="image/x-icon" sizes="72x72" href="{{ asset('opac/img/apple-touch-icon-72x72-precomposed.png') }}">
	<link rel="apple-touch-icon" type="image/x-icon" sizes="114x114" href="{{ asset('opac/img/apple-touch-icon-114x114-precomposed.png') }}">
	<link rel="apple-touch-icon" type="image/x-icon" sizes="144x144" href="{{ asset('opac/img/apple-touch-icon-144x144-precomposed.png') }}">

</head>
<body class="sidebar-dark">
	<div class="main-wrapper">

		<!-- partial:partials/_sidebar.html -->
		<nav class="sidebar">
      <div class="sidebar-header">
        <a href="#" class="sidebar-brand">
           <img src="{{ asset('opac/img/elibrary-06.png') }}" width="100" alt="" />
        </a>
        <div class="sidebar-toggler not-active">
          <span></span>
          <span></span>
          <span></span>
        </div>
      </div>
      <div class="sidebar-body">
        <ul class="nav">
            <li class="nav-item nav-category">Overview</li>
            @can('access.dashboard')
            <li class="nav-item">
              <a href="{{ url('/admin/dashboard') }}" class="nav-link">
                <i class="link-icon" data-feather="grid"></i>
                <span class="link-title">Dashboard</span>
              </a>
            </li>
            @endcan
            @can('access.inquiry')
              <li class="nav-item">
                <a href="{{ url('/admin/inquiry') }}" class="nav-link">
                  <i class="link-icon" data-feather="message-square"></i>
                  <span class="link-title">Inquiries</span>
                </a>
              </li>
            @endcan
            @can('access.news') 
              <li class="nav-item">
                <a href="{{ url('/admin/news') }}" class="nav-link">
                  <i class="link-icon" data-feather="rss"></i>
                  <span class="link-title">News Article</span>
                </a>
              </li>
            @endcan
            @can('access.reports')
              <li class="nav-item">
                <a href="{{ url('/admin/report') }}" class="nav-link">
                  <i class="link-icon" data-feather="bar-chart-2"></i>
                  <span class="link-title">Reports</span>
                </a>
              </li>
            @endcan
          @can('access.cataloging')
            <li class="nav-item nav-category">Cataloging</li>
            @can('create.catalog')
              <li class="nav-item">
                <a href="{{ url('/admin/create-record') }}" class="nav-link">
                  <i class="link-icon" data-feather="file-plus"></i>
                  <span class="link-title">Create New Record</span>
                </a>
              </li>
            @endcan
            @can('view.catalog.importlist')
              <li class="nav-item">
                <a href="{{ url('/admin/batch-list') }}" class="nav-link">
                  <i class="link-icon" data-feather="upload"></i>
                  <span class="link-title">Batch Import</span>
                </a>
              </li>
            @endcan
            @can('view.catalog.list')
              <li class="nav-item">
                <a href="{{ url('/admin/record-list') }}" class="nav-link">
                  <i class="link-icon" data-feather="book"></i>
                  <span class="link-title">Record List</span>
                </a>
              </li>

              <li class="nav-item">
                <a href="{{ url('/admin/on-hold-list') }}" class="nav-link">
                  <i class="link-icon" data-feather="pause-circle"></i>
                  <span class="link-title">On-Hold</span>
                </a>
              </li>
            @endcan
          @endcan
          @can('access.circulation')
            <li class="nav-item nav-category">Circulation</li>
            @can('circulation.reservation.view')
              <li class="nav-item">
                  <a href="{{ url('/admin/reservations') }}" class="nav-link">
                  <i class="link-icon" data-feather="calendar"></i>
                  <span class="link-title">Reservation</span>
                  <span class="link-title">
                    <span class="badge border border-primary text-info">{{ getReservationCount() }}</span>
                    @php $resUnseen = getReservationUnseenMsgCount(); @endphp
                    @if($resUnseen > 0)<span class="badge bg-danger" title="Unread patron messages">{{ $resUnseen }}</span>@endif
                  </span>
                </a>
              </li>
            @endcan
            @can('manage.circulation.checkouts')
              <li class="nav-item">
                  <a href="{{ url('/admin/check-out-in') }}" class="nav-link">
                  <i class="link-icon" data-feather="repeat"></i>
                  <span class="link-title">Check-Out/Check-In</span>
                </a>
              </li>
            @endcan
            @can('access.circulation.transaction')
              <li class="nav-item">
                <a href="{{ url('/admin/borrowed') }}" class="nav-link">
                  <i class="link-icon" data-feather="activity"></i>
                  <span class="link-title">Transaction</span>
                </a>
              </li>
            @endcan
          @endcan
          @can('access.inventory')
            <li class="nav-item nav-category">Inventory</li>
            <li class="nav-item">
              <a href="{{ url('/admin/inventory') }}" class="nav-link">
                <i class="link-icon" data-feather="package"></i>
                <span class="link-title">Physical Count</span>
              </a>
            </li>
          @endcan
          {{-- <li class="nav-item">
            <a href="{{ url('/admin/visits') }}" class="nav-link">
              <i class="link-icon" data-feather="map"></i>
              <span class="link-title">Visitor</span>
            </a>
          </li> --}}

          @can('access.accounts')
            <li class="nav-item nav-category">Accounts</li>
            {{-- <li class="nav-item">
              <a href="{{ url('/admin/users') }}" class="nav-link">
                <i class="link-icon" data-feather="users"></i>
                <span class="link-title">Users</span>
              </a>
            </li> --}}
            @hasanyrole('super_admin|developer')
              <li class="nav-item">
                <a href="{{ route('administrators') }}" class="nav-link">
                  <i class="link-icon" data-feather="shield"></i>
                  <span class="link-title">Administrators</span>
                </a>
              </li>
            @endhasanyrole
            <li class="nav-item">
              <a href="{{ url('/admin/patron') }}" class="nav-link">
                <i class="link-icon" data-feather="users"></i>
                <span class="link-title">Patrons</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="{{ url('/admin/register') }}" class="nav-link">
                <i class="link-icon" data-feather="user-plus"></i>
                <span class="link-title">Register a Patron</span>
              </a>
            </li>
          @endcan
          @can('access.configuration')
            <li class="nav-item nav-category">Administration</li>

            <li class="nav-item">
              <a href="{{ url('/admin/settings') }}" class="nav-link">
                <i class="link-icon" data-feather="settings"></i>
                <span class="link-title">Settings</span>
              </a>
            </li>
            
            @can('view.config.template')
              <li class="nav-item">
                <a href="{{ url('/admin/marc-templates') }}" class="nav-link">
                  <i class="link-icon" data-feather="layout"></i>
                  <span class="link-title">MARC Templates</span>
                </a>
              </li>
            @endcan

            {{-- Shown under Administration, but needs cataloging access: it re-syncs catalog data
                 via the access.cataloging reindex routes. --}}
            @can('access.cataloging')
              <li class="nav-item">
                <a href="{{ url('/admin/ai-sync-monitor') }}" class="nav-link">
                  <i class="link-icon" data-feather="cpu"></i>
                  <span class="link-title">AI Sync Monitor</span>
                </a>
              </li>
            @endcan
            @hasanyrole('super_admin|developer')
            <li class="nav-item">
              <a href="{{ route('admin.list_roles') }}" class="nav-link">
                <i class="link-icon" data-feather="user-check"></i>
                <span class="link-title">Roles</span>
              </a>
            </li>
            <li class="nav-item">
              <a href="{{ route('admin.list_permissions') }}" class="nav-link">
                <i class="link-icon" data-feather="key"></i>
                <span class="link-title">Permissions</span>
              </a>
            </li>
            @endhasanyrole
          @endcan
        </ul>
      </div>
    </nav>
    
		<!-- partial -->
	
		<div class="page-wrapper">
					
			<!-- partial:partials/_navbar.html -->
			<nav class="navbar">
				<a href="#" class="sidebar-toggler">
					<i data-feather="menu"></i>
				</a>
				<div class="navbar-content">
					<form class="search-form">
						<div class="input-group">
              
						</div>
					</form>
					<ul class="navbar-nav">

						
						@can('access.notifications')
						<li class="nav-item dropdown">
							@php $nfyCount = notifications()['count']; @endphp
							<a class="nav-link dropdown-toggle position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								<i data-feather="bell"></i>
								@if($nfyCount > 0)<span class="nfy-badge">{{ $nfyCount > 9 ? '9+' : $nfyCount }}</span>@endif
							</a>
							<div class="dropdown-menu dropdown-menu-end p-0" aria-labelledby="notificationDropdown">
								@include('partials.notifications')
							</div>
						</li>
						@endcan
						<li class="nav-item dropdown">
							<a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								<img class="wd-30 ht-30 rounded-circle" src="{{ asset('admintemplate/nobleui/template/assets/images/profile.jpg') }}" alt="profile">
							</a>
							<div class="dropdown-menu p-0" aria-labelledby="profileDropdown">
								<div class="d-flex flex-column align-items-center border-bottom px-5 py-3">
									<div class="mb-3">
										<img class="wd-80 ht-80 rounded-circle" src="{{ asset('admintemplate/nobleui/template/assets/images/profile.jpg') }}" alt="">
									</div>
									<div class="text-center">
										<p class="tx-16 fw-bolder">{{ Auth::user()->name }}</p>
									</div>
								</div>
                <ul class="list-unstyled p-1">
                    <li class="dropdown-item py-2">
                      <a href="{{ url('/admin/profile') }}" class="text-body ms-0">
                        <i class="me-2 icon-md" data-feather="user"></i>
                        <span>Profile</span>
                      </a>
                    </li>

                   {{-- <li class="dropdown-item py-2">
                    <a href="javascript:;" class="text-body ms-0">
                      <i class="me-2 icon-md" data-feather="edit"></i>
                      <span>Edit Profile</span>
                    </a>
                  </li>
                  <li class="dropdown-item py-2">
                    <a href="javascript:;" class="text-body ms-0">
                      <i class="me-2 icon-md" data-feather="repeat"></i>
                      <span>Switch User</span>
                    </a>
                  </li> --}}
                  
                  <li class="dropdown-item py-2">
                    <form method="POST" id="frmLogout" action="{{ route('logout') }}">
                        @csrf
                    </form>
                        <a href="javascript:;" class="text-body ms-0" onclick="event.preventDefault();
                                        getElementById('frmLogout').submit();"><i class="me-2 icon-md" data-feather="log-out"></i>
                      <span>Log Out</span></a>
                        </a>
                  </li>
                </ul>
							</div>
						</li>
					</ul>
				</div>
			</nav>
			<!-- partial -->

			{{-- A forgotten "discourage search engines" switch silently keeps the whole catalogue out
			     of Google, and nothing else on screen would ever reveal it. Warn on every admin page
			     until it is turned off. --}}
			@if(settingEnabled('seo_discourage_indexing', false))
				<div class="px-4 pt-3">
					<div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap gap-2 mb-0" role="alert">
						<span>
							<i data-feather="eye-off" class="icon-sm me-1"></i>
							<strong>Search engines are being discouraged.</strong>
							Every public page is served as <code>noindex</code>, so this catalogue will not
							appear in Google. Intended for staging &mdash; turn it off before launch.
						</span>
						@can('access.configuration')
							<a href="{{ url('/admin/settings') }}" class="btn btn-sm btn-outline-dark">Settings</a>
						@endcan
					</div>
				</div>
			@endif

			@yield('content')

			<!-- partial:partials/_footer.html -->
			<footer class="footer d-flex flex-column flex-md-row align-items-center justify-content-between px-4 py-3 border-top small">
				<p class="text-muted mb-1 mb-md-0">Copyright © 2022 <a href="#" target="_blank">DOSt-PCAARRD eLibrary</a>.</p>
				{{-- <p class="text-muted">Handcrafted With <i class="mb-1 text-primary ms-1 icon-sm" data-feather="heart"></i></p> --}}
			</footer>
			<!-- partial -->
		
		</div>
	</div>

  <!-- Modal -->
  <div class="modal hide fade in" data-keyboard="false" data-backdrop="static" id="modalSaving" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body">
          <div class="d-flex align-items-center">
            <strong>loading...Please wait</strong>
            <div class="spinner-border text-success ms-auto" role="status" aria-hidden="true"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

	<!-- core:js -->
	<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/core/core.js') }}"></script>
	<!-- endinject -->

	<!-- Plugin js for this page -->
  <script src="{{ asset('admintemplate/nobleui/template/assets/vendors/chartjs/Chart.min.js') }}"></script>
  <script src="{{ asset('admintemplate/nobleui/template/assets/vendors/jquery.flot/jquery.flot.js') }}"></script>
  <script src="{{ asset('admintemplate/nobleui/template/assets/vendors/jquery.flot/jquery.flot.resize.js') }}"></script>
  <script src="{{ asset('admintemplate/nobleui/template/assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.js') }}"></script>
  <script src="{{ asset('admintemplate/nobleui/template/assets/vendors/apexcharts/apexcharts.min.js') }}"></script>
	<!-- End plugin js for this page -->

  <script  src="{{ asset('admintemplate/nobleui/template/assets/vendors/jquery-number/jquery.number.js') }}"></script>

  <script src="{{ asset('admintemplate/nobleui/template/assets/vendors/select2/select2.min.js') }}"></script>
    @yield('js')

	<!-- inject:js -->
	<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/feather-icons/feather.min.js') }}"></script>
	<script src="{{ asset('admintemplate/nobleui/template/assets/js/template.js') }}"></script>
	<!-- endinject -->

  <script src="{{ asset('admintemplate/nobleui/template/assets/vendors/sweetalert2/sweetalert2.min.js') }}"></script>


	<!-- Custom js for this page -->
  <script src="{{ asset('admintemplate/nobleui/template/assets/js/dashboard-light.js') }}"></script>
  <script src="{{ asset('admintemplate/nobleui/template/assets/js/datepicker.js') }}"></script>
	<!-- End custom js for this page -->

  <script>

  </script>

</body>
</html>    