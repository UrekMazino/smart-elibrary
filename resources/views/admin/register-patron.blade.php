@extends("admin.layouts.master")

@section('css')
    <style>
      .form-hint { color: var(--ui-muted-2, #a3adc2); font-size: 0.71875rem; margin-top: 4px; display: block; }
      .form-label { font-size: 0.78125rem; font-weight: 600; color: var(--ui-text, #33415c); margin-bottom: 4px; }
      .req { color: var(--ui-danger-ink, #c92a4b); }

      /* Note explaining what actually happens to the password on save. */
      .note {
        background: var(--ui-warning-soft, #fff8e8); border: 1px solid var(--ui-warning-line, #f6e3bc); border-radius: 6px;
        padding: 10px 12px; font-size: 0.75rem; color: var(--ui-warning-ink, #7a5b16); line-height: 1.5;
      }

      /* Success modal: library code + QR */
      .code-box {
        background: var(--ui-surface-2, #f7f9fc); border: 1px dashed var(--ui-border, #cfd8e8); border-radius: 8px;
        padding: 14px; text-align: center;
      }
      #qrcodeInput {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
        font-size: 1rem; font-weight: 700; letter-spacing: 1px;
        color: var(--ui-text, #22283b); background: var(--ui-surface, #fff); text-align: center;
      }
      .qr-wrap svg, .qr-wrap img { max-width: 170px; height: auto; }
      /* The QR comes as a fixed-size block (DNS2D): centred, on white so it still scans on a dark background. */
      .qr-wrap { padding: 8px 0; }
      .qr-wrap > div { margin: 0 auto; background: #fff; box-shadow: 0 0 0 8px #fff; }

      #popup-message {
        display: none; position: fixed; top: 50%; left: 50%;
        transform: translate(-50%, -50%); padding: 10px 20px;
        background-color: var(--ui-surface-2, #f1f1f1); border: 1px solid var(--ui-border, #ccc); border-radius: 4px;
        font-size: 1rem; z-index: 2000;
      }
    </style>
@endsection

@section('content')
<div class="page-content">
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('accounts') }}">Accounts</a></li>
            <li class="breadcrumb-item"><a href="{{ route('patrons') }}">Patrons</a></li>
            <li class="breadcrumb-item active" aria-current="page">Register a Patron</li>
        </ol>
    </nav>
    <div class="row">
        <div class="col-md-7 col-lg-6 offset-md-2 offset-lg-3 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="card-title mb-0">REGISTER A PATRON</h6>
                        <a href="{{ url('/admin/patron') }}" class="btn btn-outline-secondary btn-sm">
                            <i data-feather="arrow-left" class="icon-sm me-1"></i>Back to Patrons
                        </a>
                    </div>
                    <p class="text-muted mb-3" style="font-size: 0.78125rem;">
                        Creates a library account and issues a library code. The code is shown once the
                        account is saved &mdash; you can always re-issue it later from the Patrons list.
                    </p>

                    <x-auth-validation-errors class="mb-3 text-danger" :errors="$errors"/>
                    @if(session('error'))
                        <div class="alert alert-danger py-2" style="font-size: 0.8125rem;">{{ session('error') }}</div>
                    @endif

                    <form method="post" action="{{ url('/admin/register/store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="full-name" class="form-label">Full name <span class="req">*</span></label>
                            <input name="fullName" type="text" class="form-control" id="full-name"
                                   placeholder="Juan Dela Cruz" value="{{ old('fullName') }}" required autofocus>
                        </div>

                        <div class="mb-3">
                            <label for="email-address" class="form-label">Email address <span class="req">*</span></label>
                            <input name="email" type="email" class="form-control" id="email-address"
                                   placeholder="name@example.com" value="{{ old('email') }}" required>
                            <span class="form-hint">Used to sign in. Must not already be registered.</span>
                        </div>

                        <div class="mb-3">
                            <label for="contact-number" class="form-label">Contact number <span class="req">*</span></label>
                            <input name="contactNumber" type="text" class="form-control" id="contact-number"
                                   placeholder="09XX XXX XXXX" value="{{ old('contactNumber') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="text" class="form-control" id="password" disabled value="Auto-generated on save">
                            <div class="note mt-2">
                                <strong>Email delivery is currently switched off</strong>, so the generated
                                password is not sent to the patron. To give them a working password, open
                                <a href="{{ url('/admin/patron') }}">Patrons</a> &rarr; <strong>Action</strong>
                                &rarr; <strong>Reset Password</strong>, which shows a new random password once,
                                and ask them to change it after signing in.
                            </div>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button class="btn btn-primary" type="submit">Register Patron</button>
                            <a href="{{ url('/admin/patron') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Shown once, right after a successful registration -->
    <div class="modal fade qrcode-modal" tabindex="-1" aria-labelledby="qrcodeModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h6 class="modal-title" id="qrcodeModalLabel">
                <i data-feather="check-circle" class="icon-sm text-success me-1"></i>Patron registered
            </h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="btn-close"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted text-center mb-3" style="font-size: 0.78125rem;">
                Give this library code to the patron &mdash; it identifies them at the counter.
            </p>
            <div class="code-box">
              <div class="qr-wrap mb-3">
                {!! DNS2D::getBarcodeHTML(session('user_library_code') ? session('user_library_code') : "0000000", 'QRCODE') !!}
              </div>
              <input type="text" class="form-control mb-2" readonly
                     value="{{ session('user_library_code') ? session('user_library_code') : '0000000' }}" id="qrcodeInput">
              <button type="button" class="btn btn-outline-primary btn-sm" data-call="copyToClipBoard" id="copy-btn">
                  <i data-feather="copy" class="icon-sm me-1"></i>Copy code
              </button>
            </div>
          </div>
          <div class="modal-footer">
            <a href="{{ url('/admin/patron') }}" class="btn btn-primary btn-sm">Go to Patrons</a>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Register another</button>
          </div>
        </div>
      </div>
    </div>

    <div class="alert alert-success" id="popup-message">Code copied!</div>
</div>

@endsection

@section('js')
<script nonce="{{ cspNonce() }}">
// This page's buttons (data-call; onclick= is blocked by the Content Security Policy).
AdminActions.allow(['copyToClipBoard']);

$(document).ready(function(){
    if (window.feather) feather.replace();

    @if(session('user_library_code'))
      $('.qrcode-modal').modal('show');
    @endif
});

function copyToClipBoard() {
  var copyText = document.getElementById("qrcodeInput");
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
    catch (e) { window.prompt('Copy this code:', text); }
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
