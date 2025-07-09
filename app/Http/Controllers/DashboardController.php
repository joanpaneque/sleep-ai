<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Video;
use App\Models\Channel;

class DashboardController extends Controller
{
    public function index()
    {
        // Get video statistics
        $video_stats = $this->getVideoStats();

        // Get storage statistics
        $storage_stats = $this->getStorageStats();

        // Get channel statistics
        $channel_stats = $this->getChannelStats();

        // Get rendering queue
        $rendering_queue = $this->getRenderingQueue();

        return Inertia::render('Dashboard', [
            'video_stats' => $video_stats,
            'storage_stats' => $storage_stats,
            'channel_stats' => $channel_stats,
            'rendering_queue' => $rendering_queue,
        ]);
    }

    private function getVideoStats()
    {
        // Get total videos generated
        $totalVideos = Video::count();

        // Calculate total storage from all videos
        $totalStorageBytes = Video::whereNotNull('size_in_bytes')->sum('size_in_bytes');

        // Calculate average video size
        $completedVideos = Video::where('status', 'completed')->whereNotNull('size_in_bytes')->count();
        $averageVideoSizeBytes = $completedVideos > 0 ? $totalStorageBytes / $completedVideos : 0;

        return [
            'total_videos' => $totalVideos,
            'total_storage_bytes' => $totalStorageBytes,
            'total_storage_formatted' => $this->formatBytes($totalStorageBytes),
            'average_video_size_bytes' => $averageVideoSizeBytes,
            'average_video_size_formatted' => $this->formatBytes($averageVideoSizeBytes),
        ];
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
            'used_space' => $appDataSize,
            'total_space' => $availableSpaceForApp,
            'used_percentage' => $usedPercentage,
            'used_space_formatted' => $this->formatBytes($appDataSize),
            'free_space_formatted' => $this->formatBytes($freeSpace),
            'total_space_formatted' => $this->formatBytes($availableSpaceForApp),
        ];
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

    private function getChannelStats()
    {
        return [
            'total_channels' => Channel::count(),
        ];
    }

    private function getRenderingQueue()
    {
        $inProgressStatuses = ['processing', 'generating_script', 'generating_content', 'rendering'];

        return Video::with('channel')
                    ->whereIn('status', $inProgressStatuses)
                    ->orderBy('created_at', 'asc')
                    ->get();
    }
}
