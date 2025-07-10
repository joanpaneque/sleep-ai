<?php

namespace App\Console\Commands;

use App\Models\DailyChannelStat;
use Illuminate\Console\Command;

class CalculateDailyChannelStats extends Command
{
    protected $signature = 'stats:calculate-daily';
    protected $description = 'Calculate and store daily channel statistics';

    public function handle()
    {
        $this->info('Calculating daily channel statistics...');

        try {
            $stats = DailyChannelStat::calculateAndStore();
            
            $this->info("Statistics calculated successfully for {$stats->date}:");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Views', $stats->formatted_total_views],
                    ['Total Channels', $stats->total_channels],
                    ['Total Videos', $stats->total_videos],
                    ['Avg Views/Video', round($stats->avg_views_per_video, 2)],
                    ['Avg Views/Channel', round($stats->avg_views_per_channel, 2)],
                ]
            );
        } catch (\Exception $e) {
            $this->error("Error calculating statistics: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
} 