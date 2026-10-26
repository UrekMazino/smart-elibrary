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
 * Static files under public/ get the same from public/.htaccess.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $headers = [
            'X-Frame-Options'        => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy'        => 'strict-origin-when-cross-origin',
        ];
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
