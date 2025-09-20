<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\ProjectApiController;
use App\Http\Controllers\Api\TaskApiController;
use App\Http\Controllers\Api\ChatApiController;
use App\Http\Controllers\Api\DocApiController;
use App\Http\Controllers\Api\FileApiController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Api\Client\ClientTaskApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned the "api" middleware group. Make something great!
|
*/

// Health check endpoints (public)
Route::get('/health', HealthController::class);

// API Version 1
Route::prefix('v1')->group(function () {
    // Health endpoints
    Route::get('/health', HealthController::class);

    // Public authentication routes
    Route::middleware('throttle:login')->group(function () {
        Route::post('/login', [AuthApiController::class, 'login']);
    });

    // Other auth endpoints with general throttling
    Route::middleware('throttle:auth')->group(function () {
        // Add other public auth routes here if needed
    });

    // Protected routes requiring authentication
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        // Auth management
        Route::get('/me', [AuthApiController::class, 'me']);
        Route::post('/logout', [AuthApiController::class, 'logout']);
        Route::post('/logout-all', [AuthApiController::class, 'logoutAll']);

        // Projects CRUD
        Route::apiResource('projects', ProjectApiController::class);
        
        // Tasks with proper project scoping for account isolation
        Route::scopeBindings()->group(function () {
            Route::apiResource('projects.tasks', TaskApiController::class);
            
            // Task-specific actions
            Route::post('/projects/{project}/tasks/{task}/complete', [TaskApiController::class, 'complete']);
            Route::post('/projects/{project}/tasks/{task}/assign', [TaskApiController::class, 'assign']);
            Route::post('/projects/{project}/tasks/{task}/comments', [TaskApiController::class, 'addComment']);
            Route::post('/projects/{project}/tasks/{task}/files', [TaskApiController::class, 'attachFile']);
            Route::delete('/projects/{project}/tasks/{task}/files/{file}', [TaskApiController::class, 'detachFile']);
        });
        
        // Additional task file endpoint (simplified path)
        Route::post('/tasks/{task}/files', [TaskApiController::class, 'files']);
        
        // Project-specific endpoints
        Route::post('/projects/{project}/ask', [ChatApiController::class, 'ask']);
        Route::post('/projects/{project}/docs', [DocApiController::class, 'store']);
        
        // File management with signed URLs
        Route::get('/files/{file}', [FileApiController::class, 'show']);
        Route::get('/files/{file}/download', [FileApiController::class, 'download'])
             ->name('api.files.download');
             
        // Client-safe endpoints (restricted access)
        Route::prefix('client')->group(function () {
            Route::scopeBindings()->group(function () {
                Route::get('/projects/{project}/tasks', [ClientTaskApiController::class, 'index']);
                Route::get('/projects/{project}/tasks/{task}', [ClientTaskApiController::class, 'show']);
            });
        });
    });
});
