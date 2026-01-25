<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DbLog extends Model
{
    protected $fillable = [
        'level',
        'category',
        'action',
        'message',
        'context',
        'meta',
        'user_id',
        'ip_address',
        'route',
        'method',
    ];

    protected $casts = [
        'context' => 'array',
        'meta' => 'array',
    ];
}
