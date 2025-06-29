<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\GoogleDriveService;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::all();

        foreach ($orders as $order) {
            $order->qrcode = base64_encode(QrCode::size(150)->generate(route('song.show', $order->id)));
        }

        return view('index', compact('orders'));
    }

    public function create()
    {
        return view('create');
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'customer_name' => 'required|string|max:255',
                'customer_email' => 'required|email|max:255',
                'customer_phone' => 'required|string|max:20',
                'group_size' => 'required|integer|min:1',
                'order_total' => 'required|numeric|min:0',
                'audio_file' => 'required|url',
                'convert_to_video' => 'boolean'
            ]);

            Log::info('Order request data:', $request->all());

            // Upload to Google Drive
            $driveService = new GoogleDriveService();
            $driveUrl = $driveService->upload($request->audio_file, $request->boolean('convert_to_video'));

            // Create order
            $order = Order::create([
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'group_size' => $request->group_size,
                'order_total' => $request->order_total,
                'audio_file' => $driveUrl,
                'status' => 'pending'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => $order
            ]);

        } catch (\Exception $e) {
            Log::error('Order creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function showSong(Order $order)
    {
        // Use the actual youtube_link from the order model
        $youtubeUrl = $order->youtube_link;
        
        // You might still need the songUrl and gdriveUrl depending on the song view
        // For now, let's keep the placeholders or fetch actual data if available
        $songUrl = $order->audio_file; // Assuming audio_file stores the direct song URL or path
        $gdriveUrl = $order->drive_link; // Assuming drive_link stores the Google Drive URL

        return view('song', compact('order', 'songUrl', 'youtubeUrl', 'gdriveUrl'));
    }

    /**
     * Manually generate and upload PDF for an order (for testing)
     */
    public function generatePdf(Order $order)
    {
        try {
            \App\Jobs\GenerateAndUploadPdfJob::dispatch($order)
                ->onQueue($order->getQueueName());
            
            return response()->json([
                'success' => true,
                'message' => 'PDF generation job dispatched successfully',
                'order_id' => $order->id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to dispatch PDF generation job: ' . $e->getMessage()
            ], 500);
        }
    }
}