<?php

namespace App\Jobs;

use App\Models\SongRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class GenerateLyricsJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    protected $songRequest;

    public function __construct(SongRequest $songRequest)
    {
        $this->songRequest = $songRequest;
    }

    public function handle()
    {
        // Call ChatGPT API to generate lyrics
        $response = Http::post('https://api.openai.com/v1/completions', [
            'model' => 'gpt-4',
            'prompt' => 'Generate song lyrics based on... (context)',
            'max_tokens' => 1000,
        ]);

        if ($response->successful()) {
            $lyrics = $response->json()['choices'][0]['text'];
            $this->songRequest->update([
                'lyrics' => $lyrics,
                'status' => 'generating',
            ]);
        } else {
            $this->fail(new \Exception('Failed to generate lyrics.'));
        }
    }
}
