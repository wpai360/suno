<?php

namespace App\Http\Controllers;

use Google\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    public function callback(Request $request)
    {
        $client = new Client();
        
        // Load credentials from file
        $credentials = json_decode(file_get_contents(storage_path('app/public/google_credentials.json')), true);
        
        // Set OAuth2 credentials
        $client->setClientId($credentials['web']['client_id']);
        $client->setClientSecret($credentials['web']['client_secret']);
        $client->setRedirectUri($credentials['web']['redirect_uris'][0]);
        
        // Set the scopes
        $client->setScopes([
            'https://www.googleapis.com/auth/drive.file',
            'https://www.googleapis.com/auth/drive'
        ]);

        // Disable SSL verification
        $client->setHttpClient(new \GuzzleHttp\Client([
            'verify' => false,
            'curl' => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]
        ]));

        if ($request->has('code')) {
            try {
                // Get the authorization code and trim any whitespace
                $code = trim($request->code);
                Log::info('Received code: ' . $code);

                // Exchange the authorization code for an access token
                $token = $client->fetchAccessTokenWithAuthCode($code);
                Log::info('Token received:', $token);
                
                // Ensure the directory exists
                if (!Storage::disk('public')->exists('')) {
                    Storage::disk('public')->makeDirectory('');
                }
                
                // Save the token
                $saved = Storage::disk('public')->put('google_access_token.json', json_encode($token));
                Log::info('File saved: ' . ($saved ? 'true' : 'false'));
                
                return redirect()->route('dashboard')->with('success', 'Google Drive access granted!');
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
        $client = new Client();
        $credentials = json_decode(file_get_contents(storage_path('app/public/google_credentials.json')), true);
        
        $client->setClientId($credentials['web']['client_id']);
        $client->setClientSecret($credentials['web']['client_secret']);
        $client->setRedirectUri($credentials['web']['redirect_uris'][0]);
        $client->setScopes([
            'https://www.googleapis.com/auth/drive.file',
            'https://www.googleapis.com/auth/drive'
        ]);
        
        $authUrl = $client->createAuthUrl();
        return redirect($authUrl);
    }
} 