<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanOldStatsCommand extends Command
{
    protected $signature = 'stats:clean-old';
    protected $description = 'Clean stats older than 28 days from daily_channel_stats table';

    public function handle()
    {
        $cutoffDate = now()->subDays(28);

        $deletedRows = DB::table('daily_channel_stats')
            ->where('datetime', '<', $cutoffDate)
            ->delete();

        $this->info("Deleted {$deletedRows} old records from daily_channel_stats table.");
    }
} 