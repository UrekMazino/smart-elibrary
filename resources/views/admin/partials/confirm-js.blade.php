<script>
/*
 * Ask before a delete or remove. Mark a <form> (asked when it is submitted) or a link / button
 * (asked when it is clicked):
 *
 *   data-confirm="What will happen."            the message (required: it turns the check on)
 *   data-confirm-title="Remove the full text?"  optional; default "Are you sure?"
 *   data-confirm-button="Remove"                optional; default "Delete"
 *
 * Destructive by default: red confirm button, Cancel focused, so Enter does not delete.
 */
(function () {
    function ask(el) {
        return Swal.fire({
            title: el.dataset.confirmTitle || 'Are you sure?',
            text: el.dataset.confirm,
            icon: 'warning',
            showCancelButton: true,
            reverseButtons: true,
            focusCancel: true,
            confirmButtonText: el.dataset.confirmButton || 'Delete',
            confirmButtonColor: '#dc3545',
            cancelButtonText: 'Cancel'
        }).then(function (result) { return result.isConfirmed; });
    }

    // Forms: hold the submit, ask, then submit for real (form.submit() fires no submit event).
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;
        e.preventDefault();
        ask(form).then(function (ok) {
            if (!ok) return;
            if (window.appBusy) appBusy.show();
            form.submit();
        });
    }, true);

    // Links and buttons outside such a form: stop the click before its own handler runs, ask,
    // then click again with the answer recorded so it goes through.
    document.addEventListener('click', function (e) {
        var el = e.target.closest('[data-confirm]:not(form)');
        if (!el || el.dataset.confirmed === '1') return;
        e.preventDefault();
        e.stopImmediatePropagation();
        ask(el).then(function (ok) {
            if (!ok) return;
            el.dataset.confirmed = '1';
            el.click();
            delete el.dataset.confirmed;
        });
    }, true);
})();
</script>
