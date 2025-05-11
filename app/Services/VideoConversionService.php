<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VideoConversionService
{
    protected $ffmpegPath;
    protected $tempDir;

    public function __construct()
    {
        // Set FFmpeg path - adjust this based on your system
        $this->ffmpegPath = 'C:\\ffmpeg\\ffmpeg.exe';
        $this->tempDir = storage_path('app/public/temp');
        
        // Create temp directory if it doesn't exist
        if (!file_exists($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    public function convertToMp4($mp3Path, $title = 'Song Video')
    {
        try {
            // Generate output path
            $outputPath = $this->tempDir . '/video_' . uniqid() . '.mp4';
            $imagePath = public_path('images/default-thumbnail.jpg');
            
            // Build the ffmpeg command
            $cmd = sprintf(
                '%s -loop 1 -i %s -i %s -c:v libx264 -c:a aac -b:a 192k -shortest -y %s 2>&1',
                $this->ffmpegPath,
                escapeshellarg($imagePath),
                escapeshellarg($mp3Path),
                escapeshellarg($outputPath)
            );

            Log::info('Executing FFmpeg command: ' . $cmd);
            
            // Execute command
            exec($cmd, $output, $returnCode);
            
            // Log the output
            Log::info('FFmpeg output: ' . implode("\n", $output));
            
            if ($returnCode !== 0) {
                Log::error('FFmpeg conversion failed. Return code: ' . $returnCode);
                throw new \Exception('Failed to convert audio to video');
            }
            
            Log::info('Video conversion completed successfully. Output: ' . $outputPath);
            
            return $outputPath;
        } catch (\Exception $e) {
            Log::error('Video conversion failed: ' . $e->getMessage());
            throw new \Exception('Failed to convert audio to video: ' . $e->getMessage());
        }
    }

    public function cleanup($filePath)
    {
        if (file_exists($filePath)) {
            unlink($filePath);
            Log::info('Cleaned up file: ' . $filePath);
        }
    }
} 