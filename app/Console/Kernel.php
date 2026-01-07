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
        $schedule->command('users:delete-unverified')->daily();
        
        // Auto-process video uploads from FileBrowser to faststart format
        // Runs every 5 minutes to convert new uploads for instant playback
        $schedule->command('video:faststart')->everyFiveMinutes()->withoutOverlapping();
        
        // Health check - monitor website every 5 minutes
        // Auto sends Discord alert if website goes down or recovers
        $schedule->command('health:check')->everyFiveMinutes()->withoutOverlapping();
        
        // Monthly admin evaluation - "Hard Mode" Leveling System
        // Runs on 1st day of each month at 00:05 to evaluate previous month
        // Sends results to Discord automatically
        $schedule->command('admin:evaluate-monthly --notify')
            ->monthlyOn(1, '00:05')
            ->withoutOverlapping();
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
