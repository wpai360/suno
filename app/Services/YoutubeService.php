<?php

namespace App\Services;

use Google\Client;
use Google\Service\YouTube;
use Illuminate\Support\Facades\Log;

class YouTubeService
{
    protected $youtube;

    public function __construct()
    {
        $client = new Client();
        
        // Load credentials from file
        $credentials = json_decode(file_get_contents(storage_path('app/public/google_credentials.json')), true);
        
        // Set OAuth2 credentials
        $client->setClientId($credentials['web']['client_id']);
        $client->setClientSecret($credentials['web']['client_secret']);
        $client->setRedirectUri($credentials['web']['redirect_uris'][0]);
        
        // Set the scopes for YouTube
        $client->setScopes([
            'https://www.googleapis.com/auth/youtube.upload',
            'https://www.googleapis.com/auth/youtube'
        ]);
        
        // Set the application name
        $client->setApplicationName('Laravel YouTube Upload');
        
        // Generate access token using refresh token
        if (file_exists(storage_path('app/public/google_access_token.json'))) {
            $accessToken = json_decode(file_get_contents(storage_path('app/public/google_access_token.json')), true);
            $client->setAccessToken($accessToken);
            
            if ($client->isAccessTokenExpired()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                file_put_contents(storage_path('app/public/google_access_token.json'), json_encode($client->getAccessToken()));
            }
        }
        
        $this->youtube = new YouTube($client);
    }

    public function uploadVideo($videoPath, $title, $description = '')
    {
        try {
            $video = new YouTube\Video();
            
            // Set video metadata
            $video->setSnippet(new YouTube\VideoSnippet());
            $video->getSnippet()->setTitle($title);
            $video->getSnippet()->setDescription($description);
            $video->getSnippet()->setCategoryId("22"); // Music category
            
            // Set video status
            $video->setStatus(new YouTube\VideoStatus());
            $video->getStatus()->setPrivacyStatus("private"); // Start as private
            
            // Upload the video
            $response = $this->youtube->videos->insert(
                'snippet,status',
                $video,
                [
                    'data' => file_get_contents($videoPath),
                    'mimeType' => 'video/mp4',
                    'uploadType' => 'multipart'
                ]
            );
            
            return $response->getId();
        } catch (\Exception $e) {
            Log::error('YouTube upload failed: ' . $e->getMessage());
            throw new \Exception('Failed to upload video to YouTube: ' . $e->getMessage());
        }
    }
}
