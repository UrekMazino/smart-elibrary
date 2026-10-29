<?php

namespace App\Console\Commands;

use App\Services\SecurityLog;
use Illuminate\Console\Command;

/**
 * Nightly (app/Console/Kernel.php): the security log keeps a year. The entries older than that are removed from the
 * top of storage/logs/security.log, in place; the file and everything newer stay (SecurityLog::prune).
 */
class PruneSecurityLog extends Command
{
    protected $signature = 'security-log:prune';

    protected $description = 'Remove security log entries older than a year from the top of the file (nightly)';

    public function handle(): int
    {
        $removed = SecurityLog::prune(now()->subYear());
        $this->info("Removed $removed security log " . ($removed === 1 ? 'entry' : 'entries') . ' older than a year.');

        return self::SUCCESS;
    }
}
