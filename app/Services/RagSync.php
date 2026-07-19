<?php

namespace App\Services;

use App\Models\RagSyncItem;
use Illuminate\Support\Facades\Log;

/**
 * Producer side of the RAG catalog-sync outbox (see RAG_CATALOG_SYNC_PLAN.md). Call this whenever a
 * material changes so the Python RAG worker re-syncs that catalog_id's chunks in LanceDB.
 *
 * Semantics:
 *   UPSERT — "sync this record to its current state". The worker gates on is_published: published →
 *            (re)index; on-hold → ensure absent. So a create-while-on-hold is handled worker-side.
 *   DELETE — "ensure this record is absent from the index" (hard-deleted, or unpublished/held).
 *
 * Every method is deduped (one pending row per catalog_id) and NEVER throws — RAG sync is a side
 * concern and must not break a catalog save. Missed events are the reconciliation sweep's job.
 */
class RagSync
{
    public const UPSERT = 'upsert';
    public const DELETE = 'delete';

    /** Must match MAX_ATTEMPTS in rag/rag_sync_worker.py (a row is "failed" once it hits this). */
    public const MAX_ATTEMPTS = 5;

    /**
     * Best-effort AI-index status for a record, from is_published + the outbox state (the Laravel
     * side can't query LanceDB directly). Returns ['label' => ..., 'class' => <bootstrap bg-*>].
     */
    public static function statusFor(int $catalogId, $isPublished): array
    {
        if (! $isPublished) {
            return ['label' => 'Hidden from AI', 'class' => 'bg-secondary'];
        }

        $pending = RagSyncItem::whereNull('processed_at')->where('catalog_id', $catalogId)->first();
        if ($pending) {
            return $pending->attempts >= self::MAX_ATTEMPTS
                ? ['label' => 'AI index failed', 'class' => 'bg-danger']
                : ['label' => 'Queued for AI', 'class' => 'bg-info'];
        }

        $last = RagSyncItem::whereNotNull('processed_at')->where('catalog_id', $catalogId)
            ->orderByDesc('processed_at')->first();
        if (! $last) {
            return ['label' => 'Not queued', 'class' => 'bg-light text-dark'];
        }

        return $last->action === self::DELETE
            ? ['label' => 'Removed from AI', 'class' => 'bg-secondary']
            : ['label' => 'AI-indexed', 'class' => 'bg-success'];
    }

    /** Enqueue one catalog_id. If an unprocessed row already exists for it, update in place (latest wins). */
    public static function enqueue(int $catalogId, string $action, string $reason): void
    {
        if ($catalogId <= 0) {
            return;
        }

        try {
            $attrs = [
                'action'     => $action,
                'reason'     => $reason,
                'attempts'   => 0,
                'error'      => null,
                'created_at' => now(),
            ];

            $pending = RagSyncItem::whereNull('processed_at')->where('catalog_id', $catalogId)->first();
            if ($pending) {
                $pending->update($attrs);
            } else {
                RagSyncItem::create(array_merge(['catalog_id' => $catalogId], $attrs));
            }
        } catch (\Throwable $e) {
            Log::warning('RagSync::enqueue failed', ['catalog_id' => $catalogId, 'error' => $e->getMessage()]);
        }
    }

    /** Bulk enqueue (imports, batch publish/hold). Clears pending rows for these ids, then inserts one each. */
    public static function enqueueMany(iterable $catalogIds, string $action, string $reason): void
    {
        try {
            $ids = collect($catalogIds)->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique()->values();
            if ($ids->isEmpty()) {
                return;
            }

            RagSyncItem::whereNull('processed_at')->whereIn('catalog_id', $ids)->delete();

            $now  = now();
            $rows = $ids->map(fn ($id) => [
                'catalog_id'   => $id,
                'action'       => $action,
                'reason'       => $reason,
                'content_hash' => null,
                'attempts'     => 0,
                'error'        => null,
                'created_at'   => $now,
                'processed_at' => null,
            ])->all();

            // Chunked insert keeps large imports within packet limits.
            foreach (array_chunk($rows, 500) as $chunk) {
                RagSyncItem::insert($chunk);
            }
        } catch (\Throwable $e) {
            Log::warning('RagSync::enqueueMany failed', ['error' => $e->getMessage()]);
        }
    }
}
