<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use Auth;

class VisitController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','patron']);
    }

    public function create()
    {
        $visit_site = null;
        $get_copy = null;

        if(isset(request()->visit_site))
        {
            $visit_site = 1;
        }

        if(isset(request()->visit_getcopy))
        {
            $get_copy = request()->visit_getcopy;
            $holding = App\Models\Holding::where('id',$get_copy)->first();
            $consortia_id = 12;
        }


        $visit = new App\Models\Visit;
        $visit->user_id = Auth::user()->id;
        $visit->fullname = Auth::user()->name;
        $visit->contactnum = Auth::user()->contactnum;
        $visit->email = Auth::user()->email;
        $visit->schedule = date('Y-m-d',strtotime(request()->visit_scheddate))." ".date('H:i:s',strtotime(request()->visit_schedtime));
        $visit->location = request()->visit_location;
        $visit->site_visit = $visit_site;
        //$visit->get_copy = $get_copy;
        $visit->save();

        $borrow = new App\Models\Barrowed_book;
        $borrow->user_id = Auth::user()->id;
        $borrow->fullname = Auth::user()->name;
        $borrow->contactnum = Auth::user()->contactnum;
        $borrow->email = Auth::user()->email;
        $borrow->created_at = date('Y-m-d',strtotime(request()->visit_scheddate))." ".date('H:i:s',strtotime(request()->visit_schedtime));
        $borrow->catalog_id = $get_copy;
        // $borrow->consortia_id = $consortia_id;
        $borrow->save();

        return view('opac.thank-you',['message' => 'Thank You!<br/>We will contact you as soon as possible!']);
    }
}
