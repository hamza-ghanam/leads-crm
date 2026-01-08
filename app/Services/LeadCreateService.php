<?php

namespace App\Services;

use App\Models\Source;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\TicketPath;
use App\Models\User;
use App\Helpers\LeadsHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadCreateService
{
    public function __construct(
        private readonly LeadsHelper $leadsHelper,
    ) {}

    /**
     * @throws ValidationException
     * @throws \Throwable
     */
    public function create(User $actor, array $payload): LeadCreateResult
    {
        // Only admin/super-admin (shared rule)
        if (!$actor->hasAnyRole(['admin', 'super-admin'])) {
            throw ValidationException::withMessages([
                'permission' => ['Only admin/super-admin can create leads.'],
            ]);
        }

        $source = Source::find((int)($payload['source_id'] ?? 0));
        if (!$source) {
            throw ValidationException::withMessages([
                'source_id' => ['Source does not exist.'],
            ]);
        }

        $assignee = User::find((int)($payload['assigned_to'] ?? 0));
        if (!$assignee) {
            throw ValidationException::withMessages([
                'assigned_to' => ['Assigned user does not exist.'],
            ]);
        }

        $statusNew = Status::where('slug', 'new')->first();
        if (!$statusNew) {
            throw ValidationException::withMessages([
                'status' => ['New status is missing (slug=new).'],
            ]);
        }

        $duplicatedStatus = Status::where('name', 'duplicated')->first();
        if (!$duplicatedStatus) {
            throw ValidationException::withMessages([
                'status' => ['Duplicated status is missing (name=duplicated).'],
            ]);
        }

        // Phone normalisation (same as web)
        $phone = str_replace(' ', '', (string)($payload['phone_number'] ?? ''));
        $phone = $this->leadsHelper->rectifyPhone($phone);

        return DB::transaction(function () use ($actor, $payload, $source, $assignee, $statusNew, $duplicatedStatus, $phone) {

            $ticket = Ticket::create([
                'campaign_name'  => $payload['campaign_name'] ?? null,
                'full_name'      => $payload['full_name'],
                'email'          => $payload['email'] ?? null,
                'phone_number'   => $phone,

                'user_id'        => $assignee->id,         // assigned_to required
                'source_id'      => $source->id,
                'status_id'      => $statusNew->id,
                'assigner_id'    => $actor->id,

                'method'         => 'Manual',
                'preferred_time' => $payload['preferred_time'] ?? null,
                'remarks'        => $payload['remarks'] ?? null,
            ]);

            $dupLead = Ticket::where('phone_number', $phone)
                ->where('phone_number', '!=', '')
                ->where('id', '!=', $ticket->id)
                ->first();

            if ($dupLead) {
                $ticket->update([
                    'status_id' => $duplicatedStatus->id,
                    'user_id'   => null,
                ]);

                return new LeadCreateResult(
                    lead: $ticket->fresh(),
                    isDuplicated: true,
                    initialPath: null
                );
            }

            $path = TicketPath::create([
                'next_user'   => $assignee->id,
                'next_status' => $statusNew->id,
                'ticket_id'   => $ticket->id,
                'comment'     => 'Initial ticket creation.',
            ]);

            return new LeadCreateResult(
                lead: $ticket->fresh(),
                isDuplicated: false,
                initialPath: $path
            );
        });
    }
}
