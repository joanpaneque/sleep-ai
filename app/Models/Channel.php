<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    protected $fillable = ['name', 'description'];

    public function videos()
    {
        return $this->hasMany(Video::class);
    }
}
