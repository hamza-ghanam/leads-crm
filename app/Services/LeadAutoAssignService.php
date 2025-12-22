<?php

namespace App\Services;

use App\Facades\Notifier;
use App\Helpers\LeadsHelper;
use App\Models\Ticket;
use App\Models\TicketPath;
use App\Models\Status;
use App\Models\User;
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
     * @param int $statusId
     *     The status ID to filter tickets by, based on latestPath.next_status
     *     (e.g. Status::NO_ANSWER, Status::WAITING).
     *
     * @param int $nextStatusId
     *     The status ID to apply when a ticket is successfully re-assigned
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
     * @param \Illuminate\Support\Collection $statusMap
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
        int $statusId,
        int $nextStatusId,
            $statusPeriod,
            $superAdmins,
            $statusMap
    )
    {
        $roles = ['sale', 'tele-sale'];

        $superAdmins = $superAdmins instanceof Collection ? $superAdmins : collect($superAdmins);
        $superAdminsEmails = $superAdmins->pluck('email')->filter()->unique()->values()->all();

        $deadId = $statusMap->get(Status::DEAD);
        if (!$deadId) {
            throw new \RuntimeException('Status DEAD not found in DB.');
        }

        $cutoff = now()->sub($this->parsePeriodToInterval($statusPeriod));

        $statusName = $statusMap->search($statusId);
        $statusName = $statusName !== false ? $statusName : null;

        $nextStatusName = $statusMap->search($nextStatusId);
        $nextStatusName = $nextStatusName !== false ? $nextStatusName : null;

        $nfStatusIds = $statusMap->only([
            Status::NEW,
            Status::FOLLOW_UP
        ])->values()->all();

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

        // Tickets whose latestPath is in $statusId and older than cutoff
        Ticket::query()
            ->where('status_id', $statusId)
            ->whereHas('latestPath', function ($q) use ($statusId, $cutoff) {
                $q->where('next_status', $statusId)
                    ->where('created_at', '<=', $cutoff);
            })
            ->with(['latestPath', 'user', 'paths'])
            ->chunkById(200, function ($tickets) use (
                $nextStatusName,
                $statusName,
                &$stats, &$index, $userIds, $userCount,
                $statusId, $nextStatusId, $deadId,
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
                            $statusName,
                            $ticket, $deadId, $statusId,
                            $superAdmins, $superAdminsEmails
                        ) {
                            $prevUserId = $ticket->user_id;
                            $prevStatusId = $ticket->status_id;

                            $ticket->update(['status_id' => $deadId]);

                            TicketPath::create([
                                'ticket_id' => $ticket->id,
                                'prev_user' => $prevUserId,
                                'next_user' => $prevUserId,
                                'prev_status' => $prevStatusId,
                                'next_status' => $deadId,
                                'comment' => "Auto moved to DEAD: exhausted assignment pool for this lead (status: {$statusName}).",
                            ]);

                            $data = [
                                'title' => 'Dead Lead',
                                'message' => "Lead moved to DEAD automatically because all eligible assignees were previously assigned (from status: {$statusName}).",
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
                        $statusName,
                        $ticket, $assignedUserId, $nextStatusId, $statusId,
                        $users, $superAdmins, $superAdminsEmails
                    ) {
                        $prevUserId = $ticket->user_id;
                        $prevStatusId = $ticket->status_id;

                        $ticket->update([
                            'user_id' => $assignedUserId,
                            'status_id' => $nextStatusId,
                        ]);

                        $tp = TicketPath::create([
                            'ticket_id' => $ticket->id,
                            'prev_user' => $prevUserId,
                            'next_user' => $assignedUserId,
                            'prev_status' => $prevStatusId,
                            'next_status' => $nextStatusId,
                            'comment' => "Auto re-assigned from status: {$statusName}.",
                        ]);

                        // Notify assigned user
                        $assignedUser = $users->firstWhere('id', $assignedUserId) ?? User::find($assignedUserId);
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
                            'message' => "Lead #{$ticket->id} was auto re-assigned (from status: {$statusName} to status: {$nextStatusName}).",
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
            return \Carbon\CarbonInterval::days($period);
        }

        if (is_string($period)) {
            $p = strtolower(trim($period));

            // "12h", "12hr", "12 hours"
            if (preg_match('/^(\d+)\s*(h|hr|hrs|hour|hours)$/', $p, $m)) {
                return \Carbon\CarbonInterval::hours((int)$m[1]);
            }

            // "7d", "7 day", "7 days"
            if (preg_match('/^(\d+)\s*(d|day|days)$/', $p, $m)) {
                return \Carbon\CarbonInterval::days((int)$m[1]);
            }

            // "36" (string number) => days default
            if (ctype_digit($p)) {
                return \Carbon\CarbonInterval::days((int)$p);
            }
        }

        // fallback
        return \Carbon\CarbonInterval::days(7);
    }
}
