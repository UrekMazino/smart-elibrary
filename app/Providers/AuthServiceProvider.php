<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
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
    }
}
