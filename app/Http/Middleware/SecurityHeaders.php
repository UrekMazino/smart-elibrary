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
        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) $response->headers->set($name, $value);   // a page's own choice wins
        }

        return $response;
    }
}
