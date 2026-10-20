<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
Route::controller(ProfileController::class)->group(function () {
    Route::get('/patron/profile/validate-email/', 'checkEmail');
});

