<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;
use Auth;

class HoldingController extends Controller
{
    public function index()
    {
        if(Auth::check() && Auth::user()->usertype == 'Administrator')
        {  
            return redirect('/admin/dashboard');
        }

        //SAVE VIEW
        saveView(request()->catalog_id);

        $result = App\Models\ViewCatalog::where('catalog_id',request()->catalog_id)->first();

        return view('opac.full-description')->with('result',$result);
    }

    public function index2($catalog_id = null)
    {
       
        if(Auth::check() && Auth::user()->usertype == 'Administrator')
        {  
            return redirect('/admin/dashboard');
        }

        if($catalog_id){

            $result = App\Models\ViewCatalog::where('catalog_id',$catalog_id)->first();

            //SAVE VIEW
            saveView($result['catalog_id']);

            return view('opac.full-description')->with('result',$result);
        }   
        else{
            return redirect('/home');
        }
            
    }
}
