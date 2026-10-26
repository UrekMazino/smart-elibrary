{{-- The chat button (lower right of every OPAC page): a General inquiry, not about one material.
     Signed-in patrons send a subject and message; the library replies in the app (their Inquiries page).
     Guests also give their full name, contact number and email, and are answered by email — or log in
     or register instead. Posts to /inquiry/general (InquiryController::general). Hidden for staff, who
     answer inquiries in the admin. The logo is public/opac/img/fnri-logo.png when that file exists. --}}
@php
    $lcUser    = Auth::user();
    $lcGuest   = ! $lcUser;
    $lcBlocked = $lcUser && $lcUser->is_blocked;
    $lcEmail   = 'info@fprdi.dost.gov.ph';
    $lcLogo    = is_file(public_path('opac/img/fnri-logo.png')) ? asset('opac/img/fnri-logo.png') : null;
    $lcIcon    = '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4 4h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H9l-5 4v-4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zm3 6.5a1.5 1.5 0 1 0 0 .01zm5 0a1.5 1.5 0 1 0 0 .01zm5 0a1.5 1.5 0 1 0 0 .01z"/></svg>';
@endphp
@if(! ($lcUser && $lcUser->isStaff()))
<style>
    .lc-chat { --lc-teal: #00857a; --lc-teal-dark: #006f65; --lc-ink: #1d2433; --lc-muted: #5d6973; --lc-line: #dfe5e8; --lc-soft: #e8f4f2; --lc-rose: #b8345f;
        position: fixed; right: 24px; bottom: 24px; z-index: 1040; font-family: inherit; }
    .lc-chat [hidden] { display: none !important; }
    .lc-fab { position: relative; width: 60px; height: 60px; border-radius: 50%; border: 3px solid var(--lc-teal); background: #fff; padding: 0; cursor: pointer;
        display: grid; place-items: center; box-shadow: 0 6px 18px rgba(0,0,0,.22); transition: transform .15s ease, box-shadow .15s ease; }
    .lc-fab:hover { transform: translateY(-2px); box-shadow: 0 10px 24px rgba(0,0,0,.26); }
    .lc-fab:focus-visible { outline: 3px solid #fff; box-shadow: 0 0 0 6px var(--lc-teal); }
    .lc-fab img { width: 40px; height: 40px; object-fit: contain; border-radius: 50%; }
    .lc-fab > svg { width: 28px; height: 28px; color: var(--lc-teal); }
    /* the small chat badge says "this opens a conversation" next to a logo */
    .lc-fab .lc-badge { position: absolute; right: -4px; bottom: -4px; width: 24px; height: 24px; border-radius: 50%; background: var(--lc-teal); color: #fff; display: grid; place-items: center; box-shadow: 0 0 0 2px #fff; }
    .lc-fab .lc-badge svg { width: 13px; height: 13px; }
    .lc-tip { position: absolute; right: 72px; top: 50%; transform: translateY(-50%); white-space: nowrap; background: var(--lc-ink); color: #fff; font-size: 13px; font-weight: 600;
        padding: 6px 12px; border-radius: 999px; box-shadow: 0 4px 12px rgba(0,0,0,.2); opacity: 0; pointer-events: none; transition: opacity .15s ease; }
    .lc-fab:hover + .lc-tip, .lc-fab:focus-visible + .lc-tip { opacity: 1; }
    .lc-chat.is-open .lc-tip { display: none; }

    .lc-panel { position: absolute; right: 0; bottom: 76px; width: 370px; max-height: min(720px, calc(100vh - 110px)); display: flex; flex-direction: column;
        background: #fff; color: var(--lc-ink); border-radius: 16px; box-shadow: 0 18px 48px rgba(0,0,0,.28); overflow: hidden; text-align: left; }
    .lc-head { display: flex; align-items: center; gap: 10px; padding: 14px 14px 14px 16px; background: var(--lc-teal); color: #fff; }
    .lc-head-logo { width: 38px; height: 38px; border-radius: 50%; background: #fff; display: grid; place-items: center; flex: none; overflow: hidden; }
    .lc-head-logo img { width: 30px; height: 30px; object-fit: contain; }
    .lc-head-logo svg { width: 22px; height: 22px; color: var(--lc-teal); }
    .lc-head h2 { margin: 0; font-size: 15.5px; font-weight: 700; color: #fff; line-height: 1.25; }
    .lc-head p { margin: 1px 0 0; font-size: 12px; color: rgba(255,255,255,.9); line-height: 1.35; }
    .lc-close { margin-left: auto; flex: none; width: 34px; height: 34px; border: 0; border-radius: 50%; background: rgba(255,255,255,.16); color: #fff; display: grid; place-items: center; cursor: pointer; }
    .lc-close:hover { background: rgba(255,255,255,.28); }
    .lc-close:focus-visible { outline: 2px solid #fff; outline-offset: 2px; }
    .lc-body { padding: 14px 16px 16px; overflow-y: auto; }
    .lc-bubble { background: var(--lc-soft); border-radius: 4px 14px 14px 14px; padding: 10px 12px; font-size: 13.5px; line-height: 1.5; margin-bottom: 10px; }
    .lc-note { font-size: 12.5px; color: var(--lc-muted); line-height: 1.5; margin: 0 0 12px; }
    .lc-note a, .lc-done a:not(.lc-btn) { color: var(--lc-teal); font-weight: 600; }
    .lc-field { margin-bottom: 10px; }
    .lc-field label { display: block; font-size: 12.5px; font-weight: 600; margin-bottom: 4px; color: var(--lc-ink); }
    .lc-field label .lc-opt { font-weight: 400; color: var(--lc-muted); }
    .lc-field input, .lc-field textarea { display: block; width: 100%; border: 1px solid var(--lc-line); border-radius: 8px; padding: 8px 10px; font-size: 14px; color: var(--lc-ink); background: #fff; }
    .lc-field textarea { resize: vertical; min-height: 96px; }
    .lc-field input:focus, .lc-field textarea:focus { outline: none; border-color: var(--lc-teal); box-shadow: 0 0 0 3px rgba(0,133,122,.18); }
    .lc-field [aria-invalid="true"] { border-color: var(--lc-rose); }
    .lc-err { display: block; color: var(--lc-rose); font-size: 12px; margin-top: 3px; }
    .lc-count { float: right; font-weight: 400; color: var(--lc-muted); font-variant-numeric: tabular-nums; }
    .lc-hp { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }
    .lc-alert { background: #fbe9ef; color: #8a1f43; border-radius: 8px; padding: 8px 10px; font-size: 13px; margin-bottom: 10px; }
    .lc-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 0; border-radius: 8px; padding: 10px 16px; font-size: 14px; font-weight: 600;
        background: var(--lc-teal); color: #fff; cursor: pointer; text-decoration: none; }
    .lc-btn:hover { background: var(--lc-teal-dark); color: #fff; }
    .lc-btn:disabled { opacity: .65; cursor: default; }
    .lc-btn.is-ghost { background: #fff; color: var(--lc-teal); border: 1px solid var(--lc-teal); }
    .lc-btn.is-ghost:hover { background: var(--lc-soft); color: var(--lc-teal); }
    /* Send stays at the bottom of the panel while the form scrolls (the guest form is long). */
    .lc-send-bar { position: sticky; bottom: -16px; margin: 0 -16px -16px; padding: 10px 16px 16px; background: #fff; box-shadow: 0 -6px 12px -10px rgba(0,0,0,.35); }
    .lc-send { width: 100%; }
    .lc-done { text-align: center; padding: 8px 4px 4px; }
    .lc-done-icon { width: 54px; height: 54px; margin: 4px auto 10px; border-radius: 50%; background: var(--lc-soft); color: var(--lc-teal); display: grid; place-items: center; }
    .lc-done-icon svg { width: 28px; height: 28px; }
    .lc-done h3 { font-size: 16px; font-weight: 700; margin: 0 0 6px; }
    .lc-done p { font-size: 13.5px; color: var(--lc-muted); line-height: 1.5; margin: 0 0 14px; }
    .lc-done .lc-actions { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; }

    @media (max-width: 575.98px) {
        .lc-chat { right: 16px; bottom: 16px; }
        .lc-fab { width: 54px; height: 54px; }
        .lc-fab img { width: 36px; height: 36px; }
        .lc-tip { display: none; }
        /* phones: the panel is a sheet across the bottom of the screen */
        .lc-panel { position: fixed; left: 0; right: 0; bottom: 0; width: auto; max-height: 88vh; border-radius: 16px 16px 0 0; }
        .lc-chat.is-open .lc-fab { visibility: hidden; }
    }
    @media (prefers-reduced-motion: reduce) { .lc-fab, .lc-tip { transition: none; } }
</style>

<div class="lc-chat" id="lc-chat">
    <button type="button" class="lc-fab" id="lc-fab" aria-expanded="false" aria-controls="lc-panel" aria-label="Ask the library (chat)">
        @if($lcLogo)<img src="{{ $lcLogo }}" alt="">@else{!! $lcIcon !!}@endif
        @if($lcLogo)<span class="lc-badge">{!! $lcIcon !!}</span>@endif
    </button>
    <span class="lc-tip" aria-hidden="true">Ask the library</span>

    <section class="lc-panel" id="lc-panel" role="dialog" aria-labelledby="lc-title" hidden>
        <header class="lc-head">
            <span class="lc-head-logo">@if($lcLogo)<img src="{{ $lcLogo }}" alt="">@else{!! $lcIcon !!}@endif</span>
            <div>
                <h2 id="lc-title">Ask the library</h2>
                <p>General inquiry · Staff reply Monday to Friday, 8:00 AM to 5:00 PM</p>
            </div>
            <button type="button" class="lc-close" id="lc-close" aria-label="Close the chat">
                <svg width="14" height="14" viewBox="0 0 16 16" aria-hidden="true"><path stroke="currentColor" stroke-width="1.8" stroke-linecap="round" d="M3 3l10 10M13 3L3 13"/></svg>
            </button>
        </header>

        <div class="lc-body">
            <div class="lc-bubble">Hi{{ $lcUser ? ', ' . \Illuminate\Support\Str::before($lcUser->name, ' ') : '' }}! Ask us anything about the library, its collection or its services.</div>

            @if($lcBlocked)
                <p class="lc-alert" role="alert">Your account is blocked from sending inquiries. Please email the library at <a href="mailto:{{ $lcEmail }}">{{ $lcEmail }}</a>.</p>
            @else
                @if($lcGuest)
                    <p class="lc-note">Have an account? <a href="{{ route('login') }}">Log in</a> to read our reply here, or <a href="{{ route('register') }}">register</a>. You can also leave your details below and we'll reply by email.</p>
                @else
                    <p class="lc-note">Our reply will appear in your <a href="{{ url('/patron/inquiries') }}">Inquiries</a> page.</p>
                @endif

                <form id="lc-form" novalidate>
                    <div class="lc-hp" aria-hidden="true"><label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                    <div class="lc-alert" id="lc-alert" role="alert" hidden></div>

                    <div class="lc-field">
                        <label for="lc-subject">Subject</label>
                        <input type="text" id="lc-subject" name="subject" maxlength="125" required>
                    </div>
                    @if($lcGuest)
                        <div class="lc-field">
                            <label for="lc-fullname">Full name</label>
                            <input type="text" id="lc-fullname" name="fullname" maxlength="150" autocomplete="name" required>
                        </div>
                        <div class="lc-field">
                            <label for="lc-contactnum">Contact no.</label>
                            <input type="tel" id="lc-contactnum" name="contactnum" maxlength="30" autocomplete="tel" inputmode="tel" required>
                        </div>
                        <div class="lc-field">
                            <label for="lc-email">Email address</label>
                            <input type="email" id="lc-email" name="email" maxlength="190" autocomplete="email" required>
                        </div>
                    @endif
                    <div class="lc-field">
                        <label for="lc-message">Message <span class="lc-count" id="lc-count" aria-hidden="true">0 / 5000</span></label>
                        <textarea id="lc-message" name="message" maxlength="5000" rows="4" required></textarea>
                    </div>
                    @if($lcGuest)
                        <p class="lc-note">We use your details only to reply to this inquiry.</p>
                    @endif
                    <div class="lc-send-bar"><button type="submit" class="lc-btn lc-send" id="lc-send">Send</button></div>
                </form>

                <div class="lc-done" id="lc-done" role="status" hidden>
                    <div class="lc-done-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" d="M5 12.5l4.5 4.5L19 7.5"/></svg></div>
                    <h3>Message sent</h3>
                    @if($lcGuest)
                        <p>Thank you<span id="lc-done-name"></span>. The library will reply to <strong id="lc-done-email"></strong> by email.</p>
                        <div class="lc-actions">
                            <button type="button" class="lc-btn is-ghost" data-lc-again>Send another</button>
                            <a class="lc-btn" href="{{ route('register') }}">Register</a>
                        </div>
                        <p style="margin:12px 0 0;font-size:12.5px;">With an account, our replies appear here in the app.</p>
                    @else
                        <p>The library's reply will appear in your Inquiries page, and you'll see it in your notifications.</p>
                        <div class="lc-actions">
                            <button type="button" class="lc-btn is-ghost" data-lc-again>Send another</button>
                            <a class="lc-btn" href="{{ url('/patron/inquiries') }}">Open my Inquiries</a>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </section>
</div>

<script nonce="{{ cspNonce() }}">
(function () {
    var root = document.getElementById('lc-chat');
    if (!root) return;
    var fab = document.getElementById('lc-fab'), panel = document.getElementById('lc-panel');
    var form = document.getElementById('lc-form'), done = document.getElementById('lc-done');
    var alertBox = document.getElementById('lc-alert'), send = document.getElementById('lc-send');
    var URL_SEND = @json(url('/inquiry/general'));

    // A draft (subject and message only, never a guest's name, phone or email) is kept in this tab while
    // the visitor moves between pages. It belongs to whoever is signed in: a draft of anyone else is
    // dropped on load, and all of them at sign-out (OWASP ASVS 14.3.1, 14.3.3).
    var DRAFT = 'lc-chat-draft:' + @json($lcUser ? 'u' . $lcUser->id : 'guest');
    function dropDrafts(keep) {
        try {
            Object.keys(sessionStorage).forEach(function (k) {
                if (k.indexOf('lc-chat-') === 0 && k !== keep) sessionStorage.removeItem(k);
            });
        } catch (e) {}
    }
    dropDrafts(DRAFT);
    document.addEventListener('click', function (e) { if (e.target.closest('[data-logout]')) dropDrafts(null); }, true);

    function setOpen(open) {
        panel.hidden = !open;
        root.classList.toggle('is-open', open);
        fab.setAttribute('aria-expanded', open ? 'true' : 'false');
        if (open) {
            var first = form ? [].slice.call(form.querySelectorAll('input:not([name=website]), textarea')).filter(function (el) { return !el.value; })[0] : null;
            (first || document.getElementById('lc-close')).focus();
        } else {
            fab.focus();
        }
    }
    fab.addEventListener('click', function () { setOpen(panel.hidden); });
    document.getElementById('lc-close').addEventListener('click', function () { setOpen(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !panel.hidden) setOpen(false); });

    if (!form) return;   // blocked account: the panel only explains

    var fields = [].slice.call(form.querySelectorAll('input:not([name=website]), textarea'));
    var drafted = fields.filter(function (el) { return el.name === 'subject' || el.name === 'message'; });
    try {
        var saved = JSON.parse(sessionStorage.getItem(DRAFT) || '{}');
        drafted.forEach(function (el) { if (saved[el.name]) el.value = saved[el.name]; });
    } catch (e) {}
    function saveDraft() {
        try { var d = {}; drafted.forEach(function (el) { d[el.name] = el.value; }); sessionStorage.setItem(DRAFT, JSON.stringify(d)); } catch (e) {}
    }
    // A guest's name, phone and email, kept in this page only (not in the browser's storage) for "Send another".
    var guestDetails = {};
    var msg = document.getElementById('lc-message'), count = document.getElementById('lc-count');
    function updCount() { count.textContent = msg.value.length + ' / 5000'; }
    updCount();
    form.addEventListener('input', function (e) {
        saveDraft();
        if (e.target === msg) updCount();
        if (e.target.getAttribute('aria-invalid') === 'true') clearError(e.target);
    });

    function clearError(el) {
        el.removeAttribute('aria-invalid');
        el.removeAttribute('aria-describedby');
        var err = el.parentNode.querySelector('.lc-err'); if (err) err.remove();
    }
    function showError(el, text) {
        clearError(el);
        var err = document.createElement('span');
        err.className = 'lc-err'; err.id = el.id + '-err'; err.textContent = text;
        el.parentNode.appendChild(err);
        el.setAttribute('aria-invalid', 'true');
        el.setAttribute('aria-describedby', err.id);
    }
    function showAlert(text) { alertBox.textContent = text; alertBox.hidden = !text; }

    // The same checks the server makes, so most mistakes show before sending.
    function check() {
        var bad = [];
        fields.forEach(function (el) {
            clearError(el);
            var v = el.value.trim();
            if (!v) bad.push([el, 'Please fill this in.']);
            else if (el.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) bad.push([el, 'Enter an email address like name@example.com.']);
            else if (el.name === 'contactnum' && (!/^\+?[\d\s\-()]+$/.test(v) || (v.match(/\d/g) || []).length < 7)) bad.push([el, 'Enter a contact number of at least 7 digits.']);
        });
        bad.forEach(function (b) { showError(b[0], b[1]); });
        if (bad.length) bad[0][0].focus();
        return !bad.length;
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        showAlert('');
        if (!check()) return;
        send.disabled = true; send.textContent = 'Sending…';
        var token = document.querySelector('meta[name="csrf_token"]');
        fetch(URL_SEND, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': token ? token.content : '', 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: new FormData(form)
        }).then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (data) { return { status: r.status, data: data }; });
        }).then(function (res) {
            if (res.status === 200 && res.data.ok) {
                var name = form.querySelector('[name=fullname]'), email = form.querySelector('[name=email]');
                ['fullname', 'contactnum', 'email'].forEach(function (n) { var el = form.querySelector('[name=' + n + ']'); if (el) guestDetails[n] = el.value; });
                if (name && document.getElementById('lc-done-name')) document.getElementById('lc-done-name').textContent = ', ' + name.value.trim().split(/\s+/)[0];
                if (email && document.getElementById('lc-done-email')) document.getElementById('lc-done-email').textContent = email.value.trim();
                form.reset(); updCount();
                try { sessionStorage.removeItem(DRAFT); } catch (e) {}
                form.hidden = true; done.hidden = false;
                done.querySelector('h3').setAttribute('tabindex', '-1'); done.querySelector('h3').focus();
            } else if (res.status === 422 && res.data.errors) {
                var first = null;
                Object.keys(res.data.errors).forEach(function (k) {
                    var el = form.querySelector('[name="' + k + '"]');
                    if (el) { showError(el, res.data.errors[k][0]); first = first || el; }
                });
                if (first) first.focus(); else showAlert(res.data.message || 'Please check the form.');
            } else if (res.status === 429) {
                showAlert('You have sent several messages in a short time. Please wait a minute and try again.');
            } else if (res.status === 419) {
                showAlert('This page has been open for a long time. Reload it and send again; your subject and message are kept.');
            } else {
                showAlert(res.data.message || 'Your message could not be sent. Please try again.');
            }
        }).catch(function () {
            showAlert('Your message could not be sent. Check your connection and try again.');
        }).finally(function () {
            send.disabled = false; send.textContent = 'Send';
        });
    });

    done.addEventListener('click', function (e) {
        if (!e.target.closest('[data-lc-again]')) return;
        done.hidden = true; form.hidden = false;
        fields.forEach(function (el) { if (guestDetails[el.name]) el.value = guestDetails[el.name]; });   // the same guest again
        document.getElementById('lc-subject').focus();
    });
})();
</script>
@endif
