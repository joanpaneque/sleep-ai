<?php

namespace App\Models;

use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = ['title', 'description', 'url', 'thumbnail', 'duration', 'channel_id', 'status', 'status_progress', 'completed_at', 'stories_amount', 'characters_amount', 'language', 'is_deleted'];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    public function deleteEverything()
    {
        // remove folder storage/app/public/channels/%channel_id%/$video_id%
        $folder = storage_path('app/public/channels/' . $this->channel_id . '/' . $this->id);
        if (file_exists($folder)) {
            File::deleteDirectory($folder);
        }

        $this->is_deleted = true;
        $this->url = null;
        $this->save();
    }
}
