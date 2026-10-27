<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AppearanceController;
use App\Http\Controllers\BorrowController;
use App\Http\Controllers\CatalogingController;
use App\Http\Controllers\CheckOutInController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\CsfController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\HoldingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MaterialTypeController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\OPACController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReservationOpacController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SignedInNowController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
| Laravel matches routes in the order they are registered, so keep that order
| when moving one: a broader route registered first can swallow a later one.
|
*/

Route::get('/home', [HomeController::class, 'index']);
Route::get('/', [HomeController::class, 'index2']);
Route::get('/news/{id}', [HomeController::class, 'news'])->whereNumber('id')->middleware('throttle:60,1'); // NEWS section: Read more
// Staff open the OPAC from the admin navbar, and come back with its "Return to admin" button.
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/opac', [HomeController::class, 'openOpac'])->name('admin.opac');
    Route::get('/admin/opac/return', [HomeController::class, 'returnToAdmin'])->name('admin.return');
});

require __DIR__.'/auth.php';

//ADMIN: dashboard activity, staff profile, patron registration, administrators
Route::controller(AdminController::class)->group(function () {
    Route::get('/admin/dashboard/activity', 'dashboardActivity')->middleware('can:access.dashboard');
    Route::get('/admin/register', 'registerPatron')->name('register_patron')->middleware(['can:access.accounts', 'can:create.patron']);
    Route::get('/admin/profile/validate-email/', 'validateEmail');
    Route::get('/admin/profile', 'profile');
    Route::post('/admin/register/store', 'storePatron')->middleware(['can:access.accounts', 'can:create.patron']);
    Route::post('/admin/profile/update', 'updateProfile');
    // Throttled: it takes the current password, so it's guessable by brute force.
    Route::post('/admin/profile/update/password', 'updatePassword')->middleware('throttle:10,1');

    // Administrator management (admin accounts + role assignment) — super_admin/developer
    // only (developer implicitly has access to everything). Enforced at the route, not
    // just hidden via the sidebar @hasanyrole.
    Route::middleware('role:super_admin|developer')->group(function () {
        Route::get('/superadmin/admin-list', 'administrators')->name('administrators');
        Route::get('/superadmin/admin-create', 'createAdministrator')->name('admin.create');
        Route::post('/superadmin/admin-store', 'storeAdministrator')->name('admin.store');
        Route::get('/superadmin/administrators/json', 'adminJson');
        Route::post('/superadmin/admin-change-role', 'adminChangeRole')->name('admin.change_role');
        Route::post('/superadmin/admin/{id}/reset-password', 'resetAdminPassword')->whereNumber('id');
        Route::post('/superadmin/admin/{id}/library-code', 'regenerateAdminLibraryCode')->whereNumber('id');
        Route::delete('/superadmin/admin/{id}', 'destroyAdmin')->whereNumber('id');
    });
});

//ROLES AND PERMISSIONS
Route::controller(RolePermissionController::class)->group(function () {

    // ROLES
    Route::get('/admin/roles', 'rolesIndex')->name('admin.list_roles');
    Route::post('/admin/roles/store', 'storeRole')->name('admin.store_role');
    Route::post('/admin/roles/update', 'updateRole')->name('admin.update_role');
    Route::delete('/admin/roles/delete/{id}', 'destroyRole')->name('admin.delete_role');

    // PERMISSIONS
    Route::get('/admin/permissions', 'permissionsIndex')->name('admin.list_permissions');
    // Access matrix: grant/revoke one (role, permission) via POST (CSRF-protected).
    Route::post('/admin/role-permissions/toggle', 'toggleRolePermission')->name('admin.role_toggle_permission');

    // Permission names are referenced by the code, so only a developer may add or remove one;
    // super_admin can still edit a permission's display name / description.
    Route::post('/admin/permissions/store', 'storePermission')->name('admin.store_permission')->middleware('role:developer');
    Route::post('/admin/permissions/update', 'updatePermission')->name('admin.update_permission');
    Route::delete('/admin/permissions/delete/{id}', 'destroyPermission')->name('admin.delete_permission')->middleware('role:developer');
});

//INVENTORY: the physical count list (the rest of Inventory is further down)
Route::controller(InventoryController::class)->middleware('can:access.inventory')->group(function () {
    Route::get('/admin/inventory-list-json', 'physicalInventoryListJson');
});

//MARC TEMPLATES AND TAG LOOKUPS
Route::controller(ConfigurationController::class)->group(function () {
    // Template management lives under Administration → Utilities (Utilities module).
    Route::middleware('can:access.utilities')->group(function () {
        Route::get('/admin/marc-templates', 'marcTemplates')->name('marc-templates-index')->middleware('can:view.config.template');
        Route::get('/admin/marc-templates/json', 'marcTemplatesJson')->name('marc-templates-json')->middleware('can:view.config.template');
        Route::get('/admin/marc-templates/create', 'createMarcTemplate')->name('create-marc-template')->middleware('can:create.config.template');
        Route::get('/admin/marc-templates/edit', 'editMarcTemplate')->middleware('can:edit.config.template');
        Route::post('/admin/marc-templates/store', 'storeMarcTemplate')->middleware('can:create.config.template');
        Route::post('/admin/marc-templates/update', 'updateMarcTemplate')->middleware('can:edit.config.template');
        Route::delete('admin/marc-templates/delete/{id}', 'destroyMarcTemplate')->name('delete-marc-template')->middleware('can:delete.config.template');
    });

    // Tag / template lookups used by the record editors (any admin who can reach them).
    Route::get('/admin/marc-tags-list/json', 'marcTagsJson');
    Route::get('/admin/get-marc-tag-details', 'getMarctagDetails');
    Route::get('/admin/get-marc-template-details', 'getMarcTemplateDetails');
});

// Administration → Utilities → AI Sync Monitor — audit trail of catalog changes + AI-index sync state. The
// Utilities module plus its own permission (Roles matrix); its per-row re-sync has its own
// route here, so the page needs no Cataloging access.
Route::controller(CatalogingController::class)->middleware(['can:access.utilities', 'can:access.ai.sync.monitor'])->group(function () {
    Route::get('/admin/ai-sync-monitor', 'aiSyncMonitor')->name('ai-sync-monitor');
    Route::get('/admin/ai-sync-monitor/json', 'aiSyncMonitorJson')->name('ai-sync-monitor-json');
    Route::post('/admin/ai-sync-monitor/run', 'runSyncNow')->name('ai-sync-monitor-run');
    Route::post('/admin/ai-sync-monitor/reindex/{id}', 'reindexRecord')->whereNumber('id')->name('ai-sync-monitor-reindex');
});

//CATALOGING: the module needs access.cataloging; each action also needs its own permission
// (the same ones that show/hide the buttons), so hiding a button is never the only guard.
Route::controller(CatalogingController::class)->middleware('can:access.cataloging')->group(function () {
    Route::get('/admin/create-record', 'createRecord')->middleware('can:create.catalog');
    Route::post('/admin/view-record', 'viewRecord');
    // GET landing for a single record (used by the post-create redirect / PRG).
    Route::get('/admin/view-record/{id}', 'showRecord')->whereNumber('id')->name('show-record');
    // The record's full text for staff (held records too): open in the browser, or ?download=1.
    Route::get('/admin/full-text/{id}', 'fullText')->whereNumber('id')->name('full-text');
    // MARC export: single record + batch, each as MARC21 binary (.mrc) or MARCXML (.xml).
    Route::get('/admin/export-record/{id}/{format}', 'exportRecord')->name('export-record')->where('format', 'mrc|xml');
    Route::get('/admin/export-records/{format}', 'exportRecords')->name('export-records')->where('format', 'mrc|xml');
    // Manual RAG re-index (human override for drift): single record + bulk.
    Route::post('/admin/reindex-record/{id}', 'reindexRecord')->name('reindex-record');
    Route::post('/admin/reindex-records', 'reindexRecords')->name('reindex-records');
    Route::post('/admin/view-batch', 'viewBatch')->middleware('can:view.catalog.importlist');
    Route::get('/admin/batch-records/{id}', 'viewBatchRecords')->middleware('can:view.catalog.importlist');
    Route::get('/admin/batch-records-json/{id}', 'viewBatchRecordsJson')->middleware('can:view.catalog.importlist');
    Route::delete('/admin/delete-batch/{id}', 'destroyBatch')->name('delete-batch')->middleware('can:delete.catalog.import');
    // Import report: records skipped or flagged for review, the skipped ones as MARC, "reviewed".
    Route::get('/admin/batch-report/{id}', 'batchReport')->name('batch-report')->middleware('can:view.catalog.importlist');
    Route::get('/admin/batch-report/{id}/skipped.mrc', 'downloadSkippedRecords')->name('batch-report-skipped')->middleware('can:view.catalog.importlist');
    Route::post('/admin/import-issues/{issue}/reviewed', 'markImportIssueReviewed')->name('import-issue-reviewed')->middleware('can:edit.catalog');

    Route::get('/admin/record-list', 'recordList')->middleware('can:view.catalog.list');
    Route::get('/admin/on-hold-list', 'onholdList')->middleware('can:view.catalog.list');
    Route::get('/admin/record-list-json', 'recordListJson')->middleware('can:view.catalog.list');
    Route::get('/admin/onhold-list-json', 'onholdListJson')->middleware('can:view.catalog.list');
    // Bulk actions on the selected rows of the Record List / On-Hold / batch records tables. The
    // controller checks the list's view permission and each action's own permission.
    Route::post('/admin/records/bulk', 'bulkRecords')->name('records-bulk')->middleware('throttle:30,1');
    // Export = POST the selection for a 15-minute ticket, then a re-requestable GET download.
    Route::post('/admin/records/bulk-export', 'bulkExportTicket')->name('records-bulk-export-ticket')->middleware('throttle:30,1');
    Route::get('/admin/records/bulk-export/{token}/{format}', 'bulkExport')->name('records-bulk-export')
        ->where(['token' => '[A-Za-z0-9]{40}', 'format' => 'mrc|xml'])->middleware('throttle:60,1');

    Route::middleware('can:publish-hold.catalog')->group(function () {
        Route::post('/admin/publish-to-opac', 'publishToOpac');
        Route::post('/admin/hold-view-from-opac', 'holdViewFromOpac');
        Route::post('/admin/batch-publish', 'togglePublishHoldMaterial');
    });

    Route::get('/admin/batch-list', 'batchList')->middleware('can:view.catalog.importlist');
    Route::get('/admin/batch-list-json', 'batchListJson')->middleware('can:view.catalog.importlist');
    Route::get('/admin/create-batch', 'createBatch')->middleware('can:create.catalog.import');
    Route::post('/admin/create-batch/store', 'storeBatch')->middleware('can:create.catalog.import');
    // Material-type mapping for Batch Import: analyze the chosen file, and saved presets.
    Route::post('/admin/create-batch/analyze', 'analyzeBatchFile')->middleware('can:create.catalog.import');
    Route::post('/admin/material-maps', 'saveMaterialMap')->middleware('can:create.catalog.import');
    Route::delete('/admin/material-maps/{id}', 'destroyMaterialMap')->whereNumber('id')->middleware('can:create.catalog.import');
    Route::post('/admin/create-record/store', 'storeRecord')->middleware('can:create.catalog');
    Route::post('/admin/edit-record/update/{id}', 'updateRecord')->name('update-record')->middleware('can:edit.catalog');

    Route::get('/admin/edit-record/{id}', 'editRecord')->name('edit-record')->middleware('can:edit.catalog');
    Route::delete('/admin/delete-record/{id}', 'destroyRecord')->name('delete-record')->middleware('can:delete.catalog');
    // "Parse a Record" runs on both the Create and the Edit page.
    Route::post('/admin/import/single-marcfile', 'singleImportMarcFile')->middleware('permission:create.catalog|edit.catalog');

    // Covers, full text and copies are part of editing a record.
    Route::middleware('can:edit.catalog')->group(function () {
        Route::post('/admin/upload/cover-photo', 'uploadCoverPhoto')->name('upload-coverphoto');
        Route::delete('/admin/delete-cover-photo', 'destroyCoverPhoto')->name('delete-coverphoto');
        Route::post('/admin/view-record/{id}/cover-placeholder', 'toggleCoverPlaceholder')->whereNumber('id')->name('cover-placeholder');

        Route::post('/admin/upload/full-text', 'uploadFullText')->name('upload-fulltext');
        Route::delete('/admin/delete-full-text', 'destroyFullText')->name('delete-fulltext');

        Route::post('/admin/view-record/add-copy', 'addCopy');
        Route::put('/admin/view-record/update-copy', 'updateCopy');
        Route::delete('/admin/view-record/{id}/delete-copy', 'destroyCopy')->name('delete-copy');
    });

    Route::get('/admin/copy-list-json', 'copyListJson');
    Route::get('/admin/getcopy-details-json/{id}', 'getCopyDetailsJson');
});

//RESERVATIONS (Circulation)
Route::controller(ReservationController::class)->middleware(['can:access.circulation', 'can:circulation.reservation.view'])->group(function () {
    Route::get('/admin/reservations', 'reservations')->name('reservations');
    Route::get('/admin/reservations/json', 'reservation_json');
    Route::post('/admin/reservation/action', 'reservation_action')->middleware('can:manage.circulation.reservation');
    Route::get('/admin/reservation/chat-json/{reservation_code}', 'reservationChatJson');
    // Messaging the patron acts on the reservation, so it needs the manage permission.
    Route::post('/admin/reservation/chat/reply', 'reservationChatReply')->middleware('can:manage.circulation.reservation');
});

// The same reservation threads, read and answered from Administration → Inquiries, which lists the
// ones a patron wrote in; staff who answer inquiries need no circulation permissions for that.
Route::controller(ReservationController::class)->middleware('can:access.inquiry')->group(function () {
    Route::get('/admin/inquiry/reservation/{reservation_code}', 'reservationChatJson');
    Route::post('/admin/inquiry/reservation/reply', 'reservationChatReply')->middleware('throttle:20,1');
});

// Read-only detail popups (material / patron / transaction / reservation) shared by the
// Reservation page AND the Transaction page, so either permission opens them.
Route::controller(ReservationController::class)
    ->middleware(['can:access.circulation', 'permission:circulation.reservation.view|access.circulation.transaction'])
    ->group(function () {
        Route::get('/admin/reservation/material-json', 'viewMaterial');
        Route::get('/admin/reservation/patron-json', 'viewPatron');
        Route::get('/admin/reservation/transaction-json', 'viewTransaction');
        Route::get('/admin/reservation/view-json', 'viewReservation');
    });

// A patron's reservation request from the OPAC.
Route::controller(ReservationOpacController::class)->group(function () {
    Route::post('/request-reservation', 'requestReservation');
});

//CHECK-OUT / CHECK-IN (Circulation)
Route::controller(CheckOutInController::class)->middleware(['can:access.circulation', 'can:manage.circulation.checkouts'])->group(function () {
    Route::get('/admin/check-out-in', 'checkoutin')->name('checkoutin');
    Route::post('/admin/check-out-in/find-material', 'findMaterial');
    Route::post('/admin/check-out-in/find-patron', 'findPatron');

    Route::post('/admin/check-out/store', 'checkoutStore');
    Route::post('/admin/check-out/update', 'checkoutUpdate');

    Route::post('/admin/check-in/store', 'checkinStore');
    Route::post('/admin/check-in/search', 'checkinSearch');
    Route::post('/admin/check-out/search', 'checkoutSearch');
});

//CSF (client satisfaction feedback)
Route::controller(CsfController::class)->group(function () {
    Route::get('/csf', 'index');
    Route::get('/csf/system-review', 'create');
    Route::post('/csf/store', 'store');
});

//PATRON PROFILE: email availability check on the registration/profile forms
Route::controller(ProfileController::class)->group(function () {
    Route::get('/patron/profile/validate-email/', 'checkEmail');
});

//RESULTS
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
Route::controller(HoldingController::class)->group(function () {
    Route::post('/full-description', 'index');
    Route::get('/full-description', 'index2');
    Route::get('/full-description/{holdingsid}', 'index2');
    Route::post('/read/view', 'recordView');
    // Reading is for signed-in patrons only; guests are sent to the login page.
    Route::get('/read/{catalog_id}', 'read')->whereNumber('catalog_id')->name('read')->middleware('auth');
});

//OPAC
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



Route::controller(DownloadController::class)->group(function () {
    Route::post('/download', 'index')->middleware('throttle:30,1');   // per account: a script can't pull files in bulk
});

//SETTINGS
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
Route::controller(AppearanceController::class)->group(function () {
    Route::get('/admin/appearance', 'edit')->name('appearance');
    Route::post('/admin/appearance', 'update');
    Route::post('/admin/appearance/reset', 'reset');
    Route::get('/admin/appearance/preview', 'preview')->middleware('throttle:120,1');   // read-only: the form's live preview
    Route::middleware(['can:access.utilities', 'can:manage.theme'])->group(function () {
        Route::get('/admin/utilities/theme', 'editDefault')->name('utilities.theme');
        Route::post('/admin/utilities/theme', 'updateDefault');
    });
});

//UTILITIES (Administration → Utilities). The hub needs the Utilities module (access.utilities);
// each utility has its own permission.
Route::controller(MaterialTypeController::class)->middleware('can:access.utilities')->group(function () {
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
Route::controller(InquiryController::class)->group(function () {
    // Signed-in users only (the form is shown only to them); the sender's details come from their account.
    Route::post('/inquiry/submit', 'create')->middleware(['auth', 'throttle:5,1']); // max 5 inquiry submits/min
    // The chat button's General inquiry: guests too (they give their name, contact no. and email).
    Route::post('/inquiry/general', 'general')->middleware('throttle:5,1');
    // A guest's inquiry is answered by email: staff mark it as replied (or undo that).
    Route::post('/admin/inquiry/{id}/emailed', 'markEmailed')->whereNumber('id')->middleware(['auth', 'admin', 'can:access.inquiry']);
    // Reply + thread endpoints require auth; ownership is enforced in the controller.
    Route::post('/inquiry/reply', 'reply')->middleware(['auth', 'throttle:20,1']);
    Route::get('/inquiry/json/{id}', 'json2')->middleware('auth');
    // Marking a thread seen changes state -> POST (CSRF-protected), not a GET.
    Route::post('/inquiry/mark-seen/{id}', 'markSeen')->middleware(['auth', 'throttle:60,1']);
    Route::get('/inquiry/seen-receipts/{id}', 'seenReceipts')->middleware('auth');
});


//REVIEW
Route::controller(ReviewController::class)->group(function () {
    Route::post('/review', 'index');
    Route::post('/review/submit', 'create');
    // Quick review popup (after a download, or after reading for a minute).
    Route::get('/review/quick/{catalog_id}', 'quickStatus')->whereNumber('catalog_id');
    Route::post('/review/quick', 'quickStore')->middleware('throttle:10,1');
    Route::post('/review/quick/{catalog_id}/skip', 'quickSkip')->whereNumber('catalog_id')->middleware('throttle:30,1');   // "Not now"
});



//ADMIN
Route::controller(AdminController::class)->group(function () {
    Route::get('/admin/dashboard', 'dashboard')->middleware('can:access.dashboard');

    Route::get('/admin/borrowed', 'borrowed')->middleware(['can:access.circulation', 'can:access.circulation.transaction']);
    Route::get('/admin/visits', 'visit')->middleware('can:access.reports');

    Route::get('/admin/inquiry', 'inquiry')->middleware('can:access.inquiry');

    // Administration → Accounts hub. Its Roles and Administrators pages are for super_admin and developer whatever
    // their permissions say, so the hub is too: unticking access.accounts cannot hide the way back to Roles.
    // Signed in now (access.sessions) is one of its cards.
    Route::get('/admin/accounts', 'accounts')->name('accounts')->middleware('role_or_permission:super_admin|developer|access.accounts|access.sessions');
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

// Administration → Accounts → Signed in now: who is signed in; sign one person or everyone out (takes the password).
Route::controller(SignedInNowController::class)->middleware(['auth', 'admin', 'can:access.sessions'])->group(function () {
    Route::get('/admin/sessions', 'index')->name('admin.sessions');
    Route::delete('/admin/sessions', 'signOut')->middleware('throttle:10,1')->name('admin.sessions.sign-out');
});

//BORROWED (Circulation → Transactions)
Route::controller(BorrowController::class)->middleware(['can:access.circulation', 'can:access.circulation.transaction'])->group(function () {
    Route::get('/admin/borrowed/json', 'json');

});

//INVENTORY
Route::controller(InventoryController::class)->middleware('can:access.inventory')->group(function () {
    Route::get('/admin/inventory', 'inventory');
    Route::get('/admin/inventory/json/{cirno}', 'getCopy');
    Route::get('/admin/inventory/presented', 'presented');
    Route::get('/admin/inventory/reportpresented', 'reportpresented');
    Route::get('/admin/inventory/reportmissing', 'reportmissing');
    Route::post('/admin/inventory/reset', 'inventoryreset');
    Route::post('/admin/inventory/delete', 'inventoryremovefromlist');
});


//NEWS
Route::controller(NewsController::class)->middleware('can:access.news')->group(function () {
    Route::get('/admin/news', 'index');
    Route::get('/admin/news/add', 'create');
    Route::get('/admin/news/json', 'json');
    Route::post('/admin/news/create', 'store');

    Route::get('/admin/news/edit/{id}', 'edit');
    Route::put('/admin/news/update/{id}', 'update');
    Route::delete('/admin/news/delete/{id}', 'destroy');
    Route::post('/admin/news/headline/{id}', 'headline')->whereNumber('id');   // the one article leading the home page's NEWS
});
