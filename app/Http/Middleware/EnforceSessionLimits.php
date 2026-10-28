<?php

namespace App\Http\Middleware;

use App\Services\SessionLimits;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs a user out once their sign-in reaches a limit (App\Services\SessionLimits): too long without activity,
 * or too long since signing in. The sign-in page then says why, and signing in again returns them to the page
 * they were on. Every request counts as activity, except the staff warning asking how much time is left.
 */
class EnforceSessionLimits
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $request->hasSession()) {
            return $next($request);
        }

        $session = $request->session();
        if (! SessionLimits::started($session)) {
            SessionLimits::start($session, false);   // signed in before the limits existed
        }

        $minutes = SessionLimits::minutes($user, $session);
        $left = SessionLimits::secondsLeft($user, $session);
        if ($left['idle'] !== null && $left['idle'] <= 0) {
            return $this->signOut($request, 'You were signed out after ' . SessionLimits::duration($minutes['idle']) . ' without activity. Sign in again to continue.');
        }
        if ($left['absolute'] <= 0) {
            return $this->signOut($request, 'You were signed out ' . SessionLimits::duration($minutes['absolute']) . ' after signing in. Sign in again to continue.');
        }

        if (! $request->routeIs('session.status')) {
            SessionLimits::touch($session);
        }

        return $next($request);
    }

    private function signOut(Request $request, string $message): Response
    {
        $session = $request->session();
        // Where to return after signing in again: this page, or for a form or a background request, the page it was sent from.
        $back = $request->isMethod('GET') && ! $request->expectsJson() ? $request->fullUrl() : $session->previousUrl();

        // logoutCurrentDevice() leaves "Keep me signed in" on the user's other devices alone.
        Auth::guard('web')->logoutCurrentDevice();
        $session->invalidate();
        $session->regenerateToken();
        $session->flash('signed_out', $message);
        if ($back) {
            redirect()->setIntendedUrl($back);
        }

        return $request->expectsJson()
            ? response()->json(['message' => $message], 401)
            : redirect()->route('login');
    }
}
