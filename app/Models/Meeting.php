<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Meeting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_id',
        'started_at',
        'ended_at',
        'method',
    ];

    /**
     * Get the ticket of the meeting.
     */
    public function ticketPath()
    {
        return $this->belongsTo(TicketPath::class);
    }
}
