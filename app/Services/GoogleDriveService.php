<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected $client;
    protected $service;
    protected $videoConverter;

    public function __construct()
    {
        $this->client = new Client();
        $this->initializeClient();
        $this->videoConverter = new VideoConversionService();
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
            
            $this->service = new Drive($this->client);
        } catch (\Exception $e) {
            Log::error('Google Drive initialization error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function upload($filePath, $isVideo = false)
    {
        try {
            if (!file_exists($filePath)) {
                throw new \Exception("File not found: $filePath");
            }

            $fileMetadata = new \Google\Service\Drive\DriveFile([
                'name' => basename($filePath),
                'mimeType' => $isVideo ? 'video/mp4' : 'audio/mpeg'
            ]);

            $content = file_get_contents($filePath);
            $file = $this->service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $isVideo ? 'video/mp4' : 'audio/mpeg',
                'uploadType' => 'multipart',
                'fields' => 'id, webViewLink'
            ]);

            return $file->getWebViewLink();
        } catch (\Exception $e) {
            Log::error('Google Drive upload error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function download($fileId, $savePath)
    {
        try {
            $response = $this->service->files->get($fileId, ['alt' => 'media']);
            file_put_contents($savePath, $response->getBody()->getContents());
            return $savePath;
        } catch (\Exception $e) {
            Log::error('Google Drive download error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function downloadFromUrl($url)
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
