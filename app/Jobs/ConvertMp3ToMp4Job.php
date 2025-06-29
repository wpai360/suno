<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\VideoConversionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus;

class ConvertMp3ToMp4Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;
    protected $audioUrl;

    public function __construct(Order $order, $audioUrl)
    {
        $this->order = $order;
        $this->audioUrl = $audioUrl;
    }

    public function handle(VideoConversionService $videoConverter)
    {
        try {
            // Refresh the order model to get the latest data, although not strictly needed here yet
            $this->order->refresh();

            Log::info('Starting MP3 to MP4 conversion', [
                'order_id' => $this->order->id,
                'audio_url' => $this->audioUrl
            ]);

            // Convert MP3 to MP4
            $videoTitle = "AI Song for " . $this->order->customer_name . " from " . $this->order->city;
            $mp4Path = $videoConverter->convertToMp4($this->audioUrl, $videoTitle);

            // Update order with video path and status
            $this->order->update([
                'video_file' => $mp4Path,
                'status' => 'video_converted'
            ]);

            Log::info('Video conversion completed successfully', [
                'order_id' => $this->order->id,
                'video_path' => $mp4Path
            ]);

            // Now dispatch the next jobs that depend on the video file, specifying their queues
            Bus::chain([
                (new UploadToGDriveJob($this->order, $mp4Path))->onQueue($this->order->getQueueName()),
                (new UploadToYouTubeJob($this->order, $mp4Path))->onQueue($this->order->getQueueName()),
                (new CleanupVideoFileJob($mp4Path))->onQueue($this->order->getQueueName())
            ])->dispatch();

        } catch (\Exception $e) {
            Log::error('Video conversion failed', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage()
            ]);
            
            $this->order->update(['status' => 'failed']);
            throw $e;
        }
    }
}
