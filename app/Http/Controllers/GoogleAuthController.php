<?php

namespace App\Http\Controllers;

use App\Services\GoogleTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    protected $tokenService;

    public function __construct(GoogleTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    public function initiate()
    {
        try {
            $client = $this->tokenService->getBasicClient();
            $authUrl = $client->createAuthUrl();
            
            Log::info('Redirecting to Google OAuth URL');
            return redirect($authUrl);
        } catch (\Exception $e) {
            Log::error('Error creating Google auth URL: ' . $e->getMessage());
            return redirect()->route('dashboard')->with('error', 'Failed to initiate Google authentication: ' . $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        if (!$request->has('code')) {
            Log::error('No authorization code received in callback');
            return redirect()->route('dashboard')->with('error', 'No authorization code received');
        }

        try {
            // Get the authorization code and trim any whitespace
            $code = trim($request->code);
            Log::info('Received Google authorization code');

            // Get the Google client
            $client = $this->tokenService->getBasicClient();
            
            // Exchange the authorization code for an access token
            $token = $client->fetchAccessTokenWithAuthCode($code);
            Log::info('Google token received successfully');
            
            // Save the token using the token service
            $this->tokenService->saveToken($token);
            
            Log::info('Google authentication completed successfully');
            return redirect()->route('dashboard')->with('success', 'Google API access granted!');
            
        } catch (\Exception $e) {
            Log::error('Error in Google callback: ' . $e->getMessage());
            Log::error('Request data: ' . json_encode($request->all()));
            return redirect()->route('dashboard')->with('error', 'Failed to get access token: ' . $e->getMessage());
        }
    }

    /**
     * Check token status
     */
    public function tokenStatus()
    {
        try {
            $tokenInfo = $this->tokenService->getTokenInfo();
            return response()->json($tokenInfo);
        } catch (\Exception $e) {
            Log::error('Error getting token status: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to get token status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Manually refresh token
     */
    public function refreshToken()
    {
        try {
            $token = $this->tokenService->getValidAccessToken();
            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'token_info' => $this->tokenService->getTokenInfo()
            ]);
        } catch (\Exception $e) {
            Log::error('Error refreshing token: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to refresh token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear stored token
     */
    public function clearToken()
    {
        try {
            $this->tokenService->clearToken();
            return response()->json([
                'success' => true,
                'message' => 'Token cleared successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error clearing token: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to clear token: ' . $e->getMessage()
            ], 500);
        }
    }
} 