<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The statistics watch (Administration → Accounts → Statistics watch; RANKING_INTEGRITY_PLAN.md step 2.6): what was
 * unusual on a day, compared with the 30 days before it, for staff to look at and decide.
 *  - spikes(): materials with far more viewers or readers that day than on their usual day. They count what the
 *    top-10 lists count (rankingRows()), so taking out the accounts behind a spike clears it; a spike made by
 *    guests stays, and shows from how many addresses it came.
 *  - accounts(): patrons who viewed, downloaded or rated far more in the day than a reader does.
 *  - guests(): guests (views.visitor_hash) who viewed far more materials in the day than a reader does.
 *  - signUps(): the day's sign-ups, and the hours with far more than usual (bursts), with their accounts.
 * Guests' IP addresses (visitor_addresses) are loaded only for developer and super admin ($addresses: canSeeAddresses()).
 * exclude() and putBack() take patrons' views, downloads and ratings out of the top-10 lists and back
 * (ranking_exclusions, with who did it and when). The thresholds are the constants below
 * (docs/implemented/RANKING_INTEGRITY.md section 6).
 */
class StatisticsWatch
{
    /** The days before the watched day that make its usual day. */
    public const BASELINE_DAYS = 30;

    /** A material's day is a spike at this many times its usual day, with at least SPIKE_AT_LEAST viewers or readers. */
    public const SPIKE_TIMES = 5;
    public const SPIKE_AT_LEAST = 10;

    /** An account is flagged at this many materials viewed, files downloaded or ratings in a day. */
    public const ACCOUNT_VIEWS = 100;
    public const ACCOUNT_DOWNLOADS = 20;
    public const ACCOUNT_RATINGS = 10;

    /** A guest is flagged at this many materials viewed in a day (a guest's row is one material a day). */
    public const GUEST_VIEWS = 100;

    /** An hour is a sign-up burst at BURST_AT_LEAST sign-ups and BURST_TIMES times the usual hour. */
    public const BURST_AT_LEAST = 10;
    public const BURST_TIMES = 5;

    /** Guests' IP addresses are for developer and super admin accounts only, whoever else has the watch (decided 2026-10-28). */
    public static function canSeeAddresses(?User $user): bool
    {
        return (bool) $user?->hasAnyRole(['developer', 'super_admin']);
    }

    /** The day to watch, from ?day=Y-m-d: today when it is missing, not a date, or still to come. */
    public static function day(mixed $value): Carbon
    {
        $today = today();
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return $today;
        }
        try {
            $day = Carbon::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return $today;
        }

        return $day->format('Y-m-d') === $value && $day->lte($today) ? $day : $today;
    }

    /**
     * Materials with a spike in views (viewer-days, as Most Viewed counts them) or downloads (readers), most unusual
     * first: catalog_id, title, link, what ('views' or 'downloads'), count, usual (a day, over BASELINE_DAYS),
     * times (null: none before), accounts, guests, files, people (the accounts behind it, as people()), and for views
     * with $addresses: addresses (ip_address => guests, busiest first; guests from before hashes were kept have none).
     */
    public static function spikes(Carbon $day, bool $addresses = false): Collection
    {
        [$from, $to, $since] = self::range($day);
        $counted = fn (string $table) => rankingRows(DB::table("$table as t"), 't', 'all');

        $views = $counted('views')->whereBetween('t.created_at', [$from, $to])->groupBy('t.catalog_id')
            ->select('t.catalog_id')->selectRaw(viewerDaysSql('t') . ' as n, COUNT(DISTINCT t.user_id) as accounts, SUM(t.user_id IS NULL) as guests')
            ->having('n', '>=', self::SPIKE_AT_LEAST)->get();
        $downloads = $counted('downloads')->whereBetween('t.created_at', [$from, $to])->groupBy('t.catalog_id')
            ->select('t.catalog_id')->selectRaw('COUNT(DISTINCT t.user_id) as n, COUNT(DISTINCT t.user_id) as accounts, 0 as guests, COUNT(*) as files')
            ->having('n', '>=', self::SPIKE_AT_LEAST)->get();

        // Their usual day: viewer-days, or reader-days, over the days before.
        $usual = fn (string $table, string $sql, Collection $rows) => $rows->isEmpty() ? collect() : $counted($table)
            ->whereIn('t.catalog_id', $rows->pluck('catalog_id'))->where('t.created_at', '>=', $since)->where('t.created_at', '<', $from)
            ->groupBy('t.catalog_id')->selectRaw("t.catalog_id, $sql as n")->pluck('n', 'catalog_id');
        $before = [
            'views' => $usual('views', viewerDaysSql('t'), $views),
            'downloads' => $usual('downloads', 'COUNT(DISTINCT t.user_id, DATE(t.created_at))', $downloads),
        ];

        $spikes = collect(['views' => $views, 'downloads' => $downloads])
            ->flatMap(fn (Collection $rows, string $what) => $rows->map(function ($r) use ($what, $before) {
                $usual = (int) ($before[$what][$r->catalog_id] ?? 0) / self::BASELINE_DAYS;
                return (object) [
                    'catalog_id' => (int) $r->catalog_id, 'what' => $what, 'count' => (int) $r->n, 'usual' => $usual,
                    'times' => $usual > 0 ? $r->n / $usual : null,
                    'accounts' => (int) $r->accounts, 'guests' => (int) $r->guests, 'files' => (int) ($r->files ?? 0),
                ];
            }))
            ->filter(fn ($s) => $s->count >= self::SPIKE_TIMES * $s->usual)
            ->sortByDesc(fn ($s) => [$s->times ?? INF, $s->count])->values();

        $titles = DB::table('view_catalogs')->whereIn('catalog_id', $spikes->pluck('catalog_id'))->pluck('title_statement', 'catalog_id');

        return $spikes->each(function ($s) use ($titles, $counted, $from, $to, $day, $addresses) {
            $title = seoTrimIsbd(html_entity_decode((string) ($titles[$s->catalog_id] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $s->title = preg_split('#\s+/\s+#u', $title, 2)[0] ?: 'Removed material';
            $s->link = isset($titles[$s->catalog_id]) ? url(recordPath(['catalog_id' => $s->catalog_id, 'title_statement' => $titles[$s->catalog_id]])) : null;
            $s->people = self::people($counted($s->what)->where('t.catalog_id', $s->catalog_id)->whereBetween('t.created_at', [$from, $to])
                ->whereNotNull('t.user_id')->distinct()->limit(500)->pluck('t.user_id'), $day);
            $s->addresses = $addresses && $s->what === 'views' && $s->guests
                ? $counted('views')->join('visitor_addresses as va', 'va.visitor_hash', '=', 't.visitor_hash')
                    ->where('t.catalog_id', $s->catalog_id)->whereBetween('t.created_at', [$from, $to])
                    ->groupBy('va.ip_address')->orderByDesc('n')->limit(20)->selectRaw('va.ip_address, COUNT(*) as n')->pluck('n', 'ip_address')
                : collect();
        });
    }

    /**
     * Guests (by their hash) who viewed GUEST_VIEWS materials or more in the day, busiest first: hash, code (its
     * first 8 characters, to tell them apart on the page), views, first and last (that day), capped (reached
     * viewCapPerHour() in an hour), and with $addresses, address (null once deleted after 90 days).
     */
    public static function guests(Carbon $day, bool $addresses = false): Collection
    {
        [$from, $to] = self::range($day);
        $guests = DB::table('views')->whereBetween('created_at', [$from, $to])->whereNotNull('visitor_hash')
            ->groupBy('visitor_hash')->having('n', '>=', self::GUEST_VIEWS)
            ->selectRaw('visitor_hash, COUNT(*) as n, MIN(created_at) as first, MAX(created_at) as last')
            ->orderByDesc('n')->get();
        if ($guests->isEmpty()) {
            return collect();
        }
        $hours = DB::table('views')->whereBetween('created_at', [$from, $to])->whereIn('visitor_hash', $guests->pluck('visitor_hash'))
            ->get(['visitor_hash', 'created_at'])->groupBy('visitor_hash')
            ->map(fn ($rows) => $rows->countBy(fn ($r) => Carbon::parse($r->created_at)->hour)->max());
        $ips = $addresses ? DB::table('visitor_addresses')->whereIn('visitor_hash', $guests->pluck('visitor_hash'))->pluck('ip_address', 'visitor_hash') : collect();

        return $guests->map(fn ($g) => (object) [
            'hash' => $g->visitor_hash, 'code' => substr($g->visitor_hash, 0, 8), 'views' => (int) $g->n,
            'first' => Carbon::parse($g->first), 'last' => Carbon::parse($g->last),
            'capped' => ($hours[$g->visitor_hash] ?? 0) >= viewCapPerHour(),
            'address' => $ips[$g->visitor_hash] ?? null,
        ]);
    }

    /**
     * Patrons who passed a threshold that day, busiest first, as people() with views (materials viewed), downloads
     * (files), materials (downloaded), ratings. Banned accounts and accounts already taken out stay listed, with
     * their status.
     */
    public static function accounts(Carbon $day): Collection
    {
        [$from, $to] = self::range($day);
        $byAccount = fn (string $table, string $sql) => DB::table($table)->whereBetween('created_at', [$from, $to])
            ->whereNotNull('user_id')->groupBy('user_id')->selectRaw("user_id, $sql as n")->pluck('n', 'user_id');
        $views = $byAccount('views', 'COUNT(DISTINCT catalog_id)');
        $downloads = $byAccount('downloads', 'COUNT(*)');
        $materials = $byAccount('downloads', 'COUNT(DISTINCT catalog_id)');
        $ratings = $byAccount('ratings', 'COUNT(*)');

        $flagged = $views->filter(fn ($n) => $n >= self::ACCOUNT_VIEWS)->keys()
            ->merge($downloads->filter(fn ($n) => $n >= self::ACCOUNT_DOWNLOADS)->keys())
            ->merge($ratings->filter(fn ($n) => $n >= self::ACCOUNT_RATINGS)->keys());

        return self::people($flagged, $day)->each(function ($p) use ($views, $downloads, $materials, $ratings) {
            $p->views = (int) ($views[$p->id] ?? 0);
            $p->downloads = (int) ($downloads[$p->id] ?? 0);
            $p->materials = (int) ($materials[$p->id] ?? 0);
            $p->ratings = (int) ($ratings[$p->id] ?? 0);
        })->sortByDesc(fn ($p) => $p->views + $p->downloads + $p->ratings)->values();
    }

    /**
     * The day's patron sign-ups: total, usualDay and usualHour (over BASELINE_DAYS), and bursts: each with from (the
     * hour), count and people (people(), oldest first, with their views, downloads and ratings so far).
     */
    public static function signUps(Carbon $day): object
    {
        [$from, $to, $since] = self::range($day);
        $patrons = fn () => DB::table('users')->where('usertype', 'Patron');   // removed accounts too: they were made
        $made = $patrons()->whereBetween('created_at', [$from, $to])->orderBy('created_at')->get(['id', 'created_at']);
        $before = $patrons()->where('created_at', '>=', $since)->where('created_at', '<', $from)->count();
        $usualHour = $before / (self::BASELINE_DAYS * 24);

        $bursts = $made->groupBy(fn ($u) => Carbon::parse($u->created_at)->hour)
            ->filter(fn ($hour) => $hour->count() >= self::BURST_AT_LEAST && $hour->count() >= self::BURST_TIMES * $usualHour)
            ->map(function (Collection $hour, int $h) use ($day) {
                $people = self::people($hour->pluck('id'), $day);
                $ids = $people->keys();
                $activity = fn (string $table) => DB::table($table)->whereIn('user_id', $ids)->groupBy('user_id')
                    ->selectRaw('user_id, COUNT(*) as n')->pluck('n', 'user_id');
                [$views, $downloads, $ratings] = [$activity('views'), $activity('downloads'), $activity('ratings')];
                $people->each(function ($p) use ($views, $downloads, $ratings) {
                    $p->views = (int) ($views[$p->id] ?? 0);
                    $p->downloads = (int) ($downloads[$p->id] ?? 0);
                    $p->ratings = (int) ($ratings[$p->id] ?? 0);
                });

                return (object) ['from' => $day->copy()->setTime($h, 0), 'count' => $hour->count(), 'people' => $people->sortBy('made')];
            })->values();

        return (object) ['total' => $made->count(), 'usualDay' => $before / self::BASELINE_DAYS, 'usualHour' => $usualHour, 'bursts' => $bursts];
    }

    /** Accounts out of the rankings now, latest first: user_id, name, email, reason, since, by (who took them out). */
    public static function excluded(): Collection
    {
        return DB::table('ranking_exclusions as rx')
            ->join('users as u', 'u.id', '=', 'rx.user_id')
            ->leftJoin('users as by', 'by.id', '=', 'rx.excluded_by')
            ->whereNull('rx.lifted_at')
            ->orderByDesc('rx.created_at')->orderByDesc('rx.id')
            ->get(['rx.user_id', 'u.name', 'u.email', 'rx.reason', 'rx.created_at as since', 'by.name as by'])
            ->each(fn ($row) => $row->since = Carbon::parse($row->since));
    }

    /** Things to look at on the day: spikes, unusual guests, and unusual accounts and bursts with someone not yet dealt with. */
    public static function toCheck(Carbon $day): int
    {
        return self::spikes($day)->count()
            + self::guests($day)->count()
            + self::accounts($day)->whereNull('status')->count()
            + self::signUps($day)->bursts->filter(fn ($b) => $b->people->whereNull('status')->isNotEmpty())->count();
    }

    /**
     * Takes these patrons out of the top-10 lists (staff and unknown ids are skipped, and so are patrons already out).
     * Their views, downloads and ratings stop counting at once. Returns the names of those taken out.
     */
    public static function exclude(array $ids, User $by, ?string $reason): Collection
    {
        $patrons = User::withTrashed()->where('usertype', 'Patron')->whereIn('id', $ids)->pluck('name', 'id');
        $out = DB::table('ranking_exclusions')->whereIn('user_id', $patrons->keys())->whereNull('lifted_at')->pluck('user_id');
        $new = $patrons->except($out->all());
        DB::table('ranking_exclusions')->insert($new->keys()->map(fn ($id) => [
            'user_id' => $id, 'reason' => $reason, 'excluded_by' => $by->id, 'created_at' => now(),
        ])->all());
        if ($new->isNotEmpty()) {
            clearHomeCache();
        }

        return $new->values();
    }

    /** Puts a patron back in the top-10 lists; the row keeps who took them out, and now who put them back. */
    public static function putBack(int $id, User $by): bool
    {
        $done = DB::table('ranking_exclusions')->where('user_id', $id)->whereNull('lifted_at')
            ->update(['lifted_by' => $by->id, 'lifted_at' => now()]) > 0;
        if ($done) {
            clearHomeCache();
        }

        return $done;
    }

    /**
     * Patron accounts, keyed by id: id, name, email, made (or null), google (made or linked with Google), fresh (made
     * in the week up to the day) and status: 'removed', 'banned', 'out' (of the rankings) or null.
     */
    private static function people(Collection $ids, Carbon $day): Collection
    {
        $ids = $ids->filter()->unique()->values();
        if ($ids->isEmpty()) {
            return collect();
        }
        $out = DB::table('ranking_exclusions')->whereIn('user_id', $ids)->whereNull('lifted_at')->pluck('user_id')->flip();
        $weekBefore = $day->copy()->subDays(6)->startOfDay();

        return DB::table('users')->whereIn('id', $ids)->where('usertype', 'Patron')
            ->get(['id', 'name', 'email', 'created_at', 'google_id', 'is_banned', 'deleted_at'])
            ->map(function ($u) use ($out, $weekBefore, $day) {
                $made = $u->created_at ? Carbon::parse($u->created_at) : null;
                return (object) [
                    'id' => (int) $u->id, 'name' => $u->name, 'email' => $u->email, 'made' => $made, 'google' => (bool) $u->google_id,
                    'fresh' => $made && $made->between($weekBefore, $day->copy()->endOfDay()),
                    'status' => $u->deleted_at ? 'removed' : ($u->is_banned ? 'banned' : (isset($out[$u->id]) ? 'out' : null)),
                ];
            })->keyBy('id');
    }

    /** @return array{Carbon, Carbon, Carbon} the day's start and end, and the start of the days before it */
    private static function range(Carbon $day): array
    {
        $from = $day->copy()->startOfDay();

        return [$from, $day->copy()->endOfDay(), $from->copy()->subDays(self::BASELINE_DAYS)];
    }
}
