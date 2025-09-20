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

Route::get('/', function () {
    return Inertia::render('Landing');
});

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
        
        Route::get('/teams/tasks', function () {
            return Inertia::render('Teams/TaskBoard');
        })->name('teams.tasks');
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
