<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Channel extends Model
{
    protected $fillable = [
        'name',
        'description',
        'intro',
        'background_video',
        'frame_image',
        'image_style_prompt',
        'thumbnail_template',
        'thumbnail_image_prompt',
        'google_oauth_webhook_token',
        'google_client_id',
        'google_client_secret',
        'google_access_token',
        'google_refresh_token',
        'google_access_token_expires_at'
    ];

    public function videos()
    {
        return $this->hasMany(Video::class);
    }

    /**
     * Get the latest YouTube channel statistics
     */
    public function latestYoutubeStats()
    {
        return $this->hasOne(YoutubeChannelStat::class)->latestOfMany('last_synced_at');
    }

    /**
     * Get all YouTube channel statistics (for historical data)
     */
    public function youtubeStats()
    {
        return $this->hasMany(YoutubeChannelStat::class);
    }

    /**
     * Get YouTube video statistics
     */
    public function youtubeVideoStats()
    {
        return $this->hasMany(YoutubeVideoStat::class);
    }

    /**
     * Get latest YouTube video statistics
     */
    public function latestYoutubeVideoStats()
    {
        return $this->hasMany(YoutubeVideoStat::class)
            ->whereIn('id', function($query) {
                $query->select(DB::raw('MAX(id)'))
                    ->from('youtube_video_stats')
                    ->where('channel_id', $this->id)
                    ->groupBy('youtube_video_id');
            });
    }

    /**
     * Check if channel has valid OAuth tokens
     */
    public function hasValidOAuthTokens(): bool
    {
        return !empty($this->google_access_token) &&
               !empty($this->google_refresh_token) &&
               ($this->google_access_token_expires_at === null || $this->google_access_token_expires_at > now());
    }

    /**
     * Check if OAuth tokens are expired
     */
    public function hasExpiredTokens(): bool
    {
        return !empty($this->google_access_token) &&
               !empty($this->google_refresh_token) &&
               $this->google_access_token_expires_at !== null &&
               $this->google_access_token_expires_at <= now();
    }
}
