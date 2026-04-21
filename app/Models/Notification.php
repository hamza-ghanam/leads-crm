<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'body',
        'url',
        'type',
        'icon',
        'is_read',
        'delivered_at',
        'clicked_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'delivered_at' => 'datetime',
        'clicked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
