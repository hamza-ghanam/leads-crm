<?php

namespace App\Console;

use App\Facades\Notifier;
use App\Helpers\LeadsHelper;
use App\Models\ArchivedLead;
use App\Models\GeneralSettings;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\TicketPath;
use App\Models\User;
use App\Services\LeadAutoAssignService;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $startingHour = 9;
        $endingHour = 19;

        $newStatusPeriod = '3h';
        $followUpPeriod = '15d';
        $meetingPeriod = '7d';
        $waitingPeriod = '30d';
        $noAnswerPeriod = '3d';

        $statusMap = Status::pluck('id', 'name');   // ['new' => 1, 'follow-up' => 2]

        $dateBegin = now()->copy()->setTime($startingHour, 0, 0);
        $dateEnd = now()->copy()->setTime($endingHour, 0, 0);

        $leadsHelper = app()->make(LeadsHelper::class);
        $assignService = new LeadAutoAssignService(app(LeadsHelper::class));

        ////// 1. Notification for Follow-up Reminder
        $schedule->call(function () use ($dateEnd, $dateBegin, $statusMap, $leadsHelper) {
            $now = now();
            if (!($now->between($dateBegin, $dateEnd))) {
                return;
            }

            $followUpStatusId = $statusMap->get(Status::FOLLOW_UP);

            if (!$followUpStatusId) {
                return;
            }

            Ticket::where('status_id', $followUpStatusId)
                ->whereHas('latestPath', function ($q) use ($now) {
                    $q->whereNotNull('reminder_at')
                        ->where('reminder_at', '<=', $now)
                        ->whereNull('reminder_sent_at');
                })
                ->with(['user', 'latestPath'])
                ->chunkById(100, function ($tickets) use ($leadsHelper) {
                    foreach ($tickets as $ticket) {
                        if (!$ticket->user) {
                            continue;
                        }

                        // ✅ إرسال الإيميل + إشعار سطح مكتب عن طريق Notification

                        $data = [
                            'title' => 'Lead Follow-up Reminder!',
                            'message' => 'You have a lead (' . $ticket->id . ') that needs your attention for follow-up.
                                          Please check the system at your earliest convenience.',
                            'user' => $ticket->user->name,
                            'ticket' => $ticket->id
                        ];

                        $leadsHelper->sendLeadMail($ticket->user->email, $data);

                        Notifier::notifyUser(
                            $ticket->user,
                            'Follow up reminder',
                            "You have a follow up on ticket #{$ticket->id}",
                            route('tickets.show', $ticket->id),
                            'ticket_follow_up',
                            ['ticket_id' => $ticket->id],
                            null
                        );

                        $ticket->latestPath->reminder_sent_at = now();
                        $ticket->latestPath->save();
                    }
                });
        })->everyMinute();

        ////// 2. Auto Import from Social Media
        // every 30 minutes, Facebook leads (Updated on: 17/5/2022)
        // Adding TikTok leads, on 13/11/2022
        // Adding Google Ads leads, on 06/04/2025
        $schedule->call(function () use ($leadsHelper, $dateEnd, $dateBegin, $pullDate) {
            $now = now();
            if (!($now->between($dateBegin, $dateEnd))) {
                return;
            }

            ////// New Method (15/05/2022) //////
            ////// Call helper function (03/09/2022)
            ////// 28/11/2024 New Zapier Webhook //////

            $sources = GeneralSettings::where('name', 'like', 'auto_import_%')
                ->where('value', 1)
                ->pluck('value', 'name')
                ->keys()
                ->map(function ($key) {
                    return str_replace('auto_import_', '', $key);
                })
                ->values()
                ->toArray();

            foreach ($sources as $source) {
                $leads = $leadsHelper->fetchLeadsFromZapier($source);
                $leadsHelper->initiateImport($leads);
                $leadIds = array_column($leads, 'key');
                $leadsHelper->removeZapierTempLeads($leadIds);
            }
        })->everyThirtyMinutes();

        ////// 3. Sales Leads - Statuses
        $schedule->call(function () use ($assignService, $noAnswerPeriod, $statusMap, $waitingPeriod, $meetingPeriod, $followUpPeriod, $newStatusPeriod, $dateBegin, $dateEnd, $pullDate) {
            $now = now();
            if (!($now->between($dateBegin, $dateEnd))) {
                return;
            }

            $superAdmins = User::role('super-admin')
                ->where('status', 'permitted')
                ->get();

            $sAdminsEmails = $superAdmins->pluck('email')->toArray();

            //// 3.1. Status: NEW
            $newStatusId = $statusMap->get(Status::NEW);
            if (!$newStatusId) {
                return;
            }

            $assignService->autoReassignFromStatus(
                $newStatusId,
                $newStatusId,
                $newStatusPeriod,
                $superAdmins,
                $statusMap
            );


            /*
            // WRS AE | Send to archive
            $newIntervalLimit = now()->subHours($newStatusPeriod);

            foreach ($tickets as $ticket) {
                $latestPath = $ticket->paths->first();
                if (!$latestPath) {
                    continue;
                }

                // لازم أحدث path يكون next_status = New
                if ((int)$latestPath->next_status !== (int)$newStatusId) {
                    continue;
                }

                // لازم يكون مر عليه intervalLimit ساعة أو أكتر
                if ($latestPath->created_at->gt($newIntervalLimit)) {
                    continue;
                }

                $isDuplicateTicket = Ticket::query()
                    ->where('id', '!=', $ticket->id) // استثني التذكرة الحالية
                    ->where(function ($q) use ($ticket) {
                        $q->when($ticket->phone_number, function ($q) use ($ticket) {
                            $q->where('phone_number', $ticket->phone_number);
                        });

                        $q->when($ticket->email, function ($q) use ($ticket) {
                            $q->orWhere('email', $ticket->email);
                        });
                    })
                    ->exists();

                $isDuplicateArchived = ArchivedLead::query()
                    ->where(function ($q) use ($ticket) {
                        $q->when($ticket->phone_number, function ($q) use ($ticket) {
                            $q->where('phone_number', $ticket->phone_number);
                        });

                        $q->when($ticket->email, function ($q) use ($ticket) {
                            $q->orWhere('email', $ticket->email);
                        });
                    })
                    ->exists();

                $isDuplicate = $isDuplicateTicket || $isDuplicateArchived;

                ArchivedLead::archiveTicket($ticket, $isDuplicate);

                $recipients = User::role('super-admin')
                    ->where('status', 'permitted')
                    ->get(['id', 'email'])
                    ->when($ticket->user && $ticket->user->manager, function ($q) use ($ticket) {
                        return $q->push($ticket->user->manager);
                    })
                    ->unique('id');

                $sAdminsEmails = $recipients->pluck('email')->toArray();

                // 2) Email
                $data = [
                    'title' => 'Archived Lead!',
                    'message' => 'A lead has been archived and need to be re-assigned to a sales employee.',
                    'user' => '',
                    'ticket' => $ticket->id
                ];

                $leadsHelper->sendLeadMail($sAdminsEmails, $data);

                Notifier::notifyMany(
                    $recipients,
                    'Archived Lead',
                    "Lead #{$ticket->id} has been archived!",
                    route('tickets.show', $ticket->id),
                    'ticket_follow_up',
                    ['ticket_id' => $ticket->id],
                    null
                );

                $ticket->delete();
            }
            */

            //// 3.2. Status: Follow Up
            $followUpStatusId = $statusMap->get(Status::FOLLOW_UP);

            $assignService->autoReassignFromStatus(
                $followUpStatusId,
                $newStatusId,
                $followUpPeriod,
                $superAdmins,
                $statusMap
            );

            //// 3.3. Status: Meeting
            $meetingStatusId = $statusMap->get(Status::MEETING);

            $assignService->autoReassignFromStatus(
                $meetingStatusId,
                $newStatusId,
                $meetingPeriod,
                $superAdmins,
                $statusMap
            );

            //// 3.4. Status: Waiting
            $waitingStatusId = $statusMap->get(Status::WAITING);

            $assignService->autoReassignFromStatus(
                $waitingStatusId,
                $newStatusId,
                $waitingPeriod,
                $superAdmins,
                $statusMap
            );


            //// 3.5. Status: No-Answer
            $noAnswerStatusId = $statusMap->get(Status::NO_ANSWER);

            $assignService->autoReassignFromStatus(
                $noAnswerStatusId,
                $newStatusId,
                $noAnswerPeriod,
                $superAdmins,
                $statusMap
            );
        })->everyTenMinutes();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
