<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Broker extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'type',
        'company_name',
        'address',
        'license_number',
        'otp',
        'nationality',
        'id_type',
        'id_number',
    ];

    public function docs()
    {
        return $this->hasMany(BrokerDoc::class);
    }
}
