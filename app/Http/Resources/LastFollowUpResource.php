<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LastFollowUpResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'comment' => $this->comment,
            'created_at' => optional($this->created_at)->toISOString(),

            'next_user' => $this->whenLoaded('nextUser', function () {
                return [
                    'id' => $this->nextUser->id,
                    'name' => $this->nextUser->name,
                ];
            }),

            'next_status' => $this->whenLoaded('nextStatus', function () {
                return [
                    'id' => $this->nextStatus->id,
                    'name' => $this->nextStatus->name,
                    'slug' => $this->nextStatus->slug,
                ];
            }),
        ];
    }
}
