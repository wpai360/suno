<?php

namespace App\Http\Controllers;

use Google\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class GoogleAuthController extends Controller
{
    protected function getGoogleClient()
    {
        $client = new Client();
        
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        $client->setScopes(config('services.google.scopes'));
        
        // Disable SSL verification for development
        if (app()->environment('local')) {
            $client->setHttpClient(new \GuzzleHttp\Client([
                'verify' => false,
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false
                ]
            ]));
        }

        return $client;
    }

    public function callback(Request $request)
    {
        $client = $this->getGoogleClient();

        if ($request->has('code')) {
            try {
                // Get the authorization code and trim any whitespace
                $code = trim($request->code);
                Log::info('Received code: ' . $code);

                // Exchange the authorization code for an access token
                $token = $client->fetchAccessTokenWithAuthCode($code);
                Log::info('Token received:', $token);
                
                // Save the token
                Storage::disk('public')->put('google_access_token.json', json_encode($token));
                
                return redirect()->route('dashboard')->with('success', 'Google API access granted!');
            } catch (\Exception $e) {
                Log::error('Error in callback: ' . $e->getMessage());
                Log::error('Request data: ' . json_encode($request->all()));
                return redirect()->route('dashboard')->with('error', 'Failed to get access token: ' . $e->getMessage());
            }
        }

        return redirect()->route('dashboard')->with('error', 'No authorization code received');
    }

    public function initiate()
    {
        $client = $this->getGoogleClient();
        $authUrl = $client->createAuthUrl();
        return redirect($authUrl);
    }
} 