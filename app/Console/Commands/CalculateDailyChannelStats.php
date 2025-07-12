<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\DailyChannelStat;
use App\Models\YoutubeVideoStat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CalculateDailyChannelStats extends Command
{
    protected $signature = 'stats:calculate-daily';
    protected $description = 'Calculate and store daily channel statistics';

    public function handle()
    {
        $this->info('Calculating daily channel statistics...');
        Log::info('Calculating daily channel statistics...');

        try {
            $now = now();
            $channels = Channel::all();
            $totalStats = ['total_views' => 0, 'total_videos' => 0];

            foreach ($channels as $channel) {
                // Calcular estadísticas para este canal
                $channelStats = YoutubeVideoStat::where('channel_id', $channel->id)
                    ->select(
                        DB::raw('COUNT(*) as total_videos'),
                        DB::raw('SUM(view_count) as total_views')
                    )
                    ->first();

                // Guardar estadísticas del canal
                DailyChannelStat::updateOrCreate(
                    [
                        'channel_id' => $channel->id,
                        'datetime' => $now->format('Y-m-d H:i:00') // Redondear al minuto actual
                    ],
                    [
                        'total_views' => $channelStats->total_views ?? 0,
                        'total_videos' => $channelStats->total_videos ?? 0,
                        'avg_views_per_video' => $channelStats->total_videos > 0 
                            ? $channelStats->total_views / $channelStats->total_videos 
                            : 0
                    ]
                );

                // Acumular totales
                $totalStats['total_views'] += $channelStats->total_views ?? 0;
                $totalStats['total_videos'] += $channelStats->total_videos ?? 0;

                $this->info("Statistics calculated for channel {$channel->name}:");
                $this->table(
                    ['Metric', 'Value'],
                    [
                        ['Total Views', number_format($channelStats->total_views ?? 0)],
                        ['Total Videos', $channelStats->total_videos ?? 0],
                        ['Avg Views/Video', $channelStats->total_videos > 0 
                            ? round($channelStats->total_views / $channelStats->total_videos, 2) 
                            : 0],
                    ]
                );
            }

            $this->info("\nTotal Statistics:");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Views (All Channels)', number_format($totalStats['total_views'])],
                    ['Total Videos (All Channels)', $totalStats['total_videos']],
                    ['Total Channels', $channels->count()],
                ]
            );

        } catch (\Exception $e) {
            $this->error("Error calculating statistics: {$e->getMessage()}");
            Log::error("Error calculating statistics: {$e->getMessage()}");
            Log::error($e->getTraceAsString());
            return 1;
        }

        return 0;
    }
} 