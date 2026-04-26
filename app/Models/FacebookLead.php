<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacebookLead extends Model
{
    protected $fillable = [
        'leadgen_id','page_id','form_id','ad_id','fb_created_time',
        'full_name','phone','email',
        'answers','webhook_payload','graph_payload','fetched_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'webhook_payload' => 'array',
        'graph_payload' => 'array',
        'fb_created_time' => 'datetime',
        'fetched_at' => 'datetime',
    ];
}
