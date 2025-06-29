<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\YoutubeController;
use App\Http\Controllers\QrCodeController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
Route::get('/song/{order}', [OrderController::class, 'showSong'])->name('song.show');
Route::post('/orders/{order}/generate-pdf', [OrderController::class, 'generatePdf'])->name('orders.generate-pdf');

Route::get('/test-conversion', [WebhookController::class, 'testMp3ToMp4Conversion'])->name('test.conversion');

// Google Authentication Routes
Route::get('/auth/google', [GoogleAuthController::class, 'initiate'])->name('google.auth');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');

// Google Token Management Routes
Route::get('/auth/google/token-status', [GoogleAuthController::class, 'tokenStatus'])->name('google.token.status');
Route::post('/auth/google/refresh-token', [GoogleAuthController::class, 'refreshToken'])->name('google.token.refresh');
Route::delete('/auth/google/clear-token', [GoogleAuthController::class, 'clearToken'])->name('google.token.clear');

// Test route for manual token refresh (for development/testing)
Route::get('/test/google-refresh', function() {
    \App\Jobs\RefreshGoogleTokenJob::dispatch();
    return response()->json(['message' => 'Token refresh job dispatched']);
})->name('test.google.refresh');

// Test route to check Google token status
Route::get('/test/google-token-status', function() {
    try {
        $tokenService = app(\App\Services\GoogleTokenService::class);
        $tokenInfo = $tokenService->getTokenInfo();
        
        return response()->json([
            'success' => true,
            'token_info' => $tokenInfo,
            'timestamp' => now()->toISOString()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => now()->toISOString()
        ], 500);
    }
})->name('test.google.token.status');

// Test route to clear Google token and force re-authentication
Route::get('/test/google-clear-token', function() {
    try {
        $tokenService = app(\App\Services\GoogleTokenService::class);
        $tokenService->clearToken();
        
        return response()->json([
            'success' => true,
            'message' => 'Token cleared successfully. Please re-authenticate with Google.',
            'auth_url' => route('google.auth'),
            'timestamp' => now()->toISOString()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => now()->toISOString()
        ], 500);
    }
})->name('test.google.clear.token');

Route::post('/youtube/upload', [YoutubeController::class, 'uploadVideo'])->name('youtube.upload');

Route::get('/qrcode/demo', [QrCodeController::class, 'demo']);

// Test route for PDF generation
Route::get('/test/pdf/{order}', function($orderId) {
    $order = \App\Models\Order::findOrFail($orderId);
    $pdfService = new \App\Services\PdfGenerationService();
    $pdfPath = $pdfService->generateOrderPdf($order);
    
    return response()->json([
        'success' => true,
        'message' => 'PDF generated successfully',
        'pdf_path' => $pdfPath,
        'order_id' => $order->id
    ]);
})->name('test.pdf');

// Simple PDF generation test with mock data
Route::get('/test/pdf-generate', function() {
    try {
        // Create a test order
        $testOrder = \App\Models\Order::create([
            'customer_name' => 'Test Customer',
            'city' => 'Test City',
            'order_total' => 25.50,
            'group_size' => 'a group',
            'items' => json_encode(['Pizza Margherita', 'Spaghetti Carbonara', 'Tiramisu']),
            'lyrics' => 'This is a test song about delicious Italian food...',
            'status' => 'completed'
        ]);

        // Generate PDF
        $pdfService = new \App\Services\PdfGenerationService();
        $pdfPath = $pdfService->generateOrderPdf($testOrder);
        
        // Upload to Google Drive
        $driveService = app(\App\Services\GoogleDriveService::class);
        $pdfDriveLink = $driveService->upload($pdfPath);
        
        // Update order
        $testOrder->update([
            'pdf_drive_link' => $pdfDriveLink,
            'pdf_file_path' => $pdfPath
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'PDF generated and uploaded successfully',
            'data' => [
                'order_id' => $testOrder->id,
                'pdf_path' => $pdfPath,
                'pdf_drive_link' => $pdfDriveLink,
                'song_url' => route('song.show', $testOrder->id)
            ]
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
})->name('test.pdf.generate');

require __DIR__.'/auth.php';
