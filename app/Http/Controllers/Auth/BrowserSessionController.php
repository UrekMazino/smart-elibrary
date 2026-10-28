<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\BrowserSessions;
use App\Services\GoogleSignIn;
use Illuminate\Http\Request;

/**
 * "Where you're signed in" on the profile pages: sign out one of your other sessions ("end" = its ref) or all of
 * them ("end" = others). Either needs your current password (OWASP ASVS 7.5.2); a wrong one comes back in the
 * "endSessions" bag, so the page can open the dialog again. An account without a password (made with Google) needs a
 * recent Google sign-in or "Confirm with Google" instead (App\Services\GoogleSignIn).
 */
class BrowserSessionController extends Controller
{
    public function destroy(Request $request)
    {
        $withPassword = $request->user()->hasPassword();
        $request->validateWithBag('endSessions', [
            'end' => ['required', 'string', 'max:64'],
            'password' => $withPassword ? ['required', 'current_password'] : [],
        ]);
        if (! $withPassword && ! GoogleSignIn::recentlyConfirmed($request->session())) {
            return back()->withInput(['end' => $request->end])->withErrors(['password' => 'Confirm with Google first.'], 'endSessions');
        }

        if ($request->end === 'others') {
            BrowserSessions::endOthers($request, $withPassword ? $request->password : null);
            return back()->with('success', 'Your other sessions are signed out.');
        }

        return back()->with('success', BrowserSessions::end($request, $request->end)
            ? 'That session is signed out.'
            : 'That session had already ended.');
    }
}
