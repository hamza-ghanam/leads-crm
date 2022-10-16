<?php

namespace App\Helpers;

use App\Mail\LeadNotifyMail;
use App\Models\GeneralSettings;
use App\Models\SalesCampaign;
use App\Models\Source;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\TicketPath;
use App\Models\User;
use Illuminate\Http\Request;
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

        foreach ($campNames as $campName) {
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
        $leadPath = TicketPath::create([
            'next_user' => $lead->user_id,
            'next_status' => $statusId,
            'ticket_id' => $lead->id,
            'comment' => $comment === '' ? 'Initial lead creation.' : $comment,
        ]);
        $leadPath->save();

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
            Mail::to($user->email)->send(new LeadNotifyMail($data));

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
        Mail::to($superAdmins)->send(new LeadNotifyMail($data));
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

    public function getSpreadsheetDetails($source): array
    {
        $spread = '';
        $sheet = '';

        switch ($source) {
            case 'facebook':
                $spread = 'fb_spreadsheet_id';
                $sheet = 'fb_sheet_id';
                break;

            case 'tiktok':
                $spread = 'tk_spreadsheet_id';
                $sheet = 'tk_sheet_id';
                break;
        }

        return [$spread, $sheet];
    }

    public function fetchLeadsFromZapier($source, $manual = null): array
    {
        [$spread, $sheet] = $this->getSpreadsheetDetails($source);

        $sheets = Sheets::spreadsheet(config('sheets.' . $spread))
            ->sheet(config('sheets.' . $sheet))
            ->get();
        $header = $sheets->pull(0);
        $rawLeads = Sheets::collection($header, $sheets);

        $leads = [];
        $newStatus = Status::where('slug', 'new')->first()->id;
        $duplicatedStatus = Status::whereName('duplicated')->first();

        foreach ($rawLeads as $key => $rawLead) {
            // Phone number
            $rawLead['phone_number'] = str_replace(' ', '', $rawLead['phone_number']);
            $rawLead['phone_number'] = $this->rectifyPhone($rawLead['phone_number']);

            $lead = new Ticket([
                'number' => $rawLead['id'],
                'ad_id' => $rawLead['ad_id'],
                'ad_name' => $rawLead['ad_name'],
                'adset_id' => $rawLead['ad_name'],
                'adset_name' => $rawLead['adset_id'],
                'campaign_id' => $rawLead['campaign_id'],
                'campaign_name' => $rawLead['campaign_name'],
                'form_id' => $rawLead['form_id'],
                'form_name' => $rawLead['form_name'] ?? '',
                'is_organic' => $rawLead['is_organic'] ?? '',
                'platform' => $rawLead['platform'],
                'full_name' => $rawLead['full_name'],
                'phone_number' => $rawLead['phone_number'],
                'email' => $rawLead['email'],
                'job_title' => $rawLead['job_title'] ?? '',
                'status_id' => $newStatus,
                'source_id' => $this->getSourceID($rawLead['platform']),
                'assigner_id' => $manual ? auth()->user()->id : null,
                'method' => ($manual ? 'Manual ' : 'Automatic ') . ucfirst($source)
            ]);

            $dupLead = Ticket::wherePhoneNumber($lead->phone_number)
                ->where('phone_number', '!=', '')
                ->where('id', '!=', $lead->id)
                ->first();

            if ($dupLead and $dupLead !== null) {
                $lead->status_id = $duplicatedStatus->id;
            }
            $leads[] = $lead;
        }

        return $leads;
    }

    public function emptyZapierLeadsSheet($source, $leadsLength)
    {
        [$spread, $sheet] = $this->getSpreadsheetDetails($source);

        for ($i = 0; $i < $leadsLength; $i++) {
            Sheets::spreadsheet(config('sheets.' . $spread))
                ->sheet(config('sheets.' . $sheet))
                ->range('A' . ($i + 2))
                ->update([['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '']]);
        }
    }

    public function initiateImport($leads)
    {
        $statuses = Status::whereIn('slug', ['new', 'follow-up']) // re-shuffled has been removed 12/9/2022
        ->get()
            ->pluck('id')
            ->toArray();

        $useCamps = boolval(GeneralSettings::whereName('use_camps')->first()->value);
        if (!$useCamps) {
            $this->importWithNoCampaigns($leads, $statuses);
        } else {
            $this->importCampaingsBased($leads, $statuses);
        }
    }

    public function getLeadsFilterParams(Request $request)
    {
        return [
            'sale' => $request->query('sales'),
            'status' => $request->query('fstatus'),
            'camp' => $request->query('camp'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
            'fullName' => $request->query('fullName'),
            'phone' => $request->query('phone'),
            'linkable' => $request->query('linkable')
        ];
    }

    public function filterLeads($filterParams, $leads)
    {
        // Campaign filter
        if (($filterParams['camp'] and $filterParams['camp'] !== '')) {
            $leads = $leads->where('campaign_name', 'LIKE', "%{$filterParams['camp']}%");
        }

        // Created at from & to filters
        if (($filterParams['from'] and $filterParams['from'] !== '') and ($filterParams['to'] and $filterParams['to'] !== '')) {
            $from = date($filterParams['from'] . ' 00:00:00');
            $to = date($filterParams['to'] . ' 23:59:59');
            $leads = $leads->whereBetween('created_at', [$from, $to]);
        } else if (($filterParams['from'] and $filterParams['from'] !== '') and (!$filterParams['to'] or $filterParams['to'] == '')) {
            $from = date($filterParams['from'] . ' 00:00:00');
            $leads = $leads->where('created_at', '>=', $from);
        } else if ((!$filterParams['from'] or $filterParams['from'] == '') and ($filterParams['to'] and $filterParams['to'] !== '')) {
            $to = date($filterParams['to'] . ' 23:59:59');
            $leads = $leads->where('created_at', '<=', $to);
        }

        // Person Full_name filter
        if (($filterParams['fullName'] and $filterParams['fullName'] !== '')) {
            $leads = $leads->where('full_name', 'LIKE', "%{$filterParams['fullName']}%");
        }

        // Person phone filter
        if (($filterParams['phone'] and $filterParams['phone'] !== '')) {
            $leads = $leads->where('phone_number', 'LIKE', "%{$filterParams['phone']}%");
        }

        return $leads;
    }

    function rectifyPhone($phoneNumber): string
    {
        $phoneNumber = str_replace(' ', '', $phoneNumber);

        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

        $num = range(0, 9);
        $phoneNumber = str_replace($persian, $num, $phoneNumber);
        $phoneNumber = str_replace($arabic, $num, $phoneNumber);

        $uaePrefix = '+971';

        if (str_starts_with($phoneNumber, '00971')) {
            $phoneNumber = substr($phoneNumber, 5);
        } else if (str_starts_with($phoneNumber, '+971')) {
            $phoneNumber = substr($phoneNumber, 4);
        } else if (str_starts_with($phoneNumber, '971')) {
            $phoneNumber = substr($phoneNumber, 3);
        } else if (str_starts_with($phoneNumber, '05')) {
            $phoneNumber = substr($phoneNumber, 1);
        }

        return $uaePrefix . ' ' . $phoneNumber;
    }

    public function getSourceID($platform)
    {
        switch ($platform) {
            case 'fb':
                return Source::where('name', 'Facebook')->first()->id;
            case 'ig':
                return Source::where('name', 'Instagram')->first()->id;
            case 'tk':
                return Source::where('name', 'TikTok')->first()->id;
            case 'sc':
                return Source::where('name', 'Snapchat')->first()->id;
            default:
                return Source::where('name', 'Unspecified')->first()->id;
        }
    }
}
