<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SecurityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Administration → Accounts → Security Log (developer and super admin, by role; App\Services\SecurityLog): the
 * security events of a date range, newest first, filtered by kind and searched by email, IP address or page.
 * Read-only: nothing here changes the log. Dates are Philippine days; the log itself is in UTC.
 */
class SecurityLogController extends Controller
{
    /** The longest range one view reads (a day is ~700 entries). */
    private const MAX_DAYS = 31;

    private const PER_PAGE = 100;

    public function index(Request $request)
    {
        $tz = config('app.timezone');
        $today = now($tz)->startOfDay();
        $to = min(self::date($request->query('to'), $tz) ?? $today, $today);
        $from = self::date($request->query('from'), $tz) ?? $to->copy()->subDays(6);
        $from = min($from, $to);
        $clamped = $from->diffInDays($to) >= self::MAX_DAYS;
        if ($clamped) {
            $from = $to->copy()->subDays(self::MAX_DAYS - 1);
        }
        $group = is_string($request->query('type')) && isset(SecurityLog::EVENTS[$request->query('type')]) ? $request->query('type') : '';
        $search = Str::lower(trim((string) (is_string($request->query('q')) ? $request->query('q') : '')));

        $entries = SecurityLog::between($from->copy()->startOfDay(), $to->copy()->endOfDay(), $more)
            ->when($group !== '', fn ($all) => $all->filter(fn ($e) => isset(SecurityLog::EVENTS[$group][$e['event']])))
            ->when($search !== '', fn ($all) => $all->filter(fn ($e) => str_contains(
                Str::lower($e['event'] . ' ' . json_encode($e['data'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)), $search)))
            ->reverse()->values();

        $pages = max(1, (int) ceil($entries->count() / self::PER_PAGE));
        $page = min(max(1, (int) $request->query('page', 1)), $pages);
        $shown = $entries->slice(($page - 1) * self::PER_PAGE, self::PER_PAGE)->values();
        $ids = $shown->flatMap(fn ($e) => [$e['data']['user'] ?? null, $e['data']['by'] ?? null])->filter()->unique();
        $people = $ids->isEmpty() ? collect() : User::withTrashed()->whereIn('id', $ids)->get(['id', 'name', 'email', 'deleted_at'])->keyBy('id');

        return view('admin.security-log', [
            'entries' => $shown,
            'total' => $entries->count(),
            'more' => $more,
            'page' => $page,
            'pages' => $pages,
            'people' => $people,
            'from' => $from,
            'to' => $to,
            'today' => $today,
            'clamped' => $clamped,
            'group' => $group,
            'search' => trim((string) $request->query('q')),
            'tz' => $tz,
            'maxDays' => self::MAX_DAYS,
        ]);
    }

    /** A Y-m-d day in the app's timezone, or null for anything else. */
    private static function date(mixed $value, string $tz): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        try {
            $day = Carbon::createFromFormat('!Y-m-d', $value, $tz);
        } catch (\Throwable) {
            return null;
        }

        return $day->format('Y-m-d') === $value ? $day : null;
    }
}
