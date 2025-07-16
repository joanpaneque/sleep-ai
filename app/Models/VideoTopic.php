<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VideoTopic extends Model
{
    protected $fillable = ['video_id', 'title', 'description'];

    public function video()
    {
        return $this->belongsTo(Video::class);
    }
}
