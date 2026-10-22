// Live preview for the Appearance form (resources/views/admin/partials/appearance-form.blade.php): each
// change is applied at once to <html> (the data-* attributes and colour variables the admin layout
// sets from App\Services\Appearance); Save keeps it. A custom colour is adjusted here the same way the
// server does (forWhiteText / onDark), so the preview matches what is saved.
(function () {
    'use strict';
    var form = document.getElementById('appearanceForm');
    if (!form) return;
    var data = JSON.parse(form.getAttribute('data-preview'));
    var html = document.documentElement;
    var note = document.getElementById('apAdjust');

    // ---- colour maths (WCAG 2.2), as in App\Services\Appearance ----
    function rgb(hex) { hex = hex.replace('#', ''); return [0, 2, 4].map(function (i) { return parseInt(hex.substr(i, 2), 16); }); }
    function lum(hex) {
        var c = rgb(hex).map(function (v) { v /= 255; return v <= 0.04045 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); });
        return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
    }
    function contrast(a, b) { var la = lum(a), lb = lum(b); return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05); }
    function hsl(hex) {
        var c = rgb(hex).map(function (v) { return v / 255; }), r = c[0], g = c[1], b = c[2];
        var max = Math.max(r, g, b), min = Math.min(r, g, b), l = (max + min) / 2, h = 0, s = 0, d = max - min;
        if (max !== min) {
            s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
            h = max === r ? (g - b) / d + (g < b ? 6 : 0) : max === g ? (b - r) / d + 2 : (r - g) / d + 4;
            h /= 6;
        }
        return [h, s, l];
    }
    function fromHsl(h, s, l) {
        var r, g, b;
        if (s === 0) { r = g = b = l; } else {
            var q = l < 0.5 ? l * (1 + s) : l + s - l * s, p = 2 * l - q;
            var hue = function (t) {
                if (t < 0) t += 1; if (t > 1) t -= 1;
                if (t < 1 / 6) return p + (q - p) * 6 * t;
                if (t < 1 / 2) return q;
                if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
                return p;
            };
            r = hue(h + 1 / 3); g = hue(h); b = hue(h - 1 / 3);
        }
        return '#' + [r, g, b].map(function (v) { return ('0' + Math.round(v * 255).toString(16)).slice(-2); }).join('');
    }
    function forWhiteText(hex) {
        var c = hsl(hex), l = c[2];
        while (contrast(fromHsl(c[0], c[1], l), '#ffffff') < data.min && l > 0) l = Math.max(0, l - 0.005);
        return fromHsl(c[0], c[1], l);
    }
    function onDark(hex) {
        var c = hsl(hex), l = c[2];
        while (contrast(fromHsl(c[0], c[1], l), data.sidebar) < data.min && l < 1) l = Math.min(1, l + 0.005);
        return fromHsl(c[0], c[1], l);
    }
    function rgbList(hex) { return rgb(hex).join(', '); }

    function value(name) { var el = form.querySelector('input[name="' + name + '"]:checked'); return el ? el.value : null; }
    function setVars(vars) { Object.keys(vars).forEach(function (k) { html.style.setProperty(k, vars[k]); }); }

    function accentVars() {
        var accent = value('accent');
        if (accent !== 'custom') { note.textContent = ''; return data.accents[accent].vars; }
        var raw = form.elements.custom.value.toLowerCase(), hex = forWhiteText(raw), dark = onDark(hex);
        note.textContent = hex === raw
            ? 'Text on this colour reads at ' + contrast(hex, '#ffffff').toFixed(1) + ':1.'
            : 'Adjusted to ' + hex + ' so text on it stays readable (' + contrast(hex, '#ffffff').toFixed(1) + ':1).';
        return { '--t-primary': hex, '--t-primary-rgb': rgbList(hex), '--t-primary-on-dark': dark, '--t-primary-on-dark-rgb': rgbList(dark) };
    }

    function apply() {
        setVars(accentVars());
        setVars(data.status[value('status')]);
        html.style.setProperty('--t-text', String(value('text') / 100));
        html.setAttribute('data-text', value('text'));
        html.setAttribute('data-motion', value('motion'));
        html.setAttribute('data-font', value('font'));
        html.setAttribute('data-status', value('status'));
    }

    form.addEventListener('change', apply);
    // Picking a colour chooses Custom.
    form.elements.custom.addEventListener('input', function () { document.getElementById('accent-custom').checked = true; apply(); });
    if (value('accent') === 'custom') accentVars();
})();
