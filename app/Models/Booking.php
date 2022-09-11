<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_name',
        'unit_number',
        'price',
        'developer_name',
        'user_id',
        'ticket_id'
    ];

    /**
     * Get the ticket that owns the booking.
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
