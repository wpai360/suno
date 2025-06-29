<?php

namespace App\Jobs;

use App\Services\GoogleTokenService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshGoogleTokenJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;
    public $tries = 3;

    public function handle(GoogleTokenService $tokenService)
    {
        try {
            Log::info('Starting scheduled Google token refresh check...');
            
            $tokenInfo = $tokenService->getTokenInfo();
            
            if (!$tokenInfo['has_token']) {
                Log::info('No Google token found, skipping refresh');
                return;
            }

            if ($tokenInfo['needs_refresh']) {
                Log::info('Token needs refresh, refreshing now...', [
                    'expires_at' => $tokenInfo['expires_at'],
                    'time_until_expiry' => $tokenInfo['time_until_expiry']
                ]);
                
                // This will automatically refresh the token
                $tokenService->getValidAccessToken();
                
                Log::info('Google token refreshed successfully via scheduled job');
            } else {
                Log::info('Google token is still valid, no refresh needed', [
                    'expires_at' => $tokenInfo['expires_at'],
                    'time_until_expiry' => $tokenInfo['time_until_expiry']
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to refresh Google token via scheduled job: ' . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Google token refresh job failed: ' . $exception->getMessage());
    }
} 