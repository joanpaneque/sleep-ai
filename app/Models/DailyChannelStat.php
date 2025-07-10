<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class DailyChannelStat extends Model
{
    protected $fillable = [
        'datetime',
        'total_views',
        'total_channels',
        'total_videos',
        'avg_views_per_video',
        'avg_views_per_channel'
    ];

    protected $casts = [
        'datetime' => 'datetime',
        'total_views' => 'integer',
        'total_channels' => 'integer',
        'total_videos' => 'integer',
        'avg_views_per_video' => 'decimal:2',
        'avg_views_per_channel' => 'decimal:2'
    ];

    /**
     * Get stats for the last N days
     */
    public static function getLastDays(int $days = 28)
    {
        return self::where('datetime', '>=', now()->subDays($days))
            ->orderBy('datetime', 'asc')
            ->get();
    }

    /**
     * Get stats for specific timeframe with minute-by-minute data
     */
    public static function getTimeframeStats(int $hours = 24)
    {
        // Obtener todos los datos disponibles
        return self::orderBy('datetime', 'asc')->get();
    }

    /**
     * Calculate and store stats for the current minute
     */
    public static function calculateAndStore(): self
    {
        $currentTime = now();
        
        // Obtener las últimas estadísticas de cada video (usando subquery para obtener el último registro de cada video)
        $latestVideoStats = YoutubeVideoStat::whereIn('id', function($query) {
            $query->select(DB::raw('MAX(id)'))
                ->from('youtube_video_stats')
                ->groupBy('youtube_video_id');
        })->get();

        // Calcular estadísticas
        $totalViews = $latestVideoStats->sum('view_count');
        $totalVideos = $latestVideoStats->count();
        $totalChannels = $latestVideoStats->pluck('channel_id')->unique()->count();
        
        $avgViewsPerVideo = $totalVideos > 0 ? $totalViews / $totalVideos : 0;
        $avgViewsPerChannel = $totalChannels > 0 ? $totalViews / $totalChannels : 0;

        // Crear nuevo registro para este minuto
        $stats = self::create([
            'datetime' => $currentTime,
            'total_views' => $totalViews,
            'total_channels' => $totalChannels,
            'total_videos' => $totalVideos,
            'avg_views_per_video' => $avgViewsPerVideo,
            'avg_views_per_channel' => $avgViewsPerChannel
        ]);

        // Log para debugging
        \Log::info('Stats calculated', [
            'datetime' => $currentTime->format('Y-m-d H:i:s'),
            'total_views' => $totalViews,
            'total_videos' => $totalVideos,
            'total_channels' => $totalChannels,
            'videos' => $latestVideoStats->map(function($stat) {
                return [
                    'video_id' => $stat->youtube_video_id,
                    'views' => $stat->view_count,
                    'last_synced' => $stat->last_synced_at
                ];
            })
        ]);

        return $stats;
    }

    /**
     * Get formatted total views
     */
    public function getFormattedTotalViewsAttribute(): string
    {
        return $this->formatNumber($this->total_views);
    }

    /**
     * Format large numbers (1K, 1M, etc.)
     */
    private function formatNumber(int $number): string
    {
        if ($number >= 1000000000) {
            return round($number / 1000000000, 1) . 'B';
        } elseif ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        }

        return (string) $number;
    }
} 