<?php

namespace App\Listeners;

use Swift_Events_EventListener;
use Swift_TransportException;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class FailedSendingListener implements Swift_Events_EventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        //
    }

    public function beforeSendPerformed($event)
    {
        // This method is called before the email is sent
    }

    public function sendPerformed($event)
    {
        if (!$event->isSuccessful()) {
            // Email sending failed
            $exception = $event->getThrowable();

            if ($exception instanceof Swift_TransportException) {
                Log::error('Email sending failed: ' . $exception->getMessage());
            }
        }
    }
}
