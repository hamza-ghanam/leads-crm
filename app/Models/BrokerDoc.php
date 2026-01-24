<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BrokerDoc extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'broker_id',
        'doc_type',
        'file_path',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'broker_id' => 'integer',
    ];

    /**
     * Get the broker that owns this document.
     */
    public function broker()
    {
        return $this->belongsTo(Broker::class);
    }
}

