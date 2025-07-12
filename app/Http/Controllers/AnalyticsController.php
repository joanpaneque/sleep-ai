<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\YoutubeAnalyticsReport;
use App\Models\YoutubeVideoAnalytics;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    /**
     * Get daily analytics for a channel
     */
    public function getDailyAnalytics(Request $request, Channel $channel): JsonResponse
    {
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $analytics = YoutubeAnalyticsReport::where('channel_id', $channel->id)
            ->where('report_type', 'daily')
            ->whereBetween('report_date', [$startDate, $endDate])
            ->orderBy('report_date')
            ->get();

        // Get revenue data from daily reports only (to avoid duplicates with revenue type)
        $revenueData = YoutubeAnalyticsReport::where('channel_id', $channel->id)
            ->where('report_type', 'daily')
            ->whereBetween('report_date', [$startDate, $endDate])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $analytics,
            'summary' => [
                'total_revenue' => round($revenueData->sum('estimated_revenue'), 2),
            ]
        ]);
    }

    /**
     * Get geographic analytics for a channel
     */
    public function getGeographicAnalytics(Request $request, Channel $channel): JsonResponse
    {
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $analytics = YoutubeAnalyticsReport::where('channel_id', $channel->id)
            ->where('report_type', 'geographic')
            ->whereBetween('report_date', [$startDate, $endDate])
            ->orderBy('views', 'desc')
            ->get()
            ->groupBy('dimension_value')
            ->map(function ($group) {
                return [
                    'id' => $group->first()->id,
                    'dimension_value' => $group->first()->dimension_value,
                    'views' => $group->sum('views'),
                    'estimated_minutes_watched' => $group->sum('estimated_minutes_watched'),
                    'subscribers_gained' => $group->sum('subscribers_gained'),
                    'subscribers_lost' => $group->sum('subscribers_lost'),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    /**
     * Get device analytics for a channel
     */
    public function getDeviceAnalytics(Request $request, Channel $channel): JsonResponse
    {
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $analytics = YoutubeAnalyticsReport::where('channel_id', $channel->id)
            ->where('report_type', 'device')
            ->whereBetween('report_date', [$startDate, $endDate])
            ->orderBy('views', 'desc')
            ->get()
            ->groupBy('dimension_value')
            ->map(function ($group) {
                return [
                    'id' => $group->first()->id,
                    'dimension_value' => $group->first()->dimension_value,
                    'views' => $group->sum('views'),
                    'estimated_minutes_watched' => $group->sum('estimated_minutes_watched'),
                    'average_view_duration' => $group->avg('average_view_duration'),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    /**
     * Get traffic source analytics for a channel
     */
    public function getTrafficSourceAnalytics(Request $request, Channel $channel): JsonResponse
    {
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $analytics = YoutubeAnalyticsReport::where('channel_id', $channel->id)
            ->where('report_type', 'traffic_source')
            ->whereBetween('report_date', [$startDate, $endDate])
            ->orderBy('views', 'desc')
            ->get()
            ->groupBy('dimension_value')
            ->map(function ($group) {
                return [
                    'id' => $group->first()->id,
                    'dimension_value' => $group->first()->dimension_value,
                    'views' => $group->sum('views'),
                    'estimated_minutes_watched' => $group->sum('estimated_minutes_watched'),
                    'average_view_duration' => $group->avg('average_view_duration'),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    /**
     * Get demographic analytics for a channel
     */
    public function getDemographicAnalytics(Request $request, Channel $channel): JsonResponse
    {
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $analytics = YoutubeAnalyticsReport::where('channel_id', $channel->id)
            ->where('report_type', 'demographics')
            ->whereBetween('report_date', [$startDate, $endDate])
            ->orderBy('viewer_percentage', 'desc')
            ->get()
            ->groupBy('dimension_value')
            ->map(function ($group) {
                return [
                    'id' => $group->first()->id,
                    'dimension_value' => $group->first()->dimension_value,
                    'viewer_percentage' => $group->avg('viewer_percentage'),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    /**
     * Get top videos analytics for a channel
     */
    public function getTopVideosAnalytics(Request $request, Channel $channel): JsonResponse
    {
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $analytics = YoutubeVideoAnalytics::where('channel_id', $channel->id)
            ->where('report_type', 'daily')
            ->whereBetween('report_date', [$startDate, $endDate])
            ->selectRaw('
                youtube_video_id, 
                SUM(views) as total_views, 
                SUM(estimated_minutes_watched) as total_watch_time, 
                SUM(likes) as total_likes, 
                SUM(comments) as total_comments,
                MAX(report_date) as report_date
            ')
            ->groupBy('youtube_video_id')
            ->orderBy('total_views', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    /**
     * Get video analytics for a specific video
     */
    public function getVideoAnalytics(Request $request, Channel $channel, string $videoId): JsonResponse
    {
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $analytics = YoutubeVideoAnalytics::where('channel_id', $channel->id)
            ->where('youtube_video_id', $videoId)
            ->whereBetween('report_date', [$startDate, $endDate])
            ->orderBy('report_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    /**
     * Get video traffic sources for a specific video
     */
    public function getVideoTrafficSources(Request $request, Channel $channel, string $videoId): JsonResponse
    {
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $analytics = YoutubeVideoAnalytics::where('channel_id', $channel->id)
            ->where('youtube_video_id', $videoId)
            ->where('report_type', 'traffic_source')
            ->whereBetween('report_date', [$startDate, $endDate])
            ->orderBy('views', 'desc')
            ->get()
            ->groupBy('dimension_value')
            ->map(function ($group) {
                return [
                    'traffic_source' => $group->first()->dimension_value,
                    'total_views' => $group->sum('views'),
                    'total_watch_time' => $group->sum('estimated_minutes_watched'),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    /**
     * Get video device breakdown for a specific video
     */
    public function getVideoDeviceBreakdown(Request $request, Channel $channel, string $videoId): JsonResponse
    {
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $analytics = YoutubeVideoAnalytics::where('channel_id', $channel->id)
            ->where('youtube_video_id', $videoId)
            ->where('report_type', 'device')
            ->whereBetween('report_date', [$startDate, $endDate])
            ->orderBy('views', 'desc')
            ->get()
            ->groupBy('dimension_value')
            ->map(function ($group) {
                return [
                    'device_type' => $group->first()->dimension_value,
                    'total_views' => $group->sum('views'),
                    'total_watch_time' => $group->sum('estimated_minutes_watched'),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    /**
     * Get video geographic breakdown for a specific video
     */
    public function getVideoGeographicBreakdown(Request $request, Channel $channel, string $videoId): JsonResponse
    {
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $analytics = YoutubeVideoAnalytics::where('channel_id', $channel->id)
            ->where('youtube_video_id', $videoId)
            ->where('report_type', 'geographic')
            ->whereBetween('report_date', [$startDate, $endDate])
            ->orderBy('views', 'desc')
            ->get()
            ->groupBy('dimension_value')
            ->map(function ($group) {
                return [
                    'country' => $group->first()->dimension_value,
                    'total_views' => $group->sum('views'),
                    'total_watch_time' => $group->sum('estimated_minutes_watched'),
                ];
            })
            ->values();

            return response()->json([
                'success' => true,
            'data' => $analytics
        ]);
    }

    /**
     * Get audience retention data for a specific video
     */
    public function getVideoAudienceRetention(Request $request, Channel $channel, string $videoId): JsonResponse
    {
        $analytics = YoutubeVideoAnalytics::where('channel_id', $channel->id)
            ->where('youtube_video_id', $videoId)
            ->where('dimension_type', 'elapsedVideoTimeRatio')
            ->whereNotNull('audience_watch_ratio')
            ->orderBy('elapsed_video_time_ratio')
            ->get();

            return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    /**
     * Get analytics summary for a channel
     */
    public function getAnalyticsSummary(Request $request, Channel $channel): JsonResponse
    {
        $startDate = $request->get('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->get('end_date', now()->format('Y-m-d'));

        $dailyReports = YoutubeAnalyticsReport::where('channel_id', $channel->id)
            ->where('report_type', 'daily')
            ->whereBetween('report_date', [$startDate, $endDate])
            ->get();

        $summary = [
            'total_views' => $dailyReports->sum('views'),
            'total_watch_time' => $dailyReports->sum('estimated_minutes_watched'),
            'total_likes' => $dailyReports->sum('likes'),
            'total_comments' => $dailyReports->sum('comments'),
            'total_shares' => $dailyReports->sum('shares'),
            'subscribers_gained' => $dailyReports->sum('subscribers_gained'),
            'subscribers_lost' => $dailyReports->sum('subscribers_lost'),
            'net_subscribers' => $dailyReports->sum('subscribers_gained') - $dailyReports->sum('subscribers_lost'),
            'avg_view_duration' => $dailyReports->avg('average_view_duration'),
            'avg_view_percentage' => $dailyReports->avg('average_view_percentage'),
            'total_revenue' => $dailyReports->sum('estimated_revenue'),
            'engagement_rate' => $dailyReports->sum('views') > 0 ? 
                (($dailyReports->sum('likes') + $dailyReports->sum('comments')) / $dailyReports->sum('views')) * 100 : 0,
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days' => $dailyReports->count()
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Get the available data range and total historical revenue for a channel
     */
    public function getDataRange(Channel $channel): JsonResponse
    {
        $revenueData = YoutubeAnalyticsReport::where('channel_id', $channel->id)
            ->where('report_type', 'daily')
            ->whereNotNull('estimated_revenue')
            ->get();

        if ($revenueData->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'has_revenue_data' => false,
                    'start_date' => null,
                    'end_date' => null,
                    'total_historical_revenue' => 0,
                    'total_days' => 0,
                    'average_daily' => 0
                ]
            ]);
        }

        $startDate = $revenueData->min('report_date');
        $endDate = $revenueData->max('report_date');
        $totalRevenue = $revenueData->sum('estimated_revenue');
        $totalDays = $revenueData->count();
        $averageDaily = $totalDays > 0 ? $totalRevenue / $totalDays : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'has_revenue_data' => true,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_historical_revenue' => round($totalRevenue, 2),
                'total_days' => $totalDays,
                'average_daily' => round($averageDaily, 2),
                'projected_monthly' => round($averageDaily * 30, 2)
            ]
        ]);
    }
} 