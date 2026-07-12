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

        //SAVE VIEW
        saveView($result->catalog_id);

        return view('opac.full-description')->with('result',$result);
    }

    public function index2($catalog_id = null)
    {
       
        if(Auth::check() && Auth::user()->usertype == 'Administrator')
        {  
            return redirect('/admin/dashboard');
        }

        if($catalog_id){

            // Only serve public records (published + not deleted); unpublished/deleted/
            // unknown ids must 404 rather than leak or crash on a null $result.
            $result = App\Models\ViewCatalog::where('catalog_id',$catalog_id)
                ->where('is_published',1)
                ->whereNull('deleted_at')
                ->first();

            abort_if(!$result, 404);

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
        if($request->filled('catalog_id'))
        {
            saveView($request->catalog_id);
        }

        return response()->json(['ok' => true]);
    }
}
