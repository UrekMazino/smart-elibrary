<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Response;
use File;
use App;
use Auth;

class DownloadController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','patron']);
    }

    public function index()
    {
        try
        {
            $file = App\Models\Multimedia::where('catalog_id',request()->holdings_download_id)->first();

            //https://www.nicesnippets.com/blog/laravel-response-download-file-from-storage-example

            $myFile = public_path($file['FileLocation']);
            $headers = ['Content-Type: application/pdf'];
            $newName = 'dost-pcaarrd-elibrary-'.time().'.pdf';

            //COUNT DOWNLOAD
            $download = new App\Models\Download;
            $download->catalog_id = request()->holdings_download_id;
            $download->user_id = Auth::user()->id;
            $download->save();

            return response()->download($myFile, $newName, $headers);



        } catch (Throwable $e) {
            report($e);
     
            return view('opac.error',['message' => 'Error downloading file']);
        }
    }
}
