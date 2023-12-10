<?php

namespace App\Console;

use App\Helpers\LeadsHelper;
use App\Mail\GeneralNotifyMail;
use App\Mail\LeadNotifyMail;
use App\Models\GeneralSettings;
use App\Models\Meeting;
use App\Models\Source;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\TicketPath;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Mail;
use Revolution\Google\Sheets\Facades\Sheets;

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
        // every 30 minutes, Facebook leads (Updated at: 17/5/2022)
        // Adding TikTok leads, at 13/11/2022
        $schedule->call(function () {
            $pullDate = date('Y-m-d H:i:s');
            $dateBegin = date('Y-m-d H:i:s', strtotime(date("Y") . '-' . date("m") . '-' . date("d") . " 10:29:57"));
            $dateEnd = date('Y-m-d H:i:s', strtotime(date("Y") . '-' . date("m") . '-' . date("d") . " 17:00:03"));
            if (!($pullDate >= $dateBegin && $pullDate <= $dateEnd)) {
                return;
                /*
                if ($posts < 20) {
                    return;
                }
                */
            }

            /**** New Method (15/05/2022) ****/
            /**** Call helper function (03/09/2022) ****/
            $leadsHelper = new LeadsHelper();

            // Facebook
            $leads = $leadsHelper->fetchLeadsFromZapier('facebook');
            $leadsHelper->initiateImport($leads);
            $leadsHelper->emptyZapierLeadsSheet('facebook', count($leads));

            // TikTok
            $leads = $leadsHelper->fetchLeadsFromZapier('tiktok');
            $leadsHelper->initiateImport($leads);
            $leadsHelper->emptyZapierLeadsSheet('tiktok', count($leads));

            /* Old Method
            $statuses = Status::whereIn('slug', ['new', 'follow-up', 'meeting'])
                ->get()
                ->pluck('id')
                ->toArray();

            $users = User::role('sale')
                ->whereHas('tickets', function ($query) use ($statuses) {
                    $query->whereIn('status_id', $statuses);
                })
                ->with('tickets')
                ->get();

            $usersNoTickets = User::role('sale')
                ->whereDoesntHave('tickets')
                ->get();

            $users = $users->merge($usersNoTickets);

            $userCounts = [];

            foreach ($users as $key => $user) {
                $userCounts += [$user->id => count($user->tickets)];
            }

            asort($userCounts);
            $userCounts = array_keys($userCounts);

            $startPos = 0;
            foreach ($tickets as $key => $ticket) {
                if ($ticket->status_id === $duplicatedStatus->id) {
                    $ticket->user_id = null;
                    $ticket->save();
                    continue;
                }

                $ticket->user_id = $userCounts[$startPos];
                $startPos++;

                if ($startPos === count($userCounts)) {
                    $startPos = 0;
                }

                $ticket->save();

                $ticketPath = TicketPath::create([
                    'next_user' => $ticket->user_id,
                    'next_status' => $newStatus,
                    'ticket_id' => $ticket->id,
                    'comment' => 'Initial ticket creation.',
                ]);

                $ticketPath->save();

                // Notify Users
                $user = User::find($ticket->user_id);

                // Super admin
                $data = [
                    'title' => 'New Lead',
                    'message' => 'A new lead is automatically assigned to the user: ',
                    'user' => $user->name,
                    'ticket' => $ticket->id
                ];

                $superAdmins = User::role('super-admin')
                    ->get()
                    ->pluck('email')
                    ->toArray();

                Mail::to($superAdmins)->send(new LeadNotifyMail($data));

                // User himself
                $data = [
                    'title' => 'New Lead',
                    'message' => 'A new lead is automatically assigned to you!',
                    'user' => '',
                    'ticket' => $ticket->id
                ];

                Mail::to($user->email)->send(new LeadNotifyMail($data));
            }

            for ($i = 0; $i < count($tickets); $i++) {
                Sheets::spreadsheet(config('sheets.fb_spreadsheet_id'))
                    ->sheet(config('sheets.fb_sheet_id'))
                    ->range('A' . ($i + 2))
                    ->update([['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '']]);
            }
            */
        })->everyThirtyMinutes();

        /// Ban users in meeting, 5 mins.
        // Stopped 16/5/2022
        /*
        $schedule->call(function () {
            $meetingStatus = Status::whereSlug('meeting')->first();
            $meetingTickets = Ticket::whereStatusId($meetingStatus->id)->get();

            foreach ($meetingTickets as $key => $meetingTicket) {
                $tPath = TicketPath::whereTicketId($meetingTicket->id)
                    ->whereNextStatus($meetingStatus->id)
                    ->orderBy('updated_at', 'DESC')
                    ->first();

                $beginDate = strtotime($tPath->meeting->started_at);
                $endDate = strtotime($tPath->meeting->ended_at);

                $nowDate = strtotime(date('Y-m-d H:i:s'));
                $ticketUser = User::findOrFail($tPath->next_user);

                if ($beginDate <= $nowDate AND $endDate >= $nowDate) {
                    if ($tPath->meeting->method !== 'automatic') {
                        continue;
                    }

                    if ($ticketUser->status != 'banned') {
                        $ticketUser->status = 'banned';

                        // Notify Users
                        $user = User::find($meetingTicket->user_id);

                        // Super admin
                        $data = [
                            'title' => 'User ban during meeting',
                            'message' => 'Please note that the user "' . $meetingTicket->user->name . '" has been banned during meeting from ' . $tPath->meeting->started_at . ' to ' . $tPath->meeting->ended_at,
                            'user' => '',
                            'ticket' => ''
                        ];

                        $superAdmins = User::role('super-admin')
                            ->get()
                            ->pluck('email')
                            ->toArray();

                        Mail::to($superAdmins)->send(new GeneralNotifyMail($data));

                        // User himself
                        $data = [
                            'title' => 'User ban during meeting',
                            'message' => 'Please note that you have been banned during meeting from ' . $tPath->meeting->started_at . ' to ' . $tPath->meeting->ended_at,
                            'user' => '',
                            'ticket' => ''
                        ];

                        Mail::to($user->email)->send(new GeneralNotifyMail($data));
                    }
                } else {
                    if ($ticketUser->status != 'permitted') {
                        $ticketUser->status = 'permitted';

                        // Notify Users
                        $user = User::find($meetingTicket->user_id);

                        // Super admin
                        $data = [
                            'title' => 'User unbanned after meeting',
                            'message' => 'Please note that the user "' . $meetingTicket->user->name . '" has been unbanned after the meeting ends.',
                            'user' => '',
                            'ticket' => ''
                        ];

                        $superAdmins = User::role('super-admin')
                            ->get()
                            ->pluck('email')
                            ->toArray();

                        Mail::to($superAdmins)->send(new GeneralNotifyMail($data));

                        // User himself
                        $data = [
                            'title' => 'User ban during meeting',
                            'message' => 'Please note that you have been unbanned during after the meeting ends.',
                            'user' => '',
                            'ticket' => ''
                        ];

                        Mail::to($user->email)->send(new GeneralNotifyMail($data));
                    }
                }

                $ticketUser->save();
            }
        })->everyFiveMinutes();
        */

        // Sales Leads, every 10 mins.
        $schedule->call(function () {
            $pullDate = date('Y-m-d H:i:s');
            $dateBegin = date('Y-m-d H:i:s', strtotime(date("Y") . '-' . date("m") . '-' . date("d") . " 10:30:00"));
            $dateEnd = date('Y-m-d H:i:s', strtotime(date("Y") . '-' . date("m") . '-' . date("d") . " 17:00:00"));

            if (!($pullDate >= $dateBegin && $pullDate <= $dateEnd)) {
                return;
            }

            $statuses = Status::whereIn('slug', ['new', 'follow-up'])  // re-shuffled has been removed 12/9/2022
                ->get()
                ->pluck('id')
                ->toArray();

            // $reShuffledId = Status::where('slug', 're-shuffled')->first()->id;

            //// 01. Status: NEW (3 Hours)
            $newStatusId = Status::where('slug', 'new')->first()->id;
            $tickets = Ticket::where('status_id', $newStatusId)->get();

            /** New method 11/9/2022 */
            $teleSales = User::role('tele-sale')
                ->where('status', 'permitted')
                ->get();

            $teleUserCounts = [];
            foreach ($teleSales as $key => $teleSale) {
                $tCount = Ticket::where('user_id', $teleSale->id)
                    ->whereIn('status_id', $statuses)
                    ->count();
                $teleUserCounts += [$teleSale->id => $tCount];
            }
            asort($teleUserCounts);
            $teleUserCounts = array_keys($teleUserCounts);

            $startPos = 0;
            foreach ($tickets as $key => $ticket) {
                $tPath = TicketPath::where('ticket_id', $ticket->id)
                    ->where('next_status', $newStatusId)
                    ->orderBy('updated_at', 'DESC')
                    ->first();

                $startTime = Carbon::createFromFormat('Y-m-d H:s:i', $tPath->updated_at);
                $endTime = Carbon::now();
                $diffHours = $endTime->diffInHours($startTime);

                if ($diffHours >= 3) {
                    $oldUser = User::find($ticket->user_id);

                    // Lead now is with Sales => tele-sales
                    if ($oldUser->getRoleNames()[0] === 'sale' || $oldUser->getRoleNames()[0] === 'sales-senior') {
                        $ticket->user_id = $teleUserCounts[$startPos];
                        $startPos++;
                    } // Lead is now with tele-sales => tele-sales manager
                    else if ($oldUser->getRoleNames()[0] === 'tele-sale') {
                        $salesManager = User::find($oldUser->manager_id);
                        $ticket->user_id = $salesManager->id;
                    }

                    $ticket->save();

                    // Check whether all tickets should go to Tele-sales or not?!
                    $ticketPath = TicketPath::create([
                        'prev_user' => $tPath->next_user,
                        'next_user' => $ticket->user_id,
                        'prev_status' => $newStatusId, // Check
                        'next_status' => $newStatusId, // Check
                        'ticket_id' => $ticket->id,
                        'comment' => 'Back from NEW after 3 hours inactive.',
                    ]);
                    $ticketPath->save();

                    if ($startPos === count($teleUserCounts)) {
                        $startPos = 0;
                    }
                }
            }

            /** Old Method, stopped at 11/9/2022 */
            /*
            // Junior
            $jrUsers = User::role('sale')->where('status', 'permitted')->get();
            $jrUserCounts = [];
            foreach ($jrUsers as $key => $user) {
                $tCount = Ticket::where('user_id', $user->id)
                    ->whereIn('status_id', $statuses)
                    ->count();
                $jrUserCounts += [$user->id => $tCount];
            }
            asort($jrUserCounts);
            $jrUserCounts = array_keys($jrUserCounts);

            // Senior
            $srUsers = User::role('sales-senior')->where('status', 'permitted')->get();
            $srUserCounts = [];
            foreach ($srUsers as $key => $user) {
                $tCount = Ticket::where('user_id', $user->id)->whereIn('status_id', $statuses)->get();
                $srUserCounts += [$user->id => $tCount];
            }
            asort($srUserCounts);
            $srUserCounts = array_keys($srUserCounts);
            $jrCount = count($srUserCounts) > 0 ? floor(count($tickets) / 3) : count($tickets);

            // Junior Distribution
            $startPos = 0;
            foreach ($tickets as $key => $ticket) {
                if ($key === $jrCount) {
                    break;
                }

                $tPath = TicketPath::where('ticket_id', $ticket->id)
                    ->where('next_status', $newStatusId)
                    ->orderBy('updated_at', 'DESC')
                    ->first();

                $startTime = Carbon::createFromFormat('Y-m-d H:s:i', $tPath->updated_at);
                $endTime = Carbon::now();
                $diffHours = $endTime->diffInHours($startTime);

                if ($diffHours >= 3) {
                    $oldUser = User::find($ticket->user_id);
                    $ticket->user_id = $jrUserCounts[$startPos];
                    $ticket->status_id = $reShuffledId;
                    $ticket->save();

                    $ticketPath = TicketPath::create([
                        'prev_user' => $tPath->next_user,
                        'next_user' => $jrUserCounts[$startPos],
                        'prev_status' => $newStatusId,
                        'next_status' => $reShuffledId,
                        'ticket_id' => $ticket->id,
                        'comment' => 'Back from NEW after 3 hours inactive.',
                    ]);
                    $ticketPath->save();

                    $startPos++;
                    if ($startPos === count($jrUserCounts)) {
                        $startPos = 0;
                    }
                }
            }

            // Senior Distribution
            if (count($srUserCounts) > 0) {
                $startPos = 0;
                foreach ($tickets as $key => $ticket) {
                    if ($key >= 0 and $key < $jrCount) {
                        continue;
                    }

                    $tPath = TicketPath::where('ticket_id', $ticket->id)
                        ->where('next_status', $newStatusId)
                        ->orderBy('updated_at', 'DESC')
                        ->first();

                    $startTime = Carbon::createFromFormat('Y-m-d H:s:i', $tPath->updated_at);
                    $endTime = Carbon::now();
                    $diffHours = $endTime->diffInHours($startTime);

                    if ($diffHours >= 3) {
                        $oldUser = User::find($ticket->user_id);
                        $ticket->user_id = $srUserCounts[$startPos];
                        $ticket->status_id = $reShuffledId;
                        $ticket->save();

                        $ticketPath = TicketPath::create([
                            'prev_user' => $tPath->next_user,
                            'next_user' => $srUserCounts[$startPos],
                            'prev_status' => $newStatusId,
                            'next_status' => $reShuffledId,
                            'ticket_id' => $ticket->id,
                            'comment' => 'Back from NEW after 3 hours inactive.',
                        ]);
                        $ticketPath->save();

                        $startPos++;

                        if ($startPos === count($srUserCounts)) {
                            $startPos = 0;
                        }
                    }
                }
            }
            */

            /** Stopped at 9/11/022 */
            //// 02. Status: Follow Up (7 days)
            $followUpDays = 7;  ///// Changed from 15 to 7 on 7/10/2022
            $followUpStatus = Status::where('slug', 'follow-up')->first()->id;
            $tickets = Ticket::where('status_id', $followUpStatus)->get();

            $statuses = Status::whereIn('slug', ['new', 'follow-up'])
                ->get()
                ->pluck('id')
                ->toArray();

            /** New method 11/9/2022 */
            $teleSales = User::role('tele-sale')
                ->where('status', 'permitted')
                ->get();

            $teleUserCounts = [];
            foreach ($teleSales as $key => $teleSale) {
                $tCount = Ticket::where('user_id', $teleSale->id)
                    ->whereIn('status_id', $statuses)
                    ->count();
                $teleUserCounts += [$teleSale->id => $tCount];
            }
            asort($teleUserCounts);
            $teleUserCounts = array_keys($teleUserCounts);

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
                        $ticket->user_id = $teleUserCounts[$startPos];
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
                        'next_user' => $teleUserCounts[$startPos],
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

                    $superAdmins = User::role('super-admin')->get()->pluck('email')->toArray();
                    Mail::to($superAdmins)->send(new LeadNotifyMail($data));

                    // User himself
                    $data = ['title' => 'Back from FOLLOW-UP', 'message' => 'Lead has been automatically returned from FOLLOW-UP to NEW after 15 days of inactivity: ', 'user' => '', 'ticket' => $ticket->id];
                    Mail::to($ticket->user->email)->send(new LeadNotifyMail($data));

                    // Old User himself
                    $data = ['title' => 'Withdrawn Lead', 'message' => 'Lead has been automatically withdrawn from you!', 'user' => '', 'ticket' => $ticket->id];
                    Mail::to($oldUser->email)->send(new LeadNotifyMail($data));

                    $startPos++;
                    if ($startPos === count($teleUserCounts)) {
                        $startPos = 0;
                    }
                }
            }

            //// 03. Status: Meeting (7 days)
            $meetingDays = 7;
            $meetingStatus = Status::where('slug', 'meeting')->first()->id;
            $tickets = Ticket::where('status_id', $meetingStatus)->get();

            /** New method 11/9/2022 */
            $teleSales = User::role('tele-sale')
                ->where('status', 'permitted')
                ->get();

            $teleUserCounts = [];
            foreach ($teleSales as $key => $teleSale) {
                $tCount = Ticket::where('user_id', $teleSale->id)
                    ->whereIn('status_id', $statuses)
                    ->count();
                $teleUserCounts += [$teleSale->id => $tCount];
            }
            asort($teleUserCounts);
            $teleUserCounts = array_keys($teleUserCounts);

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
                        $ticket->user_id = $teleUserCounts[$startPos];
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
                        'next_user' => $teleUserCounts[0],
                        'prev_status' => $meetingStatus,
                        'next_status' => $newStatusId,
                        'ticket_id' => $ticket->id,
                        'comment' => 'Back from MEETING after 7 days inactive.',
                    ]);

                    $ticketPath->save();

                    ////// Notify Users
                    // Super admin
                    $data = ['title' => 'Back from MEETING', 'message' => 'Lead has been automatically returned from MEETING to NEW after 7 days of inactivity: ', 'user' => $user->name, 'ticket' => $ticket->id];
                    $superAdmins = User::role('super-admin')->get()->pluck('email')->toArray();
                    Mail::to($superAdmins)->send(new LeadNotifyMail($data));

                    // User himself
                    $data = ['title' => 'Back from MEETING', 'message' => 'Lead has been automatically returned from MEETING to NEW after 7 days of inactivity: ', 'user' => '', 'ticket' => $ticket->id];
                    Mail::to($ticket->user->email)->send(new LeadNotifyMail($data));

                    // Old User himself
                    $data = ['title' => 'Withdrawn Lead', 'message' => 'Lead has been automatically withdrawn from you!', 'user' => '', 'ticket' => $ticket->id];
                    Mail::to($oldUser->email)->send(new LeadNotifyMail($data));

                    $startPos++;
                    if ($startPos === count($teleUserCounts)) {
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
                    // 1. Super admin
                    $user = User::find($ticket->user_id);
                    $data = ['title' => 'Dead Lead', 'message' => 'Lead has been automatically moved from WAITING to DEAD after 7 days of inactivity: ', 'user' => auth()->user()->name, 'ticket' => $ticket->id];
                    $superAdmins = User::role('super-admin')->get()->pluck('email')->toArray();
                    Mail::to($superAdmins)->send(new LeadNotifyMail($data));

                    // 2. User himself
                    $data = ['title' => 'Dead Lead', 'message' => 'Lead has been automatically moved from WAITING to DEAD after 7 days of inactivity: ', 'user' => '', 'ticket' => $ticket->id];
                    Mail::to($user->email)->send(new LeadNotifyMail($data));
                }
            }

            /* stopped at 26/5/2022
            //// 05. Status: Dead
            $deadStatus = Status::where('slug', 'dead')->first()->id;
            $tickets = Ticket::where('status_id', $deadStatus)->get();

            // Junior
            $jrUsers = User::role('sale')->where('status', 'permitted')->get();
            $jrUserCounts = [];

            foreach ($jrUsers as $key => $user) {
                $tCount = Ticket::where('user_id', $user->id)->whereIn('status_id', $statuses)->count();
                $jrUserCounts += [$user->id => $tCount];
            }
            asort($jrUserCounts);
            $jrUserCounts = array_keys($jrUserCounts);

            // Senior
            $srUsers = User::role('sales-senior')->where('status', 'permitted')->get();
            $srUserCounts = [];
            foreach ($srUsers as $key => $user) {
                $tCount = Ticket::where('user_id', $user->id)->whereIn('status_id', $statuses)->get();
                $srUserCounts += [$user->id => $tCount];
            }
            asort($srUserCounts);
            $srUserCounts = array_keys($srUserCounts);
            $jrCount = count($srUserCounts) > 0 ? floor(count($tickets) / 3) : count($tickets);

            // Junior Distribution
            $startPos = 0;
            foreach ($tickets as $key => $ticket) {
                if ($key === $jrCount) {
                    break;
                }

                $tPath = TicketPath::where('ticket_id', $ticket->id)
                    ->where('next_status', $deadStatus)
                    ->orderBy('updated_at', 'DESC')
                    ->first();

                $ticket->user_id = $jrUserCounts[$startPos];
                $ticket->status_id = $reShuffledId;
                $ticket->save();

                $ticketPath = TicketPath::create([
                    'prev_user' => $tPath->next_user,
                    'next_user' => $jrUserCounts[$startPos],
                    'prev_status' => $newStatusId,
                    'next_status' => $reShuffledId,
                    'ticket_id' => $ticket->id,
                    'comment' => 'Back from NEW after 3 hours inactive.',
                ]);
                $ticketPath->save();

                /// Notify Users
                $user = User::find($ticket->user_id);

                // Super admin
                $data = [
                    'title' => 'Re-assign Lead',
                    'message' => 'Lead has been automatically re-assigned to another user user after DEAD status.',
                    'user' => $user->name,
                    'ticket' => $ticket->id
                ];

                $superAdmins = User::role('super-admin')->get()->pluck('email')->toArray();
                Mail::to($superAdmins)->send(new LeadNotifyMail($data));

                // User himself
                $data = [
                    'title' => 'New Lead',
                    'message' => 'A new lead has been automatically assigned to you. ',
                    'user' => '',
                    'ticket' => $ticket->id
                ];

                Mail::to($user->email)->send(new LeadNotifyMail($data));

                $startPos++;
                if ($startPos === count($jrUserCounts)) {
                    $startPos = 0;
                }
            }

            // Senior Distribution
            if (count($srUserCounts) > 0) {
                $startPos = 0;
                foreach ($tickets as $key => $ticket) {
                    if ($key >= 0 and $key < $jrCount) {
                        continue;
                    }

                    $tPath = TicketPath::where('ticket_id', $ticket->id)
                        ->where('next_status', $deadStatus)
                        ->orderBy('updated_at', 'DESC')
                        ->first();

                    $ticket->user_id = $srUserCounts[$startPos];
                    $ticket->status_id = $reShuffledId;
                    $ticket->save();

                    $ticketPath = TicketPath::create([
                        'prev_user' => $tPath->next_user,
                        'next_user' => $srUserCounts[$startPos],
                        'prev_status' => $deadStatus,
                        'next_status' => $reShuffledId,
                        'ticket_id' => $ticket->id,
                        'comment' => 'Re-shuffled After DEAD status.',
                    ]);

                    $ticketPath->save();

                    /// Notify Users
                    $user = User::find($ticket->user_id);

                    // Super admin
                    $data = [
                        'title' => 'Re-assign Lead',
                        'message' => 'Lead has been automatically re-assigned to another user user after DEAD status: ',
                        'user' => $user->name,
                        'ticket' => $ticket->id
                    ];

                    $superAdmins = User::role('super-admin')->get()->pluck('email')->toArray();

                    Mail::to($superAdmins)->send(new LeadNotifyMail($data));

                    // User himself
                    $data = [
                        'title' => 'New Lead',
                        'message' => 'A new lead has been automatically assigned to you.',
                        'user' => '',
                        'ticket' => $ticket->id
                    ];

                    Mail::to($user->email)->send(new LeadNotifyMail($data));

                    $startPos++;
                    if ($startPos === count($jrUserCounts)) {
                        $startPos = 0;
                    }
                }
            }
            */
        })->everyTenMinutes();

        // Dead Sales
        /*
        $schedule->call(function () {
            // Status: Dead (10 Minutes)
            $deadStatus = Status::where('slug', 'dead')->first()->id;

            $tickets = Ticket::where('status_id', $deadStatus)->get();

            $newTeleStatus = Status::where('slug', 'new-tele')->first()->id;

            foreach ($tickets as $key => $ticket) {
                $tPath = TicketPath::where('ticket_id', $ticket->id)
                    ->where('next_status', $deadStatus)
                    ->orderBy('updated_at', 'DESC')
                    ->first();

                $statuses = Status::whereIn('slug', ['new-tele', 'follow-up-tele', 'meeting-tele'])
                    ->get()
                    ->pluck('id')
                    ->toArray();

                $users = User::role('tele-sale')
                    ->whereHas('tickets', function ($query) use ($statuses) {
                        $query->whereIn('status_id', $statuses);
                    })
                    ->where('id', '!=', $ticket->user_id)
                    ->with('tickets')
                    ->get();

                $usersNoTickets = User::role('tele-sale')
                    ->whereDoesntHave('tickets')
                    ->get();

                $users = $users->merge($usersNoTickets);

                $userCounts = [];

                foreach ($users as $key => $user) {
                    $userCounts += [$user->id => count($user->tickets)];
                }

                asort($userCounts);
                $userCounts = array_keys($userCounts);

                $ticket->user_id = $userCounts[0];
                $ticket->status_id = $newTeleStatus;
                $ticket->save();

                $ticketPath = TicketPath::create([
                    'prev_user' => $tPath->next_user,
                    'next_user' => $userCounts[0],
                    'prev_status' => $deadStatus,
                    'next_status' => $newTeleStatus,
                    'ticket_id' => $ticket->id,
                    'comment' => 'Back from DEAD.',
                ]);

                $ticketPath->save();
            }

        })->everyTenMinutes();
        */

        /* Stopped since no Tele Sales now (20/5/2022)
        // Tele-Sales Leads
        $schedule->call(function () {
            // Status: TELE NEW (3 Hours)
            $newStatus = Status::where('slug', 'new-tele')->first()->id;
            $tickets = Ticket::where('status_id', $newStatus)->get();
            foreach ($tickets as $key => $ticket) {
                $tPath = TicketPath::where('ticket_id', $ticket->id)->where('next_status', $newStatus)->orderBy('updated_at', 'DESC')->first();
                $startTime = Carbon::createFromFormat('Y-m-d H:s:i', $tPath->updated_at);
                $endTime = Carbon::now();
                $diffHours = $endTime->diffInHours($startTime);
                if ($diffHours >= 3) {
                    $statuses = Status::whereIn('slug', ['new-tele', 'follow-up-tele', 'meeting-tele'])->get()->pluck('id')->toArray();
                    $users = User::role('tele-sale')->whereHas('tickets', function ($query) use ($statuses) {
                        $query->whereIn('status_id', $statuses);
                    })->where('id', '!=', $ticket->user_id)->with('tickets')->get();
                    $usersNoTickets = User::role('tele-sale')->whereDoesntHave('tickets')->get();
                    $users = $users->merge($usersNoTickets);
                    $userCounts = [];
                    foreach ($users as $key => $user) {
                        $userCounts += [$user->id => count($user->tickets)];
                    }
                    asort($userCounts);
                    $userCounts = array_keys($userCounts);
                    $ticket->user_id = $userCounts[0];
                    $ticket->save();
                    $ticketPath = TicketPath::create(['prev_user' => $tPath->next_user, 'next_user' => $userCounts[0], 'prev_status' => $newStatus, 'next_status' => $newStatus, 'ticket_id' => $ticket->id, 'comment' => 'Back from NEW-TELE after 3 hours inactive.',]);
                    $ticketPath->save();
                }
            }
            // Status: Tele Follow Up (15 days)
            $followUpStatus = Status::where('slug', 'follow-up-tele')->first()->id;
            $tickets = Ticket::where('status_id', $followUpStatus)->get();
            foreach ($tickets as $key => $ticket) {
                $tPath = TicketPath::where('ticket_id', $ticket->id)->where('next_status', $followUpStatus)->orderBy('updated_at', 'DESC')->first();
                $startTime = Carbon::createFromFormat('Y-m-d H:s:i', $tPath->updated_at);
                $endTime = Carbon::now();
                $diffDays = $startTime->diffInDays($endTime);
                if ($diffDays >= 15) {
                    $statuses = Status::whereIn('slug', ['new-tele', 'follow-up-tele', 'meeting-tele'])->get()->pluck('id')->toArray();
                    $users = User::role('tele-sale')->whereHas('tickets', function ($query) use ($statuses) {
                        $query->whereIn('status_id', $statuses);
                    })->where('id', '!=', $ticket->user_id)->with('tickets')->get();
                    $usersNoTickets = User::role('tele-sale')->whereDoesntHave('tickets')->get();
                    $users = $users->merge($usersNoTickets);
                    $userCounts = [];
                    foreach ($users as $key => $user) {
                        $userCounts += [$user->id => count($user->tickets)];
                    }
                    asort($userCounts);
                    $userCounts = array_keys($userCounts);
                    $ticket->user_id = $userCounts[0];
                    $ticket->status_id = $newStatus;
                    $ticket->save();
                    $ticketPath = TicketPath::create(['prev_user' => $tPath->next_user, 'next_user' => $userCounts[0], 'prev_status' => $followUpStatus, 'next_status' => $newStatus, 'ticket_id' => $ticket->id, 'comment' => 'Back from TELE FOLLOW-UP after 15 days inactive.',]);
                    $ticketPath->save();
                }
            }
            // Status: Tele Meeting (7 days)
            $meetingStatus = Status::where('slug', 'meeting-tele')->first()->id;
            $tickets = Ticket::where('status_id', $meetingStatus)->get();
            foreach ($tickets as $key => $ticket) {
                $tPath = TicketPath::where('ticket_id', $ticket->id)->where('next_status', $meetingStatus)->orderBy('updated_at', 'DESC')->first();
                $startTime = Carbon::createFromFormat('Y-m-d H:s:i', $tPath->updated_at);
                $endTime = Carbon::now();
                $diffDays = $startTime->diffInDays($endTime);
                if ($diffDays >= 7) {
                    $statuses = Status::whereIn('slug', ['new-tele', 'follow-up-tele', 'meeting-tele'])->get()->pluck('id')->toArray();
                    $users = User::role('tele-sale')->whereHas('tickets', function ($query) use ($statuses) {
                        $query->whereIn('status_id', $statuses);
                    })->where('id', '!=', $ticket->user_id)->with('tickets')->get();
                    $usersNoTickets = User::role('tele-sale')->whereDoesntHave('tickets')->get();
                    $users = $users->merge($usersNoTickets);
                    $userCounts = [];
                    foreach ($users as $key => $user) {
                        $userCounts += [$user->id => count($user->tickets)];
                    }
                    asort($userCounts);
                    $userCounts = array_keys($userCounts);
                    $ticket->user_id = $userCounts[0];
                    $ticket->status_id = $newStatus;
                    $ticket->save();
                    $ticketPath = TicketPath::create(['prev_user' => $tPath->next_user, 'next_user' => $userCounts[0], 'prev_status' => $meetingStatus, 'next_status' => $newStatus, 'ticket_id' => $ticket->id, 'comment' => 'Back from TELE MEETING after 7 days inactive.',]);
                    $ticketPath->save();
                }
            }
            // Status: Tele Waiting (7 days)
            $waitingStatus = Status::where('slug', 'waiting-tele')->first()->id;
            $tickets = Ticket::where('status_id', $waitingStatus)->get();
            foreach ($tickets as $key => $ticket) {
                $tPath = TicketPath::where('ticket_id', $ticket->id)->where('next_status', $waitingStatus)->orderBy('updated_at', 'DESC')->first();
                $startTime = Carbon::createFromFormat('Y-m-d H:s:i', $tPath->updated_at);
                $endTime = Carbon::now();
                $diffDays = $startTime->diffInDays($endTime);
                if ($diffDays >= 7) {
                    $deadStatus = Status::where('slug', 'dead-tele')->first()->id;
                    $ticket->status_id = $deadStatus;
                    $ticket->save();
                    $ticketPath = TicketPath::create(['prev_user' => $tPath->next_user, 'next_user' => $tPath->next_user, 'prev_status' => $waitingStatus, 'next_status' => $deadStatus, 'ticket_id' => $ticket->id, 'comment' => 'After TELE WAITING 7 days, it\s now TELE-DEAD.',]);
                    $ticketPath->save();
                }
            }
        })->hourly();
        // Meetings Reminder
        $schedule->call(function () {
            $now = date('Y-m-d H:i:s');
            $meetings = Meeting::whereReminderAt($now)->get();
            foreach ($meetings as $key => $meeting) {
                $user = $meeting->ticketPath->nextUser;
                // Notify Users
                // Super admin
                $data = ['title' => 'Meeting Reminder', 'message' => 'Reminder of meeting: employee: ' . $user->name . ', with: ' . $meeting->ticketPath->ticket->full_name . ', at: ' . $meeting->started_at . '.', 'user' => '', 'ticket' => ''];
                $superAdmins = User::role('super-admin')->get()->pluck('email')->toArray();
                Mail::to($superAdmins)->send(new GeneralNotifyMail($data));
                // User himself
                $data = ['title' => 'Meeting Reminder', 'message' => 'Kindly reminder of meeting with: ' . $meeting->ticketPath->ticket->full_name . ', at: ' . $meeting->started_at . '.', 'user' => '', 'ticket' => ''];
                Mail::to($user->email)->send(new GeneralNotifyMail($data));
            }
        })->everyMinute();
        */
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
