<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App;
use Auth;
use App\Models\Material;
use App\Models\SavedRecord;
use App\Models\ViewCatalog;
use App\Services\AiSearchService;
use App\Services\CatalogSearch;
use App\Services\CatalogSearchParams;
use Illuminate\Support\Str;

class ResultController extends Controller
{

    public function index(Request $request, CatalogSearch $search)
    {
        if(Auth::check() && Auth::user()->usertype == 'Administrator')
        {
            return redirect('/admin/dashboard');
        }

        // AI mode: same search box, different engine. The page renders immediately with a
        // skeleton and pulls the answer from /search/ai-answer, so a slow generation never
        // holds up the response (§10.5/§10.7) — and it's the shape SSE streaming will drop into.
        //
        // Gated on the admin switch. Falls THROUGH to keyword search rather than 404ing:
        // this is a search someone actually typed, so if AI mode was turned off (or a stale
        // link/bookmark points here) they still get results instead of an error page.
        $ai = app(AiSearchService::class);

        if($ai->isEnabled() && $request->input('mode') === 'ai' && trim((string) $request->input('query')) !== '')
        {
            // Members-only. A guest reaching this page directly (typed URL / stale bookmark)
            // sees a login-required state instead of the answer; the page reads $authed and
            // shows the prompt rather than calling the endpoint (which would 401 anyway).
            return view('opac.search-result-ai', [
                'query'  => trim((string) $request->input('query')),
                'ai'     => $ai,
                'authed' => Auth::check(),
            ]);
        }

        // Keyword search: the search page (filters with counts, see App\Services\CatalogSearch).
        $p = CatalogSearchParams::fromRequest($request);
        if($p->saved && ! Auth::check())
        {
            return redirect()->guest(route('login'));
        }

        $savedIds   = SavedRecord::idsFor(Auth::id());
        $result     = $search->search($p, $savedIds);
        $typeNames  = Material::pluck('name', 'id')->all();
        $seriesName = $p->series ? getSeriesDesc($p->series) : null;

        $data = [
            'p'          => $p,
            'result'     => $result,
            'chips'      => $p->chips($typeNames, $seriesName),
            'fixes'      => $result['ok'] && $result['total'] === 0 ? $search->fixes($p, $savedIds, $typeNames, $seriesName) : [],
            'savedIds'   => $savedIds,
            'aiEnabled'  => $ai->isEnabled(),
            'advOpen'    => $request->boolean('advanced'),   // Advanced link from other pages
        ];

        // The page's own script swaps just the results area (filters + list) without a reload.
        if($request->boolean('partial'))
        {
            return view('opac.partials.search-results-main', $data);
        }

        return view('opac.search-results', $data);
    }

    /**
     * "Find an author / subject / publisher" box in the filters: matching options with counts
     * for the current search (JSON).
     */
    public function facetValues(Request $request, CatalogSearch $search)
    {
        $group = (string) $request->query('group', '');
        $text  = mb_substr(trim((string) $request->query('term', '')), 0, 100);
        $p     = CatalogSearchParams::fromRequest($request);

        return response()->json([
            'group'  => $group,
            'values' => $text === '' ? [] : $search->facetValues($p, SavedRecord::idsFor(Auth::id()), $group, $text),
        ]);
    }

    /** Citations (APA / MLA / Chicago) of a published record, for the Cite button (JSON). */
    public function cite($catalogId)
    {
        $record = ViewCatalog::where('catalog_id', (int) $catalogId)->where('is_published', 1)->whereNull('deleted_at')->first();
        abort_unless($record, 404);

        return response()->json([
            'title'     => $record->title_statement,
            'citations' => buildCitations($record),
        ]);
    }

    /**
     * Quick view button: a record's main details in a dialog over the results, so patrons can check
     * a record without leaving the list. Returns an HTML fragment (opac.partials.quick-view).
     */
    public function quickView($catalogId)
    {
        $record = ViewCatalog::where('catalog_id', (int) $catalogId)->where('is_published', 1)->whereNull('deleted_at')->first();
        abort_unless($record, 404);

        $year      = $record->pub_year ? trim($record->pub_year, '[]. ') : '';
        $publisher = seoTrimIsbd($record->publisher ?? '');
        $physical  = collect(getFullRecordFields($record->catalog_id))->firstWhere('label', 'Physical Description');

        return view('opac.partials.quick-view', [
            'r'         => $record,
            'url'       => recordUrl($record),
            'authors'   => $record->authorList(),
            'published' => implode(', ', array_filter([$publisher, $year])),
            'physical'  => $physical['value'] ?? '',
            'subjects'  => array_values($record->subjects ?? []),
            'summary'   => Str::limit(trim((string) $record->summary), 600),
            'read'      => getReadUrl($record->catalog_id),
            'available' => checkStocks($record->catalog_id),
            'rating'    => starRatingAve($record->catalog_id),
            'saved'     => isSavedRecord($record->catalog_id),
        ]);
    }

    /** Save / unsave a record for the signed-in patron (the Save button). */
    public function toggleSaved(Request $request)
    {
        $data = $request->validate(['catalog_id' => ['required', 'integer']]);
        $published = ViewCatalog::where('catalog_id', $data['catalog_id'])->where('is_published', 1)->whereNull('deleted_at')->exists();
        abort_unless($published, 404);

        $existing = SavedRecord::where('user_id', Auth::id())->where('catalog_id', $data['catalog_id'])->first();
        $existing ? $existing->delete() : SavedRecord::create(['user_id' => Auth::id(), 'catalog_id' => $data['catalog_id']]);

        return response()->json([
            'saved' => ! $existing,
            'count' => SavedRecord::where('user_id', Auth::id())->count(),
        ]);
    }

    /**
     * Instant-search JSON endpoint for the OPAC search-as-you-type overlay.
     * Meilisearch stays private; the browser only ever talks to this route.
     */
    public function suggest(Request $request, CatalogSearch $search)
    {
        $q = trim((string) $request->input('q', ''));

        if($q === '')
        {
            return response()->json(['query' => '', 'total' => 0, 'hits' => []]);
        }

        // The search page's box: titles, authors and subjects in groups (?groups=1).
        if($request->boolean('groups'))
        {
            $s = $search->suggest(mb_substr($q, 0, 100));
            $link = fn (string $key, string $value) => url('/results') . '?' . http_build_query([$key => [$value]], '', '&', PHP_QUERY_RFC3986);

            return response()->json([
                'query'    => $q,
                'titles'   => array_map(fn ($t) => ['title' => searchHighlight($t['title_hl']), 'year' => $t['year'], 'type' => $t['type'],
                    'url' => recordUrl(['catalog_id' => $t['catalog_id'], 'title_statement' => $t['title']])], $s['titles']),
                'authors'  => array_map(fn ($a) => $a + ['url' => $link('author', $a['value'])], $s['authors']),
                'subjects' => array_map(fn ($a) => $a + ['url' => $link('subject', $a['value'])], $s['subjects']),
            ]);
        }

        $type = $request->input('type');   // material_type_id, or null / 0 = all types

        $builder = App\Models\ViewCatalog::search($q)
            ->where('is_published', 1)
            ->where('deleted_at', null);

        if($type !== null && $type != 0)
        {
            $builder->where('material_type_id', (int) $type);
        }

        $page = $builder->paginate(8);   // one query -> top hits + total

        $hits = collect($page->items())->map(function ($m) {
            return [
                'catalog_id' => $m->catalog_id,
                'title'      => $m->title_statement,
                'author'     => $m->joint_authors,
                'year'       => $m->pub_year,
                'type'       => $m->material_type,
                'thumb'      => getThumbnailLink($m->catalog_id),
                'url'        => recordUrl($m),
            ];
        })->values();

        return response()->json([
            'query' => $q,
            'total' => $page->total(),
            'hits'  => $hits,
        ]);
    }

    /**
     * AI mode answer (JSON). The browser talks only to this route — never to Hermes
     * directly (§10.3: the gateway is localhost-only and the token never reaches the client).
     *
     * Returns the §10.2 contract plus hydrated `sources` for rendering. When streaming
     * lands this becomes an SSE endpoint whose terminal event carries the same payload.
     */
    public function aiAnswer(Request $request, AiSearchService $ai)
    {
        // AI mode is a members-only feature (each answer is a full GPU generation, so it's
        // gated behind an account). Guests get a clear reason the page turns into a
        // login/register prompt — not a redirect, since this is an AJAX call.
        if(! Auth::check())
        {
            return response()->json([
                'ok'      => false,
                'reason'  => 'auth',
                'message' => 'Please log in or register to use AI mode.',
            ], 401);
        }

        $q = trim((string) $request->input('q', ''));

        if($q === '')
        {
            return response()->json(['ok' => false, 'reason' => 'empty', 'message' => 'Ask a question to get an answer.'], 422);
        }

        // Degrade calmly instead of hanging or erroring; keyword search stays usable (§10.7).
        if(! $ai->isAvailable())
        {
            return response()->json([
                'ok'     => false,
                'reason' => 'unavailable',
                'message' => 'AI mode is temporarily unavailable. Keyword search is still available.',
            ], 503);
        }

        try
        {
            $res = $ai->answer($q, ['user_ref' => Auth::check() ? 'patron:'.Auth::id() : 'guest']);
        }
        catch (\Throwable $e)
        {
            report($e);

            return response()->json([
                'ok'      => false,
                'reason'  => 'error',
                'message' => 'Something went wrong generating an answer. Please try again.',
            ], 500);
        }

        // answer() already resolved sources -> catalogue records and renumbered the
        // answer's [n] citations to match, so nothing more to hydrate here.
        return response()->json([
            'ok'        => true,
            'query'     => $q,
            'answer'    => $res['answer'],
            'abstained' => $res['abstained'],
            'stub'      => $res['stub'] ?? false,
            'sources'   => $res['sources'],
            'took_ms'   => $res['usage']['took_ms'] ?? null,
        ]);
    }

    public function search()
    {
        $result = App\Models\ViewCatalog::search('Banana')->paginate(10);
        return $result;
    }
}
