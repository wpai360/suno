<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log; // Correct way to use the Log facade
use Illuminate\Http\Client\RequestException;

class SunoService
{

    private string $apiKey = "op_m9fl8w67VPaTmELByjGZp34Vv52A3A4";
    // private string $apiUrl = 'https://api.mureka.ai/v1/song';
    private string $apiUrl = 'http://localhost:3000/api';
    public function __construct()
    {
        // $this->apiUrl = env('SUNO_API_URL');
        // $this->apiKey = env('SUNO_API_KEY');
    }

    public function generateSongSuno($lyrics)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey
        ])->post($this->apiUrl . '/generate', [
            'lyrics' => $lyrics,
            'language' => 'it'
        ]);

        return $response->json();
    }

    public function generateSong(string $lyrics): array
    {
        try {
            $response = Http::withOptions([
                'verify' => false, // Bypass SSL verification
            ])->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->post($this->apiUrl . '/generate', [
                'lyrics' => $lyrics,
                'model' => 'auto',
                'prompt' => 'r&b, slow, passionate, male vocal',
            ]);

            $response->throw(); // Throws RequestException on failure

            return $response->json();
        } catch (RequestException $e) {
            Log::error('Mureka API Error', [
                'message' => $e->getMessage(),
                'status' => optional($e->response)->status(),
                'body' => optional($e->response)->body(),
            ]);
            return []; // Or return ['error' => 'Failed to generate song.'];
        } catch (\Exception $e) {
            Log::error('Unexpected Error in SongGenerationService: ' . $e->getMessage());
            return [];
        }
    }
    public function generateSongDefault(string $lyrics): array
    {
        try {
            $response = Http::post($this->apiUrl . '/custom_generate', [
                "tags" => "pop metal male melancholic",
                "title" => "Sheroo Song",
                "make_instrumental" => false,
                "wait_audio" => false,
                'prompt' => "[Verse]\nWires hum bright screens flash\nDreams ignite in a digital dash\nBreaking codes and mending firewalls\nScaling heights can't hear the falls\n\n[Verse 2]\nBinary storm in your hands\nBuilding empires from the sands\nAlgorithms whisper to the brave\nHacking through this digital wave\n\n[Chorus]\nFix the code let's make it right\nInnovation takes flight tonight\nStartup dreams scaling high\nIn this electric sky\n\n[Bridge]\nPixels dance in a row\nElectric currents start to flow\nInnovation in our veins\nBreaking out of rusty chains\n\n[Verse 3]\nWhile the stars all blink and blend\nFuture’s here it has no end\nLines of code will save the day\nChanging worlds in a new way\n\n[Chorus]\nFix the code let's make it right\nInnovation takes flight tonight\nStartup dreams scaling high\nIn this electric sky",

            ]);

            $response->throw(); // Throws RequestException on failure

            return $response->json();
        } catch (RequestException $e) {
            Log::error('Mureka API Error', [
                'message' => $e->getMessage(),
                'status' => optional($e->response)->status(),
                'body' => optional($e->response)->body(),
            ]);
            return []; // Or return ['error' => 'Failed to generate song.'];
        } catch (\Exception $e) {
            Log::error('Unexpected Error in SongGenerationService: ' . $e->getMessage());
            return [];
        }
    }
    public function getSongStatus(string $id): array
    {
        try {
            $response = Http::withOptions([
                'verify' => false, // Bypass SSL verification
            ])->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->apiUrl . '/query/' . $id);

            $response->throw();

            return $response->json();
        } catch (\Illuminate\Http\Client\RequestException $e) {
            Log::error('Mureka API Query Error', [
                'message' => $e->getMessage(),
                'status' => optional($e->response)->status(),
                'body' => optional($e->response)->body(),
            ]);
            return [];
        } catch (\Exception $e) {
            Log::error('Unexpected Error in getSongStatus: ' . $e->getMessage());
            return [];
        }
    }
}
