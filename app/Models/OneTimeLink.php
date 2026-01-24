<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OneTimeLink extends Model
{
    use SoftDeletes;

    protected $fillable = ['token', 'user_type', 'expired_at', 'linkable_id', 'linkable_type', 'generated_by'];

    protected $casts = [
        'generated_by' => 'integer',
        'linkable_id' => 'integer',
        'expired_at' => 'datetime',
    ];

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Who consumed the OTL (Broker, Contractor, etc.)
     */
    public function linkable()
    {
        return $this->morphTo();
    }
}
