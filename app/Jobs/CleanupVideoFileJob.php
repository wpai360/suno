<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CleanupVideoFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    public function handle()
    {
        try {
            Log::info('Starting cleanup for video file', [
                'file_path' => $this->filePath
            ]);

            // Check if the file exists and is a temporary file before deleting
            $tempDir = storage_path('app/public/temp');
            if (file_exists($this->filePath) && str_starts_with($this->filePath, $tempDir)) {
                 unlink($this->filePath);
                 Log::info('Temporary video file deleted successfully', [
                    'file_path' => $this->filePath
                 ]);
            } else {
                 Log::warning('File not found or not a temporary file, skipping cleanup', [
                    'file_path' => $this->filePath
                 ]);
            }

        } catch (\Exception $e) {
            Log::error('Video file cleanup failed', [
                'file_path' => $this->filePath,
                'error' => $e->getMessage()
            ]);
            // Don't re-throw the exception, cleanup failure shouldn't stop the chain
        }
    }
} 