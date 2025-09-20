<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned the "web" middleware group. Make something great!
|
*/

// Simple sanity check
Route::get('/ping', fn () => 'ok');

// Test database connection
Route::get('/test-tasks', function () {
    try {
        $count = \DB::table('tasks')->count();
        $tasks = \DB::table('tasks')->limit(3)->get();
        return response()->json([
            'total_tasks' => $count,
            'sample_tasks' => $tasks
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage()
        ]);
    }
});

Route::get('/', fn () => Inertia::render('Home'));

// Public taskboard (no auth required) - temporary server-side version
Route::get('/taskboard', function () {
    // Get tasks using raw query to avoid model issues
    try {
        $tasks = \DB::table('tasks')->get(); // Remove the deleted_at filter
        
        // Return HTML directly instead of Inertia for testing
        return response()->view('taskboard-simple', ['tasks' => $tasks]);
    } catch (\Exception $e) {
        // Fallback to simple render if database fails
        return Inertia::render('SimpleTaskBoard', [
            'tasks' => [],
            'error' => $e->getMessage()
        ]);
    }
})->name('taskboard');

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
    
    Route::get('/projects', function () {
        return Inertia::render('Projects/Index');
    })->name('projects.index');
    
    // Admin and Team only routes
    Route::middleware('role:admin,team')->group(function () {
        Route::get('/team', function () {
            return Inertia::render('Team');
        })->name('team');
    });
    
    // Admin only routes
    Route::middleware('role:admin')->group(function () {
        Route::get('/settings', function () {
            return Inertia::render('Settings');
        })->name('settings');
        
        Route::get('/billing', function () {
            return Inertia::render('Billing');
        })->name('billing');
    });
    
    // Placeholder routes for development
    Route::get('/docs', function () {
        return Inertia::render('Docs');
    })->name('docs');
    
    Route::get('/tasks', function () {
        return Inertia::render('Tasks');
    })->middleware('role:admin,team')->name('tasks');
    
    Route::get('/approvals', function () {
        return Inertia::render('Approvals');
    })->middleware('role:client')->name('approvals');
});

require __DIR__.'/auth.php';
