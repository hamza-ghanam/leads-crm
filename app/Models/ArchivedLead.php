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
        'user',
        'status',
        'source',
        'created_time',
    ];
}
