<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use App\Models\Frontpage;
use App\Models\Setting;
use App\Models\ViewCatalog;
use App\Services\AiSearchService;

class SettingController extends Controller
{
    /** Each setting the page saves, and its value while the table has no row for it (what the rest of the app reads). */
    private const DEFAULTS = [
        'enable_placeholder_covers' => '1',
        'ai_mode_enabled'           => '0',
        'ai_answer_provider'        => AiSearchService::DEFAULT_PROVIDER,
        'seo_discourage_indexing'   => '0',
        'seo_verification_google'   => '',
    ];

    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index()
    {
        return view('admin.settings', [
            'current'     => $this->effective(Setting::pluck('value', 'key')),
            'sitemap'     => $this->sitemapStatus(),
            'coverSample' => $this->coverSample(),
        ]);
    }

    /** The settings in effect: stored values, else their defaults. */
    private function effective($stored): array
    {
        $values = [];
        foreach (self::DEFAULTS as $key => $default) {
            $values[$key] = (string) ($stored[$key] ?? $default);
        }
        // An unknown stored provider is read as the default (AiSearchService::provider).
        if (! array_key_exists($values['ai_answer_provider'], AiSearchService::PROVIDERS)) {
            $values['ai_answer_provider'] = AiSearchService::DEFAULT_PROVIDER;
        }

        return $values;
    }

    /** A generated cover to show beside the Covers switch (its thumbnail), or null while there is none. */
    private function coverSample(): ?string
    {
        $name = Frontpage::where('FrontPageName', 'like', 'gen-%')->latest('id')->value('FrontPageName');

        return $name && is_file(public_path('thumb/' . $name)) ? 'thumb/' . $name : null;
    }

    /**
     * What the generated sitemap currently looks like on disk, for the Settings card. Includes a
     * host check: a sitemap built under a different APP_URL (e.g. generated in dev, then deployed)
     * advertises URLs on the wrong domain, which is easy to miss and breaks discovery.
     */
    private function sitemapStatus(): array
    {
        $index = public_path('sitemap.xml');

        if (! is_file($index)) {
            return ['exists' => false, 'urls' => 0, 'generated_at' => null, 'host_ok' => true];
        }

        $urls = 0;
        foreach (glob(public_path('sitemap-*.xml')) ?: [] as $file) {
            $urls += substr_count((string) file_get_contents($file), '<url>');
        }

        return [
            'exists'       => true,
            'urls'         => $urls,
            'generated_at' => date('d M Y, H:i', (int) filemtime($index)),
            'host_ok'      => str_contains((string) file_get_contents($index), rtrim(url('/'), '/')),
        ];
    }

    /** Rebuild sitemap.xml + robots.txt on demand (Tools → Rebuild sitemap). */
    public function generateSitemap()
    {
        \Illuminate\Support\Facades\Artisan::call('sitemap:generate');

        $status = $this->sitemapStatus();

        return response()->json([
            'success' => true,
            'urls'    => $status['urls'],
            'when'    => $status['generated_at'],
            'host_ok' => $status['host_ok'],
        ]);
    }

    public function update(Request $request)
    {
        // Search Console's whole tag pasted instead of its code: keep the code, <meta … content="CODE">.
        // The tag itself would go into the page as text, and verification would fail without a word.
        $code = trim((string) $request->input('seo_verification_google', ''));
        if (preg_match('/content\s*=\s*["\']([^"\']*)["\']/i', $code, $m)) {
            $code = trim($m[1]);
        }
        $request->merge(['seo_verification_google' => $code]);

        // Checked before anything is stored, so a refused save changes nothing.
        $request->validate([
            'seo_verification_google' => ['nullable', 'string', 'max:200', 'regex:/^[A-Za-z0-9_\-+\/=.]+$/'],
            // "Who writes the answers": only a known provider.
            'ai_answer_provider'      => ['nullable', Rule::in(array_keys(AiSearchService::PROVIDERS))],
        ], [
            'seo_verification_google.regex' => 'The Google verification code can only hold letters, digits and - _ + / = . (no spaces). Paste just the code: the part in quotes after content=.',
            'seo_verification_google.max'   => 'The Google verification code is too long. Paste just the code: the part in quotes after content=.',
        ]);

        // Toggles: present in the POST (checkbox) => '1', absent => '0'. Text: an empty string clears it.
        $values = [
            'enable_placeholder_covers' => $request->boolean('enable_placeholder_covers') ? '1' : '0',
            'seo_discourage_indexing'   => $request->boolean('seo_discourage_indexing') ? '1' : '0',
            'seo_verification_google'   => $code,
        ];

        // AI mode needs its own permission on top of access.configuration, so turning the
        // OPAC's AI on/off is a deliberate grant rather than something every config admin
        // inherits.
        //
        // Added only with that permission: the switch isn't rendered for someone without it,
        // so its checkbox is absent from their POST — and the absent-means-'0' rule would
        // silently switch AI mode off every time they saved this page for any other reason.
        if ($request->user()->can('manage.ai.mode')) {
            $values['ai_mode_enabled'] = $request->boolean('ai_mode_enabled') ? '1' : '0';
            // Absent (an older form) leaves the provider as it is.
            if ($request->filled('ai_answer_provider')) {
                $values['ai_answer_provider'] = $request->input('ai_answer_provider');
            }
        }

        $before = $this->effective(Setting::pluck('value', 'key'));
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        settingsFlush();   // don't serve this request's stale memoised copy

        // The page says what this save changed (nothing, when every value was already in effect).
        return redirect('/admin/settings')->with('saved', $this->changes($before, $values));
    }

    /** One sentence for each setting a save changed. */
    private function changes(array $before, array $after): array
    {
        $said = [];
        foreach ($after as $key => $value) {
            if ($before[$key] === $value) {
                continue;
            }
            $on = $value === '1';
            $said[] = match ($key) {
                'enable_placeholder_covers' => $on ? 'Records without a cover now get one made from their title.'
                                                   : 'Records without a cover now show “No preview available”.',
                'ai_mode_enabled'           => $on ? 'AI answers are now on in the OPAC.' : 'AI answers are now off in the OPAC.',
                'ai_answer_provider'        => 'AI answers are now written by: ' . AiSearchService::PROVIDERS[$value] . '.',
                'seo_discourage_indexing'   => $on ? 'Search engines are now asked not to list the catalogue.'
                                                   : 'Search engines may now list the catalogue.',
                'seo_verification_google'   => $value === '' ? 'The Google verification code was removed.'
                                                             : 'The Google verification code was saved.',
            };
        }

        return $said;
    }

    /**
     * Agent connection status for the AI mode card (§10.7 health).
     * Read-only: it reports, it never changes configuration.
     */
    public function aiStatus(AiSearchService $ai)
    {
        $health = $ai->health();

        return response()->json([
            'enabled'  => $ai->isEnabled(),
            'endpoint' => $ai->endpoint(),
            'ok'       => $health['ok'],
            'status'   => $health['status'],
            'detail'   => $health['detail'],
            'provider' => $ai->provider(),
            'gemini'   => $health['gemini'] ?? null,   // {configured, model}: never the key
        ]);
    }

    /**
     * Clear the app's cached data + compiled Blade views.
     *
     * What this is for: the homepage/sidebar panels and the dashboard traffic chart are
     * cached for 10 minutes, and bulk operations that use query-builder ->update() don't
     * fire model events, so those panels can hold stale data (same trap as the search
     * index). This is the "show me the truth now" button.
     *
     * Deliberately narrow — it does NOT touch:
     *   - sessions (separate store; clearing them would sign everyone out mid-work),
     *   - bootstrap/cache/{packages,services}.php (package discovery — regenerated by
     *     composer, not by us; deleting them here would be a foot-gun),
     *   - the Meilisearch index (that's Rebuild search index, a different thing entirely).
     *
     * Forgets the app's own keys (appCacheKeys) instead of Cache::flush(). The cache store
     * also holds the rate limiter's counters, so a flush would reset LOGIN brute-force
     * protection for every IP — and would erase this endpoint's own throttle counter on
     * every call, so it could never actually rate-limit.
     *
     * Uses the cache/filesystem APIs directly rather than Artisan::call('cache:clear'),
     * for the same reason reindexSearch() does: no dependency on command registration,
     * which isn't guaranteed outside the console.
     */
    public function clearCache()
    {
        try {
            $cleared = 0;
            foreach (appCacheKeys() as $key) {
                if (Cache::forget($key)) {
                    $cleared++;   // counts the ones that were actually cached
                }
            }

            $viewsCleared = 0;
            foreach (glob(storage_path('framework/views/*.php')) ?: [] as $file) {
                if (@unlink($file)) {
                    $viewsCleared++;
                }
            }

            settingsFlush();   // this request's memoised settings copy

            return response()->json([
                'ok'            => true,
                'cache_entries' => $cleared,
                'views_cleared' => $viewsCleared,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok'      => false,
                'message' => 'Clear cache failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Drift scan: compare the Meilisearch index against the database (the source of truth)
     * and report how far it has drifted. Read-only — it changes nothing.
     *
     *   orphaned = indexed, but the row is gone from the DB (hard-delete / re-import /
     *              migrate:fresh). ONLY a rebuild clears these.
     *   stale    = indexed, but is_published / deleted_at no longer match the DB, so the
     *              OPAC filters act on wrong values (inflated counts, hidden/visible rows).
     *   missing  = in the DB but never indexed, so it can't be found by search.
     */
    public function scanSearchIndex()
    {
        @set_time_limit(300);

        try {
            $index = app(\Meilisearch\Client::class)->index((new ViewCatalog)->searchableAs());

            // Pull every indexed doc's id + the two fields the OPAC filters on (chunked).
            $docs   = [];
            $offset = 0;
            $limit  = 1000;
            do {
                $query = (new \Meilisearch\Contracts\DocumentsQuery())
                    ->setOffset($offset)
                    ->setLimit($limit)
                    ->setFields(['id', 'is_published', 'deleted_at']);

                $batch = $index->getDocuments($query)->getResults();

                foreach ($batch as $doc) {
                    $docs[$doc['id']] = $doc;
                }

                $offset += $limit;
            } while (count($batch) === $limit && $offset < 200000);

            $rows = ViewCatalog::select('id', 'is_published', 'deleted_at')->get()->keyBy('id');

            $orphans = 0;
            $stale   = 0;
            foreach ($docs as $id => $doc) {
                $row = $rows->get($id);

                if (! $row) {
                    $orphans++;
                    continue;
                }

                $indexedDeleted = ! empty($doc['deleted_at']);
                $dbDeleted      = ! empty($row->deleted_at);

                if ((int) ($doc['is_published'] ?? 0) !== (int) $row->is_published
                    || $indexedDeleted !== $dbDeleted) {
                    $stale++;
                }
            }

            $missing = $rows->keys()->reject(fn ($id) => isset($docs[$id]))->count();
            $drift   = $orphans + $stale + $missing;

            return response()->json([
                'ok'         => true,
                'index_docs' => count($docs),
                'db_rows'    => $rows->count(),
                'orphans'    => $orphans,
                'stale'      => $stale,
                'missing'    => $missing,
                'drift'      => $drift,
                'in_sync'    => $drift === 0,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Scan failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Rebuild the OPAC search index (Meilisearch) from the database — the source of truth.
     *
     * flush removes stale/orphaned documents (records deleted or re-imported over time can
     * leave documents behind), then import re-adds every current row with fresh filter
     * values. Run this if search result counts ever disagree with what the results page
     * shows. Manual button rather than a schedule.
     */
    public function reindexSearch()
    {
        @set_time_limit(600);

        try {
            // NOTE: do NOT use Artisan::call('scout:flush'/'scout:import') here — Scout only
            // registers its commands when runningInConsole(), so over HTTP they don't exist
            // ("The command scout:flush does not exist"). These are exactly what those
            // commands call internally, and they work in any context.
            ViewCatalog::removeAllFromSearch();   // = scout:flush  (drops stale/orphaned docs)
            ViewCatalog::makeAllSearchable();     // = scout:import (re-adds every current row)
        } catch (\Throwable $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Re-sync failed: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'ok'   => true,
            'rows' => ViewCatalog::count(),
        ]);
    }
}
