# Content Security Policy rollout

Goal: enforce a Content Security Policy that runs only this site's scripts and inline scripts carrying the
request's nonce (OWASP ASVS 3.4.3, Level 2), so a script injected into a page can't run.

## Where it stands (26 October 2026)

- **Enforced:** `frame-ancestors 'self'; object-src 'none'; base-uri 'none'; form-action 'self'` on every
  page. Only this site can frame our pages (ASVS 3.4.6), and no page can load plugins, change its base
  address or post a form to another site. `X-Frame-Options` stays for older browsers.
- **Report-Only:** the full policy below. Browsers report what it would block but block nothing.
- **Nonces:** all 93 inline `<script>` tags carry `nonce="{{ cspNonce() }}"`. A crawl of every page as a
  guest, a patron and staff reported no violation at page load.
- **Guard:** `tests/Feature/CspRatchetTest.php` fails if an inline script lacks the nonce, or if inline
  handlers or `javascript:` links increase. Lower its two baselines as they come down.

The full policy (`App\Http\Middleware\SecurityHeaders`, `{nonce}` = `cspNonce()`):

```
default-src 'self';
script-src 'self' 'nonce-{nonce}' 'report-sample' https://cdn.jsdelivr.net https://ajax.googleapis.com;
style-src 'self' 'unsafe-inline' https://fonts.googleapis.com;
font-src 'self' data: https://fonts.gstatic.com;
img-src 'self' data: blob:; connect-src 'self'; frame-src 'self'; media-src 'self';
object-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'self'
```

## What still blocks enforcing

Inline event handlers (`onclick="…"`) and `href="javascript:…"` links: a nonce can't cover them. Browsers
report them only when they run, so the counts come from the code and from the rendered pages.

- In the code: **98 handlers** and **103 `javascript:` links** in 41 files (the ratchet test's baselines).
- Rendered, per page (staff), including table buttons built by JavaScript:

| Page | Handlers | `javascript:` links |
|---|---|---|
| `/admin/record-list` | 101 | 1 |
| `/admin/borrowed` | 20 | 1 |
| `/admin/inquiry` | 20 | 1 |
| `/admin/view-record/{id}` | 13 | 1 |
| `/admin/reservations` | 9 | 1 |
| `/admin/check-out-in` | 6 | 1 |
| `/patron/transactions` | 6 | 0 |
| `/admin/batch-list`, `/admin/on-hold-list` | 5 each | 1 each |
| `/patron/inquiries` | 4 | 0 |
| 7 more admin pages and `/patron/profile` | 2 to 3 each | 0 to 1 |
| Every other page (the sign-out link only) | 1 | 0 to 1 |

## Progress

| Date | Done | Handlers | `javascript:` links |
|---|---|---|---|
| 2026-10-26 | Policy Report-Only, nonces on every inline script | 98 | 103 |
| 2026-10-26 | Steps 1 to 3: unused theme files deleted, sign-out links, all OPAC pages | 76 (admin only) | 2 (admin inventory pages) |
| 2026-10-26 | Step 4: every admin page (data-call buttons, `AdminActions` in the admin layout) | 0 | 0 |

## Order of work

1. **Done 2026-10-26.** **Delete unused theme files:** nothing renders them, and they hold about 95 of the 103 `javascript:`
   links. They are `resources/views/index.blade.php`, `dashboard.blade.php`, `authentication-signin.blade.php`,
   `layouts/app.blade.php`, `layouts/header.blade.php`, `layouts/nav.blade.php`, `admin/layouts/header.blade.php`
   and `admin/layouts/nav.blade.php`, plus `App\View\Components\AppLayout`. Check each with a search first.
2. **Done 2026-10-26.** **The two layouts' sign-out links** (`opac/layout/master`, `admin/layouts/master`): one handler on every page.
3. **Done 2026-10-26.** **OPAC pages:** the record page (11), transactions (2), profile (2), inquiries, to review, the shelf
   browser and the breadcrumb (1 each).
4. **Done 2026-10-26.** **Admin pages,** largest first: the record view (14), `public/js/admin/reservation.js` (10), inquiries (7),
   borrowed and check-out/in (5 each), then the record, on-hold and batch lists. Their table buttons are
   built in JavaScript: give each button a `data-action` and the record id, and handle all of them with
   one listener per table.
5. **Outside scripts:**
   - The CSF page loads jQuery **1.11.1** from `ajax.googleapis.com`; that version has known
     cross-site-scripting flaws. Use the site's own jQuery 3.6, then drop `ajax.googleapis.com` from the
     policy.
   - The borrowed page loads moment.js from jsDelivr: keep it, or serve it from `public/`.
6. **Enforce** when both baselines are 0 and a crawl reports nothing:
   - set `CSP_ENFORCE=true`, which moves the full policy into the enforced header;
   - watch the browser console on the busiest pages for a week.

**Admin pages** use a small dispatcher in `admin/layouts/master.blade.php`: a button carries
`data-call="functionName"` and `data-args="[JSON]"` (`"$this"` = the button, `"$event"` = the event), and
each page lists the functions its buttons may call with `AdminActions.allow([...])`, so injected HTML can't
call anything else. Tables built in JavaScript write the attributes with `AdminActions.attrs(name, args)`.
(`data-call`, not `data-act`: a few pages already use `data-act` with listeners of their own.)

**Converting a handler (OPAC pages: one listener per page):**

```html
<!-- before -->
<button onclick="holdMaterial(1147)">Hold</button>
<!-- after -->
<button data-action="hold" data-id="1147">Hold</button>
<script nonce="{{ cspNonce() }}">
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-action="hold"]');
    if (b) holdMaterial(b.dataset.id);
  });
</script>
```

**Checking a page:** open the browser console and use the page. A handler still inline shows up as
`[Report Only] Refused to execute inline event handler…`, with the first characters of its code.

## Later

- **Styles:** `style-src` keeps `'unsafe-inline'` because there are 293 `style="…"` attributes. ASVS 3.4.3
  is about scripts; tightening styles can come after.
- **The PDF reader** (`public/pdfjs/web/viewer.html`) is a static file Apache serves without Laravel, so it
  gets no policy. Add one in `public/pdfjs/.htaccess` once the main policy is enforced.
