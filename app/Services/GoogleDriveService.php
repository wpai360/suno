<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Storage;

class GoogleDriveService
{
    protected $drive;

    public function __construct()
    {
        $client = new Client();
        $client->setAuthConfig(storage_path('app/google_credentials.json')); 
        $client->addScope(Drive::DRIVE_FILE);
        $this->drive = new Drive($client);
    }

    public function upload($filePath)
    {
        $file = new Drive\DriveFile();
        $file->setName(basename($filePath));
        $file->setParents([env('GOOGLE_DRIVE_FOLDER_ID')]);

        $content = file_get_contents($filePath);
        $uploadedFile = $this->drive->files->create($file, [
            'data' => $content,
            'mimeType' => 'audio/mp3',
            'uploadType' => 'multipart',
            'fields' => 'id'
        ]);

        return 'https://drive.google.com/file/d/' . $uploadedFile->id;
    }
}
