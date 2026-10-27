{{--
  Staff: a warning 2 minutes before the session limits sign them out (App\Services\SessionLimits). Before the idle
  limit it offers "Stay signed in"; before the 8-hour limit it can only say so. The page asks the server before
  warning (another tab may have kept the session going) and when the time is up: the server then signs out, and
  the sign-in page says why. Included by the admin and the OPAC layouts.
--}}
@php($timeoutUser = auth()->user())
@if ($timeoutUser && $timeoutUser->isStaff() && \App\Services\SessionLimits::started(request()->session()))
@php($timeoutLeft = \App\Services\SessionLimits::secondsLeft($timeoutUser, request()->session()))
<dialog id="session-timeout" class="session-timeout" aria-labelledby="session-timeout-title" aria-describedby="session-timeout-text"
        data-idle="{{ $timeoutLeft['idle'] }}" data-absolute="{{ $timeoutLeft['absolute'] }}" data-warn="{{ config('session.limits.warning') * 60 }}"
        data-status-url="{{ route('session.status') }}" data-stay-url="{{ route('session.stay') }}" data-login-url="{{ route('login') }}">
  <div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title" id="session-timeout-title">
        <span data-when="idle">Are you still there?</span>
        <span data-when="absolute" hidden>Your session is ending</span>
      </h5>
    </div>
    <div class="modal-body" id="session-timeout-text">
      <p class="mb-0" data-when="idle">Staff are signed out after {{ \App\Services\SessionLimits::duration(config('session.limits.staff.idle')) }} without activity. You’ll be signed out in <strong data-session-clock>2:00</strong> unless you stay signed in.</p>
      <p class="mb-0" data-when="absolute" hidden>A staff session lasts at most {{ \App\Services\SessionLimits::duration(config('session.limits.staff.absolute')) }}. You’ll be signed out in <strong data-session-clock>2:00</strong>, so save your work now.</p>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" data-logout>Sign out</button>
      <button type="button" class="btn btn-primary" data-session-continue autofocus>
        <span data-when="idle">Stay signed in</span><span data-when="absolute" hidden>OK</span>
      </button>
    </div>
  </div>
</dialog>
<style>
  .session-timeout { padding: 0; border: 0; background: transparent; color: inherit; width: min(30rem, calc(100vw - 2rem)); max-width: none; }
  .session-timeout::backdrop { background: rgba(0, 0, 0, .5); }
  .session-timeout .modal-content { box-shadow: 0 .5rem 1.5rem rgba(0, 0, 0, .3); }
</style>
<script nonce="{{ cspNonce() }}">
(function () {
  var box = document.getElementById('session-timeout');
  if (!box || typeof box.showModal !== 'function') return;
  var warnMs = box.dataset.warn * 1000;
  var csrf = document.querySelector('meta[name="csrf-token"], meta[name="csrf_token"]');
  var endsAt, kind, timer, ticker, closedByPage = false;

  // The server's seconds left -> which limit comes first. Before its last 2 minutes: ask the server again when
  // they start. In them: the warning, whose countdown asks the server at the end.
  function plan(left) {
    var idle = left.idle === null ? Infinity : left.idle;
    kind = idle <= left.absolute ? 'idle' : 'absolute';
    endsAt = Date.now() + Math.min(idle, left.absolute) * 1000;
    clearTimeout(timer);
    var wait = endsAt - warnMs - Date.now();
    if (wait > 0) { hide(); timer = setTimeout(check, wait); }
    else show();
  }

  function ask(url, options) {
    return fetch(url, Object.assign({
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf ? csrf.content : '' }
    }, options)).then(function (r) {
      if (r.status === 401) { location.assign(box.dataset.loginUrl); throw new Error('signed out'); }   // the sign-in page says why
      if (!r.ok) throw new Error(r.status);
      return r.json();
    });
  }

  function check() {
    ask(box.dataset.statusUrl).then(plan).catch(function () { clearTimeout(timer); timer = setTimeout(check, 30000); });
  }

  function show() {
    box.querySelectorAll('[data-when]').forEach(function (el) { el.hidden = el.dataset.when !== kind; });
    if (!box.open) box.showModal();
    clearInterval(ticker);
    tick();
    ticker = setInterval(tick, 1000);
  }

  function tick() {
    var s = Math.max(0, Math.ceil((endsAt - Date.now()) / 1000));
    box.querySelectorAll('[data-session-clock]').forEach(function (el) {
      el.textContent = Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
    });
    if (s === 0) { clearInterval(ticker); check(); }   // time's up: the server signs out
  }

  function hide() {
    clearInterval(ticker);
    if (box.open) { closedByPage = true; box.close(); }
  }

  // "Stay signed in" before the idle limit. Before the 8-hour limit "OK" only closes the warning.
  function carryOn() {
    hide();
    clearTimeout(timer);
    var atTheEnd = function () { timer = setTimeout(check, Math.max(0, endsAt - Date.now()) + 1000); };
    if (kind === 'absolute') { atTheEnd(); return; }
    ask(box.dataset.stayUrl, { method: 'POST' }).then(plan).catch(atTheEnd);
  }

  box.querySelector('[data-session-continue]').addEventListener('click', carryOn);
  // Esc closes the warning: the same as its main button. ("Sign out" is data-logout: the layout's sign-out form.)
  box.addEventListener('close', function () { if (closedByPage) closedByPage = false; else carryOn(); });
  // Background tabs run timers late: catch up when the tab is shown again.
  document.addEventListener('visibilitychange', function () {
    if (!document.hidden && endsAt - Date.now() <= warnMs) check();
  });

  plan({ idle: box.dataset.idle === '' ? null : +box.dataset.idle, absolute: +box.dataset.absolute });
})();
</script>
@endif
