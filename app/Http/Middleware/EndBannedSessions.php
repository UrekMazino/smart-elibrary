<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * A banned account is signed out at its next request, wherever it is signed in (OWASP ASVS 7.4.2). The ban was only
 * checked when signing in with the password, so a session begun before the ban carried on, and a "Keep me signed in"
 * cookie signed the account straight back in. Banning also replaces the remember token (AdminController::toggleUserFlag).
 */
class EndBannedSessions
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->is_banned || ! $request->hasSession()) {
            return $next($request);
        }

        Auth::guard('web')->logout();   // also a new remember token, so no "Keep me signed in" cookie works
        $session = $request->session();
        $session->invalidate();
        $session->regenerateToken();
        // The sign-in page then shows its banned popup, as after signing in with the password (auth/login).
        $session->flash('account_banned', true);
        $session->flashInput(['email' => $user->email]);
        $session->flash('signed_out', 'You were signed out because this account has been banned.');

        return $request->expectsJson()
            ? response()->json(['message' => 'This account has been banned.'], 401)
            : redirect()->route('login');
    }
}
