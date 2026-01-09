<?php

namespace App\Services;

use App\Helpers\LeadsHelper;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\User;

class LeadUpdateService
{
    public function update(User $actor, Ticket $lead, array $payload): LeadCreateResult
    {
        // Normalize phone if exists
        if (isset($payload['phone_number'])) {
            $payload['phone_number'] = app(LeadsHelper::class)->rectifyPhone($payload['phone_number']);

            // Duplicate detection (same logic as store)
            $dup = Ticket::where('phone_number', $payload['phone_number'])
                ->where('id', '!=', $lead->id)
                ->first();

            if ($dup) {
                // Optional: auto-mark duplicated
                $duplicatedStatusId = Status::where('name', Status::DUPLICATED)->value('id');
                $lead->status_id = $duplicatedStatusId;
                $lead->user_id = null;
            }
        }

        $lead->fill([
            'full_name'       => $payload['full_name'] ?? $lead->full_name,
            'phone_number'    => $payload['phone_number'] ?? $lead->phone_number,
            'email'           => $payload['email'] ?? $lead->email,
            'campaign_name'   => $payload['campaign_name'] ?? $lead->campaign_name,
            'source_id'       => $payload['source_id'] ?? $lead->source_id,
            'preferred_time'  => $payload['preferred_time'] ?? $lead->preferred_time,
            'remarks'         => $payload['remarks'] ?? $lead->remarks,
            'extra_data'      => $payload['extra_data'] ?? $lead->extra_data,
        ]);

        $lead->save();

        return new LeadCreateResult(
            lead: $lead->fresh(['user', 'status', 'source', 'assigner']),
            isDuplicated: $dup ?? false,
            initialPath: $lead->latestPath,
        );
    }
}
