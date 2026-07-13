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

       //One review is enough for a material: saving this Rating removes it from the
       //patron's "To Review" list (the list excludes anything they've rated), covering
       //both the download and the read of that material.

       //Back to the reviewed material
       return redirect('/full-description/'.request()->catalog_id)
              ->with('success', 'Thanks for your review!');
    }

    // public function thanks()
    // {
    //    return view('opac.thank-you');
    // }
}
