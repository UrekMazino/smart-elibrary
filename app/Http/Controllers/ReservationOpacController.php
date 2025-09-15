<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        $reservation_code = generateReservationCode();
        $transaction_code = generateTransactionCode();
        $user = Auth::user();

        $transaction = Circulation::create([
            'user_id' => $user->id,
            'catalog_id' => $request->catalog_id,
            'reservation_code' => $reservation_code,
            'transaction_code' => $transaction_code,
            'status' => 1,
            'catalog_id' => $request->catalog_id,
            'user_library_code'=> $user->library_code,
        ]);

        // Reservation Details  
        CirculationReservation::create([
            'user_id' => $user->id,
            'location_id' => 1,
            'requested_by' => $user->name,
            'reservation_code' => $reservation_code,
            'visit_date' => $request->visit_scheddate,
            'visit_time' => $request->visit_schedtime,
            'title_statement' => $request->title_statement,
        ]);

        CirculationFineHistory::create([
            'transaction_code' => $transaction_code,
        ]);

        return view('opac.thank-you',['message' => 'Thank You!<br/>We will contact you as soon as possible!']);
    }


}
