<?php

namespace App\Services;

use App\Models\ViewCatalog;
use Illuminate\Support\Facades\Http;

/**
 * AI mode ("Ask the Library") — the single seam between the OPAC and the local RAG gateway.
 *
 * Laravel ships a question to the gateway (rag_api.py, 127.0.0.1, shared token) and renders
 * the reply. The gateway owns retrieval (hybrid dense+FTS + rerank — the same stack Hermes'
 * search_docs uses) and grounded synthesis; it returns passages keyed by the document
 * "stem". This class maps each stem -> catalog_id (view_catalogs.accession_number) so the
 * answer cites real, openable catalogue records (§10.8 citation bridge).
 *
 * Gateway contract (RAG_INTEGRATION_PLAN.md §10.2), POST {url}/agent/answer:
 *   { answer, sources:[{stem, page, score, snippet}], abstained, took_ms }
 */
class AiSearchService
{
    /**
     * The admin kill switch (Admin > Settings > AI mode). Off by default: AI mode has to
     * be turned on deliberately, so a fresh install never exposes it.
     *
     * This is the ONE source of truth for "is AI mode on" — the OPAC toggle, the routes
     * and the middleware all read it, so switching off genuinely removes the feature.
     */
    public function isEnabled(): bool
    {
        return settingEnabled('ai_mode_enabled', false);
    }

    /** Where the agent gateway lives (§10.3: from .env, never the database). */
    public function endpoint(): string
    {
        return rtrim((string) config('services.hermes.url'), '/');
    }

    /**
     * Apply pending catalog-sync items ("Run sync now" on the AI Sync Monitor).
     *
     * The gateway does the indexing rather than Laravel shelling out to Python: that process
     * already holds BGE-M3 + LanceDB, so a drain costs no model load and needs no exec() surface.
     * It is BOUNDED (embedding shares one model instance with search), so repeat while
     * `remaining > 0`.
     *
     * @return array{ok:bool, processed:int, failed:int, remaining:int, detail:string}
     */
    public function drainSyncQueue(int $limit = 25): array
    {
        $fail = fn (string $detail) => [
            'ok' => false, 'processed' => 0, 'failed' => 0, 'remaining' => 0, 'detail' => $detail,
        ];

        if (! config('services.hermes.token')) {
            return $fail('No gateway token set (HERMES_GATEWAY_TOKEN in .env).');
        }

        try {
            $r = Http::withToken(config('services.hermes.token'))
                ->timeout((int) config('services.hermes.timeout', 60))
                ->post($this->endpoint() . '/sync/drain', ['limit' => $limit]);
        } catch (\Throwable $e) {
            return $fail('The AI gateway is not responding. Is rag_api.py running?');
        }

        if (! $r->successful()) {
            return $fail('The AI gateway returned an error (HTTP ' . $r->status() . ').');
        }

        $b = $r->json();

        return [
            'ok'        => true,
            'processed' => (int) ($b['processed'] ?? 0),
            'failed'    => (int) ($b['failed'] ?? 0),
            'remaining' => (int) ($b['remaining'] ?? 0),
            'detail'    => '',
        ];
    }

    /**
     * Is the AI backend usable right now? (§10.7: gateway down / models loading / GPU busy
     * -> the page degrades to a calm notice and keyword search stays fully usable.)
     */
    public function isAvailable(): bool
    {
        return $this->isEnabled() && $this->health()['ok'];
    }

    /**
     * Liveness of the agent gateway, for the Settings status line / Test connection.
     * Cached briefly so the Settings card and every AI request don't each hit /healthz.
     *
     * @return array{ok:bool, status:string, detail:string}
     */
    public function health(): array
    {
        return cache()->remember('ai_gateway_health', 15, function () {
            if (! config('services.hermes.token')) {
                return ['ok' => false, 'status' => 'not_configured',
                        'detail' => 'No gateway token set (HERMES_GATEWAY_TOKEN in .env).'];
            }

            try {
                $r = Http::timeout(5)->get($this->endpoint() . '/healthz');
            } catch (\Throwable $e) {
                return ['ok' => false, 'status' => 'unreachable',
                        'detail' => 'The AI gateway is not responding. Is rag_api.py running?'];
            }

            if (! $r->successful()) {
                return ['ok' => false, 'status' => 'error',
                        'detail' => 'The AI gateway returned an error (HTTP ' . $r->status() . ').'];
            }

            $body = $r->json();
            if (! ($body['ok'] ?? false)) {
                return ['ok' => false, 'status' => 'loading',
                        'detail' => 'The AI gateway is still loading its models.'];
            }

            return ['ok' => true, 'status' => 'connected',
                    'detail' => 'Connected — ' . number_format($body['docs'] ?? 0) . ' passages indexed.'];
        });
    }

    /**
     * Answer a question from the collection.
     *
     * Returns the answer with its sources already resolved to catalogue records and its
     * inline [n] citations renumbered to match (see resolveSources()).
     *
     * @return array{answer:string, sources:array, abstained:bool, usage:array, conversation_id:?string, stub:bool}
     */
    public function answer(string $query, array $options = []): array
    {
        $started = microtime(true);

        $res = Http::withToken(config('services.hermes.token'))
            ->timeout((int) config('services.hermes.timeout', 60))
            ->post($this->endpoint() . '/agent/answer', [
                'query'    => $query,
                'k'        => $options['k'] ?? null,
                'user_ref' => $options['user_ref'] ?? 'guest',
            ])
            ->throw()
            ->json();

        $tookMs = $res['took_ms'] ?? (int) ((microtime(true) - $started) * 1000);

        if (($res['abstained'] ?? false) || empty($res['sources'])) {
            return [
                'answer'          => '',
                'sources'         => [],
                'abstained'       => true,
                'usage'           => ['took_ms' => $tookMs],
                'conversation_id' => null,
                'stub'            => false,
            ];
        }

        // Map stems -> catalogue records and renumber the answer's citations to match.
        [$answer, $sources] = $this->resolveSources($res['answer'] ?? '', $res['sources']);

        // Every source was an unpublished/unknown record -> treat as an abstain rather than
        // show an answer whose citations all point nowhere.
        if (empty($sources)) {
            return [
                'answer'          => '',
                'sources'         => [],
                'abstained'       => true,
                'usage'           => ['took_ms' => $tookMs],
                'conversation_id' => null,
                'stub'            => false,
            ];
        }

        return [
            'answer'          => $answer,
            'sources'         => $sources,
            'abstained'       => false,
            'usage'           => ['took_ms' => $tookMs],
            'conversation_id' => null,
            'stub'            => false,
        ];
    }

    /**
     * The citation bridge (§10.8). The gateway numbers passages [1..N] and the answer text
     * cites those numbers. We can only cite passages that resolve to a live, published
     * catalogue record, so:
     *   - resolve each source's stem -> catalog_id (published, not deleted),
     *   - drop the ones that don't resolve,
     *   - renumber the survivors 1..M AND rewrite the answer's [old] -> [new],
     *     dropping citations to dropped sources.
     * This keeps the [n] chips and the source list aligned and contiguous.
     *
     * @return array{0:string, 1:array}  [rewritten answer, hydrated sources]
     */
    private function resolveSources(string $answer, array $rawSources): array
    {
        $stems = collect($rawSources)->pluck('stem')->filter()->unique()->values();

        $catalogs = $stems->isEmpty() ? collect() : ViewCatalog::whereIn('accession_number', $stems)
            ->where('is_published', 1)
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('accession_number');

        $sources = [];
        $remap   = [];   // old 1-based index => new 1-based index
        $newN    = 0;

        foreach ($rawSources as $i => $s) {
            $oldN = $i + 1;
            $c    = $catalogs->get($s['stem'] ?? null);
            if (! $c) {
                continue;   // not a citable record -> drop, and its [oldN] will be removed
            }

            $remap[$oldN] = ++$newN;
            $sources[] = [
                'n'          => $newN,
                'catalog_id' => $c->catalog_id,
                'title'      => $c->title_statement,
                'author'     => $c->joint_authors,
                'year'       => $c->pub_year ? preg_replace('/[\[\]"\'.]+/', '', (string) $c->pub_year) : null,
                'type'       => $c->material_type,
                'page'       => $s['page'] ?? null,
                'score'      => isset($s['score']) ? round((float) $s['score'], 2) : null,
                'snippet'    => $s['snippet'] ?? null,
                'thumb'      => getThumbnailLink($c->catalog_id),
                'url'        => recordUrl($c),
            ];
        }

        return [$this->remapCitations($answer, $remap), $sources];
    }

    /**
     * Rewrite [n] citations in the answer using the old->new map. A citation whose source
     * was dropped (not in the map) is removed. Handles grouped forms like [2][3] and [2, 3].
     */
    private function remapCitations(string $answer, array $remap): string
    {
        $answer = preg_replace_callback('/\[([\d,\s]+)\]/', function ($m) use ($remap) {
            $nums = preg_split('/[,\s]+/', trim($m[1]), -1, PREG_SPLIT_NO_EMPTY);
            $kept = [];
            foreach ($nums as $num) {
                $n = (int) $num;
                if (isset($remap[$n])) {
                    $kept[] = $remap[$n];
                }
            }
            return $kept ? '[' . implode('][', $kept) . ']' : '';
        }, $answer);

        // Tidy any double spaces left where a citation was removed.
        return trim(preg_replace('/ {2,}/', ' ', $answer));
    }
}
