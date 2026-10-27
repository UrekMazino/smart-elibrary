<?php

namespace App\Providers;

use App\Auth\SessionGuard;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // `developer` is the superuser role: it passes every permission check (can:, @can,
        // $user->can(), Spatie's permission: middleware), including permissions added later
        // that were never ticked for it in the access matrix.
        Gate::before(function ($user) {
            return method_exists($user, 'hasRole') && $user->hasRole('developer') ? true : null;
        });

        // A session ended by a password change (AuthenticateSession, ASVS 7.4.3) goes to the sign-in page, which says
        // why. Without this Laravel 13 answers with an empty 401 page (newer apps set it in bootstrap/app.php).
        AuthenticateSession::redirectUsing(function (Request $request) {
            $request->session()->flash('signed_out', 'You were signed out because the password of this account was changed. Sign in again to continue.');
            return route('login');
        });

        // The "session" guard, built as Laravel builds it (AuthManager::createSessionDriver, Laravel 13) but from
        // App\Auth\SessionGuard, whose "Keep me signed in" cookie has the __Host- prefix. Check this against
        // createSessionDriver after a Laravel upgrade; tests/Feature/SessionLimitsTest.php covers the cookie.
        Auth::extend('session', function ($app, $name, array $config) {
            $guard = new SessionGuard(
                $name,
                Auth::createUserProvider($config['provider'] ?? null),
                $app['session.store'],
                rehashOnLogin: $app['config']->get('hashing.rehash_on_login', true),
                timeboxDuration: $app['config']->get('auth.timebox_duration', 200000),
                hashKey: $app['config']->get('app.key'),
            );
            $guard->setCookieJar($app['cookie']);
            $guard->setDispatcher($app['events']);
            $guard->setRequest($app->refresh('request', $guard, 'setRequest'));
            if (isset($config['remember'])) {
                $guard->setRememberDuration($config['remember']);
            }

            return $guard;
        });
    }
}
