{{-- A short "saved" notice after a redirect that set session('success'), e.g. "Record saved.", with an
     optional link (session('success_link') = ['url' => …, 'label' => …], e.g. "View record" after Create
     Record). SweetAlert loads at the end of the admin layout, after the page's own scripts, so this waits
     for the page; the timer pauses while the pointer is on the notice, so the link can be clicked. --}}
@if(session('success'))
<script nonce="{{ cspNonce() }}">
    document.addEventListener('DOMContentLoaded', function () {
        if (!window.Swal) return;
        var link = @json(session('success_link'));
        var html = link ? '<a href="' + encodeURI(link.url) + '">' + $('<div>').text(link.label).html() + '</a>' : undefined;
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: @json(session('success')), html: html,
                    showConfirmButton: false, showCloseButton: true, timer: link ? 8000 : 4000, timerProgressBar: true,
                    didOpen: function (t) { t.addEventListener('mouseenter', Swal.stopTimer); t.addEventListener('mouseleave', Swal.resumeTimer); } });
    });
</script>
@endif
