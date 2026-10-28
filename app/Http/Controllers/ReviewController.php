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
      // The same rules as the quick review popup (quickStore): a 1-5 star rating of a public record.
      request()->validate([
          'catalog_id'   => ['required', 'integer'],
          'rating_input' => ['required', 'integer', 'between:1,5'],
          'remarks'      => ['nullable', 'string', 'max:1000'],
      ]);
      $record = App\Models\ViewCatalog::where('catalog_id', request()->catalog_id)
          ->where('is_published', 1)->whereNull('deleted_at')->first();
      abort_unless($record, 404);

      // One review per patron per material (a unique key on ratings).
      if (App\Models\Rating::where('user_id', Auth::id())->where('catalog_id', request()->catalog_id)->exists()) {
          return redirect(recordUrl($record))->with('success', 'You have already reviewed this material. Thank you!');
      }

      // Only its readers rate a material: a patron who has read or downloaded it (no rating without reading).
      if (! hasReadOrDownloaded(Auth::id(), $record->catalog_id)) {
          return redirect(recordUrl($record))->with('error', 'Read or download this material first, then you can rate it.');
      }

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
       return redirect(recordUrl($record))
              ->with('success', 'Thanks for your review!');
    }

    /**
     * Quick review popup (opac.partials.quick-review): has this patron already reviewed the
     * material? The popup only opens when they haven't.
     */
    public function quickStatus($catalog_id)
    {
        $reviewed = App\Models\Rating::where('user_id', Auth::id())->where('catalog_id', $catalog_id)->exists();
        $skipped  = \DB::table('review_prompt_skips')->where('user_id', Auth::id())->where('catalog_id', $catalog_id)->exists();

        return response()->json(['reviewed' => $reviewed, 'skipped' => $skipped]);
    }

    /**
     * "Not now" in the quick review popup: don't prompt this patron about the material again, on any
     * device. Kept on the server, not in the browser (OWASP ASVS 14.3.1): a browser copy outlived
     * sign-out and showed the next person on a shared computer what the patron had read. The material
     * stays in the patron's To Review list.
     */
    public function quickSkip($catalog_id)
    {
        $published = App\Models\ViewCatalog::where('catalog_id', $catalog_id)->where('is_published', 1)->whereNull('deleted_at')->exists();
        abort_unless($published, 404);

        \DB::table('review_prompt_skips')->insertOrIgnore(['user_id' => Auth::id(), 'catalog_id' => (int) $catalog_id, 'created_at' => now()]);

        return response()->json(['ok' => true]);
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

        // Only its readers rate a material (the popup only offers itself after a read or a download anyway).
        if (! hasReadOrDownloaded(Auth::id(), $data['catalog_id'])) {
            return response()->json(['ok' => false, 'message' => 'Read or download this material first, then you can rate it.'], 403);
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
