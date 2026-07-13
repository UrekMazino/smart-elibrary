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

Route::get('/home', [HomeController::class, 'index']);
Route::get('/', [HomeController::class, 'index2']);

require __DIR__.'/auth.php';
require __DIR__.'/joe.php';
require __DIR__.'/annarose.php';

//RESULTS
use App\Http\Controllers\ResultController;
Route::get('/results', [ResultController::class, 'index']);
Route::get('/search/suggest', [ResultController::class, 'suggest']);   // instant-search JSON (OPAC overlay)



//FULL DESCRIPTION
use App\Http\Controllers\HoldingController;
Route::controller(HoldingController::class)->group(function () {
    Route::post('/full-description', 'index');
    Route::get('/full-description', 'index2');
    Route::get('/full-description/{holdingsid}', 'index2');
    Route::post('/read/view', 'recordView');
    Route::get('/read/{catalog_id}', 'read')->whereNumber('catalog_id')->name('read');
});

//OPAC
use App\Http\Controllers\OPACController;

Route::controller(OPACController::class)->prefix('patron')->group(function () {
    Route::get('/profile', 'profile');
    Route::post('/profile/update/{id}', 'updatePatronProfile')->name('update.patron.profile');
    Route::get('/transactions', 'transactions');
    Route::get('/to-review', 'toReview');
    Route::get('/inquiries', 'inquiries');
    Route::get('/reservation/chat-json/{reservation_code}', 'reservationChatJson');
    Route::post('/reservation/chat/reply', 'reservationChatReply');
});



use App\Http\Controllers\DownloadController;
Route::controller(DownloadController::class)->group(function () {
    Route::post('/download', 'index');
});

//SETTINGS
use App\Http\Controllers\SettingController;
Route::controller(SettingController::class)->group(function () {
    Route::get('/admin/settings', 'index');
    Route::post('/admin/settings', 'update');
});

//INQUIRY
use App\Http\Controllers\InquiryController;
Route::controller(InquiryController::class)->group(function () {
    Route::post('/inquiry/submit', 'create')->middleware('throttle:5,1'); // max 5 inquiry submits/min
    Route::post('/inquiry/reply', 'reply');
    Route::get('/inquiry/json/{id}', 'json2');
    Route::get('/inquiry/seen/{id}', 'seen');
});


//REVIEW
use App\Http\Controllers\ReviewController;
Route::controller(ReviewController::class)->group(function () {
    Route::post('/review', 'index');
    Route::post('/review/submit', 'create');
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
    Route::post('/admin/user/toggle-flag', 'toggleUserFlag');

    Route::get('/admin/report', 'report');

    Route::post('/admin/print-report', 'reportprint');
    Route::post('/admin/report-preview', 'reportPreview');

    Route::post('/admin/patron-borrow', 'assignMaterialToPatron');

    Route::post('/admin/patron-action', 'actionPatron');

    Route::get('/admin/patron/json', 'patronjson');


});

//ADMIN
use App\Http\Controllers\AcquisitionController;
Route::controller(AcquisitionController::class)->group(function () {
        
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






