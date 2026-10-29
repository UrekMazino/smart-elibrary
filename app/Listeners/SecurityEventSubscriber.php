<?php

namespace App\Listeners;

use App\Services\SecurityLog;
use Illuminate\Auth\Events\CurrentDeviceLogout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Laravel's own sign-in events, into the security log (App\Services\SecurityLog). The app's controllers add the
 * rest: refusals, role, permission and ban changes, passwords set by staff, sessions ended.
 */
class SecurityEventSubscriber
{
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'signedIn',
            Failed::class => 'failed',
            Lockout::class => 'lockedOut',
            Logout::class => 'signedOut',
            CurrentDeviceLogout::class => 'signedOut',
            Registered::class => 'signedUp',
            PasswordReset::class => 'passwordReset',
        ];
    }

    /** With the password, with Google, by the "Keep me signed in" cookie, or right after signing up. A banned account is refused, not signed in. */
    public function signedIn(Login $event): void
    {
        $request = request();
        if ($event->user->is_banned ?? false) {
            // AuthenticatedSessionController signs it straight out again: that isn't a sign-out to log.
            $request->attributes->set('security_log.quiet_sign_out', true);
            SecurityLog::record('sign-in.refused', $event->user, ['reason' => 'banned account']);
            return;
        }
        $method = match (true) {
            $request->isMethod('POST') && $request->is('login') => 'password',
            $request->routeIs('google.callback') => 'google',
            Auth::guard('web')->viaRemember() => 'remembered',
            $request->isMethod('POST') && $request->is('register') => 'sign-up',
            default => 'other',
        };
        SecurityLog::record('sign-in', $event->user, ['method' => $method] + ($event->remember && $method !== 'remembered' ? ['kept' => true] : []));
    }

    /** The address typed, never the password; "user" only when an account has that address. */
    public function failed(Failed $event): void
    {
        SecurityLog::record('sign-in.failed', $event->user, [], Str::lower(trim((string) ($event->credentials['email'] ?? ''))));
    }

    /** Once per lockout, however many tries come in while it lasts. */
    public function lockedOut(Lockout $event): void
    {
        $request = $event->request;   // the LoginRequest, whose throttle key the lockout is on
        $key = method_exists($request, 'throttleKey') ? $request->throttleKey() : Str::lower((string) $request->input('email')) . '|' . $request->ip();
        if (cache()->add('security-log:locked:' . sha1($key), 1, max(60, RateLimiter::availableIn($key)))) {
            SecurityLog::record('sign-in.locked', null, [], Str::lower(trim((string) $request->input('email'))));
        }
    }

    public function signedOut(Logout|CurrentDeviceLogout $event): void
    {
        if ($event->user && ! request()->attributes->get('security_log.quiet_sign_out')) {
            SecurityLog::record('sign-out', $event->user);
        }
    }

    /** Signing up, "Continue with Google" for a new address, or staff making an account (then with its role). */
    public function signedUp(Registered $event): void
    {
        $request = request();
        $how = match (true) {
            $request->routeIs('google.callback') => 'with Google',
            auth()->check() => 'by staff',
            default => 'signed up',
        };
        $role = method_exists($event->user, 'getRoleNames') ? $event->user->getRoleNames()->first() : null;
        SecurityLog::record('sign-up', $event->user, array_filter(['how' => $how, 'role' => $role]));
    }

    public function passwordReset(PasswordReset $event): void
    {
        SecurityLog::record('password.changed', $event->user, ['how' => 'with an email reset link']);
    }
}
