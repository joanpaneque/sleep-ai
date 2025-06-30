<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $fillable = ['title', 'description', 'url', 'thumbnail', 'duration', 'channel_id', 'status', 'status_progress', 'completed_at', 'stories_amount', 'characters_amount', 'language'];

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }
}
