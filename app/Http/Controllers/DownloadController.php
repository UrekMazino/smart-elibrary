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
        $this->middleware(['auth']);
    }

    // Patrons and staff (browsing the OPAC from the admin) may download; a banned account may not,
    // even with a session that started before the ban (the same rule as Read).
    public function index()
    {
        $user = Auth::user();
        abort_unless($user->usertype === 'Patron' || $user->isStaff(), 403);
        abort_if($user->is_banned, 403, 'Your account has been banned from downloading materials.');

        try
        {
            $file = App\Models\Multimedia::where('catalog_id',request()->holdings_download_id)->first();

            //https://www.nicesnippets.com/blog/laravel-response-download-file-from-storage-example

            $myFile = public_path($file['FileLocation']);
            $headers = ['Content-Type: application/pdf'];
            $newName = 'dost-pcaarrd-elibrary-'.time().'.pdf';

            //COUNT DOWNLOAD (patrons only: staff checking a record don't add to the statistics)
            if (! $user->isStaff()) {
                $download = new App\Models\Download;
                $download->catalog_id = request()->holdings_download_id;
                $download->user_id = $user->id;
                $download->save();
            }

            return response()->download($myFile, $newName, $headers);



        } catch (\Throwable $e) {   // a missing record or file: the error page, not a 500
            report($e);
     
            return view('opac.error',['message' => 'Error downloading file']);
        }
    }
}
