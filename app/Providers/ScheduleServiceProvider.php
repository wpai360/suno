<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class ScheduleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            
            // Schedule Google token refresh every 50 minutes
            // This ensures tokens are refreshed 5 minutes before they expire (60-minute expiry)
            $schedule->command('google:refresh-token')
                    ->cron('*/50 * * * *')
                    ->withoutOverlapping()
                    ->runInBackground()
                    ->appendOutputTo(storage_path('logs/google-token-refresh.log'));
        });
    }
} 