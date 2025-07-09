<?php

namespace App\Services;

use App\Models\Channel;
use Illuminate\Support\Facades\Log;
use Exception;

class YoutubeService
{
    private const YOUTUBE_API_BASE_URL = 'https://www.googleapis.com/youtube/v3';
    private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';

    /**
     * Get basic channel information
     */
    public function getChannelInfo(Channel $channel): ?array
    {
        if (!$this->ensureValidToken($channel)) {
            return null;
        }

        $url = self::YOUTUBE_API_BASE_URL . '/channels';
        $params = [
            'part' => 'snippet,statistics,brandingSettings',
            'mine' => 'true',
            'access_token' => $channel->google_access_token
        ];

        $response = $this->makeApiRequest($url, $params);

        if ($response && isset($response['items']) && !empty($response['items'])) {
            return $response['items'][0];
        }

        return null;
    }

    /**
     * Get channel statistics only
     */
    public function getChannelStatistics(Channel $channel): ?array
    {
        if (!$this->ensureValidToken($channel)) {
            return null;
        }

        $url = self::YOUTUBE_API_BASE_URL . '/channels';
        $params = [
            'part' => 'statistics',
            'mine' => 'true',
            'access_token' => $channel->google_access_token
        ];

        $response = $this->makeApiRequest($url, $params);

        if ($response && isset($response['items']) && !empty($response['items'])) {
            return $response['items'][0]['statistics'] ?? null;
        }

        return null;
    }

    /**
     * Get channel snippet (basic info like title, description, thumbnails)
     */
    public function getChannelSnippet(Channel $channel): ?array
    {
        if (!$this->ensureValidToken($channel)) {
            return null;
        }

        $url = self::YOUTUBE_API_BASE_URL . '/channels';
        $params = [
            'part' => 'snippet',
            'mine' => 'true',
            'access_token' => $channel->google_access_token
        ];

        $response = $this->makeApiRequest($url, $params);

        if ($response && isset($response['items']) && !empty($response['items'])) {
            return $response['items'][0]['snippet'] ?? null;
        }

        return null;
    }

    /**
     * Get channel branding settings
     */
    public function getChannelBranding(Channel $channel): ?array
    {
        if (!$this->ensureValidToken($channel)) {
            return null;
        }

        $url = self::YOUTUBE_API_BASE_URL . '/channels';
        $params = [
            'part' => 'brandingSettings',
            'mine' => 'true',
            'access_token' => $channel->google_access_token
        ];

        $response = $this->makeApiRequest($url, $params);

        if ($response && isset($response['items']) && !empty($response['items'])) {
            return $response['items'][0]['brandingSettings'] ?? null;
        }

        return null;
    }

    /**
     * Get channel videos list
     */
    public function getChannelVideos(Channel $channel, int $maxResults = 50, string $order = 'date'): ?array
    {
        if (!$this->ensureValidToken($channel)) {
            return null;
        }

        // First get the channel's uploads playlist ID
        $url = self::YOUTUBE_API_BASE_URL . '/channels';
        $params = [
            'part' => 'contentDetails',
            'mine' => 'true',
            'access_token' => $channel->google_access_token
        ];

        $channelResponse = $this->makeApiRequest($url, $params);
        if (!$channelResponse || !isset($channelResponse['items']) || empty($channelResponse['items'])) {
            return null;
        }

        $uploadsPlaylistId = $channelResponse['items'][0]['contentDetails']['relatedPlaylists']['uploads'] ?? null;
        if (!$uploadsPlaylistId) {
            return null;
        }

        $url = self::YOUTUBE_API_BASE_URL . '/playlistItems';
        $params = [
            'part' => 'snippet,contentDetails',
            'playlistId' => $uploadsPlaylistId,
            'maxResults' => min($maxResults, 50), // YouTube API limit
            'access_token' => $channel->google_access_token
        ];

        return $this->makeApiRequest($url, $params);
    }

    /**
     * Get channel playlists
     */
    public function getChannelPlaylists(Channel $channel, int $maxResults = 50): ?array
    {
        if (!$this->ensureValidToken($channel)) {
            return null;
        }

        $url = self::YOUTUBE_API_BASE_URL . '/playlists';
        $params = [
            'part' => 'snippet,status,contentDetails',
            'mine' => 'true',
            'maxResults' => min($maxResults, 50),
            'access_token' => $channel->google_access_token
        ];

        return $this->makeApiRequest($url, $params);
    }

    /**
     * Get channel analytics data (requires YouTube Analytics API)
     */
    public function getChannelAnalytics(Channel $channel, string $startDate, string $endDate, array $metrics = ['views', 'estimatedMinutesWatched', 'subscribersGained']): ?array
    {
        if (!$this->ensureValidToken($channel)) {
            return null;
        }

        $url = 'https://youtubeanalytics.googleapis.com/v2/reports';
        $params = [
            'ids' => 'channel==MINE',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'metrics' => implode(',', $metrics),
            'access_token' => $channel->google_access_token
        ];

        return $this->makeApiRequest($url, $params);
    }

    /**
     * Search for videos in the channel
     */
    public function searchChannelVideos(Channel $channel, string $query, int $maxResults = 25): ?array
    {
        if (!$this->ensureValidToken($channel)) {
            return null;
        }

        // First get channel ID
        $channelInfo = $this->getChannelInfo($channel);
        if (!$channelInfo || !isset($channelInfo['id'])) {
            return null;
        }

        $url = self::YOUTUBE_API_BASE_URL . '/search';
        $params = [
            'part' => 'snippet',
            'channelId' => $channelInfo['id'],
            'q' => $query,
            'type' => 'video',
            'maxResults' => min($maxResults, 50),
            'access_token' => $channel->google_access_token
        ];

        return $this->makeApiRequest($url, $params);
    }

    /**
     * Get detailed video information by video ID
     */
    public function getVideoDetails(Channel $channel, string $videoId): ?array
    {
        if (!$this->ensureValidToken($channel)) {
            return null;
        }

        $url = self::YOUTUBE_API_BASE_URL . '/videos';
        $params = [
            'part' => 'snippet,statistics,contentDetails,status,recordingDetails,liveStreamingDetails',
            'id' => $videoId,
            'access_token' => $channel->google_access_token
        ];

        $response = $this->makeApiRequest($url, $params);

        if ($response && isset($response['items']) && !empty($response['items'])) {
            return $response['items'][0];
        }

        return null;
    }

    /**
     * Get video statistics (views, likes, comments, etc.)
     */
    public function getVideoStatistics(Channel $channel, string $videoId): ?array
    {
        if (!$this->ensureValidToken($channel)) {
            return null;
        }

        $url = self::YOUTUBE_API_BASE_URL . '/videos';
        $params = [
            'part' => 'statistics',
            'id' => $videoId,
            'access_token' => $channel->google_access_token
        ];

        $response = $this->makeApiRequest($url, $params);

        if ($response && isset($response['items']) && !empty($response['items'])) {
            return $response['items'][0]['statistics'] ?? null;
        }

        return null;
    }

    /**
     * Get multiple videos statistics at once
     */
    public function getMultipleVideosStatistics(Channel $channel, array $videoIds): ?array
    {
        if (!$this->ensureValidToken($channel)) {
            return null;
        }

        // YouTube API allows up to 50 video IDs per request
        $chunks = array_chunk($videoIds, 50);
        $allResults = [];

        foreach ($chunks as $chunk) {
            $url = self::YOUTUBE_API_BASE_URL . '/videos';
            $params = [
                'part' => 'snippet,statistics',
                'id' => implode(',', $chunk),
                'access_token' => $channel->google_access_token
            ];

            $response = $this->makeApiRequest($url, $params);

            if ($response && isset($response['items'])) {
                $allResults = array_merge($allResults, $response['items']);
            }
        }

        return $allResults;
    }

    /**
     * Get video comments
     */
    public function getVideoComments(Channel $channel, string $videoId, int $maxResults = 100, string $order = 'time'): ?array
    {
        if (!$this->ensureValidToken($channel)) {
            return null;
        }

        $url = self::YOUTUBE_API_BASE_URL . '/commentThreads';
        $params = [
            'part' => 'snippet,replies',
            'videoId' => $videoId,
            'order' => $order, // time, relevance
            'maxResults' => min($maxResults, 100),
            'access_token' => $channel->google_access_token
        ];

        return $this->makeApiRequest($url, $params);
    }

    /**
     * Get top comments for a video (by likes)
     */
    public function getVideoTopComments(Channel $channel, string $videoId, int $maxResults = 20): ?array
    {
        return $this->getVideoComments($channel, $videoId, $maxResults, 'relevance');
    }

    /**
     * Get video analytics data
     */
    public function getVideoAnalytics(Channel $channel, string $videoId, string $startDate, string $endDate, array $metrics = ['views', 'likes', 'dislikes', 'comments', 'shares', 'estimatedMinutesWatched', 'averageViewDuration']): ?array
    {
        if (!$this->ensureValidToken($channel)) {
            return null;
        }

        $url = 'https://youtubeanalytics.googleapis.com/v2/reports';
        $params = [
            'ids' => 'channel==MINE',
            'startDate' => $startDate,
            'endDate' => $endDate,
            'metrics' => implode(',', $metrics),
            'filters' => 'video==' . $videoId,
            'access_token' => $channel->google_access_token
        ];

        return $this->makeApiRequest($url, $params);
    }

    /**
     * Get video performance metrics (views, engagement rate, etc.)
     */
    public function getVideoPerformanceMetrics(Channel $channel, string $videoId): ?array
    {
        $videoDetails = $this->getVideoDetails($channel, $videoId);

        if (!$videoDetails || !isset($videoDetails['statistics'])) {
            return null;
        }

        $stats = $videoDetails['statistics'];
        $snippet = $videoDetails['snippet'] ?? [];

        // Calculate engagement metrics
        $viewCount = (int) ($stats['viewCount'] ?? 0);
        $likeCount = (int) ($stats['likeCount'] ?? 0);
        $commentCount = (int) ($stats['commentCount'] ?? 0);

        $engagementRate = $viewCount > 0 ? (($likeCount + $commentCount) / $viewCount) * 100 : 0;
        $likeRate = $viewCount > 0 ? ($likeCount / $viewCount) * 100 : 0;
        $commentRate = $viewCount > 0 ? ($commentCount / $viewCount) * 100 : 0;

        // Get video duration
        $duration = $videoDetails['contentDetails']['duration'] ?? 'PT0S';
        $durationSeconds = $this->parseDuration($duration);

        return [
            'video_id' => $videoId,
            'title' => $snippet['title'] ?? 'Sin título',
            'published_at' => $snippet['publishedAt'] ?? null,
            'duration_seconds' => $durationSeconds,
            'duration_formatted' => $this->formatDuration($durationSeconds),
            'view_count' => $viewCount,
            'like_count' => $likeCount,
            'comment_count' => $commentCount,
            'engagement_rate' => round($engagementRate, 2),
            'like_rate' => round($likeRate, 2),
            'comment_rate' => round($commentRate, 2),
            'views_per_day' => $this->calculateViewsPerDay($snippet['publishedAt'] ?? null, $viewCount),
            'thumbnail_url' => $snippet['thumbnails']['high']['url'] ?? null,
            'tags' => $snippet['tags'] ?? [],
            'category_id' => $snippet['categoryId'] ?? null
        ];
    }

    /**
     * Get trending videos from the channel (most viewed in recent period)
     */
    public function getChannelTrendingVideos(Channel $channel, int $days = 30, int $maxResults = 10): ?array
    {
        $videos = $this->getChannelVideos($channel, 50);

        if (!$videos || !isset($videos['items'])) {
            return null;
        }

        $videoIds = [];
        $cutoffDate = now()->subDays($days);

        // Filter videos by date and collect IDs
        foreach ($videos['items'] as $video) {
            $publishedAt = $video['snippet']['publishedAt'] ?? null;
            if ($publishedAt && $publishedAt >= $cutoffDate->toISOString()) {
                $videoIds[] = $video['snippet']['resourceId']['videoId'] ?? $video['contentDetails']['videoId'] ?? null;
            }
        }

        if (empty($videoIds)) {
            return null;
        }

        // Get statistics for these videos
        $videosWithStats = $this->getMultipleVideosStatistics($channel, array_filter($videoIds));

        if (!$videosWithStats) {
            return null;
        }

        // Sort by view count
        usort($videosWithStats, function($a, $b) {
            $viewsA = (int) ($a['statistics']['viewCount'] ?? 0);
            $viewsB = (int) ($b['statistics']['viewCount'] ?? 0);
            return $viewsB - $viewsA;
        });

        return array_slice($videosWithStats, 0, $maxResults);
    }

    /**
     * Get video engagement analysis
     */
    public function getVideoEngagementAnalysis(Channel $channel, string $videoId): ?array
    {
        $videoDetails = $this->getVideoDetails($channel, $videoId);
        $comments = $this->getVideoComments($channel, $videoId, 100);

        if (!$videoDetails) {
            return null;
        }

        $stats = $videoDetails['statistics'] ?? [];
        $viewCount = (int) ($stats['viewCount'] ?? 0);
        $likeCount = (int) ($stats['likeCount'] ?? 0);
        $commentCount = (int) ($stats['commentCount'] ?? 0);

        // Analyze comments sentiment (basic)
        $commentAnalysis = [
            'total_comments' => $commentCount,
            'recent_comments' => count($comments['items'] ?? []),
            'avg_comment_length' => 0,
            'top_commenters' => []
        ];

        if ($comments && isset($comments['items'])) {
            $totalLength = 0;
            $commenters = [];

            foreach ($comments['items'] as $comment) {
                $text = $comment['snippet']['topLevelComment']['snippet']['textDisplay'] ?? '';
                $totalLength += strlen($text);

                $author = $comment['snippet']['topLevelComment']['snippet']['authorDisplayName'] ?? 'Anónimo';
                $commenters[$author] = ($commenters[$author] ?? 0) + 1;
            }

            $commentAnalysis['avg_comment_length'] = count($comments['items']) > 0 ?
                round($totalLength / count($comments['items']), 1) : 0;

            arsort($commenters);
            $commentAnalysis['top_commenters'] = array_slice($commenters, 0, 5, true);
        }

        return [
            'video_id' => $videoId,
            'engagement_metrics' => [
                'view_count' => $viewCount,
                'like_count' => $likeCount,
                'comment_count' => $commentCount,
                'engagement_rate' => $viewCount > 0 ? round((($likeCount + $commentCount) / $viewCount) * 100, 3) : 0,
                'like_to_view_ratio' => $viewCount > 0 ? round(($likeCount / $viewCount) * 100, 3) : 0,
                'comment_to_view_ratio' => $viewCount > 0 ? round(($commentCount / $viewCount) * 100, 3) : 0,
                'like_to_comment_ratio' => $commentCount > 0 ? round($likeCount / $commentCount, 2) : 0
            ],
            'comment_analysis' => $commentAnalysis,
            'performance_score' => $this->calculatePerformanceScore($viewCount, $likeCount, $commentCount),
            'analysis_date' => now()->toISOString()
        ];
    }

    /**
     * Get channel's best performing videos
     */
    public function getChannelBestPerformingVideos(Channel $channel, int $maxResults = 10, string $metric = 'viewCount'): ?array
    {
        $videos = $this->getChannelVideos($channel, 50);

        if (!$videos || !isset($videos['items'])) {
            return null;
        }

        $videoIds = [];
        foreach ($videos['items'] as $video) {
            $videoId = $video['snippet']['resourceId']['videoId'] ?? $video['contentDetails']['videoId'] ?? null;
            if ($videoId) {
                $videoIds[] = $videoId;
            }
        }

        if (empty($videoIds)) {
            return null;
        }

        $videosWithStats = $this->getMultipleVideosStatistics($channel, $videoIds);

        if (!$videosWithStats) {
            return null;
        }

        // Sort by specified metric
        usort($videosWithStats, function($a, $b) use ($metric) {
            $valueA = (int) ($a['statistics'][$metric] ?? 0);
            $valueB = (int) ($b['statistics'][$metric] ?? 0);
            return $valueB - $valueA;
        });

        return array_slice($videosWithStats, 0, $maxResults);
    }

    /**
     * Ensure the channel has a valid access token, refresh if necessary
     */
    private function ensureValidToken(Channel $channel): bool
    {
        // Check if we have an access token
        if (!$channel->google_access_token) {
            Log::warning('No access token available for channel', ['channel_id' => $channel->id]);
            return false;
        }

        // Check if token is expired
        if ($channel->google_access_token_expires_at && now()->gt($channel->google_access_token_expires_at)) {
            Log::info('Access token expired, attempting refresh', ['channel_id' => $channel->id]);
            return $this->refreshAccessToken($channel);
        }

        // Check if token expires in the next 5 minutes (proactive refresh)
        if ($channel->google_access_token_expires_at && now()->addMinutes(5)->gt($channel->google_access_token_expires_at)) {
            Log::info('Access token expires soon, proactively refreshing', ['channel_id' => $channel->id]);
            return $this->refreshAccessToken($channel);
        }

        return true;
    }

    /**
     * Refresh the access token using the refresh token
     */
    private function refreshAccessToken(Channel $channel): bool
    {
        if (!$channel->google_refresh_token) {
            Log::error('No refresh token available for channel', ['channel_id' => $channel->id]);
            return false;
        }

        if (!$channel->google_client_id || !$channel->google_client_secret) {
            Log::error('Missing OAuth credentials for token refresh', ['channel_id' => $channel->id]);
            return false;
        }

        try {
            $postData = [
                'client_id' => $channel->google_client_id,
                'client_secret' => $channel->google_client_secret,
                'refresh_token' => $channel->google_refresh_token,
                'grant_type' => 'refresh_token'
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, self::TOKEN_ENDPOINT);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded'
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response === false) {
                throw new Exception('Failed to make request to token endpoint');
            }

            $responseData = json_decode($response, true);

            if ($httpCode !== 200 || !$responseData || !isset($responseData['access_token'])) {
                Log::error('Token refresh failed', [
                    'channel_id' => $channel->id,
                    'http_code' => $httpCode,
                    'response' => $response
                ]);
                return false;
            }

            // Calculate new expiration time
            $expiresIn = $responseData['expires_in'] ?? 3600;
            $expiresAt = now()->addSeconds($expiresIn);

            // Update the channel with new token
            $updateData = [
                'google_access_token' => $responseData['access_token'],
                'google_access_token_expires_at' => $expiresAt
            ];

            // Some refresh responses include a new refresh token
            if (isset($responseData['refresh_token'])) {
                $updateData['google_refresh_token'] = $responseData['refresh_token'];
            }

            $channel->update($updateData);

            Log::info('Access token refreshed successfully', [
                'channel_id' => $channel->id,
                'expires_at' => $expiresAt->toISOString(),
                'new_refresh_token' => isset($responseData['refresh_token'])
            ]);

            return true;

        } catch (Exception $e) {
            Log::error('Exception during token refresh', [
                'channel_id' => $channel->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Make an API request to YouTube
     */
    private function makeApiRequest(string $url, array $params): ?array
    {
        $fullUrl = $url . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            Log::error('Failed to make API request', ['url' => $url]);
            return null;
        }

        $responseData = json_decode($response, true);

        if ($httpCode !== 200) {
            Log::error('API request failed', [
                'url' => $url,
                'http_code' => $httpCode,
                'response' => $response
            ]);
            return null;
        }

        return $responseData;
    }

    /**
     * Test connection to YouTube API
     */
    public function testConnection(Channel $channel): array
    {
        if (!$this->ensureValidToken($channel)) {
            return [
                'success' => false,
                'message' => 'No se pudo obtener un token de acceso válido',
                'error' => 'TOKEN_ERROR'
            ];
        }

        $channelInfo = $this->getChannelInfo($channel);

        if (!$channelInfo) {
            return [
                'success' => false,
                'message' => 'No se pudo obtener información del canal',
                'error' => 'API_ERROR'
            ];
        }

        return [
            'success' => true,
            'message' => 'Conexión exitosa con YouTube',
            'data' => [
                'youtube_channel_id' => $channelInfo['id'] ?? null,
                'youtube_channel_title' => $channelInfo['snippet']['title'] ?? 'Canal sin título',
                'youtube_channel_description' => $channelInfo['snippet']['description'] ?? '',
                'subscriber_count' => $channelInfo['statistics']['subscriberCount'] ?? 0,
                'video_count' => $channelInfo['statistics']['videoCount'] ?? 0,
                'view_count' => $channelInfo['statistics']['viewCount'] ?? 0,
                'published_at' => $channelInfo['snippet']['publishedAt'] ?? null,
                'thumbnail_url' => $channelInfo['snippet']['thumbnails']['default']['url'] ?? null
            ]
        ];
    }

    /**
     * Helper: Parse ISO 8601 duration to seconds
     */
    private function parseDuration(string $duration): int
    {
        $interval = new \DateInterval($duration);
        return ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
    }

    /**
     * Helper: Format seconds to readable duration
     */
    private function formatDuration(int $seconds): string
    {
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
     * Helper: Calculate views per day since publication
     */
    private function calculateViewsPerDay(?string $publishedAt, int $viewCount): float
    {
        if (!$publishedAt || $viewCount === 0) {
            return 0;
        }

        $publishDate = new \DateTime($publishedAt);
        $now = new \DateTime();
        $daysDiff = $now->diff($publishDate)->days;

        return $daysDiff > 0 ? round($viewCount / $daysDiff, 2) : $viewCount;
    }

    /**
     * Helper: Calculate performance score (0-100)
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
