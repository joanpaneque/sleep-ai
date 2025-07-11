<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\YoutubeChannelStat;
use App\Models\YoutubeVideoStat;
use App\Models\YoutubeAnalyticsReport;
use App\Models\YoutubeVideoAnalytics;
use App\Services\YoutubeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAllYoutubeData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'youtube:sync-all {--force : Force sync even if recently synced} {--channel= : Sync specific channel ID only}';

    /**
     * The console command description.
     */
    protected $description = 'Sync all YouTube data for all channels with valid OAuth tokens';

    private YoutubeService $youtubeService;

    public function __construct()
    {
        parent::__construct();
        $this->youtubeService = new YoutubeService();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando sincronización de datos de YouTube...');

        $startTime = now();
        $channelId = $this->option('channel');
        $force = $this->option('force');

        // Get channels to sync
        $query = Channel::whereNotNull('google_access_token')
            ->whereNotNull('google_refresh_token');

        if ($channelId) {
            $query->where('id', $channelId);
        }

        $channels = $query->get();

        if ($channels->isEmpty()) {
            $this->warn('❌ No se encontraron canales con tokens OAuth válidos');
            return 1;
        }

        $this->info("📊 Sincronizando {$channels->count()} canal(es)...");

        $stats = [
            'channels_processed' => 0,
            'channels_success' => 0,
            'channels_failed' => 0,
            'videos_processed' => 0,
            'videos_success' => 0,
            'videos_failed' => 0
        ];

        foreach ($channels as $channel) {
            $this->info("🔄 Procesando canal: {$channel->name} (ID: {$channel->id})");

            $stats['channels_processed']++;

            try {
                // Check if we should skip this channel (recently synced)
                if (!$force && $this->isRecentlySynced($channel)) {
                    $this->info("⏭️  Canal saltado - sincronizado recientemente");
                    continue;
                }

                // Sync channel data
                $channelSynced = $this->syncChannelData($channel);

                if ($channelSynced) {
                    $stats['channels_success']++;

                    // Sync videos data
                    $videoStats = $this->syncChannelVideos($channel);
                    $stats['videos_processed'] += $videoStats['processed'];
                    $stats['videos_success'] += $videoStats['success'];
                    $stats['videos_failed'] += $videoStats['failed'];

                    // Sync YouTube Analytics data
                    $this->info("📊 Sincronizando datos de YouTube Analytics...");
                    $this->syncYoutubeAnalytics($channel);

                    $this->info("✅ Canal sincronizado exitosamente");
                } else {
                    $stats['channels_failed']++;
                    $this->error("❌ Error sincronizando canal");
                }

            } catch (\Exception $e) {
                $stats['channels_failed']++;
                $this->error("❌ Error procesando canal {$channel->name}: {$e->getMessage()}");

                Log::error('Error syncing YouTube channel', [
                    'channel_id' => $channel->id,
                    'channel_name' => $channel->name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $duration = $startTime->diffInSeconds(now());

        $this->info('');
        $this->info('📈 Resumen de sincronización:');
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Canales procesados', $stats['channels_processed']],
                ['Canales exitosos', $stats['channels_success']],
                ['Canales fallidos', $stats['channels_failed']],
                ['Videos procesados', $stats['videos_processed']],
                ['Videos exitosos', $stats['videos_success']],
                ['Videos fallidos', $stats['videos_failed']],
                ['Duración', "{$duration} segundos"]
            ]
        );

        Log::info('YouTube sync completed', $stats + ['duration_seconds' => $duration]);

        return 0;
    }

    /**
     * Clean malformed UTF-8 characters from text
     */
    private function cleanUtf8Text(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        // Remove malformed UTF-8 characters
        $cleaned = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        
        // Remove null bytes and other problematic characters
        $cleaned = str_replace(["\0", "\x00"], '', $cleaned);
        
        // Ensure proper UTF-8 encoding
        if (!mb_check_encoding($cleaned, 'UTF-8')) {
            // If still not valid UTF-8, remove non-UTF-8 characters
            $cleaned = mb_convert_encoding($cleaned, 'UTF-8', 'auto');
        }
        
        return $cleaned;
    }

    /**
     * Check if channel was recently synced
     */
    private function isRecentlySynced(Channel $channel): bool
    {
        // $latestStat = YoutubeChannelStat::getLatestForChannel($channel->id);
        // return $latestStat && $latestStat->isRecent();
        return false;
    }

    /**
     * Sync channel data from YouTube API
     */
    private function syncChannelData(Channel $channel): bool
    {
        try {
            // Get channel info from YouTube
            $channelInfo = $this->youtubeService->getChannelInfo($channel);

            if (!$channelInfo) {
                $this->error("  ❌ No se pudo obtener información del canal");
                return false;
            }

            // Get channel statistics
            $statistics = $this->youtubeService->getChannelStatistics($channel);

            if (!$statistics) {
                $this->error("  ❌ No se pudieron obtener estadísticas del canal");
                return false;
            }

            // Get branding info
            $branding = $this->youtubeService->getChannelBranding($channel);

            // Calculate additional metrics
            $videoCount = (int) ($statistics['videoCount'] ?? 0);
            $viewCount = (int) ($statistics['viewCount'] ?? 0);
            $avgViewsPerVideo = $videoCount > 0 ? round($viewCount / $videoCount, 2) : 0;

            // Count videos from last 30 days
            $recentVideos = $this->youtubeService->getChannelVideos($channel, 50);
            $videosLast30d = 0;

            if ($recentVideos && isset($recentVideos['items'])) {
                $cutoff = now()->subDays(30);
                foreach ($recentVideos['items'] as $video) {
                    $publishedAt = $video['snippet']['publishedAt'] ?? null;
                    if ($publishedAt && $publishedAt >= $cutoff->toISOString()) {
                        $videosLast30d++;
                    }
                }
            }

            // Save channel statistics
            YoutubeChannelStat::create([
                'channel_id' => $channel->id,
                'youtube_channel_id' => $channelInfo['id'] ?? null,
                'title' => $this->cleanUtf8Text($channelInfo['snippet']['title'] ?? null),
                'description' => $this->cleanUtf8Text($channelInfo['snippet']['description'] ?? null),
                'country' => $channelInfo['snippet']['country'] ?? null,
                'published_at' => isset($channelInfo['snippet']['publishedAt']) ?
                    new \DateTime($channelInfo['snippet']['publishedAt']) : null,
                'subscriber_count' => (int) ($statistics['subscriberCount'] ?? 0),
                'video_count' => $videoCount,
                'view_count' => $viewCount,
                'hidden_subscriber_count' => (bool) ($statistics['hiddenSubscriberCount'] ?? false),
                'banner_image_url' => $branding['image']['bannerExternalUrl'] ?? null,
                'profile_image_url' => $channelInfo['snippet']['thumbnails']['high']['url'] ?? null,
                'channel_keywords' => $this->cleanUtf8Text($branding['channel']['keywords'] ?? null),
                'default_language' => $branding['channel']['defaultLanguage'] ?? null,
                'avg_views_per_video' => $avgViewsPerVideo,
                'videos_last_30d' => $videosLast30d,
                'last_synced_at' => now(),
                'sync_successful' => true
            ]);

            $this->info("  ✅ Datos del canal guardados");
            return true;

        } catch (\Exception $e) {
            // Save failed sync record
            YoutubeChannelStat::create([
                'channel_id' => $channel->id,
                'last_synced_at' => now(),
                'sync_successful' => false,
                'sync_error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Sync videos data for a channel
     */
    private function syncChannelVideos(Channel $channel): array
    {
        $stats = ['processed' => 0, 'success' => 0, 'failed' => 0];

        try {
            // Get channel videos
            $videosResponse = $this->youtubeService->getChannelVideos($channel, 50);

            if (!$videosResponse || !isset($videosResponse['items'])) {
                $this->warn("  ⚠️  No se pudieron obtener videos del canal");
                return $stats;
            }

            $videoIds = [];
            foreach ($videosResponse['items'] as $video) {
                $videoId = $video['snippet']['resourceId']['videoId'] ??
                          $video['contentDetails']['videoId'] ?? null;
                if ($videoId) {
                    $videoIds[] = $videoId;
                }
            }

            if (empty($videoIds)) {
                $this->info("  ℹ️  No se encontraron videos para procesar");
                return $stats;
            }

            // Get detailed video information
            $videosWithStats = $this->youtubeService->getMultipleVideosStatistics($channel, $videoIds);

            if (!$videosWithStats) {
                $this->warn("  ⚠️  No se pudieron obtener estadísticas de videos");
                return $stats;
            }

            $this->info("  🎥 Procesando {" . count($videosWithStats) . "} videos...");

            foreach ($videosWithStats as $videoData) {
                $stats['processed']++;

                try {
                    $this->syncVideoData($channel, $videoData);
                    $stats['success']++;
                } catch (\Exception $e) {
                    $stats['failed']++;
                    $this->error("    ❌ Error procesando video {$videoData['id']}: {$e->getMessage()}");
                }
            }

            $this->info("  ✅ Videos procesados: {$stats['success']}/{$stats['processed']}");

        } catch (\Exception $e) {
            $this->error("  ❌ Error obteniendo videos: {$e->getMessage()}");
        }

        return $stats;
    }

    /**
     * Sync individual video data
     */
    private function syncVideoData(Channel $channel, array $videoData): void
    {
        $snippet = $videoData['snippet'] ?? [];
        $statistics = $videoData['statistics'] ?? [];
        $contentDetails = $videoData['contentDetails'] ?? [];
        $status = $videoData['status'] ?? [];

        // Check if video was published after January 1, 2025
        $publishedAt = $snippet['publishedAt'] ?? null;
        if ($publishedAt) {
            $publishedDate = new \DateTime($publishedAt);
            $cutoffDate = new \DateTime('2025-01-01');
            
            if ($publishedDate < $cutoffDate) {
                // Skip videos published before January 1, 2025
                return;
            }
        } else {
            // Skip videos without publication date
            return;
        }

        // Calculate metrics
        $viewCount = (int) ($statistics['viewCount'] ?? 0);
        $likeCount = (int) ($statistics['likeCount'] ?? 0);
        $commentCount = (int) ($statistics['commentCount'] ?? 0);

        $engagementRate = $viewCount > 0 ? (($likeCount + $commentCount) / $viewCount) * 100 : 0;
        $likeRate = $viewCount > 0 ? ($likeCount / $viewCount) * 100 : 0;
        $commentRate = $viewCount > 0 ? ($commentCount / $viewCount) * 100 : 0;

        // Parse duration
        $duration = $contentDetails['duration'] ?? 'PT0S';
        $durationSeconds = $this->parseDuration($duration);

        // Calculate views per day
        $viewsPerDay = $this->calculateViewsPerDay($publishedAt, $viewCount);

        // Calculate performance score
        $performanceScore = $this->calculatePerformanceScore($viewCount, $likeCount, $commentCount);

        // Get thumbnails
        $thumbnails = $snippet['thumbnails'] ?? [];

        // Clean tags array
        $cleanTags = [];
        if (isset($snippet['tags']) && is_array($snippet['tags'])) {
            foreach ($snippet['tags'] as $tag) {
                $cleanTag = $this->cleanUtf8Text($tag);
                if ($cleanTag !== null) {
                    $cleanTags[] = $cleanTag;
                }
            }
        }

        // Update or create video stat record
        YoutubeVideoStat::updateOrCreate(
            [
                'channel_id' => $channel->id,
                'youtube_video_id' => $videoData['id']
            ],
            [
                'youtube_channel_id' => $snippet['channelId'] ?? null,
                'title' => $this->cleanUtf8Text($snippet['title'] ?? null),
                'description' => $this->cleanUtf8Text($snippet['description'] ?? null),
                'published_at' => $publishedDate,
                'duration' => $duration,
                'duration_seconds' => $durationSeconds,
                'category_id' => $snippet['categoryId'] ?? null,
                'tags' => $cleanTags,
                'thumbnail_default' => $thumbnails['default']['url'] ?? null,
                'thumbnail_medium' => $thumbnails['medium']['url'] ?? null,
                'thumbnail_high' => $thumbnails['high']['url'] ?? null,
                'thumbnail_standard' => $thumbnails['standard']['url'] ?? null,
                'thumbnail_maxres' => $thumbnails['maxres']['url'] ?? null,
                'view_count' => $viewCount,
                'like_count' => $likeCount,
                'comment_count' => $commentCount,
                'favorite_count' => (int) ($statistics['favoriteCount'] ?? 0),
                'engagement_rate' => round($engagementRate, 4),
                'like_rate' => round($likeRate, 4),
                'comment_rate' => round($commentRate, 4),
                'views_per_day' => $viewsPerDay,
                'performance_score' => $performanceScore,
                'privacy_status' => $status['privacyStatus'] ?? null,
                'upload_status' => $status['uploadStatus'] ?? null,
                'embeddable' => (bool) ($status['embeddable'] ?? true),
                'made_for_kids' => (bool) ($status['madeForKids'] ?? false),
                'last_synced_at' => now(),
                'sync_successful' => true
            ]
        );
    }

    /**
     * Parse ISO 8601 duration to seconds
     */
    private function parseDuration(string $duration): int
    {
        try {
            $interval = new \DateInterval($duration);
            return ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate views per day since publication
     */
    private function calculateViewsPerDay(?string $publishedAt, int $viewCount): float
    {
        if (!$publishedAt || $viewCount === 0) {
            return 0;
        }

        try {
            $publishDate = new \DateTime($publishedAt);
            $now = new \DateTime();
            $daysDiff = $now->diff($publishDate)->days;

            return $daysDiff > 0 ? round($viewCount / $daysDiff, 2) : $viewCount;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate performance score (0-100)
     */
    private function calculatePerformanceScore(int $views, int $likes, int $comments): int
    {
        if ($views === 0) return 0;

        $engagementRate = (($likes + $comments) / $views) * 100;

        // Score based on engagement rate
        if ($engagementRate >= 10) return 100;
        if ($engagementRate >= 5) return 80;
        if ($engagementRate >= 2) return 60;
        if ($engagementRate >= 1) return 40;
        if ($engagementRate >= 0.5) return 20;

        return 10;
    }

    /**
     * Sync YouTube Analytics data for a channel
     */
    private function syncYoutubeAnalytics(Channel $channel): void
    {
        try {
            // Define date range (last 30 days)
            $endDate = now()->format('Y-m-d');
            $startDate = now()->subDays(30)->format('Y-m-d');

            $this->info("  📈 Sincronizando analytics del {$startDate} al {$endDate}");

            // 1. Sync daily channel analytics
            $this->syncDailyChannelAnalytics($channel, $startDate, $endDate);

            // 2. Sync geographic analytics
            $this->syncGeographicAnalytics($channel, $startDate, $endDate);

            // 3. Sync device analytics
            $this->syncDeviceAnalytics($channel, $startDate, $endDate);

            // 4. Sync traffic source analytics
            $this->syncTrafficSourceAnalytics($channel, $startDate, $endDate);

            // 5. Sync demographic analytics
            $this->syncDemographicAnalytics($channel, $startDate, $endDate);

            // 6. Sync video-specific analytics for top videos
            $this->syncTopVideosAnalytics($channel, $startDate, $endDate);

            // 7. Sync revenue analytics (if monetized)
            $this->syncRevenueAnalytics($channel, $startDate, $endDate);

            $this->info("  ✅ Analytics sincronizados correctamente");

        } catch (\Exception $e) {
            $this->error("  ❌ Error sincronizando analytics: {$e->getMessage()}");
            Log::error('Error syncing YouTube Analytics', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Sync daily channel analytics
     */
    private function syncDailyChannelAnalytics(Channel $channel, string $startDate, string $endDate): void
    {
        $analytics = $this->youtubeService->getComprehensiveChannelAnalytics(
            $channel,
            $startDate,
            $endDate,
            ['day'],
            ['views', 'estimatedMinutesWatched', 'averageViewDuration', 'likes', 'comments', 'shares', 'subscribersGained', 'subscribersLost', 'estimatedRevenue', 'estimatedAdRevenue', 'grossRevenue', 'cpm', 'monetizedPlaybacks', 'adImpressions']
        );

        if (!$analytics || !isset($analytics['rows'])) {
            $this->warn("    ⚠️  No se encontraron datos de analytics diarios");
            return;
        }

        $this->processChannelAnalyticsRows($channel, $analytics, 'daily', 'day');
        $this->info("    ✅ Analytics diarios guardados: " . count($analytics['rows']) . " registros");
    }

    /**
     * Sync geographic analytics
     */
    private function syncGeographicAnalytics(Channel $channel, string $startDate, string $endDate): void
    {
        $analytics = $this->youtubeService->getChannelAnalyticsByCountry(
            $channel,
            $startDate,
            $endDate,
            ['views', 'estimatedMinutesWatched', 'subscribersGained']
        );

        if (!$analytics || !isset($analytics['rows'])) {
            $this->warn("    ⚠️  No se encontraron datos geográficos");
            return;
        }

        $this->processChannelAnalyticsRows($channel, $analytics, 'geographic', 'country', $endDate);
        $this->info("    ✅ Analytics geográficos guardados: " . count($analytics['rows']) . " países");
    }

    /**
     * Sync device analytics
     */
    private function syncDeviceAnalytics(Channel $channel, string $startDate, string $endDate): void
    {
        $analytics = $this->youtubeService->getChannelAnalyticsByDevice($channel, $startDate, $endDate);

        if (!$analytics || !isset($analytics['rows'])) {
            $this->warn("    ⚠️  No se encontraron datos de dispositivos");
            return;
        }

        $this->processChannelAnalyticsRows($channel, $analytics, 'device', 'deviceType', $endDate);
        $this->info("    ✅ Analytics de dispositivos guardados: " . count($analytics['rows']) . " tipos");
    }

    /**
     * Sync traffic source analytics
     */
    private function syncTrafficSourceAnalytics(Channel $channel, string $startDate, string $endDate): void
    {
        $analytics = $this->youtubeService->getChannelAnalyticsByTrafficSource($channel, $startDate, $endDate);

        if (!$analytics || !isset($analytics['rows'])) {
            $this->warn("    ⚠️  No se encontraron datos de fuentes de tráfico");
            return;
        }

        $this->processChannelAnalyticsRows($channel, $analytics, 'traffic_source', 'insightTrafficSourceType', $endDate);
        $this->info("    ✅ Analytics de fuentes de tráfico guardados: " . count($analytics['rows']) . " fuentes");
    }

    /**
     * Sync demographic analytics
     */
    private function syncDemographicAnalytics(Channel $channel, string $startDate, string $endDate): void
    {
        $analytics = $this->youtubeService->getViewerDemographics($channel, $startDate, $endDate);

        if (!$analytics || !isset($analytics['rows'])) {
            $this->warn("    ⚠️  No se encontraron datos demográficos");
            return;
        }

        $this->processChannelAnalyticsRows($channel, $analytics, 'demographics', 'ageGroup', $endDate);
        $this->info("    ✅ Analytics demográficos guardados: " . count($analytics['rows']) . " segmentos");
    }

    /**
     * Sync analytics for top performing videos
     */
    private function syncTopVideosAnalytics(Channel $channel, string $startDate, string $endDate): void
    {
        // Get top 10 videos by views
        $topVideos = $this->youtubeService->getTopVideosByViews($channel, $startDate, $endDate, 10);

        if (!$topVideos || !isset($topVideos['rows'])) {
            $this->warn("    ⚠️  No se encontraron videos top para analytics");
            return;
        }

        $videoCount = 0;
        foreach ($topVideos['rows'] as $row) {
            $videoId = $row[0] ?? null; // First column should be video ID
            if (!$videoId) continue;

            try {
                // Sync comprehensive video analytics
                $this->syncVideoAnalytics($channel, $videoId, $startDate, $endDate);
                $videoCount++;
            } catch (\Exception $e) {
                $this->error("      ❌ Error sincronizando video {$videoId}: {$e->getMessage()}");
            }
        }

        $this->info("    ✅ Analytics de videos sincronizados: {$videoCount} videos");
    }

    /**
     * Sync analytics for a specific video
     */
    private function syncVideoAnalytics(Channel $channel, string $videoId, string $startDate, string $endDate): void
    {
        // Get comprehensive video analytics
        $analytics = $this->youtubeService->getComprehensiveVideoAnalytics($channel, $videoId, $startDate, $endDate);

        if (!$analytics || !isset($analytics['rows'])) {
            return;
        }

        $this->processVideoAnalyticsRows($channel, $videoId, $analytics, 'daily', 'day');

        // Get traffic source breakdown for this video
        $trafficAnalytics = $this->youtubeService->getDetailedTrafficSourceAnalytics($channel, $startDate, $endDate);
        if ($trafficAnalytics && isset($trafficAnalytics['rows'])) {
            $this->processVideoAnalyticsRows($channel, $videoId, $trafficAnalytics, 'traffic_source', 'insightTrafficSourceType', $endDate);
        }
    }

    /**
     * Process and save channel analytics rows
     */
    private function processChannelAnalyticsRows(Channel $channel, array $analytics, string $reportType, string $dimensionType, ?string $fixedDate = null): void
    {
        $headers = $analytics['columnHeaders'] ?? [];
        $rows = $analytics['rows'] ?? [];

        // Map column headers to indices
        $columnMap = [];
        foreach ($headers as $index => $header) {
            $columnMap[$header['name']] = $index;
        }

        foreach ($rows as $row) {
            try {
                $reportDate = $fixedDate ?? ($row[$columnMap['day'] ?? 0] ?? now()->format('Y-m-d'));
                $dimensionValue = $dimensionType === 'day' ? null : ($row[$columnMap[$dimensionType] ?? 0] ?? null);

                // Generate unique hash for this record
                $hashData = $channel->id . '|' . $reportDate . '|' . $reportType . '|' . $dimensionType . '|' . ($dimensionValue ?? '');
                $recordHash = hash('sha256', $hashData);

                // Prepare data for insertion
                $data = [
                    'channel_id' => $channel->id,
                    'youtube_channel_id' => $channel->latestYoutubeStats->youtube_channel_id ?? null,
                    'report_date' => $reportDate,
                    'report_type' => $reportType,
                    'dimension_type' => $dimensionType,
                    'dimension_value' => $dimensionValue,
                    'record_hash' => $recordHash,
                    'last_synced_at' => now(),
                    'sync_successful' => true
                ];

                // Map metrics from the row
                $this->mapMetricsToData($data, $row, $columnMap, [
                    'views' => 'views',
                    'estimatedMinutesWatched' => 'estimated_minutes_watched',
                    'averageViewDuration' => 'average_view_duration',
                    'averageViewPercentage' => 'average_view_percentage',
                    'likes' => 'likes',
                    'dislikes' => 'dislikes',
                    'comments' => 'comments',
                    'shares' => 'shares',
                    'subscribersGained' => 'subscribers_gained',
                    'subscribersLost' => 'subscribers_lost',
                    'viewerPercentage' => 'viewer_percentage',
                    'estimatedRevenue' => 'estimated_revenue',
                    'estimatedAdRevenue' => 'estimated_ad_revenue',
                    'grossRevenue' => 'gross_revenue',
                    'cpm' => 'cpm',
                    'monetizedPlaybacks' => 'monetized_playbacks',
                    'adImpressions' => 'ad_impressions'
                ]);

                // Update or create record using hash
                YoutubeAnalyticsReport::updateOrCreate(
                    ['record_hash' => $recordHash],
                    $data
                );

            } catch (\Exception $e) {
                Log::error('Error processing analytics row', [
                    'channel_id' => $channel->id,
                    'report_type' => $reportType,
                    'row' => $row,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Process and save video analytics rows
     */
    private function processVideoAnalyticsRows(Channel $channel, string $videoId, array $analytics, string $reportType, string $dimensionType, ?string $fixedDate = null): void
    {
        $headers = $analytics['columnHeaders'] ?? [];
        $rows = $analytics['rows'] ?? [];

        // Map column headers to indices
        $columnMap = [];
        foreach ($headers as $index => $header) {
            $columnMap[$header['name']] = $index;
        }

        foreach ($rows as $row) {
            try {
                $reportDate = $fixedDate ?? ($row[$columnMap['day'] ?? 0] ?? now()->format('Y-m-d'));
                $dimensionValue = $dimensionType === 'day' ? null : ($row[$columnMap[$dimensionType] ?? 0] ?? null);

                // Generate unique hash for this record
                $hashData = $channel->id . '|' . $videoId . '|' . $reportDate . '|' . $reportType . '|' . $dimensionType . '|' . ($dimensionValue ?? '');
                $recordHash = hash('sha256', $hashData);

                // Prepare data for insertion
                $data = [
                    'channel_id' => $channel->id,
                    'youtube_video_id' => $videoId,
                    'youtube_channel_id' => $channel->latestYoutubeStats->youtube_channel_id ?? null,
                    'report_date' => $reportDate,
                    'report_type' => $reportType,
                    'dimension_type' => $dimensionType,
                    'dimension_value' => $dimensionValue,
                    'record_hash' => $recordHash,
                    'last_synced_at' => now(),
                    'sync_successful' => true
                ];

                // Map metrics from the row
                $this->mapMetricsToData($data, $row, $columnMap, [
                    'views' => 'views',
                    'estimatedMinutesWatched' => 'estimated_minutes_watched',
                    'averageViewDuration' => 'average_view_duration',
                    'averageViewPercentage' => 'average_view_percentage',
                    'likes' => 'likes',
                    'dislikes' => 'dislikes',
                    'comments' => 'comments',
                    'shares' => 'shares',
                    'subscribersGained' => 'subscribers_gained',
                    'subscribersLost' => 'subscribers_lost'
                ]);

                // Update or create record using hash
                YoutubeVideoAnalytics::updateOrCreate(
                    ['record_hash' => $recordHash],
                    $data
                );

            } catch (\Exception $e) {
                Log::error('Error processing video analytics row', [
                    'channel_id' => $channel->id,
                    'video_id' => $videoId,
                    'report_type' => $reportType,
                    'row' => $row,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Sync revenue analytics (for monetized channels)
     */
    private function syncRevenueAnalytics(Channel $channel, string $startDate, string $endDate): void
    {
        try {
            $analytics = $this->youtubeService->getRevenueAnalytics($channel, $startDate, $endDate, 'day', 'USD');

            if (!$analytics || !isset($analytics['rows'])) {
                $this->info("    ℹ️  No hay datos de ingresos disponibles (canal no monetizado)");
                return;
            }

            $this->processChannelAnalyticsRows($channel, $analytics, 'revenue', 'day');
            $this->info("    ✅ Analytics de ingresos guardados: " . count($analytics['rows']) . " registros");

        } catch (\Exception $e) {
            // Revenue analytics might not be available for all channels
            $this->info("    ℹ️  Analytics de ingresos no disponibles: " . $e->getMessage());
        }
    }

    /**
     * Map metrics from API response to database fields
     */
    private function mapMetricsToData(array &$data, array $row, array $columnMap, array $metricMapping): void
    {
        foreach ($metricMapping as $apiMetric => $dbField) {
            if (isset($columnMap[$apiMetric]) && isset($row[$columnMap[$apiMetric]])) {
                $value = $row[$columnMap[$apiMetric]];
                
                // Handle revenue fields that can be null or decimal
                if (in_array($dbField, ['estimated_revenue', 'estimated_ad_revenue', 'gross_revenue', 'cpm', 'estimated_red_partner_revenue'])) {
                    $data[$dbField] = is_numeric($value) && $value > 0 ? (float) $value : null;
                } else {
                    $data[$dbField] = is_numeric($value) ? $value : 0;
                }
            }
        }
    }
}
