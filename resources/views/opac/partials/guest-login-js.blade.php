{{-- Sign-in popup for guests who press Read, Download or Save (the /read, /download and
     /search/saved routes enforce sign-in too). Shared by the record page and the search results,
     so every button says the same thing. Needs SweetAlert, which the OPAC layout loads. --}}
<script>
window.promptGuestLogin = function (action) {
    var login = @json(route('login')), register = @json(route('register'));
    var prompts = {
        read:     ['Sign in to read',     'Reading materials online is available to registered members. Log in or create a free account to continue.'],
        download: ['Sign in to download', 'Downloading materials is available to registered members. Log in or create a free account to continue.'],
        save:     ['Sign in to save',     'Saving materials to your list is available to registered members. Log in or create a free account to continue.']
    };
    var p = prompts[action] || prompts.read;
    if (!window.Swal) { window.location = login; return; }
    Swal.fire({
        icon: 'info',
        title: p[0],
        text: p[1],
        showDenyButton: true,
        showCancelButton: true,
        confirmButtonText: 'Log in',
        denyButtonText: 'Register',
        cancelButtonText: 'Not now',
        reverseButtons: true
    }).then(function (r) {
        if (r.isConfirmed) window.location = login;
        else if (r.isDenied) window.location = register;
    });
};
</script>
