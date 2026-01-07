<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketPathResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'        => $this->id,
            'comment'   => $this->comment,
            'reminder_at' => optional($this->reminder_at)->toISOString(),
            'created_at'  => optional($this->created_at)->toISOString(),

            'prev_user' => $this->whenLoaded('prevUser', function () {
                return $this->prevUser
                    ? ['id' => $this->prevUser->id, 'name' => $this->prevUser->name]
                    : null;
            }),

            'next_user' => $this->whenLoaded('nextUser', function () {
                return $this->nextUser
                    ? ['id' => $this->nextUser->id, 'name' => $this->nextUser->name]
                    : null;
            }),

            'prev_status' => $this->whenLoaded('prevStatus', function () {
                return $this->prevStatus
                    ? ['id' => $this->prevStatus->id, 'name' => $this->prevStatus->name, 'slug' => $this->prevStatus->slug]
                    : null;
            }),

            'next_status' => $this->whenLoaded('nextStatus', function () {
                return $this->nextStatus
                    ? ['id' => $this->nextStatus->id, 'name' => $this->nextStatus->name, 'slug' => $this->nextStatus->slug]
                    : null;
            }),

            'meeting' => $this->whenLoaded('meeting', function () {
                return $this->meeting ? [
                    'id'         => $this->meeting->id,
                    'started_at' => optional($this->meeting->started_at)->toISOString(),
                    'ended_at'   => optional($this->meeting->ended_at)->toISOString(),
                    'reminder_at'=> optional($this->meeting->reminder_at)->toISOString(),
                    'method'     => $this->meeting->method,
                ] : null;
            }),
        ];
    }
}
