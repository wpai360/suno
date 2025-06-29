<?php

namespace App\Services;

use Google\Client;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class GoogleTokenService
{
    protected $client;
    protected $tokenFile = 'google_access_token.json';
    protected $refreshThreshold = 300; // 5 minutes in seconds

    public function __construct()
    {
        $this->client = new Client();
        $this->initializeClient();
    }

    protected function initializeClient()
    {
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect'));
        $this->client->setScopes(config('services.google.scopes'));
        
        // Request offline access to get refresh tokens
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent'); // Force consent screen to ensure refresh token
        
        // Disable SSL verification for development
        if (app()->environment('local')) {
            $this->client->setHttpClient(new \GuzzleHttp\Client([
                'verify' => false,
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false
                ]
            ]));
        }
    }

    /**
     * Get a valid access token, refreshing if necessary
     */
    public function getValidAccessToken()
    {
        try {
            $token = $this->loadToken();
            
            if (!$token) {
                throw new \Exception('No access token found. Please authenticate with Google first.');
            }

            $this->client->setAccessToken($token);

            // Check if token needs refresh (5 minutes before expiry)
            if ($this->shouldRefreshToken($token)) {
                Log::info('Token refresh needed, refreshing now...');
                $token = $this->refreshAccessToken($token);
            }

            return $token;
        } catch (\Exception $e) {
            Log::error('Error getting valid access token: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if token should be refreshed (5 minutes before expiry)
     */
    protected function shouldRefreshToken($token)
    {
        if (!isset($token['expires_in']) || !isset($token['created'])) {
            return true; // Refresh if we don't have expiry info
        }

        $expiryTime = $token['created'] + $token['expires_in'];
        $currentTime = time();
        $timeUntilExpiry = $expiryTime - $currentTime;

        // Refresh if token expires within the next 5 minutes
        return $timeUntilExpiry <= $this->refreshThreshold;
    }

    /**
     * Refresh the access token using refresh token
     */
    protected function refreshAccessToken($token)
    {
        try {
            if (!isset($token['refresh_token'])) {
                Log::error('No refresh token available for token refresh', [
                    'token_keys' => array_keys($token),
                    'has_access_token' => isset($token['access_token']),
                    'has_expires_in' => isset($token['expires_in'])
                ]);
                throw new \Exception('No refresh token available. Re-authentication required.');
            }

            Log::info('Refreshing Google access token...', [
                'has_refresh_token' => true,
                'current_expires_in' => $token['expires_in'] ?? 'unknown'
            ]);
            
            $newToken = $this->client->fetchAccessTokenWithRefreshToken($token['refresh_token']);
            
            // Ensure we preserve the refresh token in the new token data
            if (!isset($newToken['refresh_token']) && isset($token['refresh_token'])) {
                $newToken['refresh_token'] = $token['refresh_token'];
                Log::info('Preserved existing refresh token in new token data');
            }
            
            $newToken['created'] = time();

            // Save the new token
            $this->saveToken($newToken);

            Log::info('Google access token refreshed successfully', [
                'new_expires_in' => $newToken['expires_in'] ?? 'unknown',
                'has_refresh_token' => isset($newToken['refresh_token'])
            ]);
            
            return $newToken;
        } catch (\Exception $e) {
            Log::error('Failed to refresh Google access token: ' . $e->getMessage(), [
                'error_type' => get_class($e),
                'token_has_refresh' => isset($token['refresh_token'])
            ]);
            throw $e;
        }
    }

    /**
     * Save token to storage
     */
    public function saveToken($token)
    {
        try {
            // Add creation timestamp if not present
            if (!isset($token['created'])) {
                $token['created'] = time();
            }

            // Log token details for debugging
            Log::info('Saving Google token', [
                'has_access_token' => isset($token['access_token']),
                'has_refresh_token' => isset($token['refresh_token']),
                'expires_in' => $token['expires_in'] ?? 'not_set',
                'token_type' => $token['token_type'] ?? 'not_set',
                'scope' => $token['scope'] ?? 'not_set'
            ]);

            // Ensure we have the essential token data
            if (!isset($token['access_token'])) {
                throw new \Exception('No access token in response');
            }

            Storage::disk('public')->put($this->tokenFile, json_encode($token));
            
            // Cache the token for faster access
            Cache::put('google_access_token', $token, now()->addMinutes(55));
            
            Log::info('Google access token saved successfully', [
                'has_refresh_token' => isset($token['refresh_token']),
                'expires_in' => $token['expires_in'] ?? 'unknown'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to save Google access token: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Load token from storage
     */
    protected function loadToken()
    {
        try {
            // Try cache first
            $cachedToken = Cache::get('google_access_token');
            if ($cachedToken) {
                return $cachedToken;
            }

            // Fallback to file storage
            if (Storage::disk('public')->exists($this->tokenFile)) {
                $token = json_decode(Storage::disk('public')->get($this->tokenFile), true);
                
                // Cache the token
                if ($token) {
                    Cache::put('google_access_token', $token, now()->addMinutes(55));
                }
                
                return $token;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to load Google access token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get token expiry information
     */
    public function getTokenInfo()
    {
        $token = $this->loadToken();
        
        if (!$token) {
            return [
                'has_token' => false,
                'message' => 'No token found'
            ];
        }

        $expiryTime = $token['created'] + ($token['expires_in'] ?? 3600);
        $currentTime = time();
        $timeUntilExpiry = $expiryTime - $currentTime;
        $isExpired = $timeUntilExpiry <= 0;
        $needsRefresh = $timeUntilExpiry <= $this->refreshThreshold;

        return [
            'has_token' => true,
            'expires_at' => date('Y-m-d H:i:s', $expiryTime),
            'time_until_expiry' => $timeUntilExpiry,
            'is_expired' => $isExpired,
            'needs_refresh' => $needsRefresh,
            'has_refresh_token' => isset($token['refresh_token'])
        ];
    }

    /**
     * Clear stored token
     */
    public function clearToken()
    {
        try {
            Storage::disk('public')->delete($this->tokenFile);
            Cache::forget('google_access_token');
            Log::info('Google access token cleared');
        } catch (\Exception $e) {
            Log::error('Failed to clear Google access token: ' . $e->getMessage());
        }
    }

    /**
     * Get Google Client with valid token
     */
    public function getClient()
    {
        $token = $this->getValidAccessToken();
        $this->client->setAccessToken($token);
        return $this->client;
    }

    /**
     * Get basic Google Client for OAuth flow (without requiring valid token)
     */
    public function getBasicClient()
    {
        return $this->client;
    }
} 