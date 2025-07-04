<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\YouTubeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UploadToYouTubeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;
    protected $videoFilePath;

    public function __construct(Order $order, $videoFilePath)
    {
        $this->order = $order;
        $this->videoFilePath = $videoFilePath;
    }

    public function handle(YouTubeService $youtubeService)
    {
        try {
            // Refresh the order model to get the latest data
            $this->order->refresh();

            Log::info('Starting YouTube upload', [
                'order_id' => $this->order->id
            ]);

            // Use the video file path passed to the job
            if (!$this->videoFilePath) {
                 // Fallback to order->video_file if for some reason it's not passed
                 $this->videoFilePath = $this->order->video_file;
                 if (!$this->videoFilePath) {
                    throw new \Exception('No video file path provided for YouTube upload');
                 }
            }

            // Upload to YouTube
            $youtubeResult = $youtubeService->uploadVideo(
                $this->videoFilePath,
                "AI Song for " . $this->order->customer_name . " from " . $this->order->city,
                "This is an AI-generated personalized song based on your order.",
                'public',
                config('services.youtube.channel_id') // Get channel ID from config
            );

            // Update order with YouTube details
            $this->order->update([
                'youtube_link' => $youtubeResult['video_url'],
                'youtube_id' => $youtubeResult['video_id'],
                'status' => 'completed'
            ]);

            Log::info('YouTube upload completed successfully', [
                'order_id' => $this->order->id,
                'youtube_id' => $youtubeResult['video_id']
            ]);

            // Dispatch PDF generation job after YouTube upload is complete
            \App\Jobs\GenerateAndUploadPdfJob::dispatch($this->order)
                ->onQueue($this->order->getQueueName());

        } catch (\Exception $e) {
            Log::error('YouTube upload failed', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage()
            ]);
            
            $this->order->update(['status' => 'failed']);
            throw $e;
        }
    }
}
