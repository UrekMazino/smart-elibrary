<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return "hello";
// });

// Route::get('/search-result', function () {
//     return view('opac.search-result');
// });

// Route::get('/full-description', function () {
//     return view('opac.full-description');
// });

// Route::get('/dashboard', function () {
//     return view('admin.index');
// })->middleware(['auth'])->name('dashboard');
Route::get('/admin/login', [HomeController::class, 'adminLogin']);
Route::get('/home', [HomeController::class, 'index']);
Route::get('/', [HomeController::class, 'index2']);

//MIGRATE DATA
use App\Http\Controllers\MigrateController;
Route::controller(MigrateController::class)->group(function () {
    Route::get('/migrate/holdings', 'holdings');
});

require __DIR__.'/auth.php';
require __DIR__.'/joe.php';
require __DIR__.'/annarose.php';

// Route::get('/', function () {
//     return redirect('/home');
// });


//RESULTS
use App\Http\Controllers\ResultController;
//Route::post('/results', [ResultController::class, 'index']);
Route::get('/results', [ResultController::class, 'index']);



//FULL DESCRIPTION
use App\Http\Controllers\HoldingController;
Route::controller(HoldingController::class)->group(function () {
    Route::post('/full-description', 'index');
    Route::get('/full-description', 'index2');
    Route::get('/full-description/{holdingsid}', 'index2');
});

//OPAC
use App\Http\Controllers\OPACController;

Route::controller(OPACController::class)->prefix('patron')->group(function () {
    Route::get('/profile', 'profile');
    Route::post('/profile/update/{id}', 'updatePatronProfile')->name('update.patron.profile');
    Route::get('/transactions', 'transactions');
    Route::get('/to-review', 'toReview');
    Route::get('/inquiries', 'inquiries');
});



use App\Http\Controllers\DownloadController;
Route::controller(DownloadController::class)->group(function () {
    Route::post('/download', 'index');
});

//INQUIRY
use App\Http\Controllers\InquiryController;
Route::controller(InquiryController::class)->group(function () {
    Route::post('/inquiry/submit', 'create');
    Route::post('/inquiry/reply', 'reply');
    Route::get('/inquiry/get/{id}', 'json1');
    Route::get('/inquiry/json/{id}', 'json2');
    Route::get('/inquiry/seen/{id}', 'seen');
});


//REVIEW
use App\Http\Controllers\ReviewController;
Route::controller(ReviewController::class)->group(function () {
    Route::post('/review', 'index');
    Route::post('/review/submit', 'create');
});


//VISIT
use App\Http\Controllers\VisitGuestController;
Route::controller(VisitGuestController::class)->group(function () {
    Route::post('/visit/guest', 'create');
});

use App\Http\Controllers\VisitController;
Route::controller(VisitController::class)->group(function () {
    Route::post('/visit/patron', 'create');
});

//ADMIN
use App\Http\Controllers\AdminController;
Route::controller(AdminController::class)->group(function () {
    Route::get('/admin/dashboard', 'dashboard');

    Route::get('/admin/borrowed', 'borrowed');
    Route::get('/admin/visits', 'visit');
    
    Route::get('/admin/inquiry', 'inquiry');
    Route::get('/admin/inquiry/json', 'inquiryjson');
    
    Route::get('/admin/patron', 'patron')->name('patrons');

    Route::get('/admin/report', 'report');

    Route::post('/admin/print-report', 'reportprint');

    Route::post('/admin/patron-borrow', 'assignMaterialToPatron');

    Route::post('/admin/patron-action', 'actionPatron');

    Route::get('/admin/patron/json', 'patronjson');


});

//ADMIN
use App\Http\Controllers\AcquisitionController;
Route::controller(AcquisitionController::class)->group(function () {
        
    // Route::get('/admin/acquisition', 'index');
    Route::get('/admin/new-acquisition', 'index2');
    Route::post('/admin/acquisition/create', 'create');
    Route::post('/admin/edit-acquisition-view', 'editview');
    Route::get('/admin/edit-acquisition-view-get/{id}', 'editviewGet');
    Route::post('/admin/delete-acquisition', 'delete');
    Route::post('/admin/set-as-acquisition', 'setas');

    Route::post('/admin/update-acquisition', 'update');

    Route::get('/admin/catalog', 'index');
    Route::get('/admin/catalog/json', 'catalogjson');

    Route::get('/admin/uncat', 'uncatalog');
    Route::get('/admin/uncatalog/json', 'uncatalogjson');

});

use App\Http\Controllers\BorrowController;
Route::controller(BorrowController::class)->group(function () {  
    Route::get('/admin/borrowed/json', 'json');
    Route::post('/admin/borrowed/action', 'action');

});

use App\Http\Controllers\InventoryController;
Route::controller(InventoryController::class)->group(function () {
    Route::get('/admin/inventory', 'inventory');
    Route::get('/admin/inventory/json/{cirno}', 'getCopy');
    Route::get('/admin/inventory/presented', 'presented');
    Route::get('/admin/inventory/reportpresented', 'reportpresented');
    Route::get('/admin/inventory/reportmissing', 'reportmissing');
    Route::post('/admin/inventory/reset', 'inventoryreset');
    Route::post('/admin/inventory/delete', 'inventoryremovefromlist');
});


use App\Http\Controllers\NewsController;
Route::controller(NewsController::class)->group(function () {
    Route::get('/admin/news', 'index');
    Route::get('/admin/news/add', 'create');
    Route::get('/admin/news/json', 'json');
    Route::post('/admin/news/create', 'store');

    Route::get('/admin/news/edit/{id}', 'edit');
    Route::put('/admin/news/update/{id}', 'update');
    Route::delete('/admin/news/delete/{id}', 'destroy');
});


// Route::get('update-copy', function () {
//         $list = App\Models\Holdingcopy::where('acquisition_mode',5)->get();
//         foreach ($list as $key => $value) {
//             $modes = null;

//             switch ($value->AcquisitionMode) {
//                 case 1:
//                         $modes = "Purchase";
//                     break;
//                 case 2:
//                         $modes = "Subscription";
//                     break;
//                 case 3:
//                         $modes = "Donation";
//                     break;
//                 case 4:
//                         $modes = "Complimentary";
//                     break;
//                 case 5:
//                         $modes = "Exchange";
//                     break;
//                 case 6:
//                         $modes = "Download";
//                     break;
            
//             }

//             App\Models\Holdingcopy::where('id',$value->id)->update(['acquisition_mode' => $modes]);

//         }
//     });


// Route::get('/status', function () {
    // $status = ["status" => true];

    // return $status;

    // $directory = "public/frontpages";
    // $files = Storage::allFiles($directory);
  
    // foreach ($files as $key => $value) {
    //     $path = "frontpages/".substr($value,-16);
    //     $filename = explode('.',substr($value,-16));
    //     echo $filename[0]."<br/>";

    //     $action = new App\Models\Frontpage;
    //     $action->catalog_id = $filename[0];
    //     $action->FrontPageLocation = $path;
    //     $action->save();
    // }
// });

// Route::get('/insert-download', function () {
//     for ($i=0; $i < 500; $i++) { 
//         $download = new App\Models\Download;
//         $download->catalog_id = "PCRD-H004491";
//         $download->user_id = 14019;
//         $download->save();
//     }
// });

