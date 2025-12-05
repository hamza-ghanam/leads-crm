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

        $dateBegin = now()->copy()->setTime($startingHour, 0, 0);
        $dateEnd = now()->copy()->setTime($endingHour, 0, 0);

        $pullDate = now();

        $leadsHelper = app()->make(LeadsHelper::class);


        ////// 1. Notification for Follow-up Reminder
        $schedule->call(function () use ($leadsHelper) {
            $now = now();
            $followUpStatusId = Status::where('name', Status::FOLLOW_UP)->value('id');

            if (!$followUpStatusId) {
                // لو ما في هيك حالة، لا تعمل شيء
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
            if (!($pullDate->between($dateBegin, $dateEnd))) {
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
        $schedule->call(function () use ($leadsHelper, $dateBegin, $dateEnd, $pullDate) {
            if (!($pullDate->between($dateBegin, $dateEnd))) {
                return;
            }


            //// 3.1. Status: NEW
            $newStausInterval = 3; // 1 Hour

            // re-shuffled has been removed 12/9/2022
            $statusMap = Status::whereIn('slug', ['new', 'follow-up'])
                ->pluck('id', 'slug');   // ['new' => 1, 'follow-up' => 2]

            $newStatusId = $statusMap['new'] ?? null;
            if (!$newStatusId) {
                return;
            }

            $tickets = Ticket::where('status_id', $newStatusId)
                ->whereHas('paths')
                ->with(['user', 'latestPath'])
                ->get();

            // WRS AE | Send to archive
            $newIntervalLimit = now()->subHours($newStausInterval);

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

            /** Stopped at 9/11/022 */
            //// 3.2. Status: Follow Up (7 days)
            $followUpDays = 7;
            $followUpStatus = Status::where('slug', 'follow-up')->first()->id;
            //$tickets = Ticket::where('status_id', $followUpStatus)->get();

            ///// Stopped /////
            $tickets = collect([]);

            $statuses = Status::whereIn('slug', ['new', 'follow-up'])
                ->get()
                ->pluck('id')
                ->toArray();

            /** New method 11/9/2022 */
            $salesEmps = User::role('sale')
                ->where('status', 'permitted')
                ->get();

            $salesLeadCounts = Ticket::whereIn('status_id', $statuses)
                ->whereIn('user_id', $salesEmps->pluck('id'))
                ->select('user_id', DB::raw('COUNT(*) as cnt'))
                ->groupBy('user_id')
                ->pluck('cnt', 'user_id')       // [user_id => count]
                ->sort()                        // sort by count asc
                ->keys()                        // keep only user_ids
                ->toArray();

            // Distribution
            $startPos = 0;
            foreach ($tickets as $key => $ticket) {
                $tPath = TicketPath::where('ticket_id', $ticket->id)
                    ->where('next_status', $followUpStatus)
                    ->orderBy('updated_at', 'DESC')
                    ->first();

                $startTime = Carbon::createFromFormat('Y-m-d H:s:i', $tPath->updated_at);
                $endTime = Carbon::now();
                $diffDays = $startTime->diffInDays($endTime);

                if ($diffDays >= $followUpDays) {
                    $oldUser = User::find($ticket->user_id);

                    // Lead now is with Sales => tele-sales
                    if ($oldUser->getRoleNames()[0] === 'sale' || $oldUser->getRoleNames()[0] === 'sales-senior') {
                        $ticket->user_id = $salesLeadCounts[$startPos];
                        $startPos++;
                    } // Lead is now with tele-sales => tele-sales manager
                    else if ($oldUser->getRoleNames()[0] === 'tele-sale') {
                        $salesManager = User::find($oldUser->manager_id);
                        $ticket->user_id = $salesManager->id;
                    }

                    $ticket->status_id = $newStatusId;
                    $ticket->save();

                    $ticketPath = TicketPath::create([
                        'prev_user' => $oldUser->id,
                        'next_user' => $salesLeadCounts[$startPos],
                        'prev_status' => $followUpStatus,
                        'next_status' => $newStatusId,
                        'ticket_id' => $ticket->id,
                        'comment' => 'Back from FOLLOW-UP after 15 days inactive.',
                    ]);

                    $ticketPath->save();

                    $data = [
                        'title' => 'Back from FOLLOW-UP',
                        'message' => 'Lead has been automatically returned from FOLLOW-UP to NEW after 15 days of inactivity: ',
                        'user' => $ticket->user->name,
                        'ticket' => $ticket->id
                    ];

                    $superAdmins = User::role('super-admin')
                        ->where('status', 'permitted')
                        ->get();

                    if ($superAdmins->isEmpty()) {
                        return;
                    }

                    $sAdminsEmails = $superAdmins->pluck('email')->toArray();

                    if (empty($sAdminsEmails)) {
                        return;
                    }

                    $leadsHelper->sendLeadMail($sAdminsEmails, $data);

                    Notifier::notifyMany(
                        $superAdmins,
                        'Back from FOLLOW-UP',
                        "Lead #{$ticket->id} has been automatically returned from FOLLOW-UP to NEW after 15 days of inactivity.",
                        route('tickets.show', $ticket->id),
                        'ticket_follow_up',
                        ['ticket_id' => $ticket->id],
                        null
                    );

                    // New User
                    $data = [
                        'title' => 'Back from FOLLOW-UP',
                        'message' => 'Lead has been automatically returned from FOLLOW-UP to NEW after 15 days of inactivity: ',
                        'user' => '',
                        'ticket' => $ticket->id
                    ];

                    $leadsHelper->sendLeadMail($ticket->user->email, $data);

                    Notifier::notifyUser(
                        $ticket->user,
                        'Back from FOLLOW-UP',
                        "Lead #{$ticket->id} has been automatically returned from FOLLOW-UP.",
                        route('tickets.show', $ticket->id),
                        'ticket_follow_up',
                        ['ticket_id' => $ticket->id],
                        null
                    );

                    // Old User
                    $data = [
                        'title' => 'Withdrawn Lead',
                        'message' => 'Lead has been automatically withdrawn from you after 15 days of inactivity, and assigned to another sales!',
                        'user' => '',
                        'ticket' => $ticket->id
                    ];

                    $leadsHelper->sendLeadMail($oldUser->email, $data);

                    Notifier::notifyUser(
                        $oldUser,
                        'Back from FOLLOW-UP',
                        "Lead #{$ticket->id} has been automatically withdrawn from you!",
                        route('tickets.show', $ticket->id),
                        'ticket_follow_up',
                        ['ticket_id' => $ticket->id],
                        null
                    );

                    $startPos++;
                    if ($startPos === count($salesLeadCounts)) {
                        $startPos = 0;
                    }
                }
            }

            //// 3.3. Status: Meeting (7 days)
            $meetingDays = 7;
            $meetingStatus = Status::where('slug', 'meeting')->first()->id;
            $tickets = Ticket::where('status_id', $meetingStatus)->get();

            /** New method 11/9/2022 */
            $salesEmps = User::role('tele-sale')
                ->where('status', 'permitted')
                ->get();

            $salesLeadCounts = [];
            foreach ($salesEmps as $key => $teleSale) {
                $tCount = Ticket::where('user_id', $teleSale->id)
                    ->whereIn('status_id', $statuses)
                    ->count();
                $salesLeadCounts += [$teleSale->id => $tCount];
            }
            asort($salesLeadCounts);
            $salesLeadCounts = array_keys($salesLeadCounts);

            // Junior Distribution
            $startPos = 0;
            foreach ($tickets as $key => $ticket) {
                $tPath = TicketPath::where('ticket_id', $ticket->id)
                    ->where('next_status', $meetingStatus)
                    ->orderBy('updated_at', 'DESC')
                    ->first();

                $startTime = Carbon::createFromFormat('Y-m-d H:s:i', $tPath->updated_at);
                $endTime = Carbon::now();
                $diffDays = $startTime->diffInDays($endTime);
                if ($diffDays >= $meetingDays) {
                    $oldUser = User::find($ticket->user_id);

                    // Lead now is with Sales => tele-sales
                    if ($oldUser->getRoleNames()[0] === 'sale' || $oldUser->getRoleNames()[0] === 'sales-senior') {
                        $ticket->user_id = $salesLeadCounts[$startPos];
                        $startPos++;
                    } // Lead is now with tele-sales => tele-sales manager
                    else if ($oldUser->getRoleNames()[0] === 'tele-sale') {
                        $salesManager = User::find($oldUser->manager_id);
                        $ticket->user_id = $salesManager->id;
                    }

                    $ticket->status_id = $newStatusId;
                    $ticket->save();
                    $ticketPath = TicketPath::create([
                        'prev_user' => $tPath->next_user,
                        'next_user' => $salesLeadCounts[0],
                        'prev_status' => $meetingStatus,
                        'next_status' => $newStatusId,
                        'ticket_id' => $ticket->id,
                        'comment' => 'Back from MEETING after 7 days inactive.',
                    ]);

                    $ticketPath->save();

                    ////// Notify Users
                    // Super admin
                    $data = [
                        'title' => 'Back from MEETING',
                        'message' => 'Lead has been automatically returned from MEETING to NEW after 7 days of inactivity: ',
                        'user' => $tPath->next_user->name,
                        'ticket' => $ticket->id
                    ];

                    $leadsHelper->sendLeadMail($sAdminsEmails, $data);

                    Notifier::notifyMany(
                        $superAdmins,
                        'Back from MEETING',
                        "Lead #{$ticket->id} has been automatically returned from MEETING.",
                        route('tickets.show', $ticket->id),
                        'ticket_follow_up',
                        ['ticket_id' => $ticket->id],
                        null
                    );

                    // New User
                    $data = ['title' => 'Back from MEETING', 'message' => 'Lead has been automatically returned from MEETING to NEW after 7 days of inactivity: ', 'user' => '', 'ticket' => $ticket->id];
                    $leadsHelper->sendLeadMail($ticket->user->email, $data);

                    Notifier::notifyUser(
                        $ticket->user,
                        'Back from MEETING',
                        "Lead #{$ticket->id} has been automatically returned from MEETING.",
                        route('tickets.show', $ticket->id),
                        'ticket_follow_up',
                        ['ticket_id' => $ticket->id],
                        null
                    );

                    // Old User
                    $data = ['title' => 'Withdrawn Lead', 'message' => 'Lead has been automatically withdrawn from you!', 'user' => '', 'ticket' => $ticket->id];
                    $leadsHelper->sendLeadMail($oldUser->email, $data);

                    Notifier::notifyUser(
                        $oldUser,
                        'Withdrawn Lead',
                        "Lead #{$ticket->id} has been automatically withdrawn from you!",
                        route('tickets.show', $ticket->id),
                        'ticket_follow_up',
                        ['ticket_id' => $ticket->id],
                        null
                    );

                    $startPos++;
                    if ($startPos === count($salesLeadCounts)) {
                        $startPos = 0;
                    }
                }
            }

            //// 04. Status: Waiting (7 days)
            $waitingDays = 7;
            $waitingStatus = Status::where('slug', 'waiting')->first()->id;
            $tickets = Ticket::where('status_id', $waitingStatus)->get();

            foreach ($tickets as $key => $ticket) {
                $tPath = TicketPath::where('ticket_id', $ticket->id)
                    ->where('next_status', $waitingStatus)
                    ->orderBy('updated_at', 'DESC')
                    ->first();
                $startTime = Carbon::createFromFormat('Y-m-d H:s:i', $tPath->updated_at);
                $endTime = Carbon::now();
                $diffDays = $startTime->diffInDays($endTime);

                if ($diffDays >= $waitingDays) {
                    $deadStatus = Status::where('slug', 'dead')->first()->id;
                    $ticket->status_id = $deadStatus;
                    $ticket->save();
                    $ticketPath = TicketPath::create([
                        'prev_user' => $tPath->next_user,
                        'next_user' => $tPath->next_user,
                        'prev_status' => $waitingStatus,
                        'next_status' => $deadStatus,
                        'ticket_id' => $ticket->id,
                        'comment' => 'After WAITING 7 days, it\'s now DEAD.',
                    ]);
                    $ticketPath->save();

                    /////// Notify Users
                    // Super admin
                    $user = User::find($ticket->user_id);
                    $data = ['title' => 'Dead Lead', 'message' => 'Lead has been automatically moved from WAITING to DEAD after 7 days of inactivity: ', 'user' => auth()->user()->name, 'ticket' => $ticket->id];

                    $leadsHelper->sendLeadMail($sAdminsEmails, $data);

                    Notifier::notifyUser(
                        $superAdmins,
                        'Dead Lead',
                        "Lead #{$ticket->id} is now DEAD!",
                        route('tickets.show', $ticket->id),
                        'ticket_follow_up',
                        ['ticket_id' => $ticket->id],
                        null
                    );

                    // User himself
                    $data = ['title' => 'Dead Lead', 'message' => 'Lead has been automatically moved from WAITING to DEAD after 7 days of inactivity: ', 'user' => '', 'ticket' => $ticket->id];

                    $leadsHelper->sendLeadMail($user->email, $data);

                    Notifier::notifyUser(
                        $user,
                        'Dead Lead',
                        "Lead #{$ticket->id} is now DEAD!",
                        route('tickets.show', $ticket->id),
                        'ticket_follow_up',
                        ['ticket_id' => $ticket->id],
                        null
                    );
                }
            }
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
