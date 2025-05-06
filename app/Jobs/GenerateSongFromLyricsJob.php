<?php

namespace App\Jobs;

use App\Models\SongRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class GenerateSongFromLyricsJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    protected $songRequest;

    public function __construct(SongRequest $songRequest)
    {
        $this->songRequest = $songRequest;
    }

    public function handle()
    {
        // Call Mureka API to generate song from lyrics
        $response = Http::post('https://murekaapi.com/generate', [
            'lyrics' => $this->songRequest->lyrics,
        ]);

        if ($response->successful()) {
            $mp3Url = $response->json()['mp3_url'];
            $this->songRequest->update([
                'mp3_path' => $mp3Url,
                'status' => 'uploaded',
            ]);
        } else {
            $this->fail(new \Exception('Failed to generate song.'));
        }
    }
}
