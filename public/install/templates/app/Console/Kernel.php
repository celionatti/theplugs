<?php

declare(strict_types=1);

namespace App\Console;

use Plugs\Console\Scheduling\Schedule;

/**
 * Application Console Kernel
 *
 * Define your scheduled tasks in the schedule method.
 */
class Kernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  Schedule  $schedule
     * @return void
     */
    public function schedule(Schedule $schedule): void
    {
        // Example: Run the inspire command every minute
        // $schedule->command('inspire')->everyMinute();

        // Example: Run a closure daily
        // $schedule->call(function () {
        //     // Do something...
        // })->daily()->description('Daily cleanup');
    }
}
