<?php

namespace App\Console\Commands;

use App\Models\Channel;
use App\Models\YoutubeChannelStat;
use App\Models\YoutubeVideoStat;
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
        $publishedAt = $snippet['publishedAt'] ?? null;
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
                'published_at' => $publishedAt ? new \DateTime($publishedAt) : null,
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
}
