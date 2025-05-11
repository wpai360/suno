<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ChatGPTService;
use App\Services\SunoService;
use App\Services\YouTubeService;
use App\Services\GoogleDriveService;
use App\Models\Order;
use App\Services\VideoConversionService;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{

    protected $chatGPTService;
    protected $sunoService;
    protected $videoConverter;

    public function __construct(ChatGPTService $chatGPTService, SunoService $sunoService)
    {
        $this->chatGPTService = $chatGPTService;
        $this->sunoService = $sunoService;
        $this->videoConverter = new VideoConversionService();
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

        // Generate the song using Suno API
        $songFile = $this->sunoService->generateSongMureka($lyrics);

        if (!empty($songFile['id'])) {
            $songStatus = $this->sunoService->getSongStatus($songFile['id']);
            
            // Get the first audio URL from the choices
            $audioUrl = $songStatus['choices'][0]['url'];
            
            // Convert MP3 to MP4
            $videoTitle = "AI Song for " . $orderDetails['customer'] . " from " . $orderDetails['city'];
            $mp4Path = $this->videoConverter->convertToMp4($audioUrl, $videoTitle);
            
            // Upload to Google Drive
            $driveLink = app(GoogleDriveService::class)->upload($mp4Path, true); // true for video

            // Store order and song details
            // $order = Order::create([
            //     'customer_name' => $orderDetails['customer'],
            //     'city' => $orderDetails['city'],
            //     'order_total' => $orderDetails['total'],
            //     'status' => 'completed',
            //     'lyrics' => $lyrics,
            //     'audio_file' => $audioUrl,
            //     'drive_link' => $driveLink,
            //     'group_size' => $groupSize
            // ]);

            return response()->json([
                'message' => 'Song generated, converted to video, and uploaded successfully',
                'song_file' => $audioUrl,
                'drive_link' => $driveLink
            ]);
        } else {
            return response()->json([
                'message' => 'Song generation failed or no ID returned.',
                'status' => 'error'
            ], 500);
        }
    }

    public function convertMp3ToMp4(Request $request)
    {
        try {
            $request->validate([
                'mp3_url' => 'required|url',
                'title' => 'required|string'
            ]);

            Log::info('Starting MP3 to MP4 conversion', [
                'mp3_url' => $request->mp3_url,
                'title' => $request->title
            ]);

            // Convert MP3 to MP4
            $mp4Path = $this->videoConverter->convertToMp4(
                $request->mp3_url,
                $request->title
            );

            Log::info('Conversion completed successfully', [
                'mp4_path' => $mp4Path
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Conversion completed successfully',
                'data' => [
                    'mp4_path' => $mp4Path
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Conversion failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Conversion failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
