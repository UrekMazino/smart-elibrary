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
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="X-UA-Compatible" content="ie=edge">
  <meta name="description" content="Responsive HTML Admin Dashboard Template based on Bootstrap 5">
	<meta name="author" content="NobleUI">
	<meta name="keywords" content="nobleui, bootstrap, bootstrap 5, bootstrap5, admin, dashboard, template, responsive, css, sass, html, theme, front-end, ui kit, web">

	<title>DOST-FNRI eLibrary</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
  <!-- End fonts -->

	<!-- core:css -->
	<link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/bootstrap5/css/bootstrap.min.css') }}">
	<!-- endinject -->

	<!-- Plugin css for this page -->
  <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.css') }}">
	<!-- End plugin css for this page -->

    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/css/dataTables.bootstrap5.css') }}">

    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/css/buttons.bootstrap5.min.css') }}">

	<!-- inject:css -->
	<link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/fonts/feather-font/css/iconfont.css') }}">
	<link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/flag-icon-css/css/flag-icon.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/mdi/css/materialdesignicons.min.css') }}">
	<!-- endinject -->

    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/sweetalert2/sweetalert2.min.css') }}">

  	<!-- Favicons-->
	<link rel="shortcut icon" href="{{ asset('assets/images/elibrary-02.png') }}">
	<link rel="apple-touch-icon" type="image/x-icon" href="{{ asset('opac/img/apple-touch-icon-57x57-precomposed.png') }}">
	<link rel="apple-touch-icon" type="image/x-icon" sizes="72x72" href="{{ asset('opac/img/apple-touch-icon-72x72-precomposed.png') }}">
	<link rel="apple-touch-icon" type="image/x-icon" sizes="114x114" href="{{ asset('opac/img/apple-touch-icon-114x114-precomposed.png') }}">
	<link rel="apple-touch-icon" type="image/x-icon" sizes="144x144" href="{{ asset('opac/img/apple-touch-icon-144x144-precomposed.png') }}">

    
</head>
<body>
    <div class="container-fluid p-3">
        <div class="row">
            <div class="col-12">
                <table class="table" id="tbl">
                    <thead>
                        <th>#</th>
                        <th>Accession Number</th>
                        <th>Circulation Number</th>
                        <th>Title</th>
                        <th>Date/Time Scanned</th>
                        <th></th>
                    </thead>
                    <tbody>
                        @foreach (getCopiesPresentedList() as $item)
                            <tr>
                                <td></td>
                                <td>{{$item->accession_number}}</td>
                                <td>{{$item->circulation_number}}</td>
                                <td>{{$item->title}}</td>
                                <td>{{$item->created_at}}</td>
                                <td><a href="#" data-delete-item="{{ (int) $item->id }}"><i class="mdi mdi-delete text-danger"></i></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form method="post" action="{{ url('/admin/inventory/delete') }}" id="frm_delete">
        @csrf
        <input type="hidden" name="presented_id" id="presented_id">
    </form>

</body>

<script src="{{ asset('js/jquery-3.6.0.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/bootstrap5/js/bootstrap.bundle.min.js') }}"></script>


<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/sweetalert2/sweetalert2.min.js') }}"></script>

<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/js/dataTables.bootstrap5.min.js') }}"></script>

<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/buttons.bootstrap5.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/buttons.html5.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/jszip.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/buttons.print.min.js') }}"></script>


<script nonce="{{ cspNonce() }}">
// The delete icons (a listener, not onclick=: the Content Security Policy). This report page has no
// admin layout, so no AdminActions.
document.addEventListener('click', function (e) {
    var a = e.target.closest('[data-delete-item]');
    if (!a) return;
    e.preventDefault();
    deleteFromList(+a.dataset.deleteItem);
});
$(document).ready(function() {
    var t = $('#tbl').DataTable( {
        dom: 'Bfrtip',
        buttons: [
                    {
                        extend: 'excelHtml5',
                        text: 'Export to Excel',
                    }    
            ],
    } );

    t.on('order.dt search.dt', function () {
        let i = 1;
 
        t.cells(null, 0, { search: 'applied', order: 'applied' }).every(function (cell) {
            this.data(i++);
        });
    }).draw();
} );



function deleteFromList(id)
{

    $("#presented_id").val(id);
    Swal.fire({
    title: 'Are you sure?',
    text: "You won't be able to revert this!",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, remove from the list!'
    }).then((result) => {
    if (result.isConfirmed) {
        $("#frm_delete").submit();
    }
    })
}

</script>
</html>