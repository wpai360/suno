<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\PdfGenerationService;
use App\Services\GoogleDriveService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateAndUploadPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle(PdfGenerationService $pdfService, GoogleDriveService $driveService)
    {
        try {
            Log::info('Starting PDF generation and upload', [
                'order_id' => $this->order->id
            ]);

            // Generate PDF
            $pdfPath = $pdfService->generateOrderPdf($this->order);

            Log::info('PDF generated successfully', [
                'order_id' => $this->order->id,
                'pdf_path' => $pdfPath
            ]);

            // Upload PDF to Google Drive
            $driveLink = $driveService->upload($pdfPath, false); // false for PDF, not video

            // Update order with PDF drive link
            $this->order->update([
                'pdf_drive_link' => $driveLink,
                'pdf_file_path' => $pdfPath
            ]);

            Log::info('PDF uploaded to Google Drive successfully', [
                'order_id' => $this->order->id,
                'drive_link' => $driveLink
            ]);

            // Clean up the local PDF file
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
                Log::info('Local PDF file cleaned up', [
                    'order_id' => $this->order->id,
                    'pdf_path' => $pdfPath
                ]);
            }

        } catch (\Exception $e) {
            Log::error('PDF generation and upload failed', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage()
            ]);
            
            // Don't update order status to failed since this is an additional feature
            // Just log the error and continue
            throw $e;
        }
    }
} 