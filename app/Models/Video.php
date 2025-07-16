<?php

namespace App\Models;

use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = ['title', 'description', 'url', 'thumbnail', 'duration', 'channel_id', 'status', 'status_progress', 'completed_at', 'stories_amount', 'characters_amount', 'language', 'is_deleted', 'size_in_bytes'];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function deleteEverything()
    {
        exec("rm -rf /var/www/sleepai.online/storage/app/public/channels/" . $this->channel_id . "/" . $this->id);

        $this->is_deleted = true;
        $this->url = null;
        $this->thumbnail = null;
        $this->size_in_bytes = null;
        $this->save();
    }

    /**
     * Calculate the size of the video directory in bytes
     */
    public function calculateDirectorySize()
    {
        $videoPath = storage_path('app/public/channels/' . $this->channel_id . '/' . $this->id);

        if (!is_dir($videoPath)) {
            return 0;
        }

        return $this->getDirectorySize($videoPath);
    }

    /**
     * Recursively calculate directory size
     */
    private function getDirectorySize($directory)
    {
        $size = 0;

        if (!is_dir($directory)) {
            return 0;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    public function topics()
    {
        return $this->hasMany(VideoTopic::class);
    }
}
