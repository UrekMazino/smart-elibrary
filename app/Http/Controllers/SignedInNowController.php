<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\BrowserSessions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Services\SecurityLog;

/**
 * Administration → Accounts → Signed in now (access.sessions; OWASP ASVS 7.4.5): who is signed in and on which
 * browsers, and signing one person out everywhere ("target" = their id) or everyone ("target" = everyone). Either
 * takes your password; a wrong one comes back in the "signOut" bag. As on the other account pages, your own account
 * isn't acted on here, and only a developer can sign out a developer.
 */
class SignedInNowController extends Controller
{
    public function index(Request $request)
    {
        $everyone = BrowserSessions::everyone($request);
        $type = in_array($request->query('type'), ['staff', 'patrons'], true) ? $request->query('type') : 'all';
        $search = Str::lower(trim((string) $request->query('q')));

        return view('admin.sessions', [
            'people' => $everyone
                ->when($type !== 'all', fn ($people) => $people->where('staff', $type === 'staff'))
                ->when($search !== '', fn ($people) => $people->filter(
                    fn ($person) => str_contains(Str::lower($person->user->name . ' ' . $person->user->email), $search)
                ))
                ->values(),
            'counts' => [
                'staff' => $everyone->where('staff', true)->count(),
                'patrons' => $everyone->where('staff', false)->count(),
                'visitors' => BrowserSessions::visitors(30),
            ],
            'type' => $type,
            'search' => trim((string) $request->query('q')),
            'available' => config('session.driver') === 'database',
            'developer' => $request->user()->hasRole('developer'),
        ]);
    }

    public function signOut(Request $request)
    {
        $request->validateWithBag('signOut', [
            'target' => ['required', 'regex:/^(everyone|\d+)$/'],
            'password' => ['required', 'current_password'],
        ]);

        if ($request->target === 'everyone') {
            $people = BrowserSessions::endEveryone($request);
            SecurityLog::record('sessions.ended', null, ['scope' => 'everyone signed in', 'count' => $people]);
            return back()->with('success', "Signed out $people " . Str::plural('person', $people) . '. “Keep me signed in” no longer works on their devices.');
        }

        $user = User::findOrFail($request->target);
        abort_if($user->is($request->user()), 403, 'You can’t sign yourself out here. Use Log Out, or your Profile for your other sessions.');
        abort_if($user->hasRole('developer') && ! $request->user()->hasRole('developer'), 403, 'Only a developer can sign out a developer account.');
        BrowserSessions::endAllOf($user);
        SecurityLog::record('sessions.ended', $user, ['scope' => 'everywhere, by staff']);

        return back()->with('success', "$user->name is signed out everywhere.");
    }
}
