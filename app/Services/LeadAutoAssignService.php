<?php

namespace App\Services;

use App\Facades\Notifier;
use App\Helpers\LeadsHelper;
use App\Models\Ticket;
use App\Models\TicketPath;
use App\Models\Status;
use App\Models\User;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class LeadAutoAssignService
{
    protected $leadsHelper;

    public function __construct(LeadsHelper $leadsHelper)
    {
        $this->leadsHelper = $leadsHelper;
    }

    /**
     * Automatically re-assign tickets that stayed in a given status longer than a defined period.
     *
     * The method applies a round-robin assignment across sales / tele-sales users,
     * skips users who were previously assigned to the ticket (based on TicketPath history),
     * and moves the ticket to DEAD if no eligible users remain.
     *
     * @param string $currentStatusName
     *     The status name to filter tickets by, based on latestPath.next_status
     *     (e.g. Status::NO_ANSWER, Status::WAITING).
     *
     * @param string $nextStatusName
     *     The status name to apply when a ticket is successfully re-assigned
     *     (e.g. Status::NEW, Status::FOLLOW_UP).
     *
     * @param int|string|\DateInterval $statusPeriod
     *     The period the ticket must remain in the given status before processing.
     *     Supported formats:
     *       - int            → days (e.g. 7 = 7 days)
     *       - "7d", "7 days"
     *       - "12h", "12 hours"
     *       - DateInterval   → passed directly
     *
     * @param \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection|array $superAdmins
     *     A collection or array of User models that should receive administrative
     *     notifications and emails when tickets are re-assigned or moved to DEAD.
     *
     * @param \Illuminate\Support\Collection $statusesByName
     *     A collection mapping status names to IDs, typically:
     *     Status::pluck('id', 'name')
     *     Example:
     *       ['New' => 1, 'Follow-up' => 2, 'Dead' => 6]
     *
     * @return array{
     *     reassigned: int,
     *     dead: int,
     *     skipped: int
     * }
     *     Returns counters describing how many tickets were re-assigned,
     *     moved to DEAD, or skipped during execution.
     */
    public function autoReassignFromStatus(
        string                   $currentStatusName,
        string                   $nextStatusName,
        int|string|\DateInterval $statusPeriod,
        Collection|array         $superAdmins,
        Collection               $statusesByName
    ): array
    {
        $roles = ['sale', 'tele-sale'];

        $superAdmins = $superAdmins instanceof Collection ? $superAdmins : collect($superAdmins);
        $superAdminsEmails = $superAdmins->pluck('email')->filter()->unique()->values()->all();

        $cutoff = now()->sub($this->parsePeriodToInterval($statusPeriod));

        $currentStatus = $statusesByName->get($currentStatusName);
        $nextStatus = $statusesByName->get($nextStatusName);
        $deadStatus = $statusesByName->get(Status::DEAD);

        if (!$currentStatus || !$nextStatus || !$deadStatus) {
            // Log...
            return ['reassigned' => 0, 'dead' => 0, 'skipped' => 0];
        }

        $nfStatusIds = $statusesByName
            ->only([Status::NEW, Status::FOLLOW_UP])
            ->pluck('id')
            ->all();

        $users = User::role($roles)
            ->withCount([
                'tickets as nf_tickets_count' => function ($q) use ($nfStatusIds) {
                    $q->whereIn('status_id', $nfStatusIds);
                }
            ])
            ->orderBy('nf_tickets_count', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'email', 'name']);

        $userIds = array_values($users->pluck('id')->all());
        $userCount = count($userIds);

        $stats = ['reassigned' => 0, 'dead' => 0, 'skipped' => 0];

        if ($userCount === 0) {
            return $stats;
        }

        $index = 0;

        // Tickets whose latestPath is in $currentStatus->id and older than cutoff
        Ticket::query()
            ->where('status_id', $currentStatus->id)
            ->whereHas('latestPath', function ($q) use ($currentStatus, $cutoff) {
                $q->where('next_status', $currentStatus->id)
                    ->where('created_at', '<=', $cutoff);
            })
            ->with(['latestPath', 'user', 'paths'])
            ->chunkById(200, function ($tickets) use (
                $nextStatusName,
                $currentStatusName,
                &$stats, &$index, $userIds, $userCount,
                $currentStatus, $nextStatus, $deadStatus,
                $users, $superAdmins, $superAdminsEmails
            ) {
                foreach ($tickets as $ticket) {
                    // users previously assigned (next_user) in history
                    $usedUserIds = $ticket->paths
                        ->pluck('next_user')
                        ->filter()
                        ->map(function ($v) {
                            return (int)$v;
                        })
                        ->unique()
                        ->values()
                        ->all();


                    // round-robin pick, skip used
                    $attempts = 0;
                    $assignedUserId = null;

                    while ($attempts < $userCount) {
                        $candidateUserId = (int)$userIds[$index % $userCount];
                        $index++;
                        $attempts++;

                        if (in_array($candidateUserId, $usedUserIds, true)) {
                            continue;
                        }

                        $assignedUserId = $candidateUserId;
                        break;
                    }

                    // If all are used -> DEAD
                    if (!$assignedUserId) {
                        DB::transaction(function () use (
                            $currentStatusName,
                            $ticket, $deadStatus, $currentStatus,
                            $superAdmins, $superAdminsEmails
                        ) {
                            $prevUserId = $ticket->user_id;
                            $prevStatusId = $ticket->status_id;

                            $ticket->update(['status_id' => $deadStatus->id]);

                            TicketPath::create([
                                'ticket_id' => $ticket->id,
                                'prev_user' => $prevUserId,
                                'next_user' => $prevUserId,
                                'prev_status' => $prevStatusId,
                                'next_status' => $deadStatus->id,
                                'comment' => "Auto moved to DEAD: exhausted assignment pool for this lead (status: {$currentStatusName}).",
                            ]);

                            $data = [
                                'title' => 'Dead Lead',
                                'message' => "Lead moved to DEAD automatically because all eligible assignees were previously assigned (from status: {$currentStatusName}).",
                                'user' => 'System',
                                'ticket' => $ticket->id,
                            ];

                            // Super admins: mail + notify
                            if (!empty($superAdminsEmails)) {
                                // $this->leadsHelper->sendLeadMail($superAdminsEmails, $data);
                            }

                            Notifier::notifyMany(
                                $superAdmins->pluck('id')->all(),
                                'Dead Lead',
                                "Lead #{$ticket->id} is now DEAD!",
                                route('tickets.show', $ticket->id),
                                'ticket_dead',
                                ['ticket_id' => $ticket->id],
                                null
                            );

                            // Ticket owner (withTrashed)
                            if ($ticket->user) {
                                $data['user'] = '';
                                // $this->leadsHelper->sendLeadMail($ticket->user->email, $data);

                                Notifier::notifyUser(
                                    $ticket->user,
                                    'Dead Lead',
                                    "Lead #{$ticket->id} is now DEAD!",
                                    route('tickets.show', $ticket->id),
                                    'ticket_dead',
                                    ['ticket_id' => $ticket->id],
                                    null
                                );
                            }
                        });

                        $stats['dead']++;
                        continue;
                    }

                    // Re-assign + move status to nextStatusId (as you requested)
                    DB::transaction(function () use (
                        $nextStatusName,
                        $currentStatusName,
                        $ticket, $assignedUserId, $nextStatus, $currentStatus,
                        $users, $superAdmins, $superAdminsEmails
                    ) {
                        $prevUserId = $ticket->user_id;
                        $prevStatusId = $ticket->status_id;

                        $ticket->update([
                            'user_id' => $assignedUserId,
                            'status_id' => $nextStatus->id,
                        ]);

                        $tp = TicketPath::create([
                            'ticket_id' => $ticket->id,
                            'prev_user' => $prevUserId,
                            'next_user' => $assignedUserId,
                            'prev_status' => $prevStatusId,
                            'next_status' => $nextStatus->id,
                            'comment' => "Auto re-assigned from status: {$currentStatusName}.",
                        ]);

                        // Notify assigned user
                        $assignedUser = $users->firstWhere('id', $assignedUserId);
                        if ($assignedUser) {
                            $data = [
                                'title' => 'New Lead!',
                                'message' => 'A new lead has been assigned to you.',
                                'user' => '',
                                'ticket' => $ticket->id,
                            ];

                            //$this->leadsHelper->sendLeadMail($assignedUser->email, $data);

                            Notifier::notifyUser(
                                $assignedUser,
                                'New Lead',
                                "Lead #{$ticket->id} has been assigned to you!",
                                route('tickets.show', $ticket->id),
                                'ticket_new',
                                ['ticket_id' => $ticket->id],
                                null
                            );
                        }

                        $adminData = [
                            'title' => 'Lead Re-assigned',
                            'message' => "Lead #{$ticket->id} was auto re-assigned (from status: {$currentStatusName} to status: {$nextStatusName}).",
                            'user' => 'System',
                            'ticket' => $ticket->id,
                        ];

                        if (!empty($superAdminsEmails)) {
                            // $this->leadsHelper->sendLeadMail($superAdminsEmails, $adminData);
                        }

                        Notifier::notifyMany(
                            $superAdmins->pluck('id')->all(),
                            'Lead Re-assigned',
                            "Lead #{$ticket->id} re-assigned automatically.",
                            route('tickets.show', $ticket->id),
                            'ticket_reassigned',
                            ['ticket_id' => $ticket->id],
                            null
                        );
                    });

                    $stats['reassigned']++;
                }
            });
        return $stats;
    }

    /**
     * Parse days/hours period into a Carbon interval string accepted by now()->sub().
     */
    private function parsePeriodToInterval($period): \DateInterval
    {
        // int => days (default)
        if (is_int($period)) {
            return CarbonInterval::days($period);
        }

        if (is_string($period)) {
            $p = strtolower(trim($period));

            // "12h", "12hr", "12 hours"
            if (preg_match('/^(\d+)\s*(h|hr|hrs|hour|hours)$/', $p, $m)) {
                return CarbonInterval::hours((int)$m[1]);
            }

            // "7d", "7 day", "7 days"
            if (preg_match('/^(\d+)\s*(d|day|days)$/', $p, $m)) {
                return CarbonInterval::days((int)$m[1]);
            }

            // "36" (string number) => days default
            if (ctype_digit($p)) {
                return CarbonInterval::days((int)$p);
            }
        }

        // fallback
        return CarbonInterval::days(7);
    }
}
