<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use Auth;

class ReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','patron']);
    }

    public function index()
    {
       return view('opac.review',['catalog_id' => request()->catalog_id,'download_id' => request()->download_id]);
    }

    public function create()
    {
      //SAVE REVIEW
       $review = new App\Models\Rating;
       $review->catalog_id = request()->catalog_id;
       $review->user_id = Auth::user()->id;
       $review->rating = request()->rating_input;
       $review->remarks = request()->remarks;
       $review->save();

       //MARK AS REVIEWED — one review clears the material from "to review": flag every
       //un-reviewed download of this material by this user (multiple downloads must not
       //require multiple reviews).
       App\Models\Download::where('user_id', Auth::user()->id)
            ->where('catalog_id', request()->catalog_id)
            ->whereNull('to_review')
            ->update(['to_review' => date('Y-m-d')]);

       //Back to the reviewed material
       return redirect('/full-description/'.request()->catalog_id)
              ->with('success', 'Thanks for your review!');
    }

    // public function thanks()
    // {
    //    return view('opac.thank-you');
    // }
}
