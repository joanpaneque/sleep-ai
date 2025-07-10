<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule YouTube data synchronization
Schedule::command('youtube:sync-all')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/youtube-sync.log'));

// Schedule stats calculation every minute
Schedule::command('stats:calculate-daily')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/stats-calculation.log'));

// Alternative: Run every minute (more frequent)
// Schedule::command('youtube:sync-all')
//     ->everyMinute()
//     ->withoutOverlapping()
//     ->runInBackground()
//     ->appendOutputTo(storage_path('logs/youtube-sync.log'));

// Schedule for specific times (example: every hour)
// Schedule::command('youtube:sync-all --force')
//     ->hourly()
//     ->withoutOverlapping()
//     ->runInBackground()
//     ->appendOutputTo(storage_path('logs/youtube-sync-hourly.log'));
