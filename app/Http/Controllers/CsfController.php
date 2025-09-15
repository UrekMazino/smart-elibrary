<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Csf;
use Auth;

class CsfController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index()
    {
    //    return view('opac.review',['catalog_id' => request()->catalog_id,'download_id' => request()->download_id]);
    }

    public function create()
    {
        return view('opac.csf.create');
    }

    public function store(Request $request)
    {
        $isDone = Csf::where('user_id', Auth::user()->id)->first();

        // CHECK IF ALREADY HAVE CSF
        if($isDone) {
            return redirect('/home')->with('message', 'You are already done with CSF! Thank you.');
        }

        // SAVE CSF
        $csf = new Csf;
        $csf->user_id = Auth::user()->id;
        $csf->rating1 = $request->rating1;
        $csf->rating2 = $request->rating2;
        $csf->rating3 = $request->rating3;
        $csf->rating4 = $request->rating4;
        $csf->rating5 = $request->rating5;
        $csf->rating6 = $request->rating6;
        $csf->rating7 = $request->rating7;
        $csf->comment1 = $request->comment1;
        $csf->comment2 = $request->comment2;
        $csf->comment3 = $request->comment3;
        $csf->save();

        return view('opac.thank-you',['message' => 'Thanks for your feedback!']);
    }

    public function getRatings(Request $request)
    {
        $ratingsByCategories = DB::table('csfs')
        ->select($request->rating, DB::raw('COUNT(*) as rating_sum'))
        ->groupBy($request->rating)
        ->orderByDesc('rating_sum')
        ->get();
        
        return response()->json($ratingsByCategories);
    }
}
