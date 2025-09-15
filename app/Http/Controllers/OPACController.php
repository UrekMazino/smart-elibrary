<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use Auth;
use App\Models\User;

use MeiliSearch\Client;


class OPACController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','patron']);
    }

    public function profile()
    {
        $user = User::find(Auth::id());
        return view('opac.profile', ['user' => $user]);
    }

    public function updatePatronProfile(Request $request, $id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'contactnum' => ['required', 'string', 'max:255'],
            'birthdate' => ['required', 'string', 'max:100']
        ]);
        User::where('id',Auth::user()->id)
            ->update([
                'name' => $request->name,
                'contactnum' => $request->contactnum,
                'affiliation' => $request->affiliation,
                'sex' => $request->sex,
                'birthdate' => $request->birthdate,
                'patrontypes_id' => $request->patron_type_id,
                'region_id' => $request->region_id,
                'email' => $request->email,
                'location' => $request->address
            ]);

        $user = User::find(Auth::user()->id);
        return view('opac.profile', ['user' => $user, 'success' => 'Profile updated successfully!']);
    }

    public function transactions()
    {
        $list = App\Models\Circulation::where('user_id',Auth::user()->id)->get();

        $flag = true;
        if(count($list) == 0)
        {
            $flag = false;
        }

        return view('opac.barrowed',['list' => $list,'flag' => $flag]);
    }

    public function inquiries()
    {
        $list = App\Models\View_inquiry::where('user_id',Auth::user()->id)->paginate(5);
        $flag = true;
        if(count($list) == 0)
        {
            $flag = false;
        }

        return view('opac.inquiries',['list' => $list,'flag' => $flag]);
    }

    public function toReview()
    {

        $list = App\Models\View_to_review::where('user_id',Auth::user()->id)->whereNull('to_review')->paginate(5);

        $flag = true;
        if(count($list) == 0)
        {
            $flag = false;
        }

        //$list = App\Models\View_to_review::where('user_id',Auth::user()->id)->whereNull('to_review')->groupBy(['user_id','to_review'])->get();
        return view('opac.to-review',['list' => $list,'flag' => $flag]);
    }

}
