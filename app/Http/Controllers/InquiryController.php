<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App;

class InquiryController extends Controller
{
    public function create()
    {
        $inq = new App\Models\Inquiry;
        
        if(Auth::check())
        {
            $inq->user_id = Auth::user()->id;
            $inq->catalog_id = request()->catalog_id_inquiry;
            $inq->fullname = Auth::user()->name;
            $inq->contactnum = Auth::user()->contactnum;
            $inq->email = Auth::user()->email;
            $inq->message = request()->inquiry_message;
        }
        else
        {
            $inq->catalog_id = request()->catalog_id_inquiry;
            $inq->fullname = request()->inquiry_fullname;
            $inq->contactnum = request()->inquiry_contactnum;
            $inq->email = request()->inquiry_email;
            $inq->message = request()->inquiry_message;
        }

        $inq->save();
        
        if(Auth::check() && Auth::user()->usertype = 'Administrator')
            return redirect('/admin/inquiry');
        else
            return view('opac.thank-you',['message' => 'Thank You!<br/>We will contact you as soon as possible!']);
    }

    public function reply()
    {
        $inq = new App\Models\Reply_inquiry;
        $inq->inquiry_id = request()->catalog_id_inquiry_reply;
        $inq->user_id = Auth::user()->id;
        $inq->message = request()->inquiry_message_reply;
        $inq->save();

        return redirect('/inquiries');
    }

    public function json2($id)
    {
        $inq = App\Models\View_inquiry_thread::where('id',$id)->orderBy('thread_date')->get();
        return $inq;
    }

    public function seen($id)
    {
        $inq = App\Models\Reply_inquiry::where('inquiry_id',$id)->where('user_id','!=',Auth::user()->id)->update(['seen' => date('Y-m-d H:i:s'),'seen_by' => Auth::user()->id]);
        return $inq;
    }
}
