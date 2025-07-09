<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\StorageController;

// Auth routes
Route::get('/login', [AuthController::class, 'index'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Welcome page - redirect to dashboard if authenticated
Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard');
    }

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Dashboard route
Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Channel routes
    Route::resource('channels', ChannelController::class);

    // Nested video routes within channels
    Route::resource('channels.videos', VideoController::class);
    Route::post('channels/{channel}/videos/{video}/soft-delete', [VideoController::class, 'softDelete'])->name('channels.videos.soft-delete');
    Route::post('channels/{channel}/videos/{video}/queue', [VideoController::class, 'queueVideo'])->name('channels.videos.queue');

    // Additional video routes
    Route::post('/videos/{video}/queue', [VideoController::class, 'queueVideo'])->name('videos.queue');
    Route::post('/videos/{video}/update-status', [VideoController::class, 'updateStatus'])->name('videos.updateStatus');
    Route::delete('/videos/{video}/soft-delete', [VideoController::class, 'softDelete'])->name('videos.softDelete');
});

// Guest routes
Route::middleware('guest')->group(function () {
    // Channel video deletion route
    Route::delete('/channels/{channel}/delete-videos', [ChannelController::class, 'deleteVideos'])->name('channels.delete-videos');
});

// API routes
Route::prefix('api')->group(function () {
    // Video status update
    Route::post('videos/{video}/status', [VideoController::class, 'updateStatus']);

    // Disk usage routes
    Route::get('/disk-usage', [StorageController::class, 'index'])->name('disk-usage.index');
    Route::post('/disk-usage/cleanup', [StorageController::class, 'cleanup'])->name('disk-usage.cleanup');
});

require __DIR__.'/auth.php';
