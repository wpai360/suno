<?php

namespace App\Services;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Log;

class PdfGenerationService
{
    /**
     * Generate PDF for a single order
     */
    public function generateOrderPdf(Order $order): string
    {
        try {
            // Force APP_URL for queue jobs that don't have HTTP context
            $appUrl = config('app.url');
            if (empty($appUrl) || $appUrl === 'http://localhost') {
                $appUrl = env('APP_URL', 'https://api.targetgong.com');
            }
            
            // Generate QR code for the order with forced domain
            $songUrl = $appUrl . '/song/' . $order->id;
            Log::info('Song URL: ' . $songUrl);
            $qrCode = QrCode::size(200)->generate($songUrl);
            
            // Prepare data for the PDF
            $data = [
                'order' => $order,
                'qrCode' => $qrCode,
                'orderDate' => $order->created_at->format('F j, Y'),
                'items' => is_array($order->items) ? $order->items : json_decode($order->items, true),
                'youtubeUrl' => $order->youtube_link,
                'driveUrl' => $order->drive_link,
                'songUrl' => $songUrl,
            ];

            // Generate PDF
            $pdf = Pdf::loadView('pdfs.order', $data);
            
            // Set paper size and orientation
            $pdf->setPaper('A4', 'portrait');
            
            // Generate unique filename
            $filename = 'order_' . $order->id . '_' . time() . '.pdf';
            $filepath = storage_path('app/public/pdfs/' . $filename);
            
            // Ensure directory exists
            if (!file_exists(dirname($filepath))) {
                mkdir(dirname($filepath), 0755, true);
            }
            
            // Save PDF to storage
            $pdf->save($filepath);
            
            Log::info('PDF generated successfully', [
                'order_id' => $order->id,
                'filepath' => $filepath,
                'app_url' => $appUrl,
                'song_url' => $songUrl
            ]);
            
            return $filepath;
            
        } catch (\Exception $e) {
            Log::error('PDF generation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate PDF content as string (for testing)
     */
    public function generateOrderPdfContent(Order $order): string
    {
        try {
            // Generate QR code for the order
            $qrCode = QrCode::size(200)->generate(route('song.show', $order->id));
            
            // Prepare data for the PDF
            $data = [
                'order' => $order,
                'qrCode' => $qrCode,
                'orderDate' => $order->created_at->format('F j, Y'),
                'items' => is_array($order->items) ? $order->items : json_decode($order->items, true),
                'youtubeUrl' => $order->youtube_link,
                'driveUrl' => $order->drive_link,
            ];

            // Generate PDF
            $pdf = Pdf::loadView('pdfs.order', $data);
            $pdf->setPaper('A4', 'portrait');
            
            return $pdf->output();
            
        } catch (\Exception $e) {
            Log::error('PDF content generation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
} 