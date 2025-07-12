<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class DailyChannelStat extends Model
{
    protected $fillable = [
        'channel_id',
        'datetime',
        'total_views',
        'total_videos',
        'avg_views_per_video'
    ];

    protected $casts = [
        'datetime' => 'datetime',
        'total_views' => 'integer',
        'total_videos' => 'integer',
        'avg_views_per_video' => 'decimal:2'
    ];

    /**
     * Get the channel that owns this stat
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

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
     * Get stats for specific channels
     */
    public static function getChannelStats(array $channelIds = [])
    {
        $query = self::orderBy('datetime', 'asc');
        
        if (!empty($channelIds)) {
            $query->whereIn('channel_id', $channelIds);
        }
        
        return $query->get();
    }

    /**
     * Get aggregated stats (sum all channels for each datetime)
     */
    public static function getAggregatedStats(array $channelIds = [])
    {
        $query = self::select(
            'datetime',
            DB::raw('SUM(total_views) as total_views'),
            DB::raw('SUM(total_videos) as total_videos'),
            DB::raw('AVG(avg_views_per_video) as avg_views_per_video')
        )
        ->groupBy('datetime')
        ->orderBy('datetime', 'asc');
        
        if (!empty($channelIds)) {
            $query->whereIn('channel_id', $channelIds);
        }
        
        return $query->get();
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