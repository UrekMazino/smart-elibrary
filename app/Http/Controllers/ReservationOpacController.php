<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App;
use Auth;
use App\Models\Circulation;
use App\Models\CirculationReservation;
use App\Models\CirculationFineHistory;

class ReservationOpacController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','patron']);
    }

    
    public function requestReservation(Request $request)
    {
        // The booking pickers post e.g. "10/20/2026" and "9:00 am". The library takes visits
        // Monday to Friday, 8 AM to 5 PM (as the form says).
        $request->validate([
            'catalog_id'      => ['required', 'integer', 'exists:catalogs,id'],
            'visit_scheddate' => ['bail', 'required', 'date', 'after_or_equal:today', function ($attribute, $value, $fail) {
                if (Carbon::parse($value)->isWeekend()) $fail('Visits are Monday to Friday. Please pick a weekday.');
            }],
            'visit_schedtime' => ['bail', 'required', function ($attribute, $value, $fail) {
                $time = strtotime('2000-01-01 ' . $value);
                if ($time === false) return $fail('Please pick a visit time.');
                if (date('H:i', $time) < '08:00' || date('H:i', $time) > '17:00') $fail('Visits are between 8:00 AM and 5:00 PM.');
            }],
        ], [
            'visit_scheddate.required'       => 'Please pick a visit date.',
            'visit_scheddate.date'           => 'Please pick a visit date.',
            'visit_scheddate.after_or_equal' => "The visit date can't be in the past.",
            'visit_schedtime.required'       => 'Please pick a visit time.',
        ]);

        // Only public records (published, not deleted) can be reserved. The title shown to staff
        // comes from the catalogue, not from the form: the form's copy is whatever the browser sent.
        $record = App\Models\ViewCatalog::where('catalog_id', $request->catalog_id)
            ->where('is_published', 1)->whereNull('deleted_at')->first(['title_statement']);
        abort_unless($record, 404);
        $title = $record->title_statement;

        $reservation_code = generateReservationCode();
        $transaction_code = generateTransactionCode();
        $user = Auth::user();

        // The ticket, its reservation details and its (empty) fine record are one unit: all
        // three are written or none is.
        DB::transaction(function () use ($request, $user, $title, $reservation_code, $transaction_code) {
            Circulation::create([
                'user_id' => $user->id,
                'catalog_id' => $request->catalog_id,
                'reservation_code' => $reservation_code,
                'transaction_code' => $transaction_code,
                'status' => 1,
                'patron_seen_status' => 1,   // the patron's own request: nothing new for them to see
                'user_library_code'=> $user->library_code,
            ]);

            // Reservation Details
            CirculationReservation::create([
                'user_id' => $user->id,
                'location_id' => 1,
                'requested_by' => $user->name,
                'reservation_code' => $reservation_code,
                'visit_date' => Carbon::parse($request->visit_scheddate)->toDateString(),
                'visit_time' => date('H:i:s', strtotime('2000-01-01 ' . $request->visit_schedtime)),
                'title_statement' => $title,
            ]);

            CirculationFineHistory::create([
                'transaction_code' => $transaction_code,
            ]);
        });

        return view('opac.thank-you',['message' => 'Thank You!<br/>We will contact you as soon as possible!']);
    }


}
