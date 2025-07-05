<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ChatgptService
{
    protected $apiKey;
    protected $apiUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key'); // Load from config
    }

    public function sendMessage(array $messages, array $options = [])
    {
        $defaultOptions = [
            'model' => 'gpt-4o-mini', // Or another available model
            'temperature' => 0.7,
            'max_tokens' => 150,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
        ];

        $requestOptions = array_merge($defaultOptions, $options);
        $requestOptions['messages'] = $messages;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, $requestOptions);

            $response->throw(); // Throw an exception for HTTP errors (4xx or 5xx)

            $responseData = $response->json();

            if (isset($responseData['choices'][0]['message']['content'])) {
                return $responseData['choices'][0]['message']['content'];
            } else {
                return null; // Or handle the missing content appropriately
            }
        } catch (\Exception $e) {
            // Log the error, handle it, or return an error message
            \Log::error('ChatGPT API Error: ' . $e->getMessage());
            return null; // Or return an error message to the user
        }
    }

    public function createMessage($role, $content) {
        return ['role' => $role, 'content' => $content];
    }

    public function generateLyrics($customerName, $city, $items, $groupSize)
    {
        $prompt = "Write a short, funny song in a reggae style, 100% in the Italian language, should keep in my the language of the song in italian not english.
The song should be about a food delivery order placed in zona Nord di Milano by a customer named Stefano.
They ordered: " . implode(", ", $items) . ".

Make the song:
- Humorous and friendly in tone
- Personalized with the customer's name and the dishes they ordered
- Branded as a thank-you message from TigerGong (mention the brand at least twice)
- Include a catchy chorus
- End with a friendly request to leave a positive review if they enjoyed the food
- Suitable for a reggae beat
- Fit within 60 seconds of singing time (aim for 8–12 short lines of verse, 4 lines of chorus, 1–2 line outro)

Use rhymes, playful expressions, and culturally relevant Italian phrasing.";

        $response = Http::withOptions([
            'verify' => false, // Bypass SSL verification
        ])->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => [['role' => 'system', 'content' => 'You are a creative songwriter, writing songs in italian language.'], 
                           ['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.8,
            'max_tokens' => 300,
        ]);

        return $response->json('choices.0.message.content') ?? 'No lyrics generated';
    }
}
