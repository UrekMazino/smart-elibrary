<?php

namespace App\Providers;

use App\Rules\NotCommonPassword;
use App\Services\SearchIndex;
use Illuminate\Contracts\Validation\UncompromisedVerifier;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema as IlluminateSchema;
use Illuminate\Validation\NotPwnedVerifier;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // One collector per request, so updates to the same record are sent once.
        $this->app->singleton(SearchIndex::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        IlluminateSchema::defaultStringLength(125);

        // The password policy (OWASP ASVS 6.2), used wherever a password is set: registration, reset,
        // and the patron's and staff's own password change.
        //  - 12 to 128 characters, any characters (no "must contain a digit" rules): 6.2.1, 6.2.5, 6.2.9;
        //  - not one of the most common passwords: 6.2.4;
        //  - not one known from data breaches (Have I Been Pwned): 6.2.12. Only the first 5 characters of
        //    the password's SHA-1 hash leave the server. PASSWORD_BREACH_CHECK=false turns it off (tests).
        Password::defaults(function () {
            $rule = Password::min(12)->max(128)->rules([new NotCommonPassword]);
            return config('auth.breached_password_check') ? $rule->uncompromised() : $rule;
        });

        // The breach check waits at most 5 seconds for the service (Laravel's default is 30). If it can't
        // be reached the password is accepted, so an offline server never stops anyone setting one.
        $this->app->extend(UncompromisedVerifier::class, fn ($verifier, $app) => new NotPwnedVerifier($app[HttpFactory::class], 5));

        // Search-index updates still waiting when the request or command ends (after a rollback,
        // or an exception between hold() and release()) are sent then.
        $this->app->terminating(fn () => $this->app->make(SearchIndex::class)->finish());
    }
}
