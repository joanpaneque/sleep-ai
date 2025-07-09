<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YoutubeChannelStat extends Model
{
    protected $fillable = [
        'channel_id',
        'youtube_channel_id',
        'title',
        'description',
        'country',
        'published_at',
        'subscriber_count',
        'video_count',
        'view_count',
        'hidden_subscriber_count',
        'banner_image_url',
        'profile_image_url',
        'channel_keywords',
        'default_language',
        'avg_views_per_video',
        'engagement_rate',
        'growth_rate_30d',
        'videos_last_30d',
        'last_synced_at',
        'sync_successful',
        'sync_error'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'last_synced_at' => 'datetime',
        'hidden_subscriber_count' => 'boolean',
        'sync_successful' => 'boolean',
        'subscriber_count' => 'integer',
        'video_count' => 'integer',
        'view_count' => 'integer',
        'videos_last_30d' => 'integer',
        'avg_views_per_video' => 'decimal:2',
        'engagement_rate' => 'decimal:4',
        'growth_rate_30d' => 'decimal:4'
    ];

    /**
     * Get the channel that owns this stat record
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Get the latest stat record for a channel
     */
    public static function getLatestForChannel(int $channelId): ?self
    {
        return self::where('channel_id', $channelId)
            ->orderBy('last_synced_at', 'desc')
            ->first();
    }

    /**
     * Check if this stat record is recent (within last hour)
     */
    public function isRecent(): bool
    {
        return $this->last_synced_at && $this->last_synced_at->gt(now()->subHour());
    }

    /**
     * Get formatted subscriber count
     */
    public function getFormattedSubscriberCountAttribute(): string
    {
        return $this->formatNumber($this->subscriber_count);
    }

    /**
     * Get formatted view count
     */
    public function getFormattedViewCountAttribute(): string
    {
        return $this->formatNumber($this->view_count);
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
