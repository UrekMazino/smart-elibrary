<?php

namespace App\Services;

use Illuminate\Contracts\Session\Session;

/**
 * "Continue with Google" (App\Http\Controllers\Auth\GoogleSignInController): whether it is on, and the proof of a
 * recent Google sign-in that an account without a password gives where others type their current password (signing
 * out other sessions, setting a password). A Google sign-in counts as one; "Confirm with Google" makes a new one.
 */
class GoogleSignIn
{
    /** How long a Google sign-in counts as confirming who you are. */
    public const CONFIRM_MINUTES = 15;

    /** Off while GOOGLE_CLIENT_ID or GOOGLE_CLIENT_SECRET is unset: no button, and its routes are 404. */
    public static function enabled(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public static function markConfirmed(Session $session): void
    {
        $session->put('auth.google_confirmed_at', now()->getTimestamp());
    }

    public static function recentlyConfirmed(Session $session): bool
    {
        return now()->getTimestamp() - (int) $session->get('auth.google_confirmed_at', 0) < self::CONFIRM_MINUTES * 60;
    }
}
