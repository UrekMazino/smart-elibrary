<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemManager;
use App\Models\ViewCatalog;


class MarcController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','admin']);
    }

    public function upload()
    {
        //upload muna file bago i-read per line
        if(request()->hasFile('marc_file_for_upload'))
        {
            $marc_file = request()->file('marc_file_for_upload')->store(
                'marc_file_uploaded', 'public'
            );

            return redirect('admin/uncat');
        }
    }

    public function download()
    {
            $exportfile = "";
            $material = ViewCatalog::where('id',request()->catalog_id_marc)->first();

            //kunin lahat ng data
            $exportfile .= "=245 $245a".$material['title_statement']."\r\n";

            $fsMgr = new FilesystemManager(app());
            // local disk
            $localDisk = $fsMgr->createLocalDriver(['root' => storage_path('app/public/marc_file')]);

            $filename = $material['catalog_id'].'.marc';
            $localDisk->put($filename, $exportfile);
          
            $myFile = storage_path('app/public/marc_file/'.$filename);
            $headers = ['Content-Type: text/plain'];
            $newName = $filename;
              
            return response()->download($myFile, $newName, $headers);

            echo "Your download will start shortly..";
    }
}
