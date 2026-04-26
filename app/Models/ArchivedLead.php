<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @mixin Builder
 */
class ArchivedLead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_id',
        'number',
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
        'status_id',
        'source_id',
        'method',
        'preferred_time',
        'remarks',
        'extra_data',
        'archived_at',
        'created_at',
        'updated_at',
    ];

    public static function archiveTicket(Ticket $ticket, $duplicateStatusId = false): self
    {
        $data = $ticket->only([
            'number',
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
            'status_id',
            'source_id',
            'method',
            'preferred_time',
            'remarks',
            'extra_data',
            'created_at',
            'updated_at',
        ]);

        $data['ticket_id'] = $ticket->id;
        $data['archived_at'] = now();

        // Override status if duplicate
        if ($duplicateStatusId) {
            $data['status_id'] = $duplicateStatusId;
        }

        return self::create($data);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    public function source()
    {
        return $this->belongsTo(Source::class);
    }

}
