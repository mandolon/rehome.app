<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\ProjectApiController;
use App\Http\Controllers\Api\ChatApiController;
use App\Http\Controllers\Api\DocApiController;
use App\Http\Controllers\Api\FileApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned the "api" middleware group. Make something great!
|
*/

// Public authentication routes
Route::middleware('throttle:auth')->group(function () {
    Route::post('/login', [AuthApiController::class, 'login']);
});

// Protected routes requiring authentication
Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    // Auth management
    Route::get('/me', [AuthApiController::class, 'me']);
    Route::post('/logout', [AuthApiController::class, 'logout']);

    // Projects CRUD
    Route::apiResource('projects', ProjectApiController::class);
    
    // Project-specific endpoints
    Route::post('/projects/{project}/ask', [ChatApiController::class, 'ask']);
    Route::post('/projects/{project}/docs', [DocApiController::class, 'store']);
    
    // File management with signed URLs
    Route::get('/files/{file}', [FileApiController::class, 'show']);
    Route::get('/files/{file}/download', [FileApiController::class, 'download'])
         ->name('api.files.download');
});

// Health check endpoint (public)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'version' => '1.0.0',
    ]);
});
