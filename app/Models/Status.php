<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Status extends Model
{
    use HasFactory, SoftDeletes;

    public const NEW = 'New';
    public const FOLLOW_UP = 'Follow-up';
    public const MEETING = 'Meeting';
    public const WAITING = 'Waiting';
    public const BOOKING = 'Booking';
    public const DEAD = 'Dead';
    public const REVIEWED = 'Reviewed';
    public const PRE_APPROVED = 'Pre-Approved';
    public const APPROVED = 'Approved';
    public const REJECTED = 'Rejected';
    public const DUPLICATED = 'Duplicated';
    public const RE_SHUFFLED = 'Re-shuffled';
    public const NO_ANSWER = 'No-Answer';
    public const NOT_INTERESTED = 'Not-Interested';
    public const SOLD = 'Sold';

    /**
     * Get the tickets for the status.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function next()
    {
        return $this->belongsTo($this, 'next_id');
    }

    public function prev()
    {
        return $this->belongsTo(Status::class, 'prev_id');
    }
}
