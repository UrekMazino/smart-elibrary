<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Nightly (app/Console/Kernel.php): what the eLibrary keeps about guests goes after 90 days, as SECURITY_NOTES.md
 * section 6 promises. The IP addresses behind guests' hashes (visitor_addresses) go 90 days after the guest was first
 * seen (seen again, the address is kept afresh); the hashes on guests' views (views.visitor_hash) go 90 days after
 * the view, which itself stays as a count.
 */
class ForgetVisitors extends Command
{
    public const DAYS = 90;

    protected $signature = 'visitors:forget';

    protected $description = 'Delete guests\' IP addresses and hashes older than 90 days (nightly)';

    public function handle(): int
    {
        $before = now()->subDays(self::DAYS);

        $addresses = DB::table('visitor_addresses')->where('first_seen_at', '<', $before)->delete();
        $hashes = 0;
        do {   // in batches, so a large table isn't locked for long
            $done = DB::table('views')->whereNotNull('visitor_hash')->where('created_at', '<', $before)
                ->limit(5000)->update(['visitor_hash' => null]);
            $hashes += $done;
        } while ($done > 0);

        $this->info("Deleted $addresses guest " . ($addresses === 1 ? 'address' : 'addresses') . " and $hashes "
            . ($hashes === 1 ? 'hash' : 'hashes') . ' older than ' . self::DAYS . ' days.');

        return self::SUCCESS;
    }
}
