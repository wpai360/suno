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
        $prompt = "Create a fun Italian song about an order placed in $city. 
        The customer, $customerName, ordered: " . implode(", ", $items) . ". 
        They are dining with $groupSize.";

        $response = Http::withOptions([
            'verify' => false, // Bypass SSL verification
        ])->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => [['role' => 'system', 'content' => 'You are a creative songwriter.'], 
                           ['role' => 'user', 'content' => $prompt]],
            'temperature' => 0.8,
            'max_tokens' => 300,
        ]);

        return $response->json('choices.0.message.content') ?? 'No lyrics generated';
    }
}
