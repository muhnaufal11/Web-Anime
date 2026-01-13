<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Health check endpoint for monitoring
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'service' => 'nipnime',
    ]);
});

// Video Extractor API (extract direct URL from embed)
Route::prefix('video')->group(function () {
    Route::post('/extract', [\App\Http\Controllers\Api\VideoExtractorController::class, 'extract']);
    Route::get('/supported-hosts', [\App\Http\Controllers\Api\VideoExtractorController::class, 'supportedHosts']);
});
