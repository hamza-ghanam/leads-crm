<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketPath extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['next_user', 'next_status', 'prev_user', 'prev_status', 'ticket_id', 'comment'];

    /**
     * Get the ticket that owns the booking.
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function prevStatus()
    {
        return $this->hasOne(Status::class, 'id', 'prev_status');
    }

    public function nextStatus()
    {
        return $this->hasOne(Status::class, 'id', 'next_status');
    }

    public function prevUser()
    {
        return $this->hasOne(User::class, 'id', 'prev_user')->withTrashed();
    }

    public function nextUser()
    {
        return $this->hasOne(User::class, 'id', 'next_user')->withTrashed();
    }

    /**
     * Get the source of the ticket path.
     */
    public function meeting()
    {
        return $this->hasOne(Meeting::class, 'ticket_path_id');
    }
}
