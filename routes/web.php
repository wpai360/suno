<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\YoutubeController;

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
Route::get('/auth/google', [GoogleAuthController::class, 'initiate'])->name('google.auth');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');
Route::post('/youtube/upload', [YoutubeController::class, 'uploadVideo'])->name('youtube.upload');

require __DIR__.'/auth.php';
