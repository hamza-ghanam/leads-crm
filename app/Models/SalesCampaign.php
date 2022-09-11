<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesCampaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'campaign_name',
    ];

    /**
     * Get the user (Sales) that owns the booking.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
