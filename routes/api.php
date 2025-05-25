<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Webhook routes
Route::post('/webhook/test', [WebhookController::class, 'handleWebhook']);
Route::post('/webhook/order', [WebhookController::class, 'handleOrder']);

Route::post('/webhook/convert-mp3-to-mp4', [WebhookController::class, 'convertMp3ToMp4']);
