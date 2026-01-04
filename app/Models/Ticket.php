<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number',
        'created_time',
        'ad_id',
        'ad_name',
        'adset_id',
        'adset_name',
        'campaign_id',
        'campaign_name',
        'form_id',
        'form_name',
        'is_organic',
        'platform',
        'full_name',
        'phone_number',
        'email',
        'invoice',
        'passport',
        'res_form',
        'job_title',
        'user_id',
        'status_id',
        'source_id',
        'assigner_id',
        'method',
        'preferred_time',
        'remarks',
        'extra_data',
    ];

    /**
     * Get the user that manages the ticket.
     */
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    /**
     * Get the booking associated with the ticket.
     */
    public function booking()
    {
        return $this->hasOne(Booking::class);
    }

    /**
     * Get the source of the ticket.
     */
    public function source()
    {
        return $this->belongsTo(Source::class);
    }

    /**
     * Get the source of the ticket.
     */
    public function paths()
    {
        return $this->hasMany(TicketPath::class, 'ticket_id');
    }

    public function latestPath()
    {
        return $this->hasOne(TicketPath::class)->latestOfMany();
    }

    public function latestFollowUpPath()
    {
        // uses status slug 'follow-up'
        return $this->hasOne(TicketPath::class, 'ticket_id')
            ->where('next_status', Status::where('name', Status::FOLLOW_UP)->value('id'))
            ->latestOfMany();
    }

    /**
     * Get the assigner user that assign the ticket.
     */
    public function assigner()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
