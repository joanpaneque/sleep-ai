<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YoutubeAnalyticsReport extends Model
{
    protected $fillable = [
        'channel_id',
        'youtube_channel_id',
        'report_date',
        'report_type',
        'dimension_type',
        'dimension_value',
        'views',
        'estimated_minutes_watched',
        'average_view_duration',
        'average_view_percentage',
        'likes',
        'dislikes',
        'comments',
        'shares',
        'subscribers_gained',
        'subscribers_lost',
        'videos_added_to_playlists',
        'videos_removed_from_playlists',
        'playlist_views',
        'playlist_starts',
        'playlist_saves',
        'average_time_in_playlist',
        'estimated_revenue',
        'estimated_ad_revenue',
        'gross_revenue',
        'cpm',
        'monetized_playbacks',
        'ad_impressions',
        'red_views',
        'estimated_red_minutes_watched',
        'estimated_red_partner_revenue',
        'card_impressions',
        'card_clicks',
        'card_click_rate',
        'annotation_impressions',
        'annotation_clicks',
        'annotation_click_through_rate',
        'average_concurrent_viewers',
        'peak_concurrent_viewers',
        'viewer_percentage',
        'last_synced_at',
        'sync_successful',
        'sync_error',
        'record_hash'
    ];

    protected $casts = [
        'report_date' => 'date',
        'last_synced_at' => 'datetime',
        'sync_successful' => 'boolean',
        'views' => 'integer',
        'estimated_minutes_watched' => 'integer',
        'likes' => 'integer',
        'dislikes' => 'integer',
        'comments' => 'integer',
        'shares' => 'integer',
        'subscribers_gained' => 'integer',
        'subscribers_lost' => 'integer',
        'videos_added_to_playlists' => 'integer',
        'videos_removed_from_playlists' => 'integer',
        'playlist_views' => 'integer',
        'playlist_starts' => 'integer',
        'playlist_saves' => 'integer',
        'monetized_playbacks' => 'integer',
        'ad_impressions' => 'integer',
        'red_views' => 'integer',
        'estimated_red_minutes_watched' => 'integer',
        'card_impressions' => 'integer',
        'card_clicks' => 'integer',
        'annotation_impressions' => 'integer',
        'annotation_clicks' => 'integer',
        'peak_concurrent_viewers' => 'integer',
        'average_view_duration' => 'decimal:2',
        'average_view_percentage' => 'decimal:4',
        'average_time_in_playlist' => 'decimal:2',
        'estimated_revenue' => 'decimal:2',
        'estimated_ad_revenue' => 'decimal:2',
        'gross_revenue' => 'decimal:2',
        'cpm' => 'decimal:2',
        'estimated_red_partner_revenue' => 'decimal:2',
        'card_click_rate' => 'decimal:4',
        'annotation_click_through_rate' => 'decimal:4',
        'average_concurrent_viewers' => 'decimal:2',
        'viewer_percentage' => 'decimal:4'
    ];

    /**
     * Get the channel that owns this analytics report
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Get daily reports for a channel within a date range
     */
    public static function getDailyReports(int $channelId, string $startDate, string $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('channel_id', $channelId)
            ->where('report_type', 'daily')
            ->whereBetween('report_date', [$startDate, $endDate])
            ->orderBy('report_date')
            ->get();
    }

    /**
     * Get reports by specific dimension (country, device, etc.)
     */
    public static function getReportsByDimension(int $channelId, string $dimensionType, string $startDate, string $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('channel_id', $channelId)
            ->where('dimension_type', $dimensionType)
            ->whereBetween('report_date', [$startDate, $endDate])
            ->orderBy('views', 'desc')
            ->get();
    }

    /**
     * Get aggregated metrics for a date range
     */
    public static function getAggregatedMetrics(int $channelId, string $startDate, string $endDate): array
    {
        $reports = self::where('channel_id', $channelId)
            ->where('report_type', 'daily')
            ->whereBetween('report_date', [$startDate, $endDate])
            ->get();

        return [
            'total_views' => $reports->sum('views'),
            'total_watch_time' => $reports->sum('estimated_minutes_watched'),
            'total_likes' => $reports->sum('likes'),
            'total_comments' => $reports->sum('comments'),
            'total_shares' => $reports->sum('shares'),
            'subscribers_gained' => $reports->sum('subscribers_gained'),
            'subscribers_lost' => $reports->sum('subscribers_lost'),
            'net_subscribers' => $reports->sum('subscribers_gained') - $reports->sum('subscribers_lost'),
            'total_revenue' => $reports->whereNotNull('estimated_revenue')->sum('estimated_revenue'),
            'average_view_duration' => $reports->avg('average_view_duration'),
            'engagement_rate' => $reports->sum('views') > 0 ? 
                (($reports->sum('likes') + $reports->sum('comments')) / $reports->sum('views')) * 100 : 0
        ];
    }

    /**
     * Get top countries by views
     */
    public static function getTopCountries(int $channelId, string $startDate, string $endDate, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('channel_id', $channelId)
            ->where('dimension_type', 'country')
            ->whereBetween('report_date', [$startDate, $endDate])
            ->selectRaw('dimension_value as country, SUM(views) as total_views, SUM(estimated_minutes_watched) as total_watch_time')
            ->groupBy('dimension_value')
            ->orderBy('total_views', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get device breakdown
     */
    public static function getDeviceBreakdown(int $channelId, string $startDate, string $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('channel_id', $channelId)
            ->where('dimension_type', 'deviceType')
            ->whereBetween('report_date', [$startDate, $endDate])
            ->selectRaw('dimension_value as device_type, SUM(views) as total_views, SUM(estimated_minutes_watched) as total_watch_time')
            ->groupBy('dimension_value')
            ->orderBy('total_views', 'desc')
            ->get();
    }

    /**
     * Format large numbers (1K, 1M, etc.)
     */
    public function getFormattedViewsAttribute(): string
    {
        return $this->formatNumber($this->views);
    }

    /**
     * Get formatted watch time in hours
     */
    public function getFormattedWatchTimeAttribute(): string
    {
        $hours = round($this->estimated_minutes_watched / 60, 1);
        return $hours . 'h';
    }

    /**
     * Format large numbers helper
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