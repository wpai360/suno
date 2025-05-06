<?php

namespace App\Jobs;

use App\Models\SongRequest;
use FFMpeg;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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
        // Use FFmpeg to convert MP3 to MP4
        $mp3Path = $this->songRequest->mp3_path;
        $mp4Path = storage_path('app/public/converted_video.mp4');

        FFMpeg::fromDisk('public')
            ->open($mp3Path)
            ->addFilter('-f', 'mp4')
            ->export()
            ->toDisk('public')
            ->inFormat(new \FFMpeg\Format\Video\X264)
            ->save($mp4Path);

        $this->songRequest->update(['mp4_path' => $mp4Path]);
    }
}
