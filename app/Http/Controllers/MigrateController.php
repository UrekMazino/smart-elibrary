<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App;

class MigrateController extends Controller
{
    public function holdings()
    {
        $list = App\Models\OLD_SLIM\Holding::where('TitleStatement','like','%$b%')->get();
        foreach($list AS $lists){
        $title = explode('$b',$lists->TitleStatement);
        //$title = explode('$b',$title[]);
        $remain = null;

        if(isset($title[1]))
        {
            $title2 = explode('$c',$title[1]);

            if(isset($title2[1]))
            {
                $remain = $title2[0];
                echo $lists->TitleStatement." | Remainder : ".$title2[0]." - Responsibility : ".$title2[1]."<hr/>";
            }   
            else
            {
                echo $lists->TitleStatement." | Remainder ".$title[1]." - Responsibility : N/A<hr/>";
            }
                
        }
        else
        {
            $title2 = explode('$c',$title[1]);

            if(isset($title2[1]))
                echo $lists->TitleStatement." | Remainder N/A - Responsibility : ".$title2[1]."<hr/>";
            else
                echo $lists->TitleStatement." | Remainder N/A - Responsibility : N/A<hr/>";
        }


        //App\Models\Holding::where('id',$lists->id)->update(['title_remainder' => $remain]);
    }
    }
}
