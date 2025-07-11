<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YoutubeVideoAnalytics extends Model
{
    protected $fillable = [
        'channel_id',
        'youtube_video_id',
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
        'audience_watch_ratio',
        'relative_retention_performance',
        'elapsed_video_time_ratio',
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
        'traffic_source_type',
        'traffic_source_detail',
        'device_type',
        'operating_system',
        'playback_location_type',
        'playback_location_detail',
        'age_group',
        'gender',
        'viewer_percentage',
        'country',
        'province',
        'city',
        'subscribed_status',
        'live_or_on_demand',
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
        'audience_watch_ratio' => 'decimal:4',
        'relative_retention_performance' => 'decimal:4',
        'elapsed_video_time_ratio' => 'decimal:4',
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
     * Get the channel that owns this video analytics
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Get daily analytics for a specific video
     */
    public static function getDailyAnalytics(int $channelId, string $videoId, string $startDate, string $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('channel_id', $channelId)
            ->where('youtube_video_id', $videoId)
            ->where('report_type', 'daily')
            ->whereBetween('report_date', [$startDate, $endDate])
            ->orderBy('report_date')
            ->get();
    }

    /**
     * Get traffic source breakdown for a video
     */
    public static function getTrafficSourceBreakdown(int $channelId, string $videoId, string $startDate, string $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('channel_id', $channelId)
            ->where('youtube_video_id', $videoId)
            ->where('dimension_type', 'insightTrafficSourceType')
            ->whereBetween('report_date', [$startDate, $endDate])
            ->selectRaw('dimension_value as traffic_source, SUM(views) as total_views, SUM(estimated_minutes_watched) as total_watch_time')
            ->groupBy('dimension_value')
            ->orderBy('total_views', 'desc')
            ->get();
    }

    /**
     * Get device breakdown for a video
     */
    public static function getDeviceBreakdown(int $channelId, string $videoId, string $startDate, string $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('channel_id', $channelId)
            ->where('youtube_video_id', $videoId)
            ->where('dimension_type', 'deviceType')
            ->whereBetween('report_date', [$startDate, $endDate])
            ->selectRaw('dimension_value as device_type, SUM(views) as total_views, SUM(estimated_minutes_watched) as total_watch_time')
            ->groupBy('dimension_value')
            ->orderBy('total_views', 'desc')
            ->get();
    }

    /**
     * Get geographic breakdown for a video
     */
    public static function getGeographicBreakdown(int $channelId, string $videoId, string $startDate, string $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('channel_id', $channelId)
            ->where('youtube_video_id', $videoId)
            ->where('dimension_type', 'country')
            ->whereBetween('report_date', [$startDate, $endDate])
            ->selectRaw('dimension_value as country, SUM(views) as total_views, SUM(estimated_minutes_watched) as total_watch_time')
            ->groupBy('dimension_value')
            ->orderBy('total_views', 'desc')
            ->get();
    }

    /**
     * Get audience retention data for a video
     */
    public static function getAudienceRetention(int $channelId, string $videoId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('channel_id', $channelId)
            ->where('youtube_video_id', $videoId)
            ->where('dimension_type', 'elapsedVideoTimeRatio')
            ->whereNotNull('audience_watch_ratio')
            ->orderBy('elapsed_video_time_ratio')
            ->get();
    }

    /**
     * Get demographic breakdown for a video
     */
    public static function getDemographicBreakdown(int $channelId, string $videoId, string $startDate, string $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('channel_id', $channelId)
            ->where('youtube_video_id', $videoId)
            ->where('dimension_type', 'ageGroup')
            ->whereBetween('report_date', [$startDate, $endDate])
            ->selectRaw('dimension_value as age_group, gender, SUM(viewer_percentage) as total_percentage')
            ->groupBy('dimension_value', 'gender')
            ->orderBy('total_percentage', 'desc')
            ->get();
    }

    /**
     * Get aggregated metrics for a video in a date range
     */
    public static function getAggregatedMetrics(int $channelId, string $videoId, string $startDate, string $endDate): array
    {
        $analytics = self::where('channel_id', $channelId)
            ->where('youtube_video_id', $videoId)
            ->where('report_type', 'daily')
            ->whereBetween('report_date', [$startDate, $endDate])
            ->get();

        return [
            'total_views' => $analytics->sum('views'),
            'total_watch_time' => $analytics->sum('estimated_minutes_watched'),
            'total_likes' => $analytics->sum('likes'),
            'total_comments' => $analytics->sum('comments'),
            'total_shares' => $analytics->sum('shares'),
            'subscribers_gained' => $analytics->sum('subscribers_gained'),
            'subscribers_lost' => $analytics->sum('subscribers_lost'),
            'net_subscribers' => $analytics->sum('subscribers_gained') - $analytics->sum('subscribers_lost'),
            'total_revenue' => $analytics->whereNotNull('estimated_revenue')->sum('estimated_revenue'),
            'average_view_duration' => $analytics->avg('average_view_duration'),
            'average_view_percentage' => $analytics->avg('average_view_percentage'),
            'engagement_rate' => $analytics->sum('views') > 0 ? 
                (($analytics->sum('likes') + $analytics->sum('comments')) / $analytics->sum('views')) * 100 : 0
        ];
    }

    /**
     * Get top performing videos by views in a date range
     */
    public static function getTopVideosByViews(int $channelId, string $startDate, string $endDate, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('channel_id', $channelId)
            ->where('report_type', 'daily')
            ->whereBetween('report_date', [$startDate, $endDate])
            ->selectRaw('youtube_video_id, SUM(views) as total_views, SUM(estimated_minutes_watched) as total_watch_time, SUM(likes) as total_likes, SUM(comments) as total_comments')
            ->groupBy('youtube_video_id')
            ->orderBy('total_views', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get subscriber conversion rate for a video
     */
    public function getSubscriberConversionRate(): float
    {
        if ($this->views == 0) return 0;
        return ($this->subscribers_gained / $this->views) * 100;
    }

    /**
     * Get engagement rate for this analytics record
     */
    public function getEngagementRate(): float
    {
        if ($this->views == 0) return 0;
        return (($this->likes + $this->comments) / $this->views) * 100;
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