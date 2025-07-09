<?php

use App\Http\Controllers\ChannelController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\StorageController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
// auth controller
use App\Http\Controllers\Auth\AuthController;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // Channel routes
    Route::resource('channels', ChannelController::class);

    // Channel video deletion route
    Route::delete('/channels/{channel}/delete-videos', [ChannelController::class, 'deleteVideos'])->name('channels.delete-videos');

    // Nested video routes within channels
    Route::resource('channels.videos', VideoController::class)->except(['index']);

    // Additional video routes
    Route::post('/videos/{video}/queue', [VideoController::class, 'queueVideo'])->name('videos.queue');
    Route::post('/videos/{video}/update-status', [VideoController::class, 'updateStatus'])->name('videos.updateStatus');
    Route::delete('/videos/{video}/soft-delete', [VideoController::class, 'softDelete'])->name('videos.softDelete');

    // Disk usage routes
    Route::get('/disk-usage', [StorageController::class, 'index'])->name('disk-usage.index');
    Route::post('/disk-usage/cleanup', [StorageController::class, 'cleanup'])->name('disk-usage.cleanup');

    // login route
    Route::get('/login', [AuthController::class, 'index'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


});

require __DIR__.'/auth.php';
