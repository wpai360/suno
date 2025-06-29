<?php

namespace App\Services;

use Google\Service\Drive;
use Illuminate\Support\Facades\Log;

class GoogleDriveService
{
    protected $client;
    protected $service;
    protected $videoConverter;
    protected $tokenService;

    public function __construct(GoogleTokenService $tokenService)
    {
        $this->tokenService = $tokenService;
        $this->videoConverter = new VideoConversionService();
    }

    protected function initializeClient()
    {
        try {
            // Get client with valid token from token service
            $this->client = $this->tokenService->getClient();
            $this->service = new Drive($this->client);
            
            Log::info('Google Drive client initialized successfully');
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

            // Initialize client with fresh token
            $this->initializeClient();

            // Determine MIME type based on file extension
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $mimeType = 'application/octet-stream'; // default
            
            switch ($extension) {
                case 'mp4':
                    $mimeType = 'video/mp4';
                    break;
                case 'mp3':
                    $mimeType = 'audio/mpeg';
                    break;
                case 'pdf':
                    $mimeType = 'application/pdf';
                    break;
                case 'jpg':
                case 'jpeg':
                    $mimeType = 'image/jpeg';
                    break;
                case 'png':
                    $mimeType = 'image/png';
                    break;
                default:
                    // Use the isVideo parameter as fallback for backward compatibility
                    $mimeType = $isVideo ? 'video/mp4' : 'audio/mpeg';
            }

            $fileMetadata = new \Google\Service\Drive\DriveFile([
                'name' => basename($filePath),
                'mimeType' => $mimeType
            ]);

            $content = file_get_contents($filePath);
            $file = $this->service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id, webViewLink'
            ]);

            Log::info('File uploaded to Google Drive successfully', [
                'file_id' => $file->getId(),
                'file_name' => basename($filePath),
                'mime_type' => $mimeType
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
            // Initialize client with fresh token
            $this->initializeClient();

            $response = $this->service->files->get($fileId, ['alt' => 'media']);
            file_put_contents($savePath, $response->getBody()->getContents());
            
            Log::info('File downloaded from Google Drive successfully', [
                'file_id' => $fileId,
                'save_path' => $savePath
            ]);
            
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
