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

Route::post('/youtube/upload', [YoutubeController::class, 'uploadVideo'])->name('youtube.upload');

Route::get('/qrcode/demo', [QrCodeController::class, 'demo']);

require __DIR__.'/auth.php';
