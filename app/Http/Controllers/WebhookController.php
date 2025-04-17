<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ChatGPTService;
use App\Services\SunoService;
use App\Services\YouTubeService;
use App\Services\GoogleDriveService;
use App\Models\Order;

class WebhookController extends Controller
{

    protected $chatGPTService;
    protected $sunoService;

    public function __construct(ChatGPTService $chatGPTService, SunoService $sunoService)
    {
        $this->chatGPTService = $chatGPTService;
        $this->sunoService = $sunoService;
    }
    public function handleOrder(Request $request)
    {
        $data = $request->all(); // Get JSON data

        // Extract order details
        $customerName = $data['customer']['name'] ?? 'Customer';
        $city = $data['deliveryAddress']['city'] ?? 'Unknown';
        $orderTotal = $data['payment']['amount'] / 100; // Convert cents to euros
        $items = collect($data['items'])->map(fn($item) => $item['name'])->toArray();

        // Determine group size
        $groupSize = ($orderTotal > 20 || count($items) >= 4) ? 'multiple people' : 'solo dining';

        // Generate AI lyrics using ChatGPT
        $lyrics = app(ChatGPTService::class)->generateLyrics($customerName, $city, $items, $groupSize);

        // Generate song using Suno API
        $songFile = app(SunoService::class)->generateSongDefault($lyrics);

        // Upload to Google Drive & YouTube
        $driveLink = app(GoogleDriveService::class)->upload($songFile);
        $youtubeLink = app(YouTubeService::class)->upload($songFile);

        // Save order & links in database
        $order = Order::create([
            'customer_name' => $customerName,
            'city' => $city,
            'order_total' => $orderTotal,
            'group_size' => $groupSize,
            'items' => json_encode($items),
            'lyrics' => $lyrics,
            'drive_link' => $driveLink,
            'youtube_link' => $youtubeLink,
        ]);

        return response()->json(['message' => 'AI song generated!', 'youtube_link' => $youtubeLink]);
    }

    public function handleWebhook(Request $request)
    {
        $data = $request->all();
        
        // Extract necessary order details
        $orderDetails = [
            'items' => collect($data['items'])->pluck('name')->toArray(),
            'total' => $data['payment']['amount'] / 100,
            'source' => $data['by'],
            'customer' => $data['customer']['name'] ?? 'Customer',
            'city' => $data['deliveryAddress']['city'] ?? 'Unknown'
        ];

        // Determine group size estimation
        $groupSize = ($orderDetails['total'] > 20 || count($orderDetails['items']) >= 4) ? 'a group' : 'solo';

        // Generate lyrics using ChatGPT
        $lyrics = $this->chatGPTService->generateLyrics(
            $orderDetails['customer'], 
            $orderDetails['city'], 
            $orderDetails['items'], 
            $groupSize
        );
        // dd($lyrics);
        // Generate the song using Suno API
        $songFile = $this->sunoService->generateSongDefault($lyrics);
        dd($songFile);
        if (!empty($songFile['id'])) {
            $songStatus = $this->sunoService->getSongStatus('66901046722561');
            dd($songStatus);
        } else {
            dd('Song generation failed or no ID returned.');
        }
        // Upload the song to Google Drive
        // Store order and song details
        Order::create([
            'customer_name' => $orderDetails['customer'],
            'city' => $orderDetails['city'],
            'total' => $data['payment']['amount'],
            'status' => 'processing',
            'lyrics' => $lyrics,
            'song_file' => $songFile,
        ]);

        return response()->json(['message' => 'Song generated successfully', 'song_file' => $songFile]);
    }
}
