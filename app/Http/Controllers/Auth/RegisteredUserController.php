<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Listeners\FailedSendingListener;
use App\Models\User;
use App\Mail\RegistrationMail;
use App\Providers\RouteServiceProvider;
use App\Rules\HumanCheck;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view. Its "I'm human" check comes from Cloudflare, so this page's Content Security
     * Policy lets Cloudflare in (SecurityHeaders); the rest of the site's doesn't.
     *
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        if (HumanCheck::enabled()) $request->attributes->set('csp.turnstile', true);

        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'contactnum' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            // The "I'm human" check: a token works once, so the page that shows any mistake comes with a new check.
            'cf-turnstile-response' => HumanCheck::enabled() ? [new HumanCheck('register')] : [],
        ], [
            // Generic message so we don't explicitly confirm that an email is already
            // registered (avoids account enumeration via the registration form).
            'email.unique' => "This email address can't be used to register. If you already have an account, please sign in or reset your password.",
        ]);


        $libraryCode = generateLibraryCode();

        // $mailable = new RegistrationMail(['library_code' => $libraryCode, 'fullname' => $request->name, 'type' => 'opac']);

        // Register the FailedSendingListener
        // Mail::getSwiftMailer()->registerPlugin(new FailedSendingListener);

        // $emailSent = Mail::to($request->email)->send($mailable);

        // if(is_null($emailSent)) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'contactnum' => $request->contactnum,
                'password' => Hash::make($request->password),
                'library_code' => $libraryCode,
            ]);

            event(new Registered($user));
            
            Auth::login($user);
        // } else {
        //     return redirect()->route('register')->with(['error' => 'Error! Registration failed.']);     
        // }

        return redirect(RouteServiceProvider::HOME);
    }
}
