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
| be assigned to the "web" middleware group. Make something great!
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

// Public taskboard (restored to use Inertia)
Route::get('/taskboard', function () {
    try {
        $tasks = \DB::table('tasks')->get();
        return Inertia::render('SimpleTaskBoard', [
            'tasks' => $tasks
        ]);
    } catch (\Exception $e) {
        return Inertia::render('SimpleTaskBoard', [
            'tasks' => [],
            'error' => $e->getMessage()
        ]);
    }
});

// Tasks route for main task management
Route::get('/tasks', function () {
    try {
        $tasks = \DB::table('tasks')->get();
        return Inertia::render('Teams/TaskBoard', [
            'tasks' => $tasks
        ]);
    } catch (\Exception $e) {
        return Inertia::render('Teams/TaskBoard', [
            'tasks' => [],
            'error' => $e->getMessage()
        ]);
    }
})->name('tasks.index');

// Require authentication routes
require __DIR__.'/auth.php';