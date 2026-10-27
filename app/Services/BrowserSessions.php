<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * "Where you're signed in" on the profile pages (OWASP ASVS 7.5.2): the account's sessions, each browser with when
 * it was last active, and signing out one of the others or all of them. Needs SESSION_DRIVER=database: Laravel then
 * keeps each session's user, IP address, browser and last activity in the `sessions` table. The page never sees a
 * session id, only a "ref" made from it.
 */
class BrowserSessions
{
    /** @return Collection<int, object{ref: string, browser: string, mobile: bool, ip: ?string, last_active: Carbon, current: bool}> this one first */
    public static function of(Request $request): Collection
    {
        if (config('session.driver') !== 'database') {
            return collect();
        }
        $user = $request->user();
        $current = $request->session()->getId();
        $rows = self::rows($user);
        if (! $rows->contains('id', $current)) {
            // A session is saved after its request: on its first one it isn't in the table yet.
            $rows->push((object) ['id' => $current, 'user_agent' => $request->userAgent(), 'ip_address' => $request->ip(), 'last_activity' => now()->getTimestamp()]);
        }

        return $rows
            ->filter(fn ($row) => $row->id === $current || self::stillSignedIn($user, $row))
            ->map(fn ($row) => (object) [
                'ref' => self::ref($row->id),
                'browser' => self::describe($row->user_agent),
                'mobile' => (bool) preg_match('/Mobi|iPhone|Android/', (string) $row->user_agent),
                'ip' => $row->ip_address,
                'last_active' => Carbon::createFromTimestamp($row->last_activity),
                'current' => $row->id === $current,
            ])
            ->sortBy([['current', 'desc'], ['last_active', 'desc']])
            ->values();
    }

    /** Signs out one of the user's other sessions. False when it has ended already (or isn't theirs). */
    public static function end(Request $request, string $ref): bool
    {
        $row = self::rows($request->user())
            ->first(fn ($row) => $row->id !== $request->session()->getId() && hash_equals(self::ref($row->id), $ref));
        if (! $row) {
            return false;
        }

        self::table()->where('id', $row->id)->delete();
        // Its "Keep me signed in" cookie would sign that device straight back in. There is one remember token per
        // account, so the new one also ends "Keep me signed in" on the other devices (each stays signed in until
        // its session ends); this device keeps its own.
        if (SessionLimits::rememberedIn(self::data($row))) {
            Auth::guard()->renewRememberToken();
        }

        return true;
    }

    /** Signs out all the user's other sessions, and "Keep me signed in" on every other device. */
    public static function endOthers(Request $request, string $password): void
    {
        // Laravel's own: a new hash of the same password, so AuthenticateSession ends the other sessions and the
        // other devices' remember cookies stop working. Deleting their records ends them at once.
        Auth::logoutOtherDevices($password);
        if (config('session.driver') === 'database') {
            self::table()
                ->where('user_id', $request->user()->getAuthIdentifier())
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }
    }

    /** What the page calls a session: an HMAC of its id. */
    public static function ref(string $sessionId): string
    {
        return substr(hash_hmac('sha256', $sessionId, (string) config('app.key')), 0, 32);
    }

    /** "Chrome on Windows", "Safari on iPhone": from the browser's user agent. */
    public static function describe(?string $agent): string
    {
        $agent = (string) $agent;
        $browser = match (true) {
            (bool) preg_match('/Edg(A|iOS)?\//', $agent) => 'Edge',
            str_contains($agent, 'OPR/') => 'Opera',
            str_contains($agent, 'SamsungBrowser/') => 'Samsung Internet',
            (bool) preg_match('/Firefox\/|FxiOS\//', $agent) => 'Firefox',
            (bool) preg_match('/Chrome\/|CriOS\//', $agent) => 'Chrome',
            str_contains($agent, 'Safari/') => 'Safari',
            default => null,
        };
        $system = match (true) {
            str_contains($agent, 'iPhone') => 'iPhone',
            str_contains($agent, 'iPad') => 'iPad',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'CrOS') => 'ChromeOS',
            str_contains($agent, 'Macintosh') => 'Mac',
            str_contains($agent, 'Linux') => 'Linux',
            default => null,
        };

        return match (true) {
            $browser !== null && $system !== null => "$browser on $system",
            $browser !== null => $browser,
            $system !== null => "A browser on $system",
            default => 'Unknown browser',
        };
    }

    /** Still signed in? Its record can outlive the sign-in until its next request or the clean-up. */
    private static function stillSignedIn(User $user, object $row): bool
    {
        if ($row->last_activity <= now()->subMinutes((int) config('session.lifetime'))->getTimestamp()) {
            return false;   // expired, not yet swept away
        }
        $data = self::data($row);
        $hash = $data['password_hash_' . Auth::getDefaultDriver()] ?? null;
        $password = $user->getAuthPassword();
        if ($hash !== null && ! hash_equals(Auth::guard()->hashPasswordForCookie($password), $hash) && ! hash_equals($password, $hash)) {
            return false;   // the password has changed since: AuthenticateSession ends it at its next request
        }

        return ! SessionLimits::reachedIn($user, $data);
    }

    private static function rows(User $user): Collection
    {
        if (config('session.driver') !== 'database') {
            return collect();
        }

        return self::table()->where('user_id', $user->getAuthIdentifier())->get();
    }

    private static function table()
    {
        return DB::connection(config('session.connection'))->table(config('session.table', 'sessions'));
    }

    /** A session's stored data, as Laravel's session store reads it (objects in it are not rebuilt). */
    private static function data(object $row): array
    {
        $payload = base64_decode($row->payload);
        $data = config('session.serialization', 'php') === 'json'
            ? json_decode($payload, true)
            : @unserialize($payload, ['allowed_classes' => false]);

        return is_array($data) ? $data : [];
    }
}
