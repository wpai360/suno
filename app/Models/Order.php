<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'customer_name',
        'city',
        'order_total',
        'status',
        'lyrics',
        'song_file',
        'drive_link',
        'group_size'
    ];
}
