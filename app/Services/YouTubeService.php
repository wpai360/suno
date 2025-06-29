<?php

namespace App\Services;

use Google\Service\YouTube;
use Illuminate\Support\Facades\Log;

class YouTubeService
{
    protected $client;
    protected $service;
    protected $tokenService;

    public function __construct(GoogleTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    protected function initializeClient()
    {
        try {
            // Get client with valid token from token service
            $this->client = $this->tokenService->getClient();
            $this->service = new YouTube($this->client);
            
            Log::info('YouTube client initialized successfully');
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

            // Initialize client with fresh token
            $this->initializeClient();

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

            Log::info('Video uploaded to YouTube successfully', [
                'video_id' => $response->getId(),
                'title' => $title
            ]);

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
            // Initialize client with fresh token
            $this->initializeClient();

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
            // Initialize client with fresh token
            $this->initializeClient();

            $video = $this->service->videos->listVideos('status', ['id' => $videoId])->getItems()[0];
            $video->getStatus()->setPrivacyStatus($privacyStatus);
            
            $result = $this->service->videos->update('status', $video);
            
            Log::info('YouTube video privacy updated successfully', [
                'video_id' => $videoId,
                'privacy_status' => $privacyStatus
            ]);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('YouTube update privacy error: ' . $e->getMessage());
            throw $e;
        }
    }
}
