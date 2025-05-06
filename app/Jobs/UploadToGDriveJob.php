<?php

namespace App\Jobs;

use App\Models\SongRequest;
use Google_Client;
use Google_Service_Drive;
use Illuminate\Foundation\Queue\Queueable as QueueQueueable;
use Illuminate\Support\Facades\Storage;

class UploadToGDriveJob implements ShouldQueue
{   use QueueQueueable;
    use InteractsWithQueue, SerializesModels;
    protected $songRequest;

    public function __construct(SongRequest $songRequest)
    {
        $this->songRequest = $songRequest;
    }

    public function handle()
    {
        $client = new Google_Client();
        $client->setAuthConfig(config('google.client_credentials'));
        $service = new Google_Service_Drive($client);

        $file = new \Google_Service_Drive_DriveFile();
        $file->setName('song.mp4');
        $file->setMimeType('video/mp4');

        $content = Storage::get($this->songRequest->mp4_path);
        $service->files->create($file, [
            'data' => $content,
            'mimeType' => 'video/mp4',
            'uploadType' => 'multipart',
        ]);

        $this->songRequest->update(['gdrive_link' => $file->getId()]);
    }
}
