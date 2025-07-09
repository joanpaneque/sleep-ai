<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use App\Models\Channel;
use App\Models\Video;
use Inertia\Inertia;

class StorageController extends Controller
{
    public function index()
    {
        // Get storage statistics
        $storage_stats = $this->getStorageStats();

        // Get storage breakdown by type
        $storage_breakdown = $this->getStorageBreakdown();

        // Get historical video statistics
        $historical_video_stats = $this->getHistoricalVideoStats();

        return Inertia::render('DiskUsage/Index', [
            'storage_stats' => $storage_stats,
            'storage_breakdown' => $storage_breakdown,
            'historical_video_stats' => $historical_video_stats
        ]);
    }

    private function getStorageStats()
    {
        $publicPath = storage_path('app/public');
        $channelsPath = $publicPath . '/channels';
        $appDataSize = 0;

        // Calculate total size of storage/app/public/channels directory
        if (is_dir($channelsPath)) {
            $appDataSize = $this->getDirectorySize($channelsPath);
        }

        // Add size of all channel-specific files
        $channels = Channel::all();
        foreach ($channels as $channel) {
            // Intro video
            if ($channel->intro) {
                $introPath = $publicPath . '/intros/' . $channel->intro;
                if (file_exists($introPath)) {
                    $appDataSize += filesize($introPath);
                }
            }

            // Background video
            if ($channel->background_video) {
                $backgroundPath = $publicPath . '/backgrounds/' . $channel->background_video;
                if (file_exists($backgroundPath)) {
                    $appDataSize += filesize($backgroundPath);
                }
            }

            // Frame image
            if ($channel->frame_image) {
                $framePath = $publicPath . '/frames/' . $channel->frame_image;
                if (file_exists($framePath)) {
                    $appDataSize += filesize($framePath);
                }
            }

            // Thumbnail template
            if ($channel->thumbnail_template) {
                $thumbnailPath = $publicPath . '/thumbnail_templates/' . $channel->thumbnail_template;
                if (file_exists($thumbnailPath)) {
                    $appDataSize += filesize($thumbnailPath);
                }
            }
        }

        // Get disk space information (entire disk)
        $freeSpace = disk_free_space($publicPath);
        $totalDiskSpace = disk_total_space($publicPath);
        $usedDiskSpace = $totalDiskSpace - $freeSpace;

        // Calculate system weight (everything except our app data)
        $systemWeight = $usedDiskSpace - $appDataSize;

        // Calculate available space for the app (total disk - system weight)
        $availableSpaceForApp = $totalDiskSpace - $systemWeight;

        // Calculate percentage used of available space for app
        $usedPercentage = $availableSpaceForApp > 0 ? round(($appDataSize / $availableSpaceForApp) * 100, 1) : 0;

        return [
            'used_space' => $appDataSize, // Size in bytes for calculations
            'total_space' => $availableSpaceForApp, // Total space in bytes for calculations
            'used_space_mb' => round($appDataSize / (1024 * 1024), 2),
            'free_space_mb' => round($freeSpace / (1024 * 1024), 2),
            'total_space_mb' => round($availableSpaceForApp / (1024 * 1024), 2),
            'used_percentage' => $usedPercentage,
            'used_space_formatted' => $this->formatBytes($appDataSize),
            'free_space_formatted' => $this->formatBytes($freeSpace),
            'total_space_formatted' => $this->formatBytes($availableSpaceForApp),
            'app_data_size' => $this->formatBytes($appDataSize),
            'system_weight' => $this->formatBytes($systemWeight),
            'disk_total_space' => $this->formatBytes($totalDiskSpace)
        ];
    }

                private function getStorageBreakdown()
    {
        $publicPath = storage_path('app/public');
        $channelsPath = $publicPath . '/channels';

        // Get all channels from database
        $channels = Channel::all();
        $breakdown = [];
        $totalChannelsSize = 0;

        // Colors for channels (cycling through them)
        $colors = ['blue', 'green', 'purple', 'yellow', 'red', 'indigo', 'pink', 'orange'];

        foreach ($channels as $index => $channel) {
            // Calculate size for channel videos directory only
            $channelPath = $channelsPath . '/' . $channel->id;
            $channelDirectorySize = $this->getDirectorySize($channelPath);

            // Calculate size of channel-specific files (residuos)
            $residuosSize = 0;

            // Intro video
            if ($channel->intro) {
                $introPath = $publicPath . '/intros/' . $channel->intro;
                if (file_exists($introPath)) {
                    $residuosSize += filesize($introPath);
                }
            }

            // Background video
            if ($channel->background_video) {
                $backgroundPath = $publicPath . '/backgrounds/' . $channel->background_video;
                if (file_exists($backgroundPath)) {
                    $residuosSize += filesize($backgroundPath);
                }
            }

            // Frame image
            if ($channel->frame_image) {
                $framePath = $publicPath . '/frames/' . $channel->frame_image;
                if (file_exists($framePath)) {
                    $residuosSize += filesize($framePath);
                }
            }

            // Thumbnail template
            if ($channel->thumbnail_template) {
                $thumbnailPath = $publicPath . '/thumbnail_templates/' . $channel->thumbnail_template;
                if (file_exists($thumbnailPath)) {
                    $residuosSize += filesize($thumbnailPath);
                }
            }

            $totalChannelSize = $channelDirectorySize + $residuosSize;
            $totalChannelsSize += $totalChannelSize;

            $breakdown[] = [
                'name' => $channel->name,
                'size' => $totalChannelSize,
                'channel_directory_size' => $channelDirectorySize,
                'residuos_size' => $residuosSize,
                'size_formatted' => $this->formatBytes($totalChannelSize),
                'channel_directory_formatted' => $this->formatBytes($channelDirectorySize),
                'residuos_formatted' => $this->formatBytes($residuosSize),
                'color' => $colors[$index % count($colors)],
                'icon' => 'M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z',
                'channel_id' => $channel->id,
                'description' => $channel->description
            ];
        }

        // Sort by size (largest first)
        usort($breakdown, function($a, $b) {
            return $b['size'] - $a['size'];
        });

        return $breakdown;
    }

    private function getHistoricalVideoStats()
    {
        // Get total historical storage generated by all videos
        $totalVideosCreated = Video::count();
        $totalCompletedVideos = Video::where('status', 'completed')->count();
        $totalDeletedVideos = Video::where('is_deleted', true)->count();
        $totalActiveVideos = Video::where('is_deleted', false)->where('status', 'completed')->count();

        // Calculate total size of all videos (including deleted ones that had size calculated)
        $totalHistoricalSize = Video::whereNotNull('size_in_bytes')->sum('size_in_bytes');
        $totalActiveSize = Video::where('is_deleted', false)
                                ->whereNotNull('size_in_bytes')
                                ->sum('size_in_bytes');
        $totalDeletedSize = $totalHistoricalSize - $totalActiveSize;

        // Get average video size
        $averageVideoSize = $totalCompletedVideos > 0 ? $totalHistoricalSize / $totalCompletedVideos : 0;

        // Get largest video
        $largestVideo = Video::whereNotNull('size_in_bytes')
                            ->orderBy('size_in_bytes', 'desc')
                            ->first();

        // Generate historical chart data
        $chartData = $this->generateHistoricalChartData();

        return [
            'total_videos_created' => $totalVideosCreated,
            'total_completed_videos' => $totalCompletedVideos,
            'total_deleted_videos' => $totalDeletedVideos,
            'total_active_videos' => $totalActiveVideos,
            'total_historical_size' => $totalHistoricalSize,
            'total_historical_size_formatted' => $this->formatBytes($totalHistoricalSize),
            'total_active_size' => $totalActiveSize,
            'total_active_size_formatted' => $this->formatBytes($totalActiveSize),
            'total_deleted_size' => $totalDeletedSize,
            'total_deleted_size_formatted' => $this->formatBytes($totalDeletedSize),
            'average_video_size' => $averageVideoSize,
            'average_video_size_formatted' => $this->formatBytes($averageVideoSize),
            'largest_video' => $largestVideo ? [
                'id' => $largestVideo->id,
                'title' => $largestVideo->title,
                'size' => $largestVideo->size_in_bytes,
                'size_formatted' => $this->formatBytes($largestVideo->size_in_bytes),
                'channel_name' => $largestVideo->channel->name ?? 'Canal eliminado'
            ] : null,
            'chart_data' => $chartData
        ];
    }

    private function generateHistoricalChartData()
    {
        // Get the first video created date
        $firstVideo = Video::orderBy('created_at', 'asc')->first();

        if (!$firstVideo) {
            return [];
        }

        $startDate = $firstVideo->created_at;
        $endDate = now();

        // Calculate the time span in seconds (absolute value to ensure positive)
        $totalSeconds = abs($endDate->diffInSeconds($startDate));

        // If there's very little time difference (less than 1 hour), create artificial range
        if ($totalSeconds < 3600) {
            // Create a 30-day artificial range ending today
            $startDate = $endDate->copy()->subDays(30);
            $totalSeconds = $endDate->diffInSeconds($startDate);
        }

        // Create 100 time points from start to end (chronological order)
        $points = [];
        $intervalSeconds = $totalSeconds / 99; // 99 intervals for 100 points

        for ($i = 0; $i < 100; $i++) {
            // Start from the first video date and move forward in time
            $pointDate = $startDate->copy()->addSeconds($intervalSeconds * $i);

            // For the last point, use current time to ensure we capture everything
            if ($i == 99) {
                $pointDate = $endDate->copy();
            }

            // Calculate total storage consumed up to this point in time (ACUMULATIVO)
            // Count ALL videos created from the beginning up to this point
            $totalSize = Video::whereNotNull('size_in_bytes')
                             ->where('status', 'completed')
                             ->where('created_at', '<=', $pointDate)
                             ->sum('size_in_bytes');

            $videosCount = Video::whereNotNull('size_in_bytes')
                               ->where('status', 'completed')
                               ->where('created_at', '<=', $pointDate)
                               ->count();

            $points[] = [
                'date' => $pointDate->format('Y-m-d H:i:s'),
                'date_formatted' => $pointDate->format('d/m/Y'),
                'total_size' => $totalSize,
                'total_size_formatted' => $this->formatBytes($totalSize),
                'total_size_mb' => round($totalSize / (1024 * 1024), 2)
            ];
        }

        return $points;
    }

    private function getDirectorySize($directory)
    {
        $size = 0;

        if (is_dir($directory)) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory)) as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        }

        return $size;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public function cleanup(Request $request)
    {
        $type = $request->input('type');
        $cleaned_size = 0;

        switch ($type) {
            case 'cache':
                // Clear application cache
                Artisan::call('cache:clear');
                Artisan::call('config:clear');
                Artisan::call('view:clear');
                $cleaned_size = 45 * 1024 * 1024; // Mock 45MB
                break;
            case 'logs':
                // Clear old logs (keep last 7 days)
                $log_files = glob(storage_path('logs/*.log'));
                $week_ago = now()->subWeek();
                foreach ($log_files as $file) {
                    if (filemtime($file) < $week_ago->timestamp) {
                        $cleaned_size += filesize($file);
                        unlink($file);
                    }
                }
                break;
            case 'sessions':
                // Clear expired sessions
                Artisan::call('session:clear');
                $cleaned_size = 12 * 1024 * 1024; // Mock 12MB
                break;
        }

        return response()->json([
            'success' => true,
            'message' => 'Limpieza completada exitosamente',
            'cleaned_size' => $this->formatBytes($cleaned_size)
        ]);
    }
}
