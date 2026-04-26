<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class TicketPath extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['next_user', 'next_status', 'prev_user', 'prev_status', 'ticket_id', 'comment', 'notified_at',
        'reminder_at'
    ];

    protected $casts = [
        'reminder_at' => 'datetime', // ✅ كاست عشان تجيك Carbon
    ];

    /**
     * هنا منتحقق من وجود reminder_datetime لما تتغير حالة التكت
     */
    protected static function booted()
    {
        static::saving(function (TicketPath $ticketPath) {
            // ✅ عدّل هالمصفوفة IDs للحالات اللي بدك فيها تذكير إجباري
            $statusesNeedReminder = [
                // مثلاً:
                Status::FOLLOW_UP,
                // Status::CALL_LATER,
            ];

            $statusesNeedReminder = Status::whereIn('name', $statusesNeedReminder)
                ->pluck('id')
                ->toArray();

            // إذا تغيّرت الـ status_id
            if ($ticketPath->isDirty('next_status')) {
                $newStatusId = (int)$ticketPath->next_status;

                // إذا كانت من الحالات اللي تحتاج تذكير
                if (in_array($newStatusId, $statusesNeedReminder, true)) {
                    // و ما في reminder_at
                    if (empty($ticketPath->reminder_at)) {
                        throw ValidationException::withMessages([
                            'reminder_datetime' => 'Reminder datetime is required for this status.',
                        ]);
                    }
                }
            }
        });
    }

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
