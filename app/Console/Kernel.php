<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Keep the OPAC sitemap current as records are published/held/edited. Static files, so
        // this costs nothing at request time. See OPAC_SEO_PLAN.md workstream C.
        $schedule->command('sitemap:generate')->dailyAt('03:30')->withoutOverlapping();

        // Safety net for the OPAC search index: adds what an outage missed, removes deleted
        // records, and refreshes the view counts behind the "Most viewed" sort.
        $schedule->command('search:reconcile')->dailyAt('03:45')->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
