{{--
  The password dialog of "Where you're signed in" (partials.browser-sessions): signing out one session or all the
  others needs the current password (OWASP ASVS 7.5.2). A wrong one comes back in the "endSessions" bag, and the
  dialog opens again for the same session. $sessions: BrowserSessions::of(); $modalClass: the page's modal style.
  An account without a password (made with Google) confirms with Google instead (App\Services\GoogleSignIn): within
  minutes of a Google sign-in it just confirms; otherwise the dialog offers "Confirm with Google", then Sign out again.
--}}
@php
  $endTarget = old('end');
  $endByPassword = auth()->user()->hasPassword();
  $endConfirmed = $endByPassword || \App\Services\GoogleSignIn::recentlyConfirmed(request()->session());
@endphp
<div class="modal fade" id="endSessionModal" tabindex="-1" aria-labelledby="endSessionTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content {{ $modalClass ?? '' }}">
      <form method="POST" action="{{ route('sessions.end') }}">
        @csrf
        @method('DELETE')
        <input type="hidden" name="end" value="{{ $endTarget }}">
        <div class="modal-header">
          <h5 class="modal-title" id="endSessionTitle">Sign out</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          @if ($endByPassword)
            <p class="mb-3">Sign out <strong data-end-what></strong>? Enter your password to confirm.</p>
            <label for="end-session-password" class="form-label">Current password</label>
            <input id="end-session-password" name="password" type="password" required autocomplete="current-password"
                   class="form-control @error('password', 'endSessions') is-invalid @enderror">
          @elseif ($endConfirmed)
            <p class="mb-0">Sign out <strong data-end-what></strong>?</p>
          @else
            <p class="mb-3">Sign out <strong data-end-what></strong>? You sign in with Google: first confirm it’s you with Google,
              then choose Sign out again.</p>
          @endif
          @error('password', 'endSessions')
            <span class="invalid-feedback d-block" role="alert">{{ $message }}</span>
          @enderror
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          @if ($endConfirmed)
            <button type="submit" class="btn btn-danger btn-sm">Sign out</button>
          @else
            <a class="btn btn-primary btn-sm" href="{{ route('google.confirm') }}">Confirm with Google</a>
          @endif
        </div>
      </form>
    </div>
  </div>
</div>
<script nonce="{{ cspNonce() }}">
(function () {
  var modal = document.getElementById('endSessionModal');
  var form = modal.querySelector('form');

  // What the dialog signs out: a session's ref, or "others".
  function aim(end, name) {
    form.elements.end.value = end;
    modal.querySelector('[data-end-what]').textContent = end === 'others' ? 'all your other sessions' : name;
  }
  // Bootstrap loads after this script (the layouts' scripts come after the content): only used once it has.
  function open() {
    if (window.bootstrap) bootstrap.Modal.getOrCreateInstance(modal).show();
    else $(modal).modal('show');
  }

  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-end-session]');
    if (!b) return;
    aim(b.dataset.endSession, b.dataset.sessionName);
    open();
  });
  // No password field for an account made with Google (it confirms with Google).
  modal.addEventListener('shown.bs.modal', function () { if (form.elements.password) form.elements.password.focus(); });
  modal.addEventListener('hidden.bs.modal', function () { if (form.elements.password) form.elements.password.value = ''; });

  @if ($errors->endSessions->any())
    // A wrong password: the dialog again, for the same session (after Bootstrap has set itself up).
    document.addEventListener('DOMContentLoaded', function () {
      aim(@json($endTarget), @json(optional($sessions->firstWhere('ref', $endTarget))->browser ?? 'that session'));
      setTimeout(open, 0);
    });
  @endif
})();
</script>
