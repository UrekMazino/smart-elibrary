// Live preview for the Appearance form (resources/views/admin/partials/appearance-form.blade.php): each
// change asks the server what the page would look like (AppearanceController::preview, which holds all
// the colour maths) and applies it to this page: the variables, the <html> attributes, which of the light
// and dark stylesheets apply, and the sidebar. Save keeps it.
(function () {
    'use strict';
    var form = document.getElementById('appearanceForm');
    if (!form) return;
    var url = form.getAttribute('data-preview-url');
    var html = document.documentElement;
    var note = document.getElementById('apAdjust');
    var timer = null, latest = 0;

    function params() {
        var data = new URLSearchParams(new FormData(form));
        data.delete('_token');
        return data.toString();
    }

    function apply(res) {
        document.getElementById('appearanceVars').textContent = res.css;
        Object.keys(res.attributes).forEach(function (k) { html.setAttribute(k, res.attributes[k]); });
        document.getElementById('themeLight').media = res.media.light;
        document.getElementById('themeDark').media = res.media.dark;
        document.body.classList.toggle('sidebar-dark', res.bodyClass.indexOf('sidebar-dark') !== -1);
        note.textContent = res.notes.join(' ');
    }

    function preview(delay) {
        clearTimeout(timer);
        timer = setTimeout(function () {
            var id = ++latest;
            fetch(url + '?' + params(), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (res) { if (res && id === latest) apply(res); });   // only the latest answer
        }, delay);
    }

    form.addEventListener('change', function () { preview(0); });
    // Picking a colour chooses Custom, and previews as the colour is dragged.
    form.elements.custom.addEventListener('input', function () { document.getElementById('accent-custom').checked = true; preview(150); });
    form.elements.background_custom.addEventListener('input', function () { document.getElementById('background-custom').checked = true; preview(150); });
    if (form.querySelector('input[name="accent"]:checked').value === 'custom' || form.querySelector('input[name="background"]:checked').value === 'custom') preview(0);
})();
