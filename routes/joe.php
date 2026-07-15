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
    Route::get('/admin/count-dynamicdates/json', 'countdynamicdates')->middleware('can:access.dashboard');
    Route::get('/admin/per-date-chart/json', 'perDateChartJson')->middleware('can:access.dashboard');
    Route::get('/admin/views-traffic/json', 'viewsTrafficJson')->middleware('can:access.dashboard');
    Route::get('/admin/register', 'registerPatron')->name('register_patron');
    Route::get('/admin/profile/validate-email/', 'validateEmail');
    Route::get('/admin/update-librarycode', 'updatePatronLibraryCode');
    Route::get('/admin/profile', 'profile');
    Route::post('/admin/register/store', 'storePatron');
    Route::post('/admin/profile/update', 'updateProfile');
    Route::post('/admin/profile/update/password', 'updatePassword');
    
    Route::get('/superadmin/admin-list', 'administrators')->name('administrators');
    Route::get('/superadmin/admin-create', 'createAdministrator')->name('admin.create');
    Route::post('/superadmin/admin-store', 'storeAdministrator')->name('admin.store');
    Route::get('/superadmin/administrators/json', 'adminJson');
    Route::post('/superadmin/admin-change-role', 'adminChangeRole')->name('admin.change_role');
});

Route::controller(RolePermissionController::class, 'admin')->group(function () {

    // ROLES
    Route:: get('/admin/roles', 'rolesIndex')->name('admin.list_roles');
    Route::post('/admin/roles/store', 'storeRole')->name('admin.store_role');
    Route::post('/admin/roles/update', 'updateRole')->name('admin.update_role');
    Route::delete('/admin/roles/delete/{id}', 'destroyRole')->name('admin.delete_role');

    // PERMISSIONS
    Route::get('/admin/permissions', 'permissionsIndex')->name('admin.list_permissions');
    Route::get('/admin/role-permissions', 'rolePermissions')->name('admin.role_permissions');
    Route::get('/admin/role-permissions/add', 'addPermissionToRole')->name('admin.role_add_permissions');

    Route::get('/admin/role-permissions/remove', 'removePermissionFromRole')->name('admin.role_remove_permission');

    Route::post('/admin/permissions/store', 'storePermission')->name('admin.store_permission');
    Route::post('/admin/permissions/update', 'updatePermission')->name('admin.update_permission');
    Route::delete('/admin/permissions/delete/{id}', 'destroyPermission')->name('admin.delete_permission');
});

Route::controller(InventoryController::class)->group(function () {
    Route::get('/admin/inventory-list-json', 'physicalInventoryListJson');
});
Route::controller(ConfigurationController::class, 'admin')->group(function () {
    Route::get('/admin/marc-templates', 'marcTemplates')->name('marc-templates-index');
    Route::get('/admin/marc-templates/json', 'marcTemplatesJson')->name('marc-templates-json');
    Route::get('/admin/marc-templates/create', 'createMarcTemplate')->name('create-marc-template');
    Route::get('/admin/marc-templates/edit', 'editMarcTemplate');
    Route::post('/admin/marc-templates/store', 'storeMarcTemplate');
    Route::post('/admin/marc-templates/update', 'updateMarcTemplate');
    Route::delete('admin/marc-templates/delete/{id}', 'destroyMarcTemplate')->name('delete-marc-template');

    Route::get('/admin/marc-tags-list/json', 'marcTagsJson');
    Route::get('/admin/get-marc-tag-details', 'getMarctagDetails');
    Route::get('/admin/get-marc-template-details', 'getMarcTemplateDetails');
    
    
});

Route::controller(CatalogingController::class, 'admin')->group(function () {
    Route::get('/admin/create-record', 'createRecord');
    Route::post('/admin/view-record', 'viewRecord');
    Route::post('/admin/view-batch', 'viewBatch');
    Route::get('/admin/batch-records/{id}', 'viewBatchRecords');
    Route::get('/admin/batch-records-json/{id}', 'viewBatchRecordsJson');
    Route::delete('/admin/delete-batch/{id}', 'destroyBatch')->name('delete-batch');
    
    Route::get('/admin/record-list', 'recordList');
    Route::get('/admin/on-hold-list', 'onholdList');
    Route::get('/admin/record-list-json', 'recordListJson');
    Route::get('/admin/onhold-list-json', 'onholdListJson');

    
    Route::post('/admin/hold-all-published', 'holdAllPublished');
    Route::post('/admin/publish-all-onhold', 'publishAllOnhold');
    Route::post('/admin/publish-to-opac', 'publishToOpac');
    Route::post('/admin/hold-view-from-opac', 'holdViewFromOpac');
    Route::post('/admin/batch-publish', 'togglePublishHoldMaterial');
    
    Route::get('/admin/batch-list', 'batchList');
    Route::get('/admin/batch-list-json', 'batchListJson');
    Route::get('/admin/create-batch', 'createBatch');
    Route::post('/admin/create-batch/store', 'storeBatch');
    Route::post('/admin/create-record/store', 'storeRecord');
    Route::post('/admin/edit-record/update/{id}', 'updateRecord')->name('update-record');
    
    Route::get('/admin/edit-record/{id}', 'editRecord')->name('edit-record');
    Route::delete('/admin/delete-record/{id}', 'destroyRecord')->name('delete-record');
    Route::post('/admin/import/single-marcfile', 'singleImportMarcFile');

    Route::post('/admin/upload/cover-photo', 'uploadCoverPhoto')->name('upload-coverphoto');
    Route::delete('/admin/delete-cover-photo', 'destroyCoverPhoto')->name('delete-coverphoto');

    Route::post('/admin/upload/full-text', 'uploadFullText')->name('upload-fulltext');
    Route::delete('/admin/delete-full-text', 'destroyFullText')->name('delete-fulltext');

    Route::get('/admin/copy-list-json', 'copyListJson');
    Route::get('/admin/getcopy-details-json/{id}', 'getCopyDetailsJson');
    Route::post('/admin/view-record/add-copy', 'addCopy');

    Route::put('/admin/view-record/update-copy', 'updateCopy');

    Route::delete('/admin/view-record/{id}/delete-copy', 'destroyCopy')->name('delete-copy');
    Route::get('/get-import-progress', 'getTaskProgress');
});


Route::controller(ReservationController::class)->group(function () {
    Route::get('/admin/reservations', 'reservations')->name('reservations');
    Route::get('/admin/reservations/json', 'reservation_json');
    Route::post('/admin/reservation/action', 'reservation_action')->middleware('can:manage.circulation.reservation');
    Route::get('/admin/reservation/material-json', 'viewMaterial');
    Route::get('/admin/reservation/patron-json', 'viewPatron');
    Route::get('/admin/reservation/transaction-json', 'viewTransaction');
    Route::get('/admin/reservation/view-json', 'viewReservation');
    Route::get('/admin/reservation/chat-json/{reservation_code}', 'reservationChatJson');
    Route::post('/admin/reservation/chat/reply', 'reservationChatReply');
});

Route::controller(ReservationOpacController::class)->group(function () {
    Route::post('/request-reservation', 'requestReservation');
});

Route::controller(CheckOutInController::class, 'admin')->group(function () {
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
    Route::get('/csf/get-ratings/json', 'getRatings');
    Route::post('/csf/store', 'store');
    
});

