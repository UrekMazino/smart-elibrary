<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HomeController;
use App\Models\User;
use App\Services\GoogleSignIn;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * "Continue with Google" on the sign-in and sign-up pages, for patrons. The eLibrary is the OAuth client and Google
 * the authorization server (OWASP ASVS V10): the code flow with state and PKCE (Laravel Socialite), the scopes openid,
 * email and profile; Google's tokens aren't kept. Google confirms the address, so there is no email verification:
 *  - a Google account already linked signs in to its eLibrary account;
 *  - else the account with the same address is linked to it (Google must say the address is confirmed), and its
 *    password still works;
 *  - else a patron account is made, as signing up does, without a password (App\Services\GoogleSignIn).
 * Staff accounts keep their password and its limits, so they are refused here; banned and removed accounts too.
 *
 * "Confirm with Google" (confirm) sends a signed-in patron who has no password through Google again, to prove who
 * they are where others type their password, and comes back through the same callback.
 */
class GoogleSignInController extends Controller
{
    public function redirect()
    {
        abort_unless(GoogleSignIn::enabled(), 404);

        return Socialite::driver('google')->enablePKCE()->redirect();
    }

    public function confirm(Request $request)
    {
        $user = $request->user();
        abort_unless(GoogleSignIn::enabled() && $user->google_id, 404);

        // Back to the page that asked (its address is the session's own record of the last page, not the Referer).
        $request->session()->put('google.confirm', url()->previous());

        return Socialite::driver('google')->enablePKCE()
            ->with(['prompt' => 'select_account', 'login_hint' => $user->email])->redirect();
    }

    public function callback(Request $request)
    {
        abort_unless(GoogleSignIn::enabled(), 404);

        $confirming = $request->session()->pull('google.confirm');
        if (Auth::check() && ! $confirming) {
            return redirect(HomeController::landingUrl(Auth::user()));   // already signed in (another tab)
        }
        $refuse = fn (string $message) => redirect()->to($confirming ?: route('login'))->withErrors(['google' => $message]);

        if ($request->filled('error')) {   // "Cancel" on Google's page
            return $confirming ? redirect()->to($confirming) : redirect()->route('login')->with('notice', 'Google sign-in was cancelled.');
        }
        try {
            $google = Socialite::driver('google')->enablePKCE()->user();
        } catch (Throwable $e) {   // an old or repeated return from Google (its state), or Google couldn't be reached
            Log::info('Google sign-in did not finish: ' . $e->getMessage());
            return $refuse('Google sign-in didn’t finish. Please try again.');
        }

        if ($confirming && Auth::check()) {
            if ((string) $google->getId() !== (string) Auth::user()->google_id) {
                return $refuse('That isn’t the Google account linked to your eLibrary account. Choose ' . Auth::user()->email . '.');
            }
            GoogleSignIn::markConfirmed($request->session());
            return redirect()->to($confirming)->with('success', 'Confirmed. You can go ahead.');
        }

        $email = Str::lower(trim((string) $google->getEmail()));
        if ($email === '' || ! Arr::get($google->getRaw(), 'email_verified')) {
            return $refuse('Google hasn’t confirmed this Google account’s email address, so it can’t be used here.');
        }

        $user = User::withTrashed()->where('google_id', $google->getId())->first();
        if (! $user) {
            $user = User::withTrashed()->where('email', $email)->first();
            if ($user?->google_id) {
                return $refuse('The eLibrary account for this address is linked to another Google account. Continue with that one, or sign in with your password.');
            }
        }
        if ($user?->trashed()) {
            return $refuse('This account can’t be used. Please contact the library.');
        }
        if ($user?->isStaff()) {
            return $refuse('Staff accounts sign in with their email and password, not with Google.');
        }
        if ($user?->is_banned) {   // as a password sign-in: the sign-in page's banned popup
            return redirect()->route('login')->with('account_banned', true)->withInput(['email' => $user->email])->withErrors([
                'email' => 'This account has been banned. Please email the system administrator to have the ban lifted.',
            ]);
        }

        if (! $user) {
            $user = User::create([
                'name'         => mb_substr(trim((string) $google->getName()) ?: Str::before($email, '@'), 0, 255),
                'email'        => $email,
                'password'     => null,   // none until the patron sets one on their profile
                'library_code' => generateLibraryCode(),
            ]);
            event(new Registered($user));
        }
        $user->forceFill(['google_id' => (string) $google->getId(), 'email_verified_at' => $user->email_verified_at ?? now()])->save();

        // Not "Keep me signed in": the patron stays signed in to Google, and one click brings them back.
        Auth::login($user);
        $request->session()->regenerate();
        GoogleSignIn::markConfirmed($request->session());

        return redirect()->intended(HomeController::landingUrl($user));
    }
}
