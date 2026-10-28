<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\BrowserSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\GoogleSignInController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SessionStatusController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
                ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store'])
                ->middleware('throttle:10,1'); // limit registration floods (per IP)

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    // "Continue with Google" (patrons; GoogleSignInController). 404 while GOOGLE_CLIENT_ID or GOOGLE_CLIENT_SECRET is unset.
    Route::get('auth/google', [GoogleSignInController::class, 'redirect'])
                ->middleware('throttle:10,1')
                ->name('google.redirect');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
                ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
                ->middleware('throttle:5,1') // slow reset-link/enumeration floods (per IP)
                ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
                ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
                ->middleware('throttle:5,1')
                ->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', [EmailVerificationPromptController::class, '__invoke'])
                ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
                ->middleware(['signed', 'throttle:6,1'])
                ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
                ->middleware('throttle:6,1')
                ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
                ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');

    // The staff warning before a sign-out: the time left (not counted as activity), and "Stay signed in".
    Route::get('session/status', [SessionStatusController::class, 'show'])
                ->middleware('throttle:60,1')
                ->name('session.status');

    Route::post('session/stay', [SessionStatusController::class, 'stay'])
                ->middleware('throttle:60,1')
                ->name('session.stay');

    // "Where you're signed in" (profile pages): sign out one other session, or all of them. Takes the password.
    Route::delete('sessions', [BrowserSessionController::class, 'destroy'])
                ->middleware('throttle:10,1')
                ->name('sessions.end');

    // An account without a password proves who it is with Google where others type their password.
    Route::get('auth/google/confirm', [GoogleSignInController::class, 'confirm'])
                ->middleware('throttle:10,1')
                ->name('google.confirm');
});

// Where Google sends both a sign-in and a "Confirm with Google" back, so it is neither guest-only nor signed-in-only.
Route::get('auth/google/callback', [GoogleSignInController::class, 'callback'])
            ->middleware('throttle:10,1')
            ->name('google.callback');
