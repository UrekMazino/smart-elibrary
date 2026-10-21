<?php

namespace App\Http\Middleware;

use Illuminate\Routing\Middleware\ThrottleRequests as Base;

/**
 * `throttle:N,M` with an allowance of its own per route. Laravel's version keys the count by user
 * (by IP address for guests) only, so every throttled route shared one count: a patron's quick views
 * and filter look-ups on the search page used up the 5-a-minute inquiry limit, and guests behind one
 * office IP spent the AI mode's 10 a minute on every search filter they opened.
 */
class ThrottleRequests extends Base
{
    protected function resolveRequestSignature($request)
    {
        return sha1(parent::resolveRequestSignature($request) . '|' . $request->method() . ' ' . ($request->route()?->uri() ?? ''));
    }
}
