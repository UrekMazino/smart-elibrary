<?php

namespace App\Providers;

use App\Services\SearchIndex;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema as IlluminateSchema;

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

        // Search-index updates still waiting when the request or command ends (after a rollback,
        // or an exception between hold() and release()) are sent then.
        $this->app->terminating(fn () => $this->app->make(SearchIndex::class)->finish());
    }
}
