<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SongRequest extends Model
{
    protected $fillable = [
        'mp3_path',
        'mp4_path',
        'status'
    ];
} 