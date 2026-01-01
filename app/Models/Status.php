<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method static Builder reassignable()
 */
class Status extends Model
{
    use SoftDeletes;

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

    protected $fillable = ['duration'];

    public function getDurationValue(): ?int
    {
        if (! $this->duration) {
            return null;
        }

        preg_match('/^(\d+)([hd])$/', $this->duration, $m);

        return isset($m[1]) ? (int) $m[1] : null;
    }

    public function getDurationUnit(): ?string
    {
        if (! $this->duration) {
            return null;
        }

        return str_ends_with($this->duration, 'h') ? 'hour' : 'day';
    }

    // App\Models\Status.php
    public function scopeReassignable($query)
    {
        return $query
            ->whereNotIn('name', [
                self::DEAD,
                self::REVIEWED,
                self::BOOKING,
                self::APPROVED,
                self::SOLD,
                self::PRE_APPROVED,
                self::REJECTED,
                self::DUPLICATED,
                self::RE_SHUFFLED,
            ])
            ->where('name', 'not like', '%(Tele)%');
    }

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
