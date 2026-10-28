<?php

namespace App\Http\Controllers;

use App\Services\Rankings;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Administration → Utilities → Rankings (access.rankings; App\Services\Rankings): the home page's three top-10
 * lists for a window (?window=all, else the last rankingDays() days), each with the next ones down and the numbers
 * behind every rank, worked out fresh; and "Refresh the home page", which empties its 10-minute copy.
 */
class RankingsController extends Controller
{
    /** Each list shows the home page's ten, then the next ones down to this rank. */
    private const ROWS = 15;

    public function index(Request $request)
    {
        $window = $request->query('window') === 'all' ? 'all' : 'recent';
        $lists = [
            ['key' => 'downloaded', 'label' => 'Most Downloaded', 'rows' => Rankings::downloaded($window, self::ROWS)],
            ['key' => 'viewed', 'label' => 'Most Viewed', 'rows' => Rankings::viewed($window, self::ROWS)],
            ['key' => 'rated', 'label' => 'Highest Rating', 'rows' => Rankings::rated($window, self::ROWS)],
        ];
        foreach ($lists as &$list) {
            $list['rows']->each(function ($r) {
                $title = seoTrimIsbd(html_entity_decode((string) $r->title_statement, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                $r->title = preg_split('#\s+/\s+#u', $title, 2)[0] ?: 'Untitled';
                $r->link = url(recordPath($r));
                $r->thumb = url(getThumbnailPath($r->catalog_id));
                $r->last = isset($r->last) ? Carbon::parse($r->last) : null;
            });
        }

        return view('admin.rankings', [
            'window' => $window,
            'lists' => $lists,
            'totals' => Rankings::totals($window),
            'watch' => $request->user()->can('access.statistics.watch'),
        ]);
    }

    public function refresh()
    {
        clearHomeCache();

        return back()->with('success', 'The home page now shows the lists as they are below.');
    }
}
