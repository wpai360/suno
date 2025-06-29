<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\ChatgptService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateLyricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;
    protected $customerName;
    protected $city;
    protected $items;
    protected $groupSize;

    public function __construct(Order $order, $customerName, $city, $items, $groupSize)
    {
        $this->order = $order;
        $this->customerName = $customerName;
        $this->city = $city;
        $this->items = $items;
        $this->groupSize = $groupSize;
    }

    public function handle(ChatgptService $chatGPTService)
    {
        try {
            Log::info('Starting lyrics generation', [
                'order_id' => $this->order->id,
                'customer' => $this->customerName
            ]);

            // Generate lyrics using ChatGPT
            $lyrics = $chatGPTService->generateLyrics(
                $this->customerName,
                $this->city,
                $this->items,
                $this->groupSize
            );

            // Update order with lyrics
            $this->order->update([
                'lyrics' => $lyrics,
                'status' => 'lyrics_generated'
            ]);

            Log::info('Lyrics generated successfully', [
                'order_id' => $this->order->id
            ]);

            // Dispatch the next job in the chain
            GenerateSongFromLyricsJob::dispatch($this->order, $lyrics)
                ->onQueue('songs');

        } catch (\Exception $e) {
            Log::error('Lyrics generation failed', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage()
            ]);
            
            $this->order->update(['status' => 'failed']);
            throw $e;
        }
    }
}
