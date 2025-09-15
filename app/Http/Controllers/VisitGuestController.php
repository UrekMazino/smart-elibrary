<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;

class VisitGuestController extends Controller
{
    public function create()
    {
        $visit_site = null;
        $get_copy = null;
        $consortia_id = null;
        if(isset(request()->visit_site))
        {
            $visit_site = 1;
        }

        if(isset(request()->visit_getcopy))
        {
            $get_copy = request()->visit_getcopy;
            $holding = App\Models\Holding::where('id',$get_copy)->first();
            //$consortia_id = $holding['consortia_id'];
        }


        // $visit = new App\Models\Visit;
        // $visit->fullname = request()->visit_fullname;
        // $visit->contactnum = request()->visit_contactnum;
        // $visit->email = request()->visit_email;
        // $visit->schedule = date('Y-m-d',strtotime(request()->visit_scheddate))." ".date('H:i:s',strtotime(request()->visit_schedtime));
        // $visit->location = request()->visit_location;
        // $visit->site_visit = $visit_site;
        // $visit->save();

        $borrow = new App\Models\Barrowed_book;
        $borrow->catalog_id = $get_copy;
        $borrow->fullname = request()->visit_fullname;
        $borrow->contactnum = request()->visit_contactnum;
        $borrow->email = request()->visit_email;
        $borrow->created_at = date('Y-m-d',strtotime(request()->visit_scheddate))." ".date('H:i:s',strtotime(request()->visit_schedtime));
        $borrow->catalog_id = $get_copy;
        //$borrow->consortia_id = $consortia_id;
        $borrow->save();

        return view('opac.thank-you',['message' => 'Thank You!<br/>We will contact you as soon as possible!']);
    }
}
