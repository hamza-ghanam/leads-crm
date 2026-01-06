<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    public function toArray($request)
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'phone' => $this->phone_number,
            'email' => $this->email,

            'status' => $this->whenLoaded('status', fn () => [
                'id' => $this->status->id,
                'name' => $this->status->name,
                'slug' => $this->status->slug,
            ]),

            'source' => $this->whenLoaded('source', fn () => [
                'id' => $this->source->id,
                'name' => $this->source->name,
                'slug' => $this->source->slug,
            ]),

            'assigned_to' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'role' => $this->user?->getRoleNames()->first(),
            ],

            'assigner' => [
                'id' => $this->assigner?->id,
                'name' => $this->assigner?->name,
                'role' => $this->assigner?->getRoleNames()->first(),
            ],

            'last_follow_up' => $this->lastFollowUpPreview($user),
            'created_at' => $this->created_at,
        ];
    }

    private function lastFollowUpPreview($user): string
    {
        $path = $this->latestFollowUpPath;

        if (!$path || !$path->comment) {
            return '-';
        }

        if ($user->hasAnyRole(['sale', 'tele-sale'])) {
            if ((int)$path->next_user !== (int)$user->id) {
                return '-';
            }
        }

        $comment = (string)$path->comment;
        return mb_strlen($comment) < 75
            ? $comment
            : mb_substr($comment, 0, 75) . '...';
    }
}
