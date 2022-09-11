<?php

namespace App\Helpers;

use App\Mail\LeadNotifyMail;
use App\Models\GeneralSettings;
use App\Models\SalesCampaign;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\TicketPath;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Revolution\Google\Sheets\Facades\Sheets;

class LeadsHelper
{
    function __construct()
    {

    }

    public function importWithNoCampaigns($leads, $statuses)
    {
        // Junior
        $jrUsers = User::role('sale')->where('status', 'permitted')->get();
        // Senior
        $srUsers = User::role('sales-senior')->where('status', 'permitted')->get();
        [$jrUserCounts, $srUserCounts] = $this->prepareJrAndSrSalesLists($jrUsers, $srUsers, $statuses);
        $this->distributeLeads($leads, $jrUserCounts, $srUserCounts);
    }

    public function importCampaingsBased($leads, $statuses)
    {
        $leads = collect($leads);

        $campNames = array_change_key_case($leads->pluck('campaign_name')->toArray(), CASE_LOWER);

        $campLeads = array_fill_keys($campNames, []);
        $campSalesSR = array_fill_keys($campNames, []);
        $campSalesJR = array_fill_keys($campNames, []);

        foreach ($campNames as $key => $campName) {
            $campLeads[$campName] = $leads->where('campaign_name', $campName);

            // Junior
            $campSalesJR[$campName] = SalesCampaign::where('campaign_name', $campName)
                ->whereHas('user', function ($q) {
                    $q->role('sale')->where('status', 'permitted');
                })->pluck('user_id')->toArray();

            // Senior
            $campSalesSR[$campName] = SalesCampaign::where('campaign_name', $campName)
                ->whereHas('user', function ($q) {
                    $q->role('sales-senior')->where('status', 'permitted');
                })->pluck('user_id')->toArray();

            if (count($campSalesSR[$campName]) + count($campSalesJR[$campName]) > 0) {
                [$jrUserCounts, $srUserCounts] = $this->prepareJrAndSrSalesLists($campSalesJR[$campName], $campSalesSR[$campName], $statuses);
                $this->distributeLeads($campLeads[$campName], $jrUserCounts, $srUserCounts);
                unset($campLeads[$campName]);
                unset($campNames[$campName]);
            }
        }

        // Campaigns with no users
        //$leadsNoCamp = $leads->whereNull('campaign_name');
        //$leadsNoCamp = $leadsNoCamp->merge($leads->where('campaign_name', ''));

        $this->storeUnassignedLeads($campLeads);
    }

    public function distributeLeads($leads, $jrUserCounts, $srUserCounts)
    {
        $srUserCounts = array_keys($srUserCounts);
        $jrCount = count($srUserCounts) > 0 ? floor(count($leads) / 3) : count($leads);
        $duplicatedStatus = Status::whereName('duplicated')->first();
        $newStatus = Status::where('slug', 'new')->first();

        // Junior Distribution
        $startPos = 0;
        foreach ($leads as $key => $lead) {
            if ($key === $jrCount) {
                break;
            }
            if ($lead->status_id === $duplicatedStatus->id) {
                $lead->user_id = null;
                $lead->save();
                continue;
            }

            // Assign a user
            $lead->user_id = $jrUserCounts[$startPos];
            $startPos++;
            $lead->save();
            $this->createAndAssignLead($lead, $newStatus->id);
            if ($startPos === count($jrUserCounts)) {
                $startPos = 0;
            }
        }

        // Senior Distribution
        if (count($srUserCounts) > 0) {
            $startPos = 0;
            foreach ($leads as $key => $lead) {
                if ($key >= 0 and $key < $jrCount) {
                    continue;
                }
                if ($lead->status_id === $duplicatedStatus->id) {
                    $lead->user_id = null;
                    $lead->save();
                    continue;
                }

                // Assign a user
                $lead->user_id = $srUserCounts[$startPos];
                $startPos++;
                if ($startPos === count($srUserCounts)) {
                    $startPos = 0;
                }
                $lead->save();
                $this->createAndAssignLead($lead, $newStatus->id);
            }
        }
    }

    public function storeUnassignedLeads($campLeads)
    {
        $newStatus = Status::where('slug', 'new')->first();

        foreach ($campLeads as $leads) {
            foreach ($leads as $lead) {
                $lead->user_id = null;
                $lead->save();
                $this->createAndAssignLead($lead, $newStatus->id, 'Unassigned Lead.');
            }
        }
    }

    public function createAndAssignLead($lead, $statusId, $comment = '')
    {
        $ticketPath = TicketPath::create([
            'next_user' => $lead->user_id,
            'next_status' => $statusId,
            'ticket_id' => $lead->id,
            'comment' => $comment === '' ? 'Initial lead creation.' : $comment,
        ]);
        $ticketPath->save();

        /// Notify Users
        $user = User::find($lead->user_id);

        if ($user) {
            // User himself
            $data = [
                'title' => 'New Lead',
                'message' => 'A new lead is automatically assigned to you!',
                'user' => '',
                'ticket' => $lead->id
            ];
            //Mail::to($user->email)->send(new LeadNotifyMail($data));

            $message = 'A new lead is automatically assigned to the user: ';
        } else {
            $message = 'A new lead is created and have to be assigned to sales.';
        }

        // Super admin
        $data = [
            'title' => 'New Lead',
            'message' => $message,
            'user' => $user ? $user->name : '',
            'ticket' => $lead->id
        ];

        $superAdmins = User::role('super-admin')->get()->pluck('email')->toArray();
        //Mail::to($superAdmins)->send(new LeadNotifyMail($data));
    }

    public function prepareJrAndSrSalesLists($salesJR, $salesSR, $statuses): array
    {
        $jrUserCounts = [];
        foreach ($salesJR as $userJR) {
            $tCount = Ticket::where('user_id', $userJR)
                ->whereIn('status_id', $statuses)
                ->count();

            $jrUserCounts += [$userJR => $tCount];
        }
        asort($jrUserCounts);
        $jrUserCounts = array_keys($jrUserCounts);

        // Senior
        $srUserCounts = [];
        foreach ($salesSR as $userSR) {
            $tCount = Ticket::where('user_id', $userSR)
                ->whereIn('status_id', $statuses)
                ->count();
            $srUserCounts += [$userSR => $tCount];
        }
        asort($srUserCounts);
        $srUserCounts = array_keys($srUserCounts);

        return [$jrUserCounts, $srUserCounts];
    }

    public function emptyLeadsSheet($leads)
    {
        for ($i = 0; $i < count($leads); $i++) {
            Sheets::spreadsheet(config('sheets.post_spreadsheet_id'))
                ->sheet(config('sheets.post_sheet_id'))
                ->range('A' . ($i + 2))
                ->update([['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '']]);
        }
    }

    public function initiateImport($leads)
    {
        $statuses = Status::whereIn('slug', ['new', 'follow-up', 're-shuffled'])
            ->get()
            ->pluck('id')
            ->toArray();

        $useCamps = boolval(GeneralSettings::whereName('use_camps')->first()->value);
        if (!$useCamps) {
            $this->importWithNoCampaigns($leads, $statuses);
        } else {
            $this->importCampaingsBased($leads, $statuses);
        }

        $this->emptyLeadsSheet($leads);
    }
}
