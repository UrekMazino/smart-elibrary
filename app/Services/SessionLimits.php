<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Str;

/**
 * How long a sign-in lasts (OWASP ASVS 7.3.1, 7.3.2; config/session.php "limits"): a user is signed out after
 * a while without activity (idle limit) and a while after signing in, however active (absolute limit). Staff
 * 30 minutes and 8 hours; patrons 2 hours and 12 hours, or 30 days with no idle limit on a device where they
 * ticked "Keep me signed in". The clocks are kept in the session. EnforceSessionLimits signs users out; the
 * staff warning (partials/session-timeout) asks for the time left. docs/implemented/SECURITY_NOTES.md section 5.
 */
class SessionLimits
{
    private const KEY = 'sign_in';

    /** A sign-in (the Login event, also when the "Keep me signed in" cookie signs a patron back in): the clocks start. */
    public static function start(Session $session, bool $remember): void
    {
        $now = now()->getTimestamp();
        $session->put(self::KEY, ['at' => $now, 'active_at' => $now, 'remember' => $remember]);
    }

    public static function started(Session $session): bool
    {
        return $session->has(self::KEY);
    }

    /** The user did something: the idle clock starts again. */
    public static function touch(Session $session): void
    {
        $session->put(self::KEY . '.active_at', now()->getTimestamp());
    }

    /** Did the user tick "Keep me signed in" for this sign-in? */
    public static function remembered(Session $session): bool
    {
        return (bool) $session->get(self::KEY . '.remember');
    }

    /** @return array{idle: ?int, absolute: int} minutes allowed; idle null = no idle limit */
    public static function minutes(User $user, Session $session): array
    {
        return self::minutesFor($user, self::remembered($session));
    }

    /** @return array{idle: ?int, absolute: int} seconds left before each limit; 0 or less = reached */
    public static function secondsLeft(User $user, Session $session): array
    {
        return self::left($user, (array) $session->get(self::KEY));
    }

    /** Another session of the user, from its stored data: has it reached a limit? (One from before the limits hasn't.) */
    public static function reachedIn(User $user, array $sessionData): bool
    {
        if (! isset($sessionData[self::KEY])) {
            return false;
        }
        $left = self::left($user, $sessionData[self::KEY]);

        return ($left['idle'] !== null && $left['idle'] <= 0) || $left['absolute'] <= 0;
    }

    /** Another session, from its stored data: when did its sign-in start? (Null for one from before the limits.) */
    public static function signedInAtIn(array $sessionData): ?int
    {
        return $sessionData[self::KEY]['at'] ?? null;
    }

    /** Another session, from its stored data: was "Keep me signed in" ticked for it? */
    public static function rememberedIn(array $sessionData): bool
    {
        return (bool) ($sessionData[self::KEY]['remember'] ?? false);
    }

    private static function minutesFor(User $user, bool $remembered): array
    {
        if (! $user->isStaff() && $remembered) {
            return ['idle' => null, 'absolute' => (int) config('auth.guards.web.remember')];
        }

        return config('session.limits.' . ($user->isStaff() ? 'staff' : 'patron'));
    }

    /** @param array{at?: int, active_at?: int, remember?: bool} $clocks */
    private static function left(User $user, array $clocks): array
    {
        $minutes = self::minutesFor($user, (bool) ($clocks['remember'] ?? false));
        $now = now()->getTimestamp();

        return [
            'idle' => $minutes['idle'] === null ? null : ($clocks['active_at'] ?? 0) + $minutes['idle'] * 60 - $now,
            'absolute' => ($clocks['at'] ?? 0) + $minutes['absolute'] * 60 - $now,
        ];
    }

    /** "30 minutes", "2 hours", "30 days" */
    public static function duration(int $minutes): string
    {
        [$count, $unit] = match (true) {
            $minutes % 1440 === 0 => [intdiv($minutes, 1440), 'day'],
            $minutes % 60 === 0 => [intdiv($minutes, 60), 'hour'],
            default => [$minutes, 'minute'],
        };

        return $count . ' ' . Str::plural($unit, $count);
    }
}
