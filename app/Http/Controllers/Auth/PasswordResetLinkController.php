<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Attempt to send the reset link, but ALWAYS return the same neutral response
        // (never surface INVALID_USER / RESET_THROTTLED) so this endpoint can't be used
        // to enumerate which email addresses are registered.
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'If that email address is in our records, a password reset link has been sent.');
    }
}
