<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Log extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'operation',
        'old_data',
        'new_data',
        'class_name',
    ];

    /**
     * Get the user that did the operation.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
