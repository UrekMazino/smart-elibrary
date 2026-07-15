<?php

namespace App\Services;

use App\Models\ViewCatalog;

/**
 * AI mode ("Ask the Library") — the single seam between the OPAC and Hermes.
 *
 * ============================ NOT CONNECTED YET =============================
 * Hermes is NOT wired up. answer() currently returns PLACEHOLDER prose built from
 * real catalog hits so the UI can be designed and reviewed. Every response carries
 * 'stub' => true, and the page renders a "Preview" banner off that flag — so nobody
 * can mistake this for a real answer. Deleting the stub is a one-method change.
 *
 * When Hermes lands, answer() becomes a server-side POST to the Hermes gateway
 * (127.0.0.1, shared token) per RAG_INTEGRATION_PLAN.md §10.2, and the return shape
 * below is exactly that contract, so nothing above this class changes:
 *
 *   { answer, sources[{catalog_id, page, score, snippet}], abstained, usage{took_ms}, conversation_id }
 *
 * Deliberately NOT in here (§10): the agent loop, prompt/guardrails, and retrieval.
 * Hermes owns those. Laravel ships a question in and renders the reply out.
 * ============================================================================
 */
class AiSearchService
{
    /** Placeholder answers only; no Hermes call is made. Flips to false when wired. */
    public function isStub(): bool
    {
        return true;
    }

    /**
     * Is the AI backend usable right now? (§10.7: re-index running / GPU busy / gateway
     * down -> the page degrades to a calm notice and keyword search stays fully usable.)
     * Real version: a cached GET /healthz against the gateway.
     */
    public function isAvailable(): bool
    {
        return true;
    }

    /**
     * Answer a question from the collection.
     *
     * @return array{answer:string, sources:array, abstained:bool, usage:array, conversation_id:?string, stub:bool}
     */
    public function answer(string $query, array $options = []): array
    {
        $started = microtime(true);

        // ---- STUB ---------------------------------------------------------------
        // Real implementation (later):
        //   $res = Http::withToken(config('services.hermes.token'))
        //              ->timeout(config('services.hermes.timeout'))
        //              ->post(config('services.hermes.url').'/agent/chat', [
        //                  'conversation_id' => $options['conversation_id'] ?? (string) Str::uuid(),
        //                  'message'         => $query,
        //                  'history'         => $options['history'] ?? [],
        //                  'profile'         => 'elibrary-rag',
        //                  'user_ref'        => $options['user_ref'] ?? 'guest',
        //                  'stream'          => false,
        //              ])->throw()->json();
        $sources = $this->stubSources($query);

        // No catalog hits -> exercise the abstain path (§10.2 'abstained'), which is what
        // the hardened agent does when the corpus can't support an answer.
        if (empty($sources)) {
            return [
                'answer'          => '',
                'sources'         => [],
                'abstained'       => true,
                'usage'           => ['took_ms' => (int) ((microtime(true) - $started) * 1000)],
                'conversation_id' => null,
                'stub'            => true,
            ];
        }

        return [
            'answer'          => $this->stubAnswer($query, $sources),
            'sources'         => $sources,
            'abstained'       => false,
            'usage'           => ['took_ms' => (int) ((microtime(true) - $started) * 1000)],
            'conversation_id' => null,
            'stub'            => true,
        ];
        // ---- /STUB --------------------------------------------------------------
    }

    /**
     * Hydrate sources into what the UI needs (§10.8 citation bridge).
     *
     * catalog_id is the join key and is mandatory: a source without one can't be cited
     * (never cite a bare filename), so it's dropped here rather than rendered.
     */
    public function hydrateSources(array $sources): array
    {
        $ids = collect($sources)->pluck('catalog_id')->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $catalogs = ViewCatalog::whereIn('catalog_id', $ids)
            ->where('is_published', 1)
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('catalog_id');

        $out = [];
        $n   = 0;

        foreach ($sources as $s) {
            $c = $catalogs->get($s['catalog_id'] ?? null);

            // Unknown / unpublished / deleted catalog_id -> not citable, so skip it
            // rather than show a dead citation.
            if (! $c) {
                continue;
            }

            $out[] = [
                'n'          => ++$n,
                'catalog_id' => $c->catalog_id,
                'title'      => $c->title_statement,
                'author'     => $c->joint_authors,
                'year'       => $c->pub_year ? preg_replace('/[\[\]"\'.]+/', '', (string) $c->pub_year) : null,
                'type'       => $c->material_type,
                'page'       => $s['page'] ?? null,
                'score'      => isset($s['score']) ? round((float) $s['score'], 2) : null,
                'snippet'    => $s['snippet'] ?? null,
                'thumb'      => getThumbnailLink($c->catalog_id),
                'url'        => url('/full-description/' . $c->catalog_id),
            ];
        }

        return $out;
    }

    // ===================== stub-only helpers (delete with the stub) =====================

    /** Real catalog hits, so the citation bridge and the layout are exercised for real. */
    private function stubSources(string $query): array
    {
        $hits = ViewCatalog::search($query)
            ->where('is_published', 1)
            ->where('deleted_at', null)
            ->paginate(3);

        $scores = [0.82, 0.64, 0.51];

        return collect($hits->items())->values()->map(fn ($m, $i) => [
            'catalog_id' => $m->catalog_id,
            'page'       => ($i + 1) * 7,
            'score'      => $scores[$i] ?? 0.4,
            'snippet'    => $this->stubSnippet($m),
        ])->all();
    }

    private function stubSnippet($m): string
    {
        $text = trim((string) ($m->summary ?: $m->general_note ?: ''));

        if ($text === '') {
            return 'Placeholder passage — the real snippet will be the retrieved chunk of this document.';
        }

        return \Illuminate\Support\Str::limit(preg_replace('/\s+/', ' ', $text), 220);
    }

    /** Obviously-placeholder prose. The UI also shows a Preview banner (see 'stub'). */
    private function stubAnswer(string $query, array $sources): string
    {
        $n = count($sources);

        return "This is placeholder text standing in for a generated answer, so the layout of AI "
             . "mode can be reviewed before Hermes is connected. A real reply would answer "
             . "\u{201C}{$query}\u{201D} in a few grounded sentences, citing the passages it drew on "
             . "inline like this [1]"
             . ($n > 1 ? ", and combining several documents where they agree [2]" : '')
             . ($n > 2 ? " or noting where they differ [3]" : '')
             . ". Every claim would trace back to a source below — and when the collection can't "
             . "support an answer, the assistant says so instead of guessing.";
    }
}
