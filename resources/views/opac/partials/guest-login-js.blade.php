{{-- Sign-in popup for guests who press Read, Download, Save or AI mode (the routes enforce sign-in
     too). Shared by the homepage, the record page and the search results, so every prompt looks
     and reads the same. A quiet card in the OPAC's own colours (teal, Poppins, 7px buttons) rather
     than SweetAlert's defaults. Needs SweetAlert, which the OPAC layout loads. --}}
<style>
    .gl-popup.swal2-popup { width: min(380px, calc(100vw - 32px)); padding: 28px 26px 22px; border-radius: 14px;
        box-shadow: 0 20px 50px rgba(17, 24, 39, .22); font-family: "Poppins", Helvetica, sans-serif; }
    .gl-popup .swal2-icon.gl-badge { width: 46px; height: 46px; margin: 0 auto 14px; border: 0; border-radius: 50%;
        background: #e3f5f2; color: #00756b; animation: none; }
    .gl-popup .gl-badge .swal2-icon-content { font-size: 0; }
    .gl-popup .gl-badge svg { width: 22px; height: 22px; }
    .gl-popup .gl-title { padding: 0; margin: 0 0 6px; font-size: 18px; font-weight: 600; line-height: 1.35; color: #1d2433; }
    .gl-popup .gl-text { margin: 0; padding: 0; font-size: 14px; line-height: 1.6; color: #5d6973; }
    .gl-popup .gl-actions { width: 100%; margin: 20px 0 0; padding: 0; gap: 10px; flex-wrap: nowrap; }
    .gl-popup .gl-btn { flex: 1 1 0; margin: 0; padding: 9px 14px; border-radius: 7px; font: 500 14px/1.2 "Poppins", Helvetica, sans-serif;
        cursor: pointer; transition: background-color .15s ease, border-color .15s ease, color .15s ease; }
    .gl-popup .gl-btn:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(0, 117, 107, .25); }
    .gl-popup .gl-btn-primary { background: #00756b; border: 1px solid #00756b; color: #fff; }
    .gl-popup .gl-btn-primary:hover { background: #005f57; border-color: #005f57; }
    .gl-popup .gl-btn-ghost { background: #fff; border: 1px solid #d5dee2; color: #1d2433; }
    .gl-popup .gl-btn-ghost:hover { border-color: #00756b; color: #00756b; }
    .gl-popup .gl-close { width: 32px; height: 32px; top: 10px; right: 10px; border-radius: 8px; font-size: 22px; color: #8a96a0; }
    .gl-popup .gl-close:hover { background: #f3f6f7; color: #1d2433; }
    .gl-popup .gl-close:focus-visible { box-shadow: 0 0 0 3px rgba(0, 117, 107, .25); }
    @keyframes gl-in  { from { opacity: 0; transform: translateY(8px) scale(.98); } to { opacity: 1; transform: none; } }
    @keyframes gl-out { from { opacity: 1; } to { opacity: 0; transform: translateY(4px); } }
    .gl-in  { animation: gl-in .18s ease-out; }
    .gl-out { animation: gl-out .12s ease-in forwards; }
    @media (prefers-reduced-motion: reduce) { .gl-in, .gl-out { animation: none; } }
</style>
<script nonce="{{ cspNonce() }}">
window.promptGuestLogin = function (action) {
    var login = @json(route('login')), register = @json(route('register'));
    var svg = function (paths) {
        return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + paths + '</svg>';
    };
    var prompts = {
        read:     ['Sign in to read', 'Reading materials online is available to registered members. Log in or create a free account to continue.',
                   svg('<path d="M2 4.5h6a4 4 0 0 1 4 4V20a3 3 0 0 0-3-3H2z"/><path d="M22 4.5h-6a4 4 0 0 0-4 4V20a3 3 0 0 1 3-3h7z"/>')],
        download: ['Sign in to download', 'Downloading materials is available to registered members. Log in or create a free account to continue.',
                   svg('<path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 20h14"/>')],
        save:     ['Sign in to save', 'Saving materials to your list is available to registered members. Log in or create a free account to continue.',
                   svg('<path d="m12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.9 1-6.1-4.4-4.3 6.1-.9z"/>')],
        ai:       ['Sign in to use AI mode', 'Asking the library’s AI is available to registered members. Log in or create a free account to continue.',
                   svg('<path d="M12 3l1.8 4.7L18.5 9.5l-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8z"/>')]
    };
    var p = prompts[action] || prompts.read;
    if (!window.Swal) { window.location = login; return; }
    Swal.fire({
        icon: 'info',
        iconHtml: p[2],
        title: p[0],
        text: p[1],
        showCloseButton: true,
        closeButtonAriaLabel: 'Not now',
        showConfirmButton: true,
        showDenyButton: true,
        confirmButtonText: 'Log in',
        denyButtonText: 'Register',
        reverseButtons: true,          // Register, then Log in on the right
        buttonsStyling: false,
        backdrop: 'rgba(17, 24, 39, .42)',
        customClass: {
            popup: 'gl-popup', icon: 'gl-badge', title: 'gl-title', htmlContainer: 'gl-text',
            actions: 'gl-actions', confirmButton: 'gl-btn gl-btn-primary', denyButton: 'gl-btn gl-btn-ghost', closeButton: 'gl-close'
        },
        showClass: { popup: 'gl-in' },
        hideClass: { popup: 'gl-out' }
    }).then(function (r) {
        if (r.isConfirmed) window.location = login;
        else if (r.isDenied) window.location = register;
    });
};
</script>
