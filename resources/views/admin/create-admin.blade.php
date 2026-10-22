@extends("admin.layouts.master")

@section('css')
    <style>
      .form-hint { color: #a3adc2; font-size: 11.5px; margin-top: 4px; display: block; }
      .form-label { font-size: 12.5px; font-weight: 600; color: #33415c; margin-bottom: 4px; }
      .req { color: #c92a4b; }

      /* Section divider inside the form */
      .form-section {
        font-size: 11px; text-transform: uppercase; letter-spacing: .4px; color: #7987a1;
        font-weight: 700; padding-bottom: 6px; border-bottom: 1px solid #eef0f2; margin-bottom: 14px;
      }

      .note {
        background: #fff8e8; border: 1px solid #f6e3bc; border-radius: 6px;
        padding: 10px 12px; font-size: 12px; color: #7a5b16; line-height: 1.5;
      }

      /* Credentials handed over once, after the account is created */
      .cred-box {
        background: #f7f9fc; border: 1px dashed #cfd8e8; border-radius: 8px; padding: 14px;
      }
      .cred-label {
        font-size: 11px; text-transform: uppercase; letter-spacing: .4px;
        color: #7987a1; font-weight: 700; margin-bottom: 5px;
      }
      .cred-value {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 16px; font-weight: 700; letter-spacing: 1px;
        color: #22283b; background: #fff; text-align: center;
      }
      .qr-wrap svg, .qr-wrap img { max-width: 150px; height: auto; }

      #popup-message {
        display: none; position: fixed; top: 50%; left: 50%;
        transform: translate(-50%, -50%); padding: 10px 20px;
        background-color: #f1f1f1; border: 1px solid #ccc; border-radius: 4px;
        font-size: 16px; z-index: 2000;
      }
    </style>
@endsection

@section('content')
<div class="page-content">
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('accounts') }}">Accounts</a></li>
            <li class="breadcrumb-item"><a href="{{ route('administrators') }}">Administrators</a></li>
            <li class="breadcrumb-item active" aria-current="page">Add administrator</li>
        </ol>
    </nav>
    <div class="row">
        <div class="col-md-7 col-lg-6 offset-md-2 offset-lg-3 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="card-title mb-0">ADD NEW ADMIN</h6>
                        <a href="{{ route('administrators') }}" class="btn btn-outline-secondary btn-sm">
                            <i data-feather="arrow-left" class="icon-sm me-1"></i>Back to Administrators
                        </a>
                    </div>
                    <p class="text-muted mb-3" style="font-size: 12.5px;">
                        Creates a staff account with the role you pick below. A temporary password and
                        library code are generated and shown once, right after saving.
                    </p>

                    <x-auth-validation-errors class="mb-3 text-danger" :errors="$errors"/>
                    @if(session('error'))
                        <div class="alert alert-danger py-2" style="font-size: 13px;">{{ session('error') }}</div>
                    @endif

                    <form method="post" action="{{ route('admin.store') }}">
                        @csrf

                        <div class="form-section">Account</div>

                        <div class="mb-3">
                            <label for="user-name" class="form-label">Username <span class="req">*</span></label>
                            <input name="userName" type="text" class="form-control" id="user-name"
                                   placeholder="jdelacruz" value="{{ old('userName') }}" required autofocus>
                            <span class="form-hint">Must be unique.</span>
                        </div>

                        <div class="mb-3">
                            <label for="full-name" class="form-label">Full name <span class="req">*</span></label>
                            <input name="fullName" type="text" class="form-control" id="full-name"
                                   placeholder="Juan Dela Cruz" value="{{ old('fullName') }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="email-address" class="form-label">Email address <span class="req">*</span></label>
                            <input name="email" type="email" class="form-control" id="email-address"
                                   placeholder="name@example.com" value="{{ old('email') }}" required>
                            <span class="form-hint">Used to sign in. Must not already be registered.</span>
                        </div>

                        <div class="mb-4">
                            <label for="contact-number" class="form-label">Contact number <span class="req">*</span></label>
                            <input name="contactNumber" type="text" class="form-control" id="contact-number"
                                   placeholder="09XX XXX XXXX" value="{{ old('contactNumber') }}" required>
                        </div>

                        <div class="form-section">Access</div>

                        <div class="mb-3">
                            <label for="roles" class="form-label">Role <span class="req">*</span></label>
                            <select class="form-select" name="role" id="roles" required>
                                <option value="" selected disabled>Select a role…</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}" {{ old('role') === $role->name ? 'selected' : '' }}>
                                        {{ $role->display_name }}
                                    </option>
                                @endforeach
                            </select>
                            <span class="form-hint">
                                The role decides what this admin can reach. Review or change what each role
                                can do on the <a href="{{ route('admin.list_roles') }}">Roles</a> page; the
                                role can be changed later from the Administrators list.
                            </span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="text" class="form-control" disabled value="Generated automatically">
                            <div class="note mt-2">
                                Email delivery is switched off, so nothing is sent to the new admin. The
                                temporary password is shown <strong>once</strong> after saving &mdash; copy it
                                then and hand it over. Ask them to change it from their profile after signing in.
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button class="btn btn-primary" type="submit">Create Admin</button>
                            <a href="{{ route('administrators') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Shown once, right after an admin account is created -->
    <div class="modal fade credentials-modal" data-bs-backdrop="static" tabindex="-1"
         aria-labelledby="credentialsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h6 class="modal-title" id="credentialsModalLabel">
                <i data-feather="check-circle" class="icon-sm text-success me-1"></i>Administrator created
            </h6>
          </div>
          <div class="modal-body">
            <p class="text-muted text-center mb-3" style="font-size: 12.5px;">
                Give these to <strong>{{ session('new_admin_name') }}</strong> now &mdash;
                they are shown only once and can't be retrieved later.
            </p>

            <div class="cred-box mb-3">
              <div class="cred-label">Temporary password</div>
              <input type="text" class="form-control cred-value mb-2" readonly
                     value="{{ session('new_admin_password') }}" id="passwordInput">
              <div class="text-center">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyToClipBoard('passwordInput')">
                    <i data-feather="copy" class="icon-sm me-1"></i>Copy password
                </button>
              </div>
            </div>

            <div class="cred-box">
              <div class="cred-label">Library code</div>
              <div class="qr-wrap text-center mb-2">
                {!! DNS2D::getBarcodeHTML(session('user_library_code') ? session('user_library_code') : "0000000", 'QRCODE') !!}
              </div>
              <input type="text" class="form-control cred-value mb-2" readonly
                     value="{{ session('user_library_code') ? session('user_library_code') : '0000000' }}" id="qrcodeInput">
              <div class="text-center">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="copyToClipBoard('qrcodeInput')">
                    <i data-feather="copy" class="icon-sm me-1"></i>Copy code
                </button>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <a href="{{ route('administrators') }}" class="btn btn-primary btn-sm">Done &mdash; go to Administrators</a>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Add another</button>
          </div>
        </div>
      </div>
    </div>

    <div class="alert alert-success" id="popup-message">Copied!</div>
</div>

@endsection

@section('js')
<script>
$(document).ready(function(){
    if (window.feather) feather.replace();

    @if(session('new_admin_password'))
      $('.credentials-modal').modal('show');
    @endif
});

function copyToClipBoard(inputId) {
  var copyText = document.getElementById(inputId);
  var text = copyText ? copyText.value : '';

  function showCopied() {
    var popup = $('#popup-message');
    popup.css('display', 'block');
    setTimeout(function () { popup.css('display', 'none'); }, 2000); // hide after 2s
  }

  // navigator.clipboard needs a secure context (https/localhost); this app runs over http,
  // so fall back to a temp textarea + execCommand.
  function fallbackCopy() {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.top = '-9999px';
    document.body.appendChild(ta);
    ta.focus();
    ta.select();
    try { document.execCommand('copy'); showCopied(); }
    catch (e) { window.prompt('Copy this:', text); }
    document.body.removeChild(ta);
  }

  if (navigator.clipboard && window.isSecureContext) {
    navigator.clipboard.writeText(text).then(showCopied, fallbackCopy);
  } else {
    fallbackCopy();
  }
}
</script>
@endsection
