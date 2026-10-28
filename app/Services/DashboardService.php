<?php

namespace App\Services;

use App\Models\User;
use App\Services\Rankings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Data for the admin dashboard (admin/index).
 *
 *   overview() — the "needs attention" counts and the library-at-a-glance cards, rendered
 *                with the page (not affected by the date range).
 *   activity() — everything scoped to the selected date range (KPIs with the previous-period
 *                comparison, the trend series, top materials), fetched by the page as JSON
 *                whenever the range changes.
 *
 * A "patron" is a non-deleted user with usertype Patron; a "title" is a non-deleted catalog.
 */
class DashboardService
{
    /** Date-range presets shown in the dashboard's range menu (key => label). */
    public const RANGES = [
        '7d'  => 'Last 7 days',
        '30d' => 'Last 30 days',
        '90d' => 'Last 90 days',
        '12m' => 'Last 12 months',
        'all' => 'All time',
    ];

    /** SQL DATE_FORMAT / PHP date format / axis label format per bucket size. */
    private const BUCKETS = [
        'day'   => ['%Y-%m-%d', 'Y-m-d', 'M j'],
        'month' => ['%Y-%m', 'Y-m', 'M Y'],
        'year'  => ['%Y', 'Y', 'Y'],
    ];

    public static function overview(User $user): array
    {
        $gate = Gate::forUser($user);
        $now  = Carbon::now();

        // --- Needs attention: each tile links to the page that resolves it (only if the user can open it).
        $attention = [
            [
                'icon' => 'clock', 'tone' => 'warning', 'label' => 'Pending reservations', 'hint' => 'Waiting for approval',
                'count' => DB::table('circulations')->where('status', 1)->count(),
                'url' => $gate->allows('access.circulation') && $gate->allows('circulation.reservation.view') ? url('/admin/reservations') : null,
            ],
            [
                'icon' => 'alert-triangle', 'tone' => 'danger', 'label' => 'Overdue loans', 'hint' => 'Past their due date',
                'count' => DB::table('circulations')->where('status', 3)->where('due_date', '<', $now)->count(),
                'url' => $gate->allows('access.circulation') && $gate->allows('access.circulation.transaction') ? url('/admin/borrowed') : null,
            ],
            [
                'icon' => 'message-square', 'tone' => 'info', 'label' => 'Unread inquiries', 'hint' => 'New patron messages',
                'count' => count(NotificationService::unreadInquiryIds($user)),
                'url' => $gate->allows('access.inquiry') ? url('/admin/inquiry') : null,
            ],
            [
                'icon' => 'pause-circle', 'tone' => 'neutral', 'label' => 'On-hold titles', 'hint' => 'Not yet published',
                'count' => DB::table('catalogs')->whereNull('deleted_at')->where('is_published', 0)->count(),
                'url' => $gate->allows('view.catalog.list') ? url('/admin/on-hold-list') : null,
            ],
        ];
        // Only for staff who may open the statistics watch: what was unusual today (StatisticsWatch::toCheck).
        if ($gate->allows('access.statistics.watch')) {
            $attention[] = [
                'icon' => 'eye', 'tone' => 'warning', 'label' => 'Statistics watch', 'hint' => 'Unusual activity today',
                'count' => StatisticsWatch::toCheck(today()), 'url' => route('admin.statistics-watch'),
            ];
        }

        // --- Collection
        $titles    = DB::table('catalogs')->whereNull('deleted_at')->count();
        $published = DB::table('catalogs')->whereNull('deleted_at')->where('is_published', 1)->count();
        $fulltext  = DB::table('multimedia as m')->join('catalogs as c', 'c.id', '=', 'm.catalog_id')
            ->whereNull('m.deleted_at')->whereNull('c.deleted_at')->distinct()->count('m.catalog_id');
        $covers    = DB::table('frontpages as f')->join('catalogs as c', 'c.id', '=', 'f.catalog_id')
            ->whereNull('f.deleted_at')->whereNull('c.deleted_at')->distinct()->count('f.catalog_id');
        $activeLoans = DB::table('circulations')->where('status', 3)->count();

        $byType = DB::table('catalogs as c')
            ->leftJoin('materials as m', 'm.id', '=', 'c.material_type_id')
            ->whereNull('c.deleted_at')
            ->groupBy('c.material_type_id', 'm.name')
            ->orderByDesc('total')
            ->get([DB::raw("COALESCE(m.name, 'Unassigned') as name"), DB::raw('COUNT(*) as total')]);

        // --- Patrons
        $patrons = fn () => DB::table('users')->where('usertype', 'Patron')->whereNull('deleted_at');
        $byCategory = DB::table('users as u')
            ->leftJoin('patrontypes as p', 'p.id', '=', 'u.patrontypes_id')
            ->where('u.usertype', 'Patron')->whereNull('u.deleted_at')
            ->groupBy('u.patrontypes_id', 'p.types')
            ->orderByDesc('total')
            ->get([DB::raw("COALESCE(p.types, 'Not set') as name"), DB::raw('COUNT(*) as total')]);

        // --- Recent activity lists
        $reviews = DB::table('ratings as r')
            ->leftJoin('users as u', 'u.id', '=', 'r.user_id')
            ->leftJoin('view_catalogs as v', 'v.catalog_id', '=', 'r.catalog_id')
            ->orderByDesc('r.created_at')->orderByDesc('r.id')
            ->limit(5)
            ->get(['r.catalog_id', 'r.rating', 'r.remarks', 'r.created_at', 'u.name', 'v.title_statement'])
            ->map(fn ($r) => [
                'catalog_id' => (int) $r->catalog_id,
                'rating'     => (int) $r->rating,
                'remarks'    => trim((string) $r->remarks),
                'name'       => $r->name ?: 'Former patron',
                'title'      => self::splitTitle($r->title_statement)['title'] ?: 'Removed material',
                'when'       => Carbon::parse($r->created_at),
            ]);

        $newPatrons = DB::table('users as u')
            ->leftJoin('patrontypes as p', 'p.id', '=', 'u.patrontypes_id')
            ->where('u.usertype', 'Patron')->whereNull('u.deleted_at')
            ->orderByDesc('u.created_at')->orderByDesc('u.id')
            ->limit(5)
            ->get(['u.name', 'u.email', 'u.created_at', 'u.is_banned', 'u.is_blocked', 'p.types'])
            ->map(fn ($u) => [
                'name'     => $u->name,
                'email'    => $u->email,
                'type'     => $u->types ?: 'Not set',
                'when'     => Carbon::parse($u->created_at),
                'status'   => $u->is_banned ? 'Banned' : ($u->is_blocked ? 'Blocked' : null),
            ]);

        return [
            'firstName'  => trim(explode(' ', (string) $user->name)[0]),
            'attention'  => $attention,
            'collection' => compact('titles', 'published', 'fulltext', 'covers', 'activeLoans') + ['onHold' => $titles - $published],
            'byType'     => $byType,
            'patrons'    => [
                'total'   => $patrons()->count(),
                'banned'  => $patrons()->where('is_banned', 1)->count(),
                'blocked' => $patrons()->where('is_blocked', 1)->count(),
            ],
            'byCategory' => $byCategory,
            'reviews'    => $reviews,
            'newPatrons' => $newPatrons,
            'links'      => [
                'record'     => $gate->allows('access.cataloging') ? url('/admin/view-record') : null,
                'patrons'    => $gate->allows('access.accounts') ? url('/admin/patron') : null,
                'onHold'     => $gate->allows('view.catalog.list') ? url('/admin/on-hold-list') : null,
                'newRecord'  => $gate->allows('create.catalog') ? url('/admin/create-record') : null,
                'newPatron'  => $gate->allows('access.accounts') && $gate->allows('create.patron') ? url('/admin/register') : null,
                'reports'    => $gate->allows('access.reports') ? url('/admin/report') : null,
                'rankings'   => $gate->allows('access.utilities') && $gate->allows('access.rankings') ? route('utilities.rankings') : null,
            ],
        ];
    }

    /**
     * Activity in a date range: preset key ('7d', '30d', '90d', '12m', 'all') or 'custom' with
     * from/to (Y-m-d). Cached for two minutes per range — the views table has no created_at index.
     */
    public static function activity(string $range, ?string $from = null, ?string $to = null): array
    {
        $key = 'dash_activity:' . md5($range . '|' . $from . '|' . $to . '|' . Carbon::today()->toDateString());

        $activity = cache()->remember($key, 120, fn () => self::computeActivity($range, $from, $to));

        // Top materials is Utilities → Rankings' lists, from the same queries and worked out now (not from the
        // 2-minute copy above), so the two always agree. Rankings has two windows: all time for "All time",
        // else the home page's last rankingDays() days.
        $activity['top'] = self::rankingsTop($range === 'all' ? 'all' : 'recent');

        return $activity;
    }

    /** The first five of each top-10 list (App\Services\Rankings) for the Top materials panel, with the window. */
    private static function rankingsTop(string $window, int $limit = 5): array
    {
        $item = fn ($r, $count, string $detail) => self::splitTitle($r->title_statement) + ['id' => (int) $r->catalog_id, 'count' => $count, 'detail' => $detail];
        $plural = fn (int $n, string $word) => number_format($n) . ' ' . ($n === 1 ? $word : $word . 's');

        return [
            'window'     => ['key' => $window, 'label' => rankingWindowLabel($window)],
            'viewed'     => Rankings::viewed($window, $limit)->map(fn ($r) => $item($r, (int) $r->total, number_format((int) $r->guests) . ' by ' . ((int) $r->guests === 1 ? 'guest' : 'guests')))->all(),
            'downloaded' => Rankings::downloaded($window, $limit)->map(fn ($r) => $item($r, (int) $r->total, $plural((int) $r->files, 'file')))->all(),
            'rated'      => Rankings::rated($window, $limit)->map(fn ($r) => $item($r, round((float) $r->score, 2),
                '★ ' . number_format((float) $r->rating, 1) . ' · ' . $plural((int) $r->votes, 'rating')))->all(),
        ];
    }

    private static function computeActivity(string $range, ?string $from, ?string $to): array
    {
        [$start, $end, $bucket, $label] = self::resolveRange($range, $from, $to);

        // The previous period: the same number of buckets immediately before $start (none for all time).
        $prev = null;
        if ($range !== 'all') {
            $n = count(self::bucketKeys($start, $end, $bucket));
            $prevStart = $start->copy()->sub($n, $bucket);
            $prev = [$prevStart, $start->copy()->subSecond()];
        }

        $patronScope = fn ($q) => $q->where('usertype', 'Patron')->whereNull('deleted_at');
        $metrics = [
            'views'     => ['views', null],
            'downloads' => ['downloads', null],
            'reads'     => ['reads', null],
            'patrons'   => ['users', $patronScope],
        ];

        $kpis = [];
        foreach ($metrics as $name => [$table, $scope]) {
            $series   = self::series($table, $start, $end, $bucket, $scope);
            $previous = $prev ? self::series($table, $prev[0], $prev[1], $bucket, $scope) : null;
            $kpis[$name] = [
                'value'    => array_sum($series),
                'previous' => $previous !== null ? array_sum($previous) : null,
                'series'   => $series,
                'prevSeries' => $previous,
            ];
        }

        $guestViews = DB::table('views')->whereBetween('created_at', [$start, $end])->where('is_guest', 'YES')->count();

        return [
            'range' => [
                'key'     => $range,
                'label'   => $label,
                'from'    => $start->toDateString(),
                'to'      => $end->toDateString(),
                'bucket'  => $bucket,
                'compare' => $prev ? 'previous ' . self::spanLabel($start, $end, $bucket) : null,
            ],
            'labels'     => self::labels($start, $end, $bucket),
            'prevLabels' => $prev ? self::labels($prev[0], $prev[1], $bucket) : null,
            'kpis'       => $kpis,
            'guestShare' => $kpis['views']['value'] ? round($guestViews / $kpis['views']['value'] * 100) : null,
        ];
    }

    /** [start, end, bucket, label] for a preset or a custom from/to range. */
    private static function resolveRange(string $range, ?string $from, ?string $to): array
    {
        $today = Carbon::today();

        switch ($range) {
            case '7d':  return [$today->copy()->subDays(6), $today->copy()->endOfDay(), 'day', self::RANGES['7d']];
            case '30d': return [$today->copy()->subDays(29), $today->copy()->endOfDay(), 'day', self::RANGES['30d']];
            case '90d': return [$today->copy()->subDays(89), $today->copy()->endOfDay(), 'day', self::RANGES['90d']];
            case '12m': return [$today->copy()->startOfMonth()->subMonths(11), $today->copy()->endOfDay(), 'month', self::RANGES['12m']];
            case 'all':
                // From the month of the earliest recorded activity (or this month when there is none).
                $first = collect([
                    DB::table('views')->min('created_at'),
                    DB::table('downloads')->min('created_at'),
                    DB::table('reads')->min('created_at'),
                    DB::table('users')->where('usertype', 'Patron')->whereNull('deleted_at')->min('created_at'),
                ])->filter()->map(fn ($d) => Carbon::parse($d))->min() ?? $today->copy();
                $start = $first->copy()->startOfMonth();
                $bucket = $start->diffInMonths($today) >= 36 ? 'year' : 'month';
                if ($bucket === 'year') $start->startOfYear();
                return [$start, $today->copy()->endOfDay(), $bucket, self::RANGES['all']];
        }

        // Custom range (validated as Y-m-d, from <= to by the controller). Never past today.
        $start = Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
        $end   = Carbon::createFromFormat('Y-m-d', $to)->endOfDay()->min($today->copy()->endOfDay());
        if ($start->gt($end)) $start = $end->copy()->startOfDay();
        $days   = $start->diffInDays($end) + 1;
        $bucket = $days <= 92 ? 'day' : ($start->diffInMonths($end) < 36 ? 'month' : 'year');

        return [$start, $end, $bucket, $start->format('M j, Y') . ' – ' . $end->format('M j, Y')];
    }

    /** Counts per bucket between $start and $end, zero-filled, in bucket order. */
    private static function series(string $table, Carbon $start, Carbon $end, string $bucket, ?callable $scope = null): array
    {
        $q = DB::table($table)->whereBetween('created_at', [$start, $end]);
        if ($scope) $scope($q);
        $rows = $q->selectRaw("DATE_FORMAT(created_at, '" . self::BUCKETS[$bucket][0] . "') as b, COUNT(*) as n")
            ->groupBy('b')
            ->pluck('n', 'b');

        return array_map(fn ($k) => (int) ($rows[$k] ?? 0), self::bucketKeys($start, $end, $bucket));
    }

    /** Bucket keys (PHP date format of the bucket) covering $start..$end. */
    private static function bucketKeys(Carbon $start, Carbon $end, string $bucket): array
    {
        $keys = [];
        $cursor = $start->copy()->{'startOf' . ucfirst($bucket)}();
        while ($cursor->lte($end)) {
            $keys[] = $cursor->format(self::BUCKETS[$bucket][1]);
            $cursor->add(1, $bucket);
        }

        return $keys;
    }

    /** Axis labels for each bucket ("Oct 14" / "Oct 2026" / "2026"). */
    private static function labels(Carbon $start, Carbon $end, string $bucket): array
    {
        $suffix = ['day' => '', 'month' => '-01', 'year' => '-01-01'][$bucket];

        return array_map(fn ($k) => Carbon::createFromFormat('Y-m-d', $k . $suffix)->format(self::BUCKETS[$bucket][2]),
            self::bucketKeys($start, $end, $bucket));
    }

    /** "7 days" / "12 months" / "3 years" — names the comparison period. */
    private static function spanLabel(Carbon $start, Carbon $end, string $bucket): string
    {
        $n = count(self::bucketKeys($start, $end, $bucket));

        return $n . ' ' . ($n === 1 ? $bucket : $bucket . 's');
    }

    /** ['title' => title proper, 'by' => statement of responsibility] from a 245 title statement. */
    private static function splitTitle($statement): array
    {
        $plain = seoTrimIsbd(html_entity_decode((string) $statement, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        [$title, $by] = array_pad(preg_split('#\s+/\s+#u', $plain, 2), 2, '');

        return ['title' => seoTrimIsbd($title), 'by' => trim($by)];
    }
}
