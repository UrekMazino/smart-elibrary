<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\BrowserSessions;
use Illuminate\Http\Request;

/**
 * "Where you're signed in" on the profile pages: sign out one of your other sessions ("end" = its ref) or all of
 * them ("end" = others). Either needs your current password (OWASP ASVS 7.5.2); a wrong one comes back in the
 * "endSessions" bag, so the page can open the dialog again.
 */
class BrowserSessionController extends Controller
{
    public function destroy(Request $request)
    {
        $request->validateWithBag('endSessions', [
            'end' => ['required', 'string', 'max:64'],
            'password' => ['required', 'current_password'],
        ]);

        if ($request->end === 'others') {
            BrowserSessions::endOthers($request, $request->password);
            return back()->with('success', 'Your other sessions are signed out.');
        }

        return back()->with('success', BrowserSessions::end($request, $request->end)
            ? 'That session is signed out.'
            : 'That session had already ended.');
    }
}
