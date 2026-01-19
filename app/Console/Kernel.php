<?php

namespace App\Console;

use App\Facades\Notifier;
use App\Helpers\LeadsHelper;
use App\Models\GeneralSettings;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\User;
use App\Services\LeadAutoAssignService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
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
        $statusesByName = Status::all()->keyBy('name');

        $leadsHelper = app()->make(LeadsHelper::class);
        $assignService = new LeadAutoAssignService(app(LeadsHelper::class));

        ////// 1. Notification for Follow-up Reminder
        $schedule->call(function () use ($leadsHelper) {
            if (!$this->withinWorkingWindow()) {
                return;
            }

            $statusesByName = Cache::remember('statuses_by_name', 300, function () {
                return Status::query()
                    ->select(['id', 'name', 'duration']) // زِد الحقول اللي تحتاجها
                    ->get()
                    ->keyBy('name');
            });

            $followUp = $statusesByName->get(Status::FOLLOW_UP);
            if (!$followUp) {
                return;
            }

            $followUpStatusId = $followUp->id;

            Ticket::where('status_id', $followUpStatusId)
                ->whereHas('latestPath', function ($q) {
                    $q->whereNotNull('reminder_at')
                        ->where('reminder_at', '<=', now())
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
                      ى      "You have a follow up on ticket #{$ticket->id}",
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
        $schedule->call(function () use ($leadsHelper) {
            if (!$this->withinWorkingWindow()) {
                return;
            }

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

                [$jrStats, $srStats, $processedLeadKeys] = $leadsHelper->initiateImport($leads);

                DB::table('logs')->insert([
                    'text'  => json_encode($jrStats, JSON_THROW_ON_ERROR),
                    'level' => 'info',
                ]);

                DB::table('logs')->insert([
                    'text'  => json_encode($srStats, JSON_THROW_ON_ERROR),
                    'level' => 'info',
                ]);

                DB::table('logs')->insert([
                    'text'  => json_encode($processedLeadKeys, JSON_THROW_ON_ERROR),
                    'level' => 'info',
                ]);

                $leadIds = array_column($leads, 'number');
                DB::table('logs')->insert([
                    'text'  => json_encode($leadIds, JSON_THROW_ON_ERROR),
                    'level' => 'info',
                ]);

                $leadsHelper->removeTempLeads($leadIds);
            }
        })->everyThirtyMinutes();

        ////// 3. Sales Leads - Statuses
        $schedule->call(function () use ($assignService) {
            if (!$this->withinWorkingWindow()) {
                return;
            }

            $superAdmins = User::role('super-admin')
                ->where('status', 'permitted')
                ->get(['id', 'email']);

            $statusesByName = Cache::remember('statuses_by_name', 300, function () {
                return Status::query()
                    ->select(['id', 'name', 'duration']) // زِد الحقول اللي تحتاجها
                    ->get()
                    ->keyBy('name');
            });

            $statusesToProcess = $statusesByName->filter(function ($s) {
                if ($s->name === Status::DEAD) {
                    return false;
                }

                $d = strtolower(trim((string)$s->duration));

                if ($d === '') {
                    return false;
                }

                if (preg_match('/^0+\s*[mhdw]$/', $d)) {
                    return false;
                }

                if ($d === '0') return false;

                if (!preg_match('/^\d+\s*[mhdw]$/', $d)) return false;

                return true;
            });

            foreach ($statusesToProcess as $status) {
                $assignService->autoReassignFromStatus(
                    $status->name,
                    Status::NEW,
                    $status->duration,
                    $superAdmins,
                    $statusesByName
                );
            }


            /*
            //// 3.1. Status: NEW
            $newStatus = $statusesByName->get(Status::NEW);
            if (!$newStatus) {
                return;
            }

            $assignService->autoReassignFromStatus(
                Status::NEW,
                Status::NEW,
                $newStatus->duration,
                $superAdmins,
                $statusesByName
            );

            //// 3.2. Status: Follow Up
            $followUpStatus = $statusesByName->get(Status::FOLLOW_UP);
            if (!$followUpStatus) {
                return;
            }

            $assignService->autoReassignFromStatus(
                Status::FOLLOW_UP,
                Status::NEW,
                $followUpStatus->duration,
                $superAdmins,
                $statusesByName
            );

            //// 3.3. Status: Meeting
            $meetingStatus = $statusesByName->get(Status::MEETING);
            if (!$meetingStatus) {
                return;
            }

            $assignService->autoReassignFromStatus(
                Status::MEETING,
                Status::NEW,
                $meetingStatus->duration,
                $superAdmins,
                $statusesByName
            );

            //// 3.4. Status: Waiting
            $waitingStatus = $statusesByName->get(Status::WAITING);
            if (!$waitingStatus) {
                return;
            }

            $assignService->autoReassignFromStatus(
                Status::WAITING,
                Status::NEW,
                $waitingStatus->duration,
                $superAdmins,
                $statusesByName
            );


            //// 3.5. Status: No-Answer
            $noAnswerStatus = $statusesByName->get(Status::NO_ANSWER);
            if (!$noAnswerStatus) {
                return;
            }

            $assignService->autoReassignFromStatus(
                Status::NO_ANSWER,
                Status::NEW,
                $noAnswerStatus->duration,
                $superAdmins,
                $statusesByName
            );
            */


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
        })->everyTenMinutes()
            ->name('tickets:auto-reassign')
            ->withoutOverlapping();

        /*
        $schedule->call(function () {
            if (!$this->withinWorkingWindow()) {
                return;
            }

            DB::table('logs')->insert([
                'text' => 'Start time: ' . now()->toDateTimeString() . ' - ' . 'Test',
                'level' => 'info',
            ]);
        })->everyMinute()
            ->name('tickets:testassign')
            ->withoutOverlapping();
        */
    }

    protected function withinWorkingWindow(): bool
    {
        $settings = Cache::remember('general_settings_window', 60, function () {
            return GeneralSettings::whereIn('name', ['start_time', 'end_time', 'working_days'])
                ->pluck('value', 'name')
                ->toArray();
        });

        $start = $settings['start_time'] ?? '09:00';
        $end = $settings['end_time'] ?? '19:00';

        if ($start === $end) {
            return false; // أو true إذا بدك 24/7
        }

        $workingDays = json_decode($settings['working_days'] ?? '[]', true) ?: [];
        if (empty($workingDays)) {
            $workingDays = ['sat', 'sun', 'mon', 'tue', 'wed', 'thu', 'fri'];
        }

        $now = now();
        $map = ['Mon' => 'mon', 'Tue' => 'tue', 'Wed' => 'wed', 'Thu' => 'thu', 'Fri' => 'fri', 'Sat' => 'sat', 'Sun' => 'sun'];

        $today = Carbon::today();
        $begin = $today->copy()->setTimeFromTimeString($start);
        $finish = $today->copy()->setTimeFromTimeString($end);

        // نفس اليوم
        if ($finish->greaterThan($begin)) {
            $todayKey = $map[$now->format('D')] ?? null;
            if (!$todayKey || !in_array($todayKey, $workingDays, true)) return false;

            // استبعاد النهاية
            return $now->greaterThanOrEqualTo($begin) && $now->lessThan($finish);
        }

        // يقطع منتصف الليل
        $finish->addDay();

        $endToday = $today->copy()->setTimeFromTimeString($end);

        if ($now->lessThan($endToday)) {
            $begin->subDay();
            $effectiveDayKey = $map[$now->copy()->subDay()->format('D')] ?? null;
        } else {
            $effectiveDayKey = $map[$now->format('D')] ?? null;
        }

        if (!$effectiveDayKey || !in_array($effectiveDayKey, $workingDays, true)) return false;

        return $now->greaterThanOrEqualTo($begin) && $now->lessThan($finish);
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
