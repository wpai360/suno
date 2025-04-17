<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/webhook/order', [WebhookController::class, 'handleOrder']);

Route::post('/webhook/test', [WebhookController::class, 'handleWebhook']);
