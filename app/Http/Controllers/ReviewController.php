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
       return redirect(recordUrl(App\Models\ViewCatalog::where('catalog_id', request()->catalog_id)->first()))
              ->with('success', 'Thanks for your review!');
    }

    /**
     * Quick review popup (opac.partials.quick-review): has this patron already reviewed the
     * material? The popup only opens when they haven't.
     */
    public function quickStatus($catalog_id)
    {
        $reviewed = App\Models\Rating::where('user_id', Auth::id())->where('catalog_id', $catalog_id)->exists();

        return response()->json(['reviewed' => $reviewed]);
    }

    /** Save a review from the quick review popup (star rating + optional / templated remarks). */
    public function quickStore(Request $request)
    {
        $data = $request->validate([
            'catalog_id' => ['required', 'integer'],
            'rating'     => ['required', 'integer', 'between:1,5'],
            'remarks'    => ['nullable', 'string', 'max:1000'],
        ]);

        $published = App\Models\ViewCatalog::where('catalog_id', $data['catalog_id'])->where('is_published', 1)->whereNull('deleted_at')->exists();
        abort_unless($published, 404);

        // One review per patron per material (the To Review list drops it once rated).
        if (App\Models\Rating::where('user_id', Auth::id())->where('catalog_id', $data['catalog_id'])->exists()) {
            return response()->json(['ok' => false, 'message' => 'You have already reviewed this material.'], 409);
        }

        $review = new App\Models\Rating;
        $review->catalog_id = $data['catalog_id'];
        $review->user_id = Auth::id();
        $review->rating = $data['rating'];
        $review->remarks = trim((string) ($data['remarks'] ?? '')) ?: null;
        $review->save();

        return response()->json(['ok' => true]);
    }

    // public function thanks()
    // {
    //    return view('opac.thank-you');
    // }
}
