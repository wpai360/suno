<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'city',
        'order_total',
        'group_size',
        'items',
        'lyrics',
        'drive_link',
        'youtube_link',
        'status',
        'audio_file',
        'video_file',
        'youtube_id'
    ];

    protected $casts = [
        'items' => 'array',
        'order_total' => 'decimal:2'
    ];

    protected $attributes = [
        'items' => '[]',
        'status' => 'pending'
    ];
}
