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
