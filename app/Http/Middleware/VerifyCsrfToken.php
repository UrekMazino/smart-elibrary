<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];

    /**
     * No XSRF-TOKEN cookie. Laravel sets it for JavaScript libraries (axios) that copy it into a header,
     * but every form and fetch here sends the token from the page itself (@csrf, the csrf_token meta
     * tag). One cookie fewer to protect (OWASP ASVS 3.3).
     */
    protected $addHttpCookie = false;
}
