<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The three top-10 lists' queries (docs/implemented/RANKING_INTEGRITY.md). The home page's shelves and medals
 * (getMostDownloaded() and the others in app/Http/helper.php, cached 10 minutes) and Utilities → Rankings (fresh,
 * with more rows and the numbers behind them) read these same ones. Published materials only; the rows that count
 * are rankingRows()'s (no banned accounts, none taken out); $window is 'recent' (the last rankingDays() days) or
 * 'all'. On a tie the older record comes first, so both pages order alike.
 */
class Rankings
{
    /** The home page shows each list's first ten; they earn the medals. */
    public const TOP = 10;

    /** Highest Rating: the votes before a record's own average weighs as much as the library's (m in the formula). */
    public const VOTES_WEIGHT = 3;

    /** Most Downloaded, by readers: catalog_id, title_statement, total (readers), files (downloads, again included), last. */
    public static function downloaded(string $window, int $limit): Collection
    {
        return self::published(rankingRows(DB::table('downloads as d'), 'd', $window), 'd')
            ->select('d.catalog_id', 'v.title_statement')
            ->selectRaw('COUNT(DISTINCT d.user_id) as total, COUNT(*) as files, MAX(d.created_at) as last')
            ->groupBy('d.catalog_id', 'v.title_statement')
            ->orderByDesc('total')->orderBy('d.catalog_id')->limit($limit)->get();
    }

    /** Most Viewed, by viewer-days (viewerDaysSql()): catalog_id, title_statement, total, guests (of the total). */
    public static function viewed(string $window, int $limit): Collection
    {
        return self::published(rankingRows(DB::table('views as vw'), 'vw', $window), 'vw')
            ->select('vw.catalog_id', 'v.title_statement')
            ->selectRaw(viewerDaysSql('vw') . ' as total, SUM(vw.user_id IS NULL) as guests')
            ->groupBy('vw.catalog_id', 'v.title_statement')
            ->orderByDesc('total')->orderBy('vw.catalog_id')->limit($limit)->get();
    }

    /**
     * Highest Rating, by a weighted average so one vote can't top it (the formula IMDb's charts use):
     * score = v/(v+m)·R + m/(v+m)·C, v the record's votes, R its average, C libraryAverage(), m VOTES_WEIGHT.
     * Rows: catalog_id, title_statement, rating (R, to one decimal), votes, score.
     */
    public static function rated(string $window, int $limit): Collection
    {
        $m = self::VOTES_WEIGHT;
        $c = self::libraryAverage($window);

        return self::ratings($window)
            ->select('r.catalog_id', 'v.title_statement')
            ->selectRaw('ROUND(AVG(r.rating), 1) as rating, COUNT(*) as votes, COUNT(*) / (COUNT(*) + ?) * AVG(r.rating) + ? / (COUNT(*) + ?) * ? as score', [$m, $m, $m, $c])
            ->groupBy('r.catalog_id', 'v.title_statement')
            ->orderByDesc('score')->orderByDesc('votes')->orderBy('r.catalog_id')->limit($limit)->get();
    }

    /** C: the average of the window's ratings of published materials (0 with none). */
    public static function libraryAverage(string $window): float
    {
        return (float) (self::ratings($window)->avg('r.rating') ?? 0);
    }

    /**
     * What the window's lists are made of, over published materials: views (viewer-days), guestViews, readers
     * (reader and material pairs), files (downloads), ratings, average (C), and the accounts left out now: banned
     * (patrons), out (taken out on the statistics watch).
     */
    public static function totals(string $window): object
    {
        $views = self::published(rankingRows(DB::table('views as vw'), 'vw', $window), 'vw')
            ->selectRaw(viewerDaysSql('vw') . ' as total, COALESCE(SUM(vw.user_id IS NULL), 0) as guests')->first();
        $downloads = self::published(rankingRows(DB::table('downloads as d'), 'd', $window), 'd')
            ->selectRaw('COUNT(DISTINCT d.user_id, d.catalog_id) as readers, COUNT(*) as files')->first();

        return (object) [
            'views' => (int) $views->total, 'guestViews' => (int) $views->guests,
            'readers' => (int) $downloads->readers, 'files' => (int) $downloads->files,
            'ratings' => self::ratings($window)->count(), 'average' => self::libraryAverage($window),
            'banned' => DB::table('users')->where('usertype', 'Patron')->where('is_banned', 1)->whereNull('deleted_at')->count(),
            'out' => DB::table('ranking_exclusions')->whereNull('lifted_at')->count(),
        ];
    }

    private static function ratings(string $window)
    {
        return self::published(rankingRows(DB::table('ratings as r'), 'r', $window), 'r');
    }

    private static function published($query, string $table)
    {
        return $query->join('view_catalogs as v', 'v.catalog_id', '=', "$table.catalog_id")
            ->where('v.is_published', 1)->whereNull('v.deleted_at');
    }
}
