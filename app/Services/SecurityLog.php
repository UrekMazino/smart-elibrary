<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * The security event log (OWASP ASVS V16; SECURITY_NOTES.md section 2): sign-ins, failures, lockouts, refused pages,
 * role, permission, ban and password changes, and sessions ended for someone. One file, storage/logs/security.log
 * (config/logging.php, channel "security"), only ever added to; a line is
 *
 *     2026-10-29T03:15:00.123456+00:00 sign-in.failed {"email":"…","ip":"…","agent":"…","url":"POST /login"}
 *
 * in time order, so a day is found by binary search rather than by reading the whole year. prune() removes the lines
 * older than a year from the top, in place, nightly (security-log:prune). Read on Administration → Accounts →
 * Security Log (developer and super admin). Never a password, a token or a session id.
 */
class SecurityLog
{
    /** What each event is called on the page, grouped as the page's filter groups them. */
    public const EVENTS = [
        'Signing in' => [
            'sign-in' => 'Signed in',
            'sign-in.failed' => 'Wrong email or password',
            'sign-in.locked' => 'Locked out (too many tries)',
            'sign-in.refused' => 'Sign-in refused',
            'sign-out' => 'Signed out',
        ],
        'Accounts' => [
            'sign-up' => 'Account made',
            'password.changed' => 'Password changed',
            'account.banned' => 'Banned',
            'account.unbanned' => 'Ban lifted',
            'account.blocked' => 'Blocked',
            'account.unblocked' => 'Unblocked',
            'account.deleted' => 'Account deleted',
            'sessions.ended' => 'Sessions signed out',
        ],
        'Roles and permissions' => [
            'role.changed' => 'Role changed',
            'role.permission' => 'Role’s permission changed',
            'role.created' => 'Role created',
            'role.deleted' => 'Role deleted',
            'permission.created' => 'Permission created',
            'permission.deleted' => 'Permission deleted',
        ],
        'Refused pages' => [
            'access.denied' => 'Page refused',
        ],
    ];

    /** Entries the page reads at most for one range (a day is ~700); past it, it asks for a shorter range. */
    public const READ_LIMIT = 50000;

    public static function path(): string
    {
        return (string) config('logging.channels.security.path');
    }

    /**
     * Writes an event: $user is the account it is about (if any); the staff member doing it is added as "by" when
     * that is someone else; the address, browser and page are this request's. A failure to write is reported,
     * never thrown: the log must not stop anyone signing in.
     */
    public static function record(string $event, ?User $user = null, array $details = [], ?string $email = null): void
    {
        try {
            $request = request();
            $by = Auth::guard('web')->id();
            $context = array_filter([
                'user' => $user?->getKey(),
                'email' => $email ?? $user?->email,
                'by' => $by && $by !== $user?->getKey() ? $by : null,
            ] + $details + [
                'ip' => $request->ip(),
                'agent' => Str::limit((string) $request->userAgent(), 200, ''),
                'url' => $request->method() . ' /' . ltrim($request->path(), '/'),
            ], fn ($value) => $value !== null && $value !== '');

            Log::channel('security')->info($event, $context);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** The label for an event ("Wrong email or password"), or the event's own name for one the page doesn't know. */
    public static function label(string $event): string
    {
        foreach (self::EVENTS as $events) {
            if (isset($events[$event])) {
                return $events[$event];
            }
        }

        return $event;
    }

    /** One line about what happened, from an entry's details ("with Google", "librarian → super_admin"…). */
    public static function describe(string $event, array $data): string
    {
        return match ($event) {
            'sign-in' => ['password' => 'with the password', 'google' => 'with Google', 'remembered' => 'kept signed in on this device',
                'sign-up' => 'right after signing up'][$data['method'] ?? ''] ?? '',
            'sign-in.failed' => isset($data['user']) ? '' : 'no account with this address',
            'sign-in.refused' => $data['reason'] ?? '',
            'access.denied' => trim(($data['url'] ?? '') . (isset($data['reason']) ? ' · ' . $data['reason'] : '')),
            'sign-up' => trim(($data['how'] ?? '') . (isset($data['role']) ? ' · role ' . $data['role'] : '')),
            'password.changed' => $data['how'] ?? '',
            'role.changed' => ($data['from'] ?? 'none') . ' → ' . ($data['to'] ?? 'none'),
            'role.permission' => (($data['granted'] ?? false) ? 'gave ' : 'took away ') . ($data['permission'] ?? '?') . (($data['granted'] ?? false) ? ' to ' : ' from ') . ($data['role'] ?? '?'),
            'role.created', 'role.deleted' => $data['role'] ?? '',
            'permission.created', 'permission.deleted' => $data['permission'] ?? '',
            'sessions.ended' => trim(($data['scope'] ?? '') . (isset($data['count']) ? ' · ' . $data['count'] . ' ' . Str::plural('person', $data['count']) : '')),
            default => '',
        };
    }

    /**
     * The entries from $from to $to (inclusive), oldest first: each ['at' => Carbon in UTC, 'event' => …, 'data' => […]].
     * Stops at READ_LIMIT; $more then says there were more.
     */
    public static function between(Carbon $from, Carbon $to, ?bool &$more = null): Collection
    {
        $more = false;
        $entries = collect();
        $fh = @fopen(self::path(), 'r');
        if (! $fh) {
            return $entries;
        }
        try {
            flock($fh, LOCK_SH);   // not while prune() is moving lines
            $size = fstat($fh)['size'];
            fseek($fh, self::firstAtOrAfter($fh, $size, self::stamp($from)));
            $until = self::stamp($to);
            while (($line = fgets($fh)) !== false) {
                if (strncmp($line, $until, 32) > 0) {
                    break;
                }
                if ($entries->count() >= self::READ_LIMIT) {
                    $more = true;
                    break;
                }
                if ($entry = self::parse($line)) {
                    $entries->push($entry);
                }
            }
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }

        return $entries;
    }

    /**
     * Removes the entries older than $before from the top of the file, in place: the rest moves up and the file is
     * shortened. Writers wait meanwhile (they lock the file to add a line), and their next line goes at the end.
     * Returns how many entries went.
     */
    public static function prune(Carbon $before): int
    {
        $fh = @fopen(self::path(), 'r+');
        if (! $fh) {
            return 0;
        }
        try {
            flock($fh, LOCK_EX);
            $size = fstat($fh)['size'];
            $keepFrom = self::firstAtOrAfter($fh, $size, self::stamp($before));
            if ($keepFrom === 0) {
                return 0;
            }

            $removed = 0;
            for ($at = 0; $at < $keepFrom; $at += 1 << 20) {
                fseek($fh, $at);
                $removed += substr_count((string) fread($fh, min(1 << 20, $keepFrom - $at)), "\n");
            }
            for ($read = $keepFrom, $write = 0; $read < $size; $read += strlen($chunk), $write += strlen($chunk)) {
                fseek($fh, $read);
                $chunk = (string) fread($fh, 1 << 20);
                if ($chunk === '') {
                    break;
                }
                fseek($fh, $write);
                fwrite($fh, $chunk);
            }
            fflush($fh);
            ftruncate($fh, $size - $keepFrom);

            return $removed;
        } finally {
            flock($fh, LOCK_UN);
            fclose($fh);
        }
    }

    /** A time as the log writes it, to compare with a line's first 32 characters. */
    private static function stamp(Carbon $at): string
    {
        return $at->copy()->utc()->format('Y-m-d\TH:i:s.uP');
    }

    /** @return array{at: Carbon, event: string, data: array}|null */
    private static function parse(string $line): ?array
    {
        $parts = explode(' ', rtrim($line, "\n"), 3);
        if (count($parts) < 2) {
            return null;
        }
        try {
            $at = Carbon::createFromFormat('Y-m-d\TH:i:s.uP', $parts[0]);
        } catch (Throwable) {
            return null;
        }
        $data = json_decode($parts[2] ?? '[]', true);

        return ['at' => $at->utc(), 'event' => $parts[1], 'data' => is_array($data) ? $data : []];
    }

    /**
     * The offset of the first line at or after $stamp (or the file's size), by binary search over byte offsets:
     * the lines are in time order. before($b): the line starting at or after byte $b is earlier than $stamp.
     */
    private static function firstAtOrAfter($fh, int $size, string $stamp): int
    {
        $lineStart = function (int $b) use ($fh, $size): int {
            if ($b <= 0) {
                return 0;
            }
            if ($b >= $size) {
                return $size;
            }
            fseek($fh, $b - 1);
            fgets($fh);   // to the end of the line holding byte $b - 1

            return min((int) ftell($fh), $size);
        };
        $before = function (int $b) use ($fh, $size, $lineStart, $stamp): bool {
            $start = $lineStart($b);
            if ($start >= $size) {
                return false;
            }
            fseek($fh, $start);

            return strncmp((string) fgets($fh), $stamp, 32) < 0;
        };

        $lo = 0;
        $hi = $size;
        while ($lo < $hi) {
            $mid = intdiv($lo + $hi, 2);
            $before($mid) ? $lo = $mid + 1 : $hi = $mid;
        }

        return $lineStart($lo);
    }
}
