<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Browser protections for every page the app serves:
 * - X-Frame-Options: other sites can't show our pages in a frame (clickjacking: tricking a signed-in
 *   staff member into clicking "Delete" or "Ban" on a hidden admin page). Our own frames still work.
 * - X-Content-Type-Options: browsers use the declared type, never guess one (an upload served as an
 *   image is never run as a page).
 * - Referrer-Policy: other sites see only our domain, not the page (search terms, record ids).
 * - Strict-Transport-Security (HSTS, OWASP ASVS 3.4.1), on HTTPS responses only: after one HTTPS visit
 *   the browser uses HTTPS for the site and its subdomains for a year (config app.hsts_max_age), so a
 *   later visit can't be quietly downgraded to plain HTTP on the way (public Wi-Fi). Browsers ignore it
 *   over HTTP, so local development on http:// is unaffected.
 * - Content Security Policy (OWASP ASVS 3.4.3, 3.4.6), on HTML pages:
 *   - enforced now: only this site may frame our pages (frame-ancestors), no plugins (object-src), no
 *     <base> tag (base-uri), forms post only to this site (form-action);
 *   - the full policy (scripts only from this site, the CDNs we use and inline scripts carrying this
 *     request's nonce, cspNonce()) as Report-Only until every page passes it, then enforced with
 *     CSP_ENFORCE=true. Inline onclick= handlers are what it still reports: CSP_ROLLOUT.md.
 * Static files under public/ get the same from public/.htaccess.
 */
class SecurityHeaders
{
    /** Always enforced: none of these can break a page of ours. */
    private const CSP_BASE = "frame-ancestors 'self'; object-src 'none'; base-uri 'none'; form-action 'self'";

    /** The full policy; {nonce} is this request's cspNonce(). */
    private const CSP_FULL = "default-src 'self'; "
        . "script-src 'self' 'nonce-{nonce}' 'report-sample' https://cdn.jsdelivr.net https://ajax.googleapis.com; "
        . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
        . "font-src 'self' data: https://fonts.gstatic.com; "
        . "img-src 'self' data: blob:; connect-src 'self'; frame-src 'self'; media-src 'self'; "
        . "object-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'self'";

    public function handle(Request $request, Closure $next)
    {
        app()->forgetInstance('csp.nonce');   // a fresh nonce for every request
        $response = $next($request);

        $headers = [
            'X-Frame-Options'        => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy'        => 'strict-origin-when-cross-origin',
        ];
        if (str_starts_with((string) $response->headers->get('Content-Type'), 'text/html')) {
            $full = str_replace('{nonce}', cspNonce(), self::CSP_FULL);
            if (config('app.csp_enforce')) {
                $headers['Content-Security-Policy'] = $full;
            } else {
                $headers['Content-Security-Policy'] = self::CSP_BASE;
                $headers['Content-Security-Policy-Report-Only'] = $full;
            }
        }
        if ($request->isSecure()) {
            $maxAge = (int) config('app.hsts_max_age');
            $headers['Strict-Transport-Security'] = $maxAge > 0 ? "max-age=$maxAge; includeSubDomains" : 'max-age=0';
        }
        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) $response->headers->set($name, $value);   // a page's own choice wins
        }

        return $response;
    }
}
