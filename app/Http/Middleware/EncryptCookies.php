<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        'sr_view',          // OPAC search results: list / grid preference, set by the page's script
        '__Host-sr_view',   // the same over HTTPS (Secure, prefixed)
    ];
}
