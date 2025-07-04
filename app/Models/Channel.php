<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    protected $fillable = ['name', 'description', 'intro', 'background_video', 'frame_image', 'image_style_prompt', 'thumbnail_template', 'thumbnail_image_prompt'];

    public function videos()
    {
        return $this->hasMany(Video::class);
    }
}
