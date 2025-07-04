<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ChatgptService;
use App\Services\SunoService;
use App\Services\YouTubeService;
use App\Services\GoogleDriveService;
use App\Models\Order;
use App\Services\VideoConversionService;
use Illuminate\Support\Facades\Log;
use App\Jobs\GenerateLyricsJob;

class WebhookController extends Controller
{

    protected $chatGPTService;
    protected $sunoService;
    protected $videoConverter;

    public function __construct(ChatgptService $chatGPTService, SunoService $sunoService)
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
        $lyrics = app(ChatgptService::class)->generateLyrics($customerName, $city, $items, $groupSize);

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
        try {
            $data = $request->all();
            
            // Extract necessary order details
            $orderDetails = [
                'items' => collect($data['items'] ?? [])->pluck('name')->toArray(),
                'total' => $data['payment']['amount'] / 100,
                'source' => $data['by'] ?? 'web',
                'customer' => $data['customer']['name'] ?? 'Customer',
                'city' => $data['deliveryAddress']['city'] ?? 'Unknown'
            ];

            // Determine group size estimation
            $groupSize = ($orderDetails['total'] > 20 || count($orderDetails['items']) >= 4) ? 'a group' : 'solo';

            // Create order record
            $order = Order::create([
                'customer_name' => $orderDetails['customer'],
                'city' => $orderDetails['city'],
                'order_total' => $orderDetails['total'],
                'group_size' => $groupSize,
                'items' => json_encode($orderDetails['items']),
                'status' => 'pending'
            ]);

            // Start the job chain
            GenerateLyricsJob::dispatch($order, $orderDetails['customer'], $orderDetails['city'], $orderDetails['items'], $groupSize)
                ->onQueue($order->getQueueName());

            return response()->json([
                'message' => 'Order received and processing started',
                'order_id' => $order->id
            ]);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'message' => 'Failed to process webhook',
                'error' => $e->getMessage()
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

    public function testMp3ToMp4Conversion()
    {
        try {
            $mp3File = base_path('input.mp3');
            $imageFile = base_path('input.jpeg');
            $outputFile = base_path('output.mp4');

            // Check if input files exist
            if (!file_exists($mp3File)) {
                return response()->json(['error' => 'MP3 file not found'], 404);
            }
            if (!file_exists($imageFile)) {
                return response()->json(['error' => 'Image file not found'], 404);
            }

            // Use the VideoConversionService
            $outputPath = $this->videoConverter->convertToMp4($mp3File, 'Test Video');

            // Log the results
            Log::info('FFmpeg conversion completed', [
                'output_path' => $outputPath
            ]);

            // Upload to Google Drive
            /** @var \App\Services\GoogleDriveService $driveService */
            $driveService = app(\App\Services\GoogleDriveService::class);
            $driveLink = $driveService->upload($outputPath, true, '1z-hsLq_BzwOuc4xBJL6VNvVkLtLB5chn');

            // Upload to YouTube
            $youtubeService = app(YouTubeService::class);
            $youtubeResult = $youtubeService->uploadVideo(
                $outputPath,
                'AI Generated Music Video',
                'This is an AI-generated music video',
                'public'
            );

            // Create a test order for PDF generation
            $testOrder = Order::create([
                'customer_name' => 'Test Customer',
                'city' => 'Test City',
                'order_total' => 25.50,
                'group_size' => 'a group',
                'items' => json_encode(['Pizza Margherita', 'Spaghetti Carbonara', 'Tiramisu']),
                'lyrics' => 'This is a test song about delicious Italian food...',
                'drive_link' => $driveLink,
                'youtube_link' => $youtubeResult['video_url'] ?? '',
                'youtube_id' => $youtubeResult['video_id'] ?? '',
                'status' => 'completed'
            ]);

            // Generate and upload PDF
            $pdfService = app(\App\Services\PdfGenerationService::class);
            $pdfPath = $pdfService->generateOrderPdf($testOrder);
            
            // Upload PDF to Google Drive
            $pdfDriveLink = $driveService->upload($pdfPath, false, '1SNCAXxgJMtEtYdggMK60pSeInoiLIU1h'); 
            
            // Update order with PDF link
            $testOrder->update([
                'pdf_drive_link' => $pdfDriveLink,
                'pdf_file_path' => $pdfPath
            ]);

            // Clean up local PDF file
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }

            return response()->json([
                'success' => true,
                'message' => 'Conversion, upload, and PDF generation successful',
                'data' => [
                    'output_file' => $outputFile,
                    'drive_link' => $driveLink,
                    'youtube' => $youtubeResult,
                    'test_order' => [
                        'id' => $testOrder->id,
                        'customer_name' => $testOrder->customer_name,
                        'song_url' => route('song.show', $testOrder->id)
                    ],
                    'pdf_drive_link' => $pdfDriveLink
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Conversion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error during conversion: ' . $e->getMessage()
            ], 500);
        }
    }
}
