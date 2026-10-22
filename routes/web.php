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
// Staff open the OPAC from the admin navbar, and come back with its "Return to admin" button.
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/opac', [HomeController::class, 'openOpac'])->name('admin.opac');
    Route::get('/admin/opac/return', [HomeController::class, 'returnToAdmin'])->name('admin.return');
});

require __DIR__.'/auth.php';
require __DIR__.'/joe.php';
require __DIR__.'/annarose.php';

//RESULTS
use App\Http\Controllers\ResultController;
Route::get('/results', [ResultController::class, 'index']);
Route::get('/search/suggest', [ResultController::class, 'suggest']);   // instant-search JSON (OPAC overlay)
Route::get('/search/facet', [ResultController::class, 'facetValues'])->middleware('throttle:60,1');            // "Find an author" box
Route::get('/search/cite/{catalogId}', [ResultController::class, 'cite'])->whereNumber('catalogId');           // Cite button
Route::get('/search/quick/{catalogId}', [ResultController::class, 'quickView'])->whereNumber('catalogId')->middleware('throttle:60,1'); // Quick view button
Route::post('/search/saved', [ResultController::class, 'toggleSaved'])->middleware(['auth', 'throttle:60,1']); // Save button
// AI mode answer (OPAC "Ask the Library").
//  - ai.enabled : the admin kill switch (Settings). Off -> 404, feature genuinely gone.
//  - throttle   : each real request will be a full generation on one GPU, so the edge
//                 has to shed load (RAG_INTEGRATION_PLAN.md §10.5).
Route::get('/search/ai-answer', [ResultController::class, 'aiAnswer'])->middleware(['ai.enabled', 'throttle:10,1']);



//FULL DESCRIPTION
use App\Http\Controllers\HoldingController;
Route::controller(HoldingController::class)->group(function () {
    Route::post('/full-description', 'index');
    Route::get('/full-description', 'index2');
    Route::get('/full-description/{holdingsid}', 'index2');
    Route::post('/read/view', 'recordView');
    // Reading is for signed-in patrons only; guests are sent to the login page.
    Route::get('/read/{catalog_id}', 'read')->whereNumber('catalog_id')->name('read')->middleware('auth');
});

//OPAC
use App\Http\Controllers\OPACController;

Route::controller(OPACController::class)->prefix('patron')->group(function () {
    Route::get('/profile', 'profile');
    // Patrons' own password endpoint. The modal used to post to /admin/profile/update/password,
    // which the `admin` middleware bounced, so the change silently never happened.
    // Throttled: it takes the current password, so it's guessable by brute force.
    // Declared before the {id} route below, which would otherwise match "password" as an id.
    Route::post('/profile/update/password', 'updatePassword')->name('update.patron.password')->middleware('throttle:10,1');
    // whereNumber so this can never swallow a literal path segment (see above).
    Route::post('/profile/update/{id}', 'updatePatronProfile')->name('update.patron.profile')->whereNumber('id');
    Route::get('/transactions', 'transactions');
    Route::get('/to-review', 'toReview');
    Route::get('/inquiries', 'inquiries');
    Route::get('/reservation/chat-json/{reservation_code}', 'reservationChatJson');
    Route::post('/reservation/chat/reply', 'reservationChatReply')->middleware('throttle:20,1');
});



use App\Http\Controllers\DownloadController;
Route::controller(DownloadController::class)->group(function () {
    Route::post('/download', 'index');
});

//SETTINGS
use App\Http\Controllers\SettingController;
// Administration → Settings: the Configuration module plus its own permission (Roles matrix).
Route::controller(SettingController::class)->middleware(['can:access.configuration', 'can:access.settings'])->group(function () {
    Route::get('/admin/settings', 'index');
    Route::post('/admin/settings', 'update');
    // Drift scan (read-only: compares the index against the DB and reports the drift).
    Route::get('/admin/settings/scan-search', 'scanSearchIndex')->middleware('throttle:10,1');
    // Manual "Re-sync search index" button (rebuilds Meilisearch from the DB).
    Route::post('/admin/settings/reindex-search', 'reindexSearch')->middleware('throttle:5,1');
    // AI agent connection status (read-only) for the AI mode card.
    Route::get('/admin/settings/ai-status', 'aiStatus')->middleware(['can:manage.ai.mode', 'throttle:20,1']);
    // Clear cached data + compiled views (not sessions, not the search index).
    Route::post('/admin/settings/clear-cache', 'clearCache')->middleware('throttle:10,1');

    Route::post('/admin/settings/generate-sitemap', 'generateSitemap')->middleware('throttle:5,1');
});

// APPEARANCE (App\Services\Appearance): each staff member's own, from the profile menu, and the
// library default under Utilities → Theme.
use App\Http\Controllers\AppearanceController;
Route::controller(AppearanceController::class)->group(function () {
    Route::get('/admin/appearance', 'edit')->name('appearance');
    Route::post('/admin/appearance', 'update');
    Route::post('/admin/appearance/reset', 'reset');
    Route::get('/admin/appearance/preview', 'preview')->middleware('throttle:120,1');   // read-only: the form's live preview
    Route::middleware(['can:access.configuration', 'can:manage.theme'])->group(function () {
        Route::get('/admin/utilities/theme', 'editDefault')->name('utilities.theme');
        Route::post('/admin/utilities/theme', 'updateDefault');
    });
});

//UTILITIES (Administration → Utilities). The hub needs the Administration module; each
// utility has its own permission.
use App\Http\Controllers\MaterialTypeController;
Route::controller(MaterialTypeController::class)->middleware('can:access.configuration')->group(function () {
    Route::get('/admin/utilities', 'utilities')->name('utilities');
    Route::middleware('can:manage.material.types')->group(function () {
        Route::get('/admin/utilities/material-types', 'index')->name('utilities.material-types');
        Route::post('/admin/utilities/material-types', 'store');
        Route::put('/admin/utilities/material-types/{id}', 'update')->whereNumber('id');
        Route::post('/admin/utilities/material-types/{id}/toggle', 'toggle')->whereNumber('id');
        Route::post('/admin/utilities/material-types/{id}/merge', 'merge')->whereNumber('id');
        Route::delete('/admin/utilities/material-types/{id}', 'destroy')->whereNumber('id');
    });
});

//INQUIRY
use App\Http\Controllers\InquiryController;
Route::controller(InquiryController::class)->group(function () {
    // Signed-in users only (the form is shown only to them); the sender's details come from their account.
    Route::post('/inquiry/submit', 'create')->middleware(['auth', 'throttle:5,1']); // max 5 inquiry submits/min
    // Reply + thread endpoints require auth; ownership is enforced in the controller.
    Route::post('/inquiry/reply', 'reply')->middleware(['auth', 'throttle:20,1']);
    Route::get('/inquiry/json/{id}', 'json2')->middleware('auth');
    // Marking a thread seen changes state -> POST (CSRF-protected), not a GET.
    Route::post('/inquiry/mark-seen/{id}', 'markSeen')->middleware(['auth', 'throttle:60,1']);
    Route::get('/inquiry/seen-receipts/{id}', 'seenReceipts')->middleware('auth');
});


//REVIEW
use App\Http\Controllers\ReviewController;
Route::controller(ReviewController::class)->group(function () {
    Route::post('/review', 'index');
    Route::post('/review/submit', 'create');
    // Quick review popup (after a download, or after reading for a minute).
    Route::get('/review/quick/{catalog_id}', 'quickStatus')->whereNumber('catalog_id');
    Route::post('/review/quick', 'quickStore')->middleware('throttle:10,1');
});



//ADMIN
use App\Http\Controllers\AdminController;
Route::controller(AdminController::class)->group(function () {
    Route::get('/admin/dashboard', 'dashboard')->middleware('can:access.dashboard');

    Route::get('/admin/borrowed', 'borrowed')->middleware(['can:access.circulation', 'can:access.circulation.transaction']);
    Route::get('/admin/visits', 'visit')->middleware('can:access.reports');

    Route::get('/admin/inquiry', 'inquiry')->middleware('can:access.inquiry');
    Route::get('/admin/inquiry/json', 'inquiryjson')->middleware('can:access.inquiry');

    // Administration → Accounts hub. Its Roles and Administrators pages are for super_admin and developer whatever
    // their permissions say, so the hub is too: unticking access.accounts cannot hide the way back to Roles.
    Route::get('/admin/accounts', 'accounts')->name('accounts')->middleware('role_or_permission:super_admin|developer|access.accounts');
    Route::get('/admin/patron', 'patron')->name('patrons')->middleware('can:access.accounts');
    Route::post('/admin/user/toggle-flag', 'toggleUserFlag')->middleware('can:manage.patron.status');

    Route::get('/admin/report', 'report')->middleware('can:access.reports');

    Route::post('/admin/print-report', 'reportprint')->middleware('can:access.reports');
    Route::post('/admin/report-preview', 'reportPreview')->middleware('can:access.reports');


    // Patron account actions — each gated by its own permission (UI hides what you can't do).
    Route::post('/admin/patron/{id}/reset-password', 'resetPatronPassword')->whereNumber('id')->middleware(['can:access.accounts', 'can:edit.patron']);
    Route::post('/admin/patron/{id}/library-code', 'regeneratePatronLibraryCode')->whereNumber('id')->middleware(['can:access.accounts', 'can:edit.patron']);
    Route::delete('/admin/patron/{id}', 'destroyPatron')->whereNumber('id')->middleware(['can:access.accounts', 'can:delete.patron']);

    Route::get('/admin/patron/json', 'patronjson')->middleware('can:access.accounts');


});

//ADMIN
use App\Http\Controllers\BorrowController;
Route::controller(BorrowController::class)->middleware(['can:access.circulation', 'can:access.circulation.transaction'])->group(function () {
    Route::get('/admin/borrowed/json', 'json');

});

use App\Http\Controllers\InventoryController;
Route::controller(InventoryController::class)->middleware('can:access.inventory')->group(function () {
    Route::get('/admin/inventory', 'inventory');
    Route::get('/admin/inventory/json/{cirno}', 'getCopy');
    Route::get('/admin/inventory/presented', 'presented');
    Route::get('/admin/inventory/reportpresented', 'reportpresented');
    Route::get('/admin/inventory/reportmissing', 'reportmissing');
    Route::post('/admin/inventory/reset', 'inventoryreset');
    Route::post('/admin/inventory/delete', 'inventoryremovefromlist');
});


use App\Http\Controllers\NewsController;
Route::controller(NewsController::class)->middleware('can:access.news')->group(function () {
    Route::get('/admin/news', 'index');
    Route::get('/admin/news/add', 'create');
    Route::get('/admin/news/json', 'json');
    Route::post('/admin/news/create', 'store');

    Route::get('/admin/news/edit/{id}', 'edit');
    Route::put('/admin/news/update/{id}', 'update');
    Route::delete('/admin/news/delete/{id}', 'destroy');
});






