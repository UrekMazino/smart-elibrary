<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\MarcController;
Route::controller(MarcController::class)->group(function () {
    Route::post('/marcfile/upload', 'upload');
    Route::post('/marcfile/download', 'download');

});

use App\Http\Controllers\ProfileController;
Route::controller(ProfileController::class)->group(function () {
    // Route::post('/profile/update', 'update');
    Route::get('/patron/profile/validate-email/', 'checkEmail');
});

