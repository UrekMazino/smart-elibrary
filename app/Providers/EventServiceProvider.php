<?php

namespace App\Providers;

use App\Listeners\SecurityEventSubscriber;
use App\Services\SessionLimits;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /** Sign-ins, failures, lockouts, sign-outs, sign-ups and reset links into the security log. */
    protected $subscribe = [
        SecurityEventSubscriber::class,
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        // A sign-in starts the clocks for the session limits. One made by the "Keep me signed in" cookie
        // comes with remember = true, as does a sign-in with the box ticked.
        Event::listen(Login::class, function (Login $event) {
            if (request()->hasSession()) {
                SessionLimits::start(request()->session(), $event->remember);
            }
        });
    }
}
