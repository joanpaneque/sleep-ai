<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\VideoController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnalyticsController;

// Auth routes
Route::get('/login', [AuthController::class, 'index'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Root route - redirect based on authentication status
Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/dashboard');
    }

    return redirect('/login');
});

// Dashboard route
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Channel routes
    Route::resource('channels', ChannelController::class);
    Route::get('channels/{channel}/settings', [ChannelController::class, 'settings'])->name('channels.settings');
    Route::get('channels/{channel}/analytics', [ChannelController::class, 'analytics'])->name('channels.analytics');
    Route::get('channels/{channel}/videos/{video}/analytics-page', [ChannelController::class, 'videoAnalyticsPage'])->name('channels.videos.analytics-page');
    Route::get('channels/{channel}/videos/{video}/details', [ChannelController::class, 'getVideoDetails']);
    Route::post('channels/{channel}/test-connection', [ChannelController::class, 'testConnection'])->name('channels.test-connection');
    Route::post('channels/{channel}/start-oauth', [ChannelController::class, 'startOAuth'])->name('channels.start-oauth');

    // YouTube API routes
    Route::get('channels/{channel}/youtube/info', [ChannelController::class, 'getYoutubeChannelInfo'])->name('channels.youtube.info');
    Route::get('channels/{channel}/youtube/stats', [ChannelController::class, 'getYoutubeChannelStats'])->name('channels.youtube.stats');
    Route::get('channels/{channel}/youtube/videos', [ChannelController::class, 'getYoutubeChannelVideos'])->name('channels.youtube.videos');
    Route::get('channels/{channel}/youtube/playlists', [ChannelController::class, 'getYoutubeChannelPlaylists'])->name('channels.youtube.playlists');
    Route::get('channels/{channel}/youtube/dashboard', [ChannelController::class, 'getYoutubeChannelDashboard'])->name('channels.youtube.dashboard');
    Route::get('channels/{channel}/youtube/trending', [ChannelController::class, 'getChannelTrendingVideos'])->name('channels.youtube.trending');
    Route::get('channels/{channel}/youtube/best-performing', [ChannelController::class, 'getChannelBestPerformingVideos'])->name('channels.youtube.best-performing');

    // Video-specific YouTube API routes
    Route::get('channels/{channel}/videos/{video}/details', [ChannelController::class, 'getVideoDetails'])->name('channels.videos.details');
    Route::get('channels/{channel}/videos/{video}/statistics', [ChannelController::class, 'getVideoStatistics'])->name('channels.videos.statistics');
    Route::get('channels/{channel}/videos/{video}/comments', [ChannelController::class, 'getVideoComments'])->name('channels.videos.comments');
    Route::get('channels/{channel}/videos/{video}/performance', [ChannelController::class, 'getVideoPerformanceMetrics'])->name('channels.videos.performance');
    Route::get('channels/{channel}/videos/{video}/engagement', [ChannelController::class, 'getVideoEngagementAnalysis'])->name('channels.videos.engagement');
    Route::get('channels/{channel}/videos/{video}/analytics', [ChannelController::class, 'getVideoAnalytics'])->name('channels.videos.analytics');

    // Local database routes (synced data)
    Route::get('channels/{channel}/stats-db', [ChannelController::class, 'getChannelStatsFromDB'])->name('channels.stats.db');
    Route::get('channels/{channel}/videos-db', [ChannelController::class, 'getChannelVideosFromDB'])->name('channels.videos.db');
    Route::get('channels/{channel}/top-performing-db', [ChannelController::class, 'getTopPerformingVideosFromDB'])->name('channels.top-performing.db');
    Route::get('channels/{channel}/dashboard-db', [ChannelController::class, 'getChannelDashboardFromDB'])->name('channels.dashboard.db');
    Route::post('channels/{channel}/sync', [ChannelController::class, 'triggerSync'])->name('channels.sync');

    // Global Analytics routes
    Route::get('analytics', [ChannelController::class, 'globalAnalytics'])->name('analytics.index');
    Route::get('analytics/global-stats', [ChannelController::class, 'getGlobalStats']);
    Route::get('analytics/all-videos', [ChannelController::class, 'getAllVideos']);
    Route::post('analytics/sync-all', [ChannelController::class, 'triggerGlobalSync']);
    Route::get('/analytics/daily-stats', [AnalyticsController::class, 'getDailyStats'])
        ->name('analytics.daily-stats');

    Route::post('generate-webhook-token', [ChannelController::class, 'regenerateToken'])->name('generate-webhook-token');

    // Nested video routes within channels
    Route::resource('channels.videos', VideoController::class);
    Route::post('channels/{channel}/videos/{video}/soft-delete', [VideoController::class, 'softDelete'])->name('channels.videos.soft-delete');
    Route::post('channels/{channel}/videos/{video}/queue', [VideoController::class, 'queueVideo'])->name('channels.videos.queue');

    // Additional video routes
    Route::post('/videos/{video}/queue', [VideoController::class, 'queueVideo'])->name('videos.queue');
    Route::post('/videos/{video}/update-status', [VideoController::class, 'updateStatus'])->name('videos.updateStatus');
    Route::delete('/videos/{video}/soft-delete', [VideoController::class, 'softDelete'])->name('videos.softDelete');

    Route::delete('channels/{channel}/videos', [ChannelController::class, 'deleteVideos'])->name('channels.videos.delete');
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

// Webhook routes (outside middleware for external access)
Route::any('/webhook/oauth/{token}', [ChannelController::class, 'handleOAuthWebhook'])->name('webhook.oauth');

require __DIR__.'/auth.php';
