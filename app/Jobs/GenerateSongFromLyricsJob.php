<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\SunoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus;

class GenerateSongFromLyricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;
    protected $lyrics;

    public function __construct(Order $order, $lyrics)
    {
        $this->order = $order;
        $this->lyrics = $lyrics;
    }

    public function handle(SunoService $sunoService)
    {
        try {
            Log::info('Starting song generation', [
                'order_id' => $this->order->id
            ]);

            // Generate song using Suno API
            $songFile = $sunoService->generateSongMureka($this->lyrics);

            if (!empty($songFile['id'])) {
                $songStatus = $sunoService->getSongStatus($songFile['id']);
                $audioUrl = $songStatus['choices'][0]['url'];

                // Update order with audio URL
                $this->order->update([
                    'audio_file' => $audioUrl,
                    'status' => 'song_generated'
                ]);

                Log::info('Song generated successfully', [
                    'order_id' => $this->order->id,
                    'audio_url' => $audioUrl
                ]);

                // Dispatch the next job (video conversion)
                ConvertMp3ToMp4Job::dispatch($this->order, $audioUrl)
                    ->onQueue('video');

            } else {
                throw new \Exception('Song generation failed or no ID returned');
            }

        } catch (\Exception $e) {
            Log::error('Song generation failed', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage()
            ]);
            
            $this->order->update(['status' => 'failed']);
            throw $e;
        }
    }
}
