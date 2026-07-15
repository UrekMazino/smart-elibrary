<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Models\Setting;
use App\Models\ViewCatalog;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    public function index()
    {
        return view('admin.settings', [
            'settings' => Setting::pluck('value', 'key'),
        ]);
    }

    public function update(Request $request)
    {
        // Toggles: present in the POST (checkbox) => '1', absent => '0'
        foreach (['enable_placeholder_covers'] as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->boolean($key) ? '1' : '0']
            );
        }

        return redirect('/admin/settings')->with('success', 'Settings saved.');
    }

    /**
     * Drift scan: compare the Meilisearch index against the database (the source of truth)
     * and report how far it has drifted. Read-only — it changes nothing.
     *
     *   orphaned = indexed, but the row is gone from the DB (hard-delete / re-import /
     *              migrate:fresh). ONLY a Re-sync clears these.
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
            Artisan::call('scout:flush', ['model' => ViewCatalog::class]);
            Artisan::call('scout:import', ['model' => ViewCatalog::class]);
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
