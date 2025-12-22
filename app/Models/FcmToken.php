<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FcmToken extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'token', 'device_type', 'user_agent', 'last_used_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}








