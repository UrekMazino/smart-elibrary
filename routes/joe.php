<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\CatalogingController;
use App\Http\Controllers\CheckOutInController;
use App\Http\Controllers\CsfController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ReservationOpacController;
use App\Http\Controllers\ConfigurationController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

//ADMIN
Route::controller(AdminController::class, 'admin')->group(function () {
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

Route::controller(RolePermissionController::class, 'admin')->group(function () {

    // ROLES
    Route:: get('/admin/roles', 'rolesIndex')->name('admin.list_roles');
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

Route::controller(InventoryController::class)->middleware('can:access.inventory')->group(function () {
    Route::get('/admin/inventory-list-json', 'physicalInventoryListJson');
});
Route::controller(ConfigurationController::class, 'admin')->group(function () {
    // Template management lives under Administration → Utilities (Configuration module).
    Route::middleware('can:access.configuration')->group(function () {
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
// Configuration module plus its own permission (Roles matrix); its per-row re-sync has its own
// route here, so the page needs no Cataloging access.
Route::controller(CatalogingController::class)->middleware(['can:access.configuration', 'can:access.ai.sync.monitor'])->group(function () {
    Route::get('/admin/ai-sync-monitor', 'aiSyncMonitor')->name('ai-sync-monitor');
    Route::get('/admin/ai-sync-monitor/json', 'aiSyncMonitorJson')->name('ai-sync-monitor-json');
    Route::post('/admin/ai-sync-monitor/run', 'runSyncNow')->name('ai-sync-monitor-run');
    Route::post('/admin/ai-sync-monitor/reindex/{id}', 'reindexRecord')->whereNumber('id')->name('ai-sync-monitor-reindex');
});

// Cataloging: the module needs access.cataloging; each action also needs its own permission
// (the same ones that show/hide the buttons), so hiding a button is never the only guard.
Route::controller(CatalogingController::class, 'admin')->middleware('can:access.cataloging')->group(function () {
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

Route::controller(ReservationOpacController::class)->group(function () {
    Route::post('/request-reservation', 'requestReservation');
});

Route::controller(CheckOutInController::class, 'admin')->middleware(['can:access.circulation', 'can:manage.circulation.checkouts'])->group(function () {
    Route::get('/admin/check-out-in', 'checkoutin')->name('checkoutin');
    Route::post('/admin/check-out-in/find-material', 'findMaterial');
    Route::post('/admin/check-out-in/find-patron', 'findPatron');

    Route::post('/admin/check-out/store', 'checkoutStore');
    Route::post('/admin/check-out/update', 'checkoutUpdate');
    
    Route::post('/admin/check-in/store', 'checkinStore');
    Route::post('/admin/check-in/search', 'checkinSearch');
    Route::post('/admin/check-out/search', 'checkoutSearch');
    
});



Route::controller(CsfController::class)->group(function () {
    
    Route::get('/csf', 'index');
    Route::get('/csf/system-review', 'create');
    Route::post('/csf/store', 'store');
    
});

