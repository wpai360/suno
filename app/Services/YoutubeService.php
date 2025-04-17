<?php

namespace App\Services;

use Google\Client;
use Google\Service\YouTube;

class YouTubeService
{
    protected $youtube;

    public function __construct()
    {
        $client = new Client();
        $client->setAuthConfig(storage_path('app/youtube_credentials.json')); 
        $client->addScope(YouTube::YOUTUBE_UPLOAD);
        $this->youtube = new YouTube($client);
    }

    public function upload($filePath)
    {
        $video = new YouTube\Video();
        $video->setSnippet((new YouTube\VideoSnippet())->setTitle('AI Personalized Song'));
        $video->setStatus((new YouTube\VideoStatus())->setPrivacyStatus('unlisted'));

        $upload = $this->youtube->videos->insert('snippet,status', $video, [
            'data' => file_get_contents($filePath),
            'mimeType' => 'video/mp4',
            'uploadType' => 'multipart'
        ]);

        return 'https://www.youtube.com/watch?v=' . $upload->id;
    }
}
