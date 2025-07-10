<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyChannelStat extends Model
{
    protected $fillable = [
        'date',
        'total_views',
        'total_channels',
        'total_videos',
        'avg_views_per_video',
        'avg_views_per_channel'
    ];

    protected $casts = [
        'date' => 'date',
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
        return self::where('date', '>=', now()->subDays($days))
            ->orderBy('date', 'asc')
            ->get();
    }

    /**
     * Calculate and store daily stats
     */
    public static function calculateAndStore(): self
    {
        $today = now()->startOfDay();
        
        // Get all video stats from today
        $videoStats = YoutubeVideoStat::where('last_synced_at', '>=', $today)
            ->get();

        // Calculate stats
        $totalViews = $videoStats->sum('view_count');
        $totalVideos = $videoStats->count();
        $totalChannels = $videoStats->pluck('channel_id')->unique()->count();
        
        $avgViewsPerVideo = $totalVideos > 0 ? $totalViews / $totalVideos : 0;
        $avgViewsPerChannel = $totalChannels > 0 ? $totalViews / $totalChannels : 0;

        // Create or update today's record
        return self::updateOrCreate(
            ['date' => $today->toDateString()],
            [
                'total_views' => $totalViews,
                'total_channels' => $totalChannels,
                'total_videos' => $totalVideos,
                'avg_views_per_video' => $avgViewsPerVideo,
                'avg_views_per_channel' => $avgViewsPerChannel
            ]
        );
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