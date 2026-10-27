<?php

use Illuminate\Support\Facades\Route;

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

// No API routes. The default /api/user (the signed-in account as JSON) is gone: nothing used it, and it
// returned the whole account record (OWASP ASVS 15.3.1).
