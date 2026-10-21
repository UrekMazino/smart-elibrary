<?php

namespace App\Services;

use App\Models\ViewCatalog;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\EngineManager;
use Meilisearch\Contracts\DocumentsQuery;

/**
 * The one way the app updates the OPAC search index (Meilisearch, through Scout).
 *
 * A search document is built from four tables (view_catalogs, multimedia, frontpages, views), and
 * most writes here go through the query builder, which Scout's automatic model syncing never sees.
 * So that syncing is off for ViewCatalog, and every change calls refresh() with the catalogue ids
 * it touched (EVENT_DRIVEN_SIDE_EFFECTS_PLAN.md, section 4.5).
 *
 * refresh() is state-based: the index is made to match the database as it is when the update is
 * sent (current records indexed, deleted ones removed), so calling it twice or out of order is
 * harmless. Updates go out after the current transaction commits (at once when there is none), so
 * a rolled-back change never reaches the index. Between hold() and release() they are collected
 * and sent in chunks instead of one call per record (batch import, corpus import).
 *
 * A failed call (Meilisearch down) is reported, never thrown: the change itself stands, and the
 * nightly `search:reconcile` brings the index back in line.
 */
class SearchIndex
{
    public const CHUNK = 500;

    /** @var array<int, true> catalogue ids waiting to be sent */
    private array $pending = [];

    private int $holds = 0;

    /** Bring the index in line with the database for these catalogue ids. */
    public static function refresh(int|iterable|null $catalogIds): void
    {
        app(self::class)->add($catalogIds);
    }

    /** Collect updates until release(), then send them in chunks. Pair every hold() with a release(). */
    public static function hold(): void
    {
        app(self::class)->holds++;
    }

    public static function release(): void
    {
        $index = app(self::class);
        $index->holds = max(0, $index->holds - 1);
        if ($index->holds === 0 && $index->pending) $index->sendAfterCommit();
    }

    public function add(int|iterable|null $catalogIds): void
    {
        foreach (is_iterable($catalogIds) ? $catalogIds : [$catalogIds] as $id) {
            if ((int) $id > 0) $this->pending[(int) $id] = true;
        }
        if ($this->pending && ($this->holds === 0 || count($this->pending) >= self::CHUNK)) {
            $this->sendAfterCommit();
        }
    }

    private function sendAfterCommit(): void
    {
        DB::afterCommit(fn () => $this->send());
    }

    /** Send every pending update now. Also called when the request or command ends. */
    public function send(): void
    {
        $ids = array_keys($this->pending);
        $this->pending = [];

        foreach (array_chunk($ids, self::CHUNK) as $chunk) {
            try {
                ViewCatalog::whereIn('catalog_id', $chunk)->whereNull('deleted_at')->searchable();
                ViewCatalog::whereIn('catalog_id', $chunk)->whereNotNull('deleted_at')->unsearchable();
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /** Called when the request or command ends: send what is left and forget any hold. */
    public function finish(): void
    {
        $this->holds = 0;
        if ($this->pending) $this->send();
    }

    /**
     * The document ids (view_catalogs.id) in the Meilisearch index, or null when the search
     * driver can't list them (the null driver in tests, for example).
     */
    public function indexedKeys(): ?array
    {
        if (config('scout.driver') !== 'meilisearch') return null;

        $index = app(EngineManager::class)->engine()->index((new ViewCatalog)->searchableAs());
        $keys = [];
        for ($offset = 0; ; $offset += 1000) {
            $page = $index->getDocuments((new DocumentsQuery())->setFields(['id'])->setOffset($offset)->setLimit(1000))->getResults();
            foreach ($page as $document) $keys[] = (int) $document['id'];
            if (count($page) < 1000) break;
        }
        return $keys;
    }

    /** Remove documents by id (view_catalogs.id) whose record no longer exists at all. */
    public function deleteKeys(array $keys): void
    {
        if (! $keys || config('scout.driver') !== 'meilisearch') return;

        $index = app(EngineManager::class)->engine()->index((new ViewCatalog)->searchableAs());
        foreach (array_chunk($keys, self::CHUNK) as $chunk) {
            $index->deleteDocuments($chunk);
        }
    }
}
