<?php

namespace App\Logging;

use DateTimeZone;
use Illuminate\Log\Logger;

/** A log channel's times in UTC (the security log), whatever the app's timezone (Asia/Manila). */
class UseUtc
{
    public function __invoke(Logger $logger): void
    {
        $logger->getLogger()->setTimezone(new DateTimeZone('UTC'));
    }
}
