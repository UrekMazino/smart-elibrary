{{-- The rich-text editor (TinyMCE) in the staff member's appearance (App\Services\Appearance): a dark toolbar on a dark
     background, and the page being edited in the theme's text and surface colours. The editing area is a frame of its
     own, so the admin theme's sheets never reach it. Spread into tinymce.init: Object.assign({...}, editorLook()). --}}
<script nonce="{{ cspNonce() }}">
window.editorLook = function () {
    var root = document.documentElement, css = getComputedStyle(root);
    var dark = root.dataset.mode === 'dark' || (root.dataset.mode === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    var role = function (name) { return css.getPropertyValue(name).trim(); };   // empty in the standard look (no roles set)
    var text = role('--ui-text'), surface = role('--ui-surface'), link = role('--ui-accent-text');

    var style = '';
    if (text) style += 'body { color: ' + text + '; } ';
    if (surface) style += 'body { background-color: ' + surface + '; } ';
    if (link) style += 'a { color: ' + link + '; } ';

    return { skin: dark ? 'oxide-dark' : 'oxide', content_css: dark ? 'dark' : 'default', content_style: style };
};
</script>
