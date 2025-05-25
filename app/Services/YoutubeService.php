<?php

namespace App\Services;

use Google\Client;
use Google\Service\YouTube;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class YouTubeService
{
    protected $client;
    protected $service;

    public function __construct()
    {
        $this->client = new Client();
        $this->initializeClient();
    }

    protected function initializeClient()
    {
        try {
            // Get the access token from storage
            $accessToken = json_decode(Storage::disk('public')->get('google_access_token.json'), true);
            
            $this->client->setAccessToken($accessToken);
            
            // Refresh token if expired
            if ($this->client->isAccessTokenExpired()) {
                if ($this->client->getRefreshToken()) {
                    $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                    Storage::disk('public')->put('google_access_token.json', json_encode($this->client->getAccessToken()));
                }
            }

            // Disable SSL verification
            $this->client->setHttpClient(new \GuzzleHttp\Client([
                'verify' => false,
                'curl' => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false
                ]
            ]));
            
            $this->service = new YouTube($this->client);
        } catch (\Exception $e) {
            Log::error('YouTube initialization error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function uploadVideo($videoPath, $title, $description = '', $privacyStatus = 'private')
    {
        try {
            if (!file_exists($videoPath)) {
                throw new \Exception("Video file not found: $videoPath");
            }

            // Create video snippet
            $snippet = new \Google\Service\YouTube\VideoSnippet();
            $snippet->setTitle($title);
            $snippet->setDescription($description);
            $snippet->setTags(['AI Generated', 'Music']);

            // Set video status
            $status = new \Google\Service\YouTube\VideoStatus();
            $status->setPrivacyStatus($privacyStatus);

            // Create video object
            $video = new \Google\Service\YouTube\Video();
            $video->setSnippet($snippet);
            $video->setStatus($status);

            // Upload video
            $response = $this->service->videos->insert(
                'snippet,status',
                $video,
                [
                    'data' => file_get_contents($videoPath),
                    'mimeType' => 'video/mp4',
                    'uploadType' => 'multipart'
                ]
            );

            return [
                'video_id' => $response->getId(),
                'video_url' => 'https://www.youtube.com/watch?v=' . $response->getId()
            ];
        } catch (\Exception $e) {
            Log::error('YouTube upload error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getVideoDetails($videoId)
    {
        try {
            $response = $this->service->videos->listVideos('snippet,statistics', ['id' => $videoId]);
            return $response->getItems()[0] ?? null;
        } catch (\Exception $e) {
            Log::error('YouTube get video details error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateVideoPrivacy($videoId, $privacyStatus)
    {
        try {
            $video = $this->service->videos->listVideos('status', ['id' => $videoId])->getItems()[0];
            $video->getStatus()->setPrivacyStatus($privacyStatus);
            return $this->service->videos->update('status', $video);
        } catch (\Exception $e) {
            Log::error('YouTube update privacy error: ' . $e->getMessage());
            throw $e;
        }
    }
}
