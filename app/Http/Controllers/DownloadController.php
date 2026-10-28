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

        // Only public records may be downloaded (published + not deleted), the same rule as Read:
        // an unpublished, held or deleted record's full text is not for patrons.
        $catalogId = request()->holdings_download_id;
        $public = ctype_digit((string) $catalogId) && App\Models\ViewCatalog::where('catalog_id', $catalogId)
            ->where('is_published', 1)->whereNull('deleted_at')->exists();
        abort_unless($public, 404);

        // The file, from private storage (never served directly by the web server). A row whose
        // file is missing gets the error page.
        $myFile = fullTextFile($catalogId);
        if (! $myFile) return view('opac.error', ['message' => 'Error downloading file']);

        try
        {
            $headers = ['Content-Type' => 'application/pdf'];
            $newName = 'dost-fnri-elibrary-'.time().'.pdf';

            //COUNT DOWNLOAD (patrons only: staff checking a record don't add to the statistics)
            if (! $user->isStaff()) {
                $download = new App\Models\Download;
                $download->catalog_id = $catalogId;
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
