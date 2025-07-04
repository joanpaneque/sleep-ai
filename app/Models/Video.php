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
        exec("rm -rf /var/www/sleepai.online/storage/app/public/channels/" . $this->channel_id . "/" . $this->id);

        $this->is_deleted = true;
        $this->url = null;
        $this->thumbnail = null;
        $this->save();
    }
}
