@extends("admin.layouts.master")

@section('css')
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.css') }}">
    <style>
      table td {
        word-break: break-word;
        vertical-align: top;
        white-space: normal !important;
      }
      #popup-message {
        display: none;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        padding: 10px 20px;
        background-color: #f1f1f1;
        border: 1px solid #ccc;
        border-radius: 4px;
        font-size: 16px;
      }
    </style>
@endsection

@section('content')
<div class="page-content">
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
        </ol>
    </nav>
    <div class="row">
        <div class="col-md-6 offset-md-3 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title text-center">Register a Patron</h6>
                    <x-auth-validation-errors class="mb-4 text-danger" :errors="$errors"/>
                    <div><p class='text-danger text-center'>{{ session('error') ? session('error') : "" }}</p></div>
                    <form method="post" action="{{ url('/admin/register/store') }}" enctype="form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="exampleInputText1" class="form-label">Fullname</label>
                            <input name="fullName" type="text" class="form-control" id="full-name" placeholder="Enter Name" :value="old('fullName')" required autofocus>
                        </div>

                        <div class="mb-3">
                            <label for="exampleInputEmail3" class="form-label">Email Address</label>
                            <input name="email" type="email" class="form-control" id="email-address" placeholder="Enter Email" :value="old('email')" required>
                        </div>

                        <div class="mb-3">
                            <label for="exampleInputText2" class="form-label">Contact Number</label>
                            <input name="contactNumber" type="text" class="form-control" id="contact-nsumber" :value="old('contactNumber')" required>
                        </div>

                        <div class="mb-3">
                            <label for="exampleInputPassword3" class="form-label">Password</label>
                            <label class="form-label" style="font-size:10px;"><span class="badge bg-danger">Default</span> <span class="text-danger">Password will be sent to patron's email.</span></label>
                            <input type="password" class="form-control" id="password" disabled value="LibraryCode" placeholder="Enter Password">
                        </div>
                        
                        <div class="text-center">
                          <button class="btn btn-primary" type="submit">Submit</button>
                        </div>
                    </form>
  
                    <!-- QRCODE modal -->
                    <div class="modal fade qrcode-modal" tabindex="-1" aria-labelledby="myExtraLargeModalLabel" aria-hidden="true">
                      <div class="modal-dialog modal-l">
                        <div class="modal-content">

                          <div class="modal-header alert alert-success">
                            <h5 class="modal-title text-center" id="qrcodeModalLabel">{{ session('success') ? session('success') : "" }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="btn-close">
                            </button>
                          </div>
                          <div class="modal-body" style="margin: auto;">
                            <div><h5 class="text-center text-uppercase mb-3">Patron Library Code</h5></div>
                            <div class="mb-3">{!! DNS2D::getBarcodeHTML(session('user_library_code') ? session('user_library_code') : "0000000", 'QRCODE') !!}</div>
                            <div class="text-center">
                              <div class="alert alert-success" id="popup-message">Text copied!</div>
                              <input type="text" class="form-control text-center mb-2" disabled value="{{ session('user_library_code') ? session('user_library_code') : '0000000' }}" id="qrcodeInput">

                              <!-- The button used to copy the text -->
                              <button class="btn btn-info text-center" onclick="copyToClipBoard()" id="copy-btn">Copy Code</button>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <!-- QRCODE modal -->
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.js') }}"></script>
<script>
$(document).ready(function(){
        @if(session('user_library_code')) {
          $('.qrcode-modal').modal('show');
        }
        @endif

});

function copyToClipBoard() {
  // Get the text field
  var copyText = document.getElementById("qrcodeInput");

  // Select the text field
  copyText.select();
  copyText.setSelectionRange(0, 99999); // For mobile devices

   // Copy the text inside the text field
  navigator.clipboard.writeText(copyText.value);

  var popup = $('#popup-message');
  popup.css('display', 'block');

  setTimeout(function() {
    popup.css('display', 'none');
  }, 2000); // Popup message disappears after 2 seconds
}
</script>
@endsection