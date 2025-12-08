<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Check for schedule reminders every minute
        $schedule->command('schedule:check-reminders')->everyMinute();
        
        // Check for assignment reminders (3 days before, on deadline, 3 days after) at 07:00 AM daily
        $schedule->command('assignments:check-reminders')->dailyAt('07:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
