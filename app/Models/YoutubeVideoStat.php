<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YoutubeVideoStat extends Model
{
    protected $fillable = [
        'channel_id',
        'youtube_video_id',
        'youtube_channel_id',
        'title',
        'description',
        'published_at',
        'duration',
        'duration_seconds',
        'category_id',
        'tags',
        'thumbnail_default',
        'thumbnail_medium',
        'thumbnail_high',
        'thumbnail_standard',
        'thumbnail_maxres',
        'view_count',
        'like_count',
        'comment_count',
        'favorite_count',
        'engagement_rate',
        'like_rate',
        'comment_rate',
        'views_per_day',
        'performance_score',
        'privacy_status',
        'upload_status',
        'embeddable',
        'made_for_kids',
        'last_synced_at',
        'sync_successful',
        'sync_error'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'tags' => 'array',
        'embeddable' => 'boolean',
        'made_for_kids' => 'boolean',
        'sync_successful' => 'boolean',
        'view_count' => 'integer',
        'like_count' => 'integer',
        'comment_count' => 'integer',
        'favorite_count' => 'integer',
        'duration_seconds' => 'integer',
        'performance_score' => 'integer',
        'engagement_rate' => 'decimal:4',
        'like_rate' => 'decimal:4',
        'comment_rate' => 'decimal:4',
        'views_per_day' => 'decimal:2'
    ];

    /**
     * Get the channel that owns this video stat
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Get the latest stat record for a video
     */
    public static function getLatestForVideo(int $channelId, string $youtubeVideoId): ?self
    {
        return self::where('channel_id', $channelId)
            ->where('youtube_video_id', $youtubeVideoId)
            ->orderBy('last_synced_at', 'desc')
            ->first();
    }

    /**
     * Get top performing videos for a channel
     */
    public static function getTopPerforming(int $channelId, string $metric = 'view_count', int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('channel_id', $channelId)
            ->orderBy($metric, 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recent videos (last 30 days)
     */
    public static function getRecent(int $channelId, int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('channel_id', $channelId)
            ->where('published_at', '>=', now()->subDays($days))
            ->orderBy('published_at', 'desc')
            ->get();
    }

    /**
     * Check if this stat record is recent (within last hour)
     */
    public function isRecent(): bool
    {
        return $this->last_synced_at && $this->last_synced_at->gt(now()->subHour());
    }

    /**
     * Get formatted view count
     */
    public function getFormattedViewCountAttribute(): string
    {
        return $this->formatNumber($this->view_count);
    }

    /**
     * Get formatted like count
     */
    public function getFormattedLikeCountAttribute(): string
    {
        return $this->formatNumber($this->like_count);
    }

    /**
     * Get formatted comment count
     */
    public function getFormattedCommentCountAttribute(): string
    {
        return $this->formatNumber($this->comment_count);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        $seconds = $this->duration_seconds;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        } else {
            return sprintf('%d:%02d', $minutes, $secs);
        }
    }

    /**
     * Get performance level based on score
     */
    public function getPerformanceLevelAttribute(): string
    {
        if ($this->performance_score >= 80) return 'Excelente';
        if ($this->performance_score >= 60) return 'Bueno';
        if ($this->performance_score >= 40) return 'Regular';
        if ($this->performance_score >= 20) return 'Bajo';
        return 'Muy Bajo';
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
