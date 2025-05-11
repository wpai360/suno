<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected $drive;
    protected $videoConverter;

    public function __construct()
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
        
        // Set the application name
        $client->setApplicationName('Laravel Drive Upload');
        
        // Generate access token using refresh token
        if (Storage::disk('public')->exists('google_access_token.json')) {
            $accessToken = json_decode(Storage::disk('public')->get('google_access_token.json'), true);
            $client->setAccessToken($accessToken);
            
            if ($client->isAccessTokenExpired()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                Storage::disk('public')->put('google_access_token.json', json_encode($client->getAccessToken()));
            }
        } else {
            // If no access token exists, redirect to get authorization
            $authUrl = $client->createAuthUrl();
            header('Location: ' . $authUrl);
            exit;
        }
        
        // Disable SSL verification for Google API calls
        $client->setHttpClient(new \GuzzleHttp\Client([
            'verify' => false,
            'curl' => [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false
            ]
        ]));
        
        $this->drive = new Drive($client);
        $this->videoConverter = new VideoConversionService();
    }

    public function upload($filePath, $convertToVideo = false)
    {
        try {
            // If it's a URL, download it first
            if (filter_var($filePath, FILTER_VALIDATE_URL)) {
                $tempFile = $this->downloadFromUrl($filePath);
                $filePath = $tempFile;
            }

            // Convert to video if requested
            if ($convertToVideo) {
                $videoPath = $this->videoConverter->convertToMp4($filePath);
                $filePath = $videoPath;
            }

            $file = new Drive\DriveFile();
            $file->setName(basename($filePath));
            $file->setParents([env('GOOGLE_DRIVE_FOLDER_ID')]);

            $content = file_get_contents($filePath);
            $uploadedFile = $this->drive->files->create($file, [
                'data' => $content,
                'mimeType' => $convertToVideo ? 'video/mp4' : 'audio/mp3',
                'uploadType' => 'multipart',
                'fields' => 'id'
            ]);

            // Clean up temp files
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }
            if ($convertToVideo && isset($videoPath) && file_exists($videoPath)) {
                $this->videoConverter->cleanup($videoPath);
            }

            return 'https://drive.google.com/file/d/' . $uploadedFile->id;
        } catch (\Exception $e) {
            Log::error('Google Drive upload failed: ' . $e->getMessage());
            throw new \Exception('Failed to upload file to Google Drive: ' . $e->getMessage());
        }
    }

    protected function downloadFromUrl($url)
    {
        try {
            // Create temp directory if it doesn't exist
            $tempDir = storage_path('app/public/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Get the file extension from the URL
            $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (empty($extension)) {
                $extension = 'mp3'; // Default to mp3 if no extension found
            }
            
            // Create temp file with proper extension in our temp directory
            $tempFile = $tempDir . '/mureka_' . uniqid() . '.' . $extension;
            
            Log::info('Downloading file to: ' . $tempFile);
            
            $ch = curl_init($url);
            $fp = fopen($tempFile, 'wb');
            
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            
            curl_exec($ch);
            
            if (curl_errno($ch)) {
                throw new \Exception('Failed to download file: ' . curl_error($ch));
            }
            
            curl_close($ch);
            fclose($fp);
            
            Log::info('File downloaded successfully to: ' . $tempFile);
            
            return $tempFile;
        } catch (\Exception $e) {
            Log::error('Failed to download file from URL: ' . $e->getMessage());
            throw new \Exception('Failed to download file from URL: ' . $e->getMessage());
        }
    }
}
