<?php

namespace App\Console\Commands;

use App\Jobs\RefreshGoogleTokenJob;
use Illuminate\Console\Command;

class RefreshGoogleTokenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google:refresh-token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh Google access token if it needs refreshing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Google token refresh check...');
        
        try {
            // Dispatch the refresh job
            RefreshGoogleTokenJob::dispatch();
            
            $this->info('Google token refresh job dispatched successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to dispatch Google token refresh job: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
} 