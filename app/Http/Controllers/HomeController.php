<?php

namespace App\Http\Controllers;
use App\Providers\RouteServiceProvider;

use Illuminate\Http\Request;
use Auth;

class HomeController extends Controller
{
    public function index()
    {
        if(!Auth::user())
        {
            return view('opac.index');
        }
        else
        {
            if(Auth::user()->usertype == 'Patron')
            {
                return view('opac.index');
            }
            else
            {
                return redirect('admin/dashboard');
            }
        }

    }

    public function index2()
    {
        return redirect(RouteServiceProvider::HOME);
    }
}
