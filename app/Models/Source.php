<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Source extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name'];

    /**
     * Get the tickets for the status.
     */
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
