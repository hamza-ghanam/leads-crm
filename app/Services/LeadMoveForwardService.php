<?php

namespace App\Services;

use App\Enums\ApiErrorCode;
use App\Helpers\ApiResponse;
use App\Models\Booking;
use App\Models\Meeting;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\TicketPath;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Mpdf\Container\NotFoundException;
use Symfony\Component\CssSelector\Exception\InternalErrorException;
use Symfony\Component\HttpFoundation\Response;

readonly class LeadMoveForwardService
{
    public function __construct(
        private LeadAccess $leadAccess
    ) {}

    /**
     * @return array
     * @throws InternalErrorException
     */
    public function moveForward(User $actor, Ticket $lead, array $payload): array
    {
        // LeadAccess (extra safety: controller already checked visibility)
        // For sales/tele-sales, lead must still belong to them at the moment of action
        $this->leadAccess->assertCanActOnLeadOrFail($actor, $lead);

        $statusId = (int) $payload['status_id'];
        $toStatus = Status::find($statusId);

        if (!$toStatus) {
            throw new NotFoundException();
        }

        // Role-specific restrictions (same as web)
        $this->assertRoleRestrictionsSameAsWeb($actor, $toStatus);

        $slug = (string) $toStatus->slug;

        $isBooking       = stripos($slug, 'book') !== false;
        $isMeeting       = stripos($slug, 'meet') !== false;
        $isFollowUp      = ($slug === 'follow-up');
        $isNotInterested = ($slug === 'not-interested');

        // Determine assignee: if not provided keep current lead assigned_to (web behavior)
        $assignedToId = isset($payload['assigned_to']) && $payload['assigned_to']
            ? (int)$payload['assigned_to']
            : (int)($lead->user_id ?? 0);

        $assignedTo = $assignedToId > 0 ? User::find($assignedToId) : null;
        if (!$assignedTo && !$this->isMgmtOrDeadTarget($toStatus)) {
            // For normal statuses, assignee is required
            throw ValidationException::withMessages(
                ['errors' => ['assigned_to' => ['Assigned to ID is required.']]]
            );
        }

        // Conditional validations (web parity)
        if ($isBooking) {
            $this->requireFields($payload, [
                'client_unit', 'client_price', 'client_project', 'client_developer'
            ]);
        }

        if ($isFollowUp) {
            $this->requireFields($payload, ['reminder_datetime']);
        }

        if ($isMeeting) {
            $this->requireFields($payload, ['meeting_range']);
        }

        try {
            [$freshLead, $freshPath] = DB::transaction(function () use ($actor, $lead, $toStatus, $payload, $assignedTo, $isMeeting, $isFollowUp, $isBooking, $isNotInterested) {

                $meeting = null;

                // Create meeting (if needed)
                if ($isMeeting) {
                    [$start, $end] = $this->parseMeetingRange($payload['meeting_range']);

                    $meeting = Meeting::create([
                        'started_at' => $start->toDateTimeString(),
                        'ended_at' => $end->toDateTimeString(),
                        'method' => 'automatic',
                        'reminder_at' => $start->copy()->subMinutes(30)->toDateTimeString(),
                    ]);
                }

                // Create path record (base)
                $path = TicketPath::create([
                    'prev_user' => $lead->user_id,
                    'next_user' => $assignedTo?->id, // may be overwritten for mgmt statuses
                    'prev_status' => $lead->status_id,
                    'next_status' => $toStatus->id,
                    'ticket_id' => $lead->id,
                    'comment' => (string)$payload['comment'],
                    'reminder_at' => $isFollowUp
                        ? Carbon::parse($payload['reminder_datetime'])
                        : null,
                ]);

                // Decide lead.user_id based on status rules (same as web)
                $deadId = Status::where('slug', 'dead')->value('id');
                $deadTeleId = Status::where('slug', 'dead-tele')->value('id');

                $mgmtStatusIds = Status::whereIn('slug', ['reviewed', 'sold', 'pre-approved', 'rejected'])
                    ->pluck('id')
                    ->toArray();

                if (in_array((int)$toStatus->id, array_filter([(int)$deadId, (int)$deadTeleId]), true)) {
                    $lead->user_id = null;
                } elseif (in_array((int)$toStatus->id, $mgmtStatusIds, true)) {
                    // mgmt statuses assigned to actor
                    $lead->user_id = $actor->id;
                    $path->next_user = $actor->id;
                    $path->save();
                } else {
                    // normal: assign to chosen assigned_to
                    $lead->user_id = $assignedTo?->id;
                }

                // Update lead status
                $lead->status_id = $toStatus->id;
                $lead->save();

                // Link meeting to path
                if ($meeting) {
                    $meeting->ticket_path_id = $path->id;
                    $meeting->save();
                }

                // Not interested => auto-kill (same as web)
                if ($isNotInterested) {
                    $dead = Status::where('slug', 'dead')->first();

                    if ($dead) {
                        TicketPath::create([
                            'prev_user' => $lead->user_id,
                            'next_user' => $lead->user_id,
                            'prev_status' => $toStatus->id,
                            'next_status' => $dead->id,
                            'ticket_id' => $lead->id,
                            'comment' => 'Lead is now DEAD as the client is Not Interested.',
                        ]);

                        $lead->status_id = $dead->id;
                        $lead->save();
                    }
                }

                // Booking creation (same as web)
                if ($isBooking) {
                    Booking::create([
                        'project_name' => $payload['client_project'],
                        'unit_number' => $payload['client_unit'],
                        'price' => $payload['client_price'],
                        'developer_name' => $payload['client_developer'],
                        'user_id' => $actor->id,
                        'ticket_id' => $lead->id,
                    ]);
                }

                // Return fresh lead + latest path
                $lead->load(['user', 'status', 'source', 'assigner']);
                $path->load(['prevUser', 'nextUser', 'prevStatus', 'nextStatus', 'meeting']);

                return [$lead, $path];
            });

            return [
                'lead' => $freshLead,
                'path' => $freshPath
            ];
        } catch (\Throwable $e) {
            //// Log
            throw new InternalErrorException();
        }
    }

    private function assertRoleRestrictionsSameAsWeb(User $actor, Status $toStatus): void
    {
        if ($toStatus->slug === 'approved' && !$actor->hasRole('super-admin')) {
            throw new AuthorizationException();
        }

        if ($actor->hasRole('accountant')) {
            $soldId = Status::whereSlug('sold')->value('id');
            if ($soldId && (int)$toStatus->id !== (int)$soldId) {
                throw new AuthorizationException();
            }
        }

        if ($actor->hasRole('admin')) {
            $reviewedId = Status::whereSlug('reviewed')->value('id');
            if ($reviewedId && (int)$toStatus->id !== (int)$reviewedId) {
                throw new AuthorizationException();
            }
        }
    }

    private function isMgmtOrDeadTarget(Status $toStatus): bool
    {
        return in_array($toStatus->slug, ['reviewed', 'sold', 'pre-approved', 'rejected', 'dead', 'dead-tele'], true);
    }

    private function requireFields(array $payload, array $fields): void
    {
        $errors = [];

        foreach ($fields as $field) {
            if (!isset($payload[$field]) || $payload[$field] === '') {
                $errors[$field] = ["The {$field} field is required."];
            }
        }

        if (!empty($errors)) {
           throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Parse meeting range like "2026-01-10 10:00 - 2026-01-10 10:30"
     */
    private function parseMeetingRange(string $range): array
    {
        $parts = explode(' - ', $range);

        if (count($parts) < 2) {
            throw ValidationException::withMessages([
                'meeting_range' => ['Invalid meeting_range format.']
            ]);
        }

        try {
            $start = Carbon::parse(trim($parts[0]));
            $end = Carbon::parse(trim($parts[1]));
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'meeting_range' => ['Invalid date format.']
            ]);
        }

        if ($end->lessThanOrEqualTo($start)) {
            throw ValidationException::withMessages([
                'meeting_range' => ['End must be after start.']
            ]);
        }

        return [$start, $end];
    }
}
