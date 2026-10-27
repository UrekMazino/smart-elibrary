<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SessionLimits;
use Illuminate\Http\Request;

/** For the staff warning before a sign-out (partials/session-timeout): the time left, and "Stay signed in". */
class SessionStatusController extends Controller
{
    /** Seconds left before the idle and the absolute limit. Asking doesn't count as activity (EnforceSessionLimits). */
    public function show(Request $request)
    {
        return response()->json(SessionLimits::secondsLeft($request->user(), $request->session()));
    }

    /** "Stay signed in": like any other request it counted as activity, so the idle clock has started again. */
    public function stay(Request $request)
    {
        return $this->show($request);
    }
}
