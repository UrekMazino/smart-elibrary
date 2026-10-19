<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use Auth;

class HoldingController extends Controller
{
    public function index()
    {
        if(Auth::check() && Auth::user()->usertype == 'Administrator')
        {  
            return redirect('/admin/dashboard');
        }

        // Only serve records that are actually public (published + not deleted); a bad or
        // unpublished id must not leak. 404 instead of rendering with a null $result.
        $result = App\Models\ViewCatalog::where('catalog_id',request()->catalog_id)
            ->where('is_published',1)
            ->whereNull('deleted_at')
            ->first();

        abort_if(!$result, 404);

        // The search results navigate here by POSTing a catalog_id, which would render the record
        // at the id-less URL /full-description — not shareable, and a second URL for the same
        // content. Send the browser to the canonical slug URL instead (Post/Redirect/Get).
        return redirect(recordUrl($result));
    }

    public function index2($segment = null)
    {

        if(Auth::check() && Auth::user()->usertype == 'Administrator')
        {
            return redirect('/admin/dashboard');
        }

        if($segment){

            // URLs are "{catalog_id}-{title-slug}"; the leading integer is the lookup key and the
            // slug is decorative, so a retitled record never breaks an existing link.
            $catalog_id = (int) $segment;
            abort_if($catalog_id <= 0, 404);

            // Only serve public records (published + not deleted); unpublished/deleted/
            // unknown ids must 404 rather than leak or crash on a null $result.
            $result = App\Models\ViewCatalog::where('catalog_id',$catalog_id)
                ->where('is_published',1)
                ->whereNull('deleted_at')
                ->first();

            abort_if(!$result, 404);

            // One canonical URL per record: a bare id (old links, shared URLs) or a stale slug
            // (title since edited) redirects permanently to the current form. Done BEFORE
            // saveView so a redirect hop is not counted as a second view.
            $canonical = seoRecordSegment($result);
            if ((string) $segment !== $canonical) {
                return redirect()->to(url('/full-description/' . $canonical), 301);
            }

            //SAVE VIEW
            saveView($result->catalog_id);

            return view('opac.full-description')->with('result',$result);
        }
        else{
            return redirect('/home');
        }

    }

    /**
     * Record a view when a material is opened in the in-browser reader (Read button).
     * Uses the same saveView() as the full-description page, so reads count toward Views.
     */
    public function recordView(Request $request)
    {
        // Only a real record is counted (views.catalog_id is a foreign key to catalogs).
        if($request->filled('catalog_id') && ctype_digit((string) $request->catalog_id)
            && App\Models\Catalog::whereKey($request->catalog_id)->exists())
        {
            saveView($request->catalog_id);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Guarded reader stream. The in-browser reader (PDF.js) loads the file through this
     * route instead of the raw public path, so we can deny guests (the route's `auth`
     * middleware) and banned accounts the Read feature. Also enforces the same
     * published/not-deleted rule as full-description.
     */
    public function read($catalog_id)
    {
        // Banned accounts are not allowed to use the Read feature (covers a session that was
        // already signed in when the admin banned the account).
        abort_if(Auth::user()->is_banned, 403, 'Your account has been banned from reading materials.');

        // Only public records may be read (published + not deleted).
        $result = App\Models\ViewCatalog::where('catalog_id',$catalog_id)
            ->where('is_published',1)
            ->whereNull('deleted_at')
            ->first();
        abort_if(!$result, 404);

        $pdf = App\Models\Multimedia::where('catalog_id',$catalog_id)->first();
        abort_if(!$pdf, 404);

        $loc  = ltrim((string)$pdf['FileLocation'], '/');
        $path = public_path($loc);
        abort_if($loc === '' || !is_file($path), 404);

        // A logged-in patron reading a material adds it to their "To Review" list — the
        // same as downloading. Recorded once per user + material: PDF.js sends several range
        // requests at once, so rely on the unique (user_id, catalog_id) key rather than a
        // check-then-insert that two of them could pass together. Staff reads are not recorded.
        if (Auth::check() && ! Auth::user()->isStaff()) {
            \DB::table('reads')->insertOrIgnore([
                'user_id'    => Auth::id(),
                'catalog_id' => $result->catalog_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Inline stream (supports range requests) so PDF.js can page through it.
        return response()->file($path, ['Content-Type' => 'application/pdf']);
    }
}
