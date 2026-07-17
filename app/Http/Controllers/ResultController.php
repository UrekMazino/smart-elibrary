<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App;
use Auth;
use App\Services\AiSearchService;

class ResultController extends Controller
{

    public function index(Request $request)
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
            return view('opac.search-result-ai', [
                'query' => trim((string) $request->input('query')),
                'ai'    => $ai,
            ]);
        }

        // The OPAC search form may omit any of these keys — guard them all so a
        // partial/empty submission returns results instead of a 500.
        $query     = $request->input('query');                       // null when absent
        $layout    = $request->input('layout', 'grid');
        $perPage   = ($layout == 'grid') ? 15 : 12;                  // grid = 3x5 rows per page
        $seriesId  = $request->input('series_statement_id', 0);
        $seachtype = (array) $request->input('seachtype', ['0']);    // always an array
        $searhType = $seachtype;                                     // passed to the view
        $allTypes  = count($seachtype) == 1 && $seachtype[0] == 0;   // "0" = all material types

        if($seriesId != 0)
        {
            // Browse by series
            $result = App\Models\ViewCatalog::where('series_statement_id', $seriesId)
                ->where('deleted_at', null)->where('is_published', 1)
                ->orderBy('pub_year', 'DESC')->paginate($perPage);
        }
        elseif($request->has('seachtype') && $request->has('year1'))
        {
            // Type/year filtered search (year filtering itself was never wired up upstream)
            $builder = App\Models\ViewCatalog::search($query ?? '');
            if(! $allTypes)
            {
                count($seachtype) == 1
                    ? $builder->where('material_type_id', $seachtype[0])
                    : $builder->whereIn('material_type_id', $seachtype);
            }
            $result = $builder->where('deleted_at', null)->where('is_published', 1)
                ->orderBy('pub_year', 'DESC')->paginate($perPage);
        }
        elseif(isset($query) && $query !== '')
        {
            // Keyword search (optionally narrowed by material type)
            $builder = App\Models\ViewCatalog::search($query);
            if(! $allTypes)
            {
                $builder->whereIn('material_type_id', $seachtype);
            }
            $result = $builder->where('deleted_at', null)->where('is_published', 1)
                ->orderBy('pub_year', 'DESC')->paginate($perPage);
        }
        else
        {
            // No keyword: list by material type
            $result = App\Models\ViewCatalog::whereIn('material_type_id', $seachtype)
                ->where('deleted_at', null)->where('is_published', 1)
                ->orderBy('pub_year', 'DESC')->paginate($perPage);
        }

        // The result Blade views read these keys straight from $_GET; guarantee
        // they exist (the union operator preserves any values actually submitted)
        // so a partial submission never throws "Undefined array key" while rendering.
        $_GET += [
            'query'               => $query ?? '',
            'seachtype'           => $seachtype,
            'series_statement_id' => $seriesId,
            'layout'              => $layout,
            'page'                => $request->input('page', 1),
        ];

        if($layout == 'list')
            return view('opac.search-result-list', ['result' => $result, 'layout' => 'list', 'search_type' => $searhType]);
        else
            return view('opac.search-result-grid', ['result' => $result, 'layout' => 'grid', 'search_type' => $searhType]);
    }

    /**
     * Instant-search JSON endpoint for the OPAC search-as-you-type overlay.
     * Meilisearch stays private; the browser only ever talks to this route.
     */
    public function suggest(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        if($q === '')
        {
            return response()->json(['query' => '', 'total' => 0, 'hits' => []]);
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
                'url'        => url('/full-description/' . $m->catalog_id),
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
