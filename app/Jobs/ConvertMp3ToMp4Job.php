<?php

namespace App\Jobs;

use App\Models\SongRequest;
use App\Services\VideoConversionService;
use App\Services\YouTubeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ConvertMp3ToMp4Job implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    protected $songRequest;

    public function __construct(SongRequest $songRequest)
    {
        $this->songRequest = $songRequest;
    }

    public function handle()
    {
        try {
            // Convert MP3 to MP4
            $videoConverter = new VideoConversionService();
            $mp4Path = $videoConverter->convertToMp4(
                $this->songRequest->mp3_path,
                'AI Generated Song - ' . $this->songRequest->id
            );

            // Upload to YouTube
            $youtubeService = new YouTubeService();
            $videoId = $youtubeService->uploadVideo(
                $mp4Path,
                'AI Generated Song - ' . $this->songRequest->id,
                'An AI-generated personalized song.'
            );

            // Update the song request
            $this->songRequest->update([
                'mp4_path' => $mp4Path,
                'youtube_id' => $videoId,
                'status' => 'completed'
            ]);

            // Clean up the temporary MP4 file
            $videoConverter->cleanup($mp4Path);

        } catch (\Exception $e) {
            Log::error('Conversion/Upload failed: ' . $e->getMessage());
            $this->songRequest->update(['status' => 'failed']);
            throw $e;
        }
    }
}
