<?php

namespace App\Helpers;

use App\Mail\LeadNotifyMail;
use App\Models\GeneralSettings;
use App\Models\SalesCampaign;
use App\Models\Source;
use App\Models\Status;
use App\Models\TempLead;
use App\Models\Ticket;
use App\Models\TicketPath;
use App\Models\User;
use App\Notifications\SendPushNotification;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
//use Revolution\Google\Sheets\Facades\Sheets;
use Illuminate\Support\Facades\Http;
use App\Models\FcmToken;

class LeadsHelper
{
    function __construct()
    {

    }

    public function importWithNoCampaigns($leads, $statuses)
    {
        // Junior
        $jrUsers = User::role('sale')->where('status', 'permitted')->pluck('id');
        // Senior
        $srUsers = User::role('tele-sale')->where('status', 'permitted')->pluck('id');

        // Send IDs only
        [$jrUserCounts, $srUserCounts] = $this->prepareJrAndSrSalesLists($jrUsers, $srUsers, $statuses);

        return $this->distributeLeads($leads, $jrUserCounts, $srUserCounts);
    }

    public function importCampaignsBased($leads, $statuses)
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
                [$assignmentsCountJR, $assignmentsCountSR] = $this->distributeLeads($campLeads[$campName], $jrUserCounts, $srUserCounts);
                unset($campLeads[$campName]);
                unset($campNames[$campName]);
            }
        }

        // Campaigns with no users
        //$leadsNoCamp = $leads->whereNull('campaign_name');
        //$leadsNoCamp = $leadsNoCamp->merge($leads->where('campaign_name', ''));

        $this->storeUnassignedLeads($campLeads);

        return [$assignmentsCountJR, $assignmentsCountSR];
    }

    public function distributeLeads($leads, $jrUserIds, $srUserIds)
    {
        $jrUserIds = array_values(array_filter($jrUserIds, fn ($id) => (int)$id > 0));
        $srUserIds = array_values(array_filter($srUserIds, fn ($id) => (int)$id > 0));

        $newStatusId        = Status::where('name', Status::NEW)->value('id');
        $duplicatedStatusId = Status::where('name', Status::DUPLICATED)->value('id');

        $tempLeads = [];

        // If there are seniors, you allocate 1/3 of leads to juniors, otherwise all to juniors.
        // (Keeping your original business rule, but making it explicit and safe.)
        $totalLeads = is_countable($leads) ? count($leads) : $leads->count();
        $jrQuota    = count($srUserIds) > 0 ? (int) floor($totalLeads / 3) : $totalLeads;

        $assignmentsCountJR = [];
        $assignmentsCountSR = [];

        // Helper to assign a slice of leads to a rotating list of users
        $assignSlice = function (array $userIds, iterable $slice, array &$assignmentsCount) use (
            $duplicatedStatusId,
            $newStatusId,
            &$tempLeads
        ) {
            /*
            if (count($userIds) === 0) {
                // No users to assign to, just handle duplicates / temp keys collection if needed.
                foreach ($slice as $lead) {
                    if ((int) $lead->status_id === (int) $duplicatedStatusId) {
                        $lead->user_id = null;
                        $tempLeads[] = $lead->key ?? null;

                        // If 'key' is not a DB column, avoid persisting it accidentally
                        unset($lead->key);

                        $lead->save();
                    }
                }
                return;
            }
            */

            $pos = 0;
            $userCount = count($userIds);

            foreach ($slice as $lead) {
                // Duplicated lead → unassign and skip create/assign
                /*
                if ((int) $lead->status_id === (int) $duplicatedStatusId) {
                    $lead->user_id = null;
                    $tempLeads[] = $lead->key ?? null;

                    // unset($lead->key);

                    $lead->save();
                    continue;
                }
                */

                $currentUserId = $userIds[$pos];

                $lead->user_id = $currentUserId;
                $tempLeads[] = $lead->key ?? null;

                unset($lead->key);

                $lead->save();

                $this->createAndAssignLead($lead, $newStatusId);

                $assignmentsCount[$currentUserId] = ($assignmentsCount[$currentUserId] ?? 0) + 1;

                $pos = ($pos + 1) % $userCount;
            }
        };

        // Split leads: first jrQuota to JR, remainder to SR (if any)
        // Works for both arrays and collections
        $jrLeads = is_array($leads) ? array_slice($leads, 0, $jrQuota) : $leads->take($jrQuota);
        $srLeads = is_array($leads) ? array_slice($leads, $jrQuota) : $leads->slice($jrQuota);

        // Junior distribution
        $assignSlice($jrUserIds, $jrLeads, $assignmentsCountJR);

        // Senior distribution (only if seniors exist)
        if (count($srUserIds) > 0) {
            $assignSlice($srUserIds, $srLeads, $assignmentsCountSR);
        }

        // Clean nulls if key might be missing
        $tempLeads = array_values(array_filter($tempLeads, fn ($v) => !is_null($v)));

        return [$assignmentsCountJR, $assignmentsCountSR, $tempLeads];
    }

    public function storeUnassignedLeads($campLeads)
    {
        $newStatus = Status::where('slug', 'new')->first();

        foreach ($campLeads as $leads) {
            foreach ($leads as $lead) {
                $lead->user_id = null;
                unset($lead->key);
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
            // Mail::to($user->email)->send(new LeadNotifyMail($data));

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

            $jrUserCounts[$userJR] = $tCount;
        }

        asort($jrUserCounts);
        $jrUserCounts = array_keys($jrUserCounts);

        // Senior
        $srUserCounts = [];
        foreach ($salesSR as $userSR) {
            $tCount = Ticket::where('user_id', $userSR)
                ->whereIn('status_id', $statuses)
                ->count();
            $srUserCounts[$userSR] = $tCount;
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

    public function fetchLeadsFromZapierOLD($source, $manual = null): array
    {
        [$spread, $sheet] = $this->getSpreadsheetDetails($source);
        $sheets = [];

//        $sheets = Sheets::spreadsheet(config('sheets.' . $spread))
//            ->sheet(config('sheets.' . $sheet))
//            ->get();

        $header = $sheets->pull(0);
        $rawLeads = Sheets::collection($header, $sheets);

        $leads = [];
        $newStatus = Status::where('slug', 'new')->first()->id;
        $duplicatedStatus = Status::whereName('duplicated')->first()->id;

        foreach ($rawLeads as $key => $rawLead) {
            // Phone number
            $rawLead['phone_number'] = str_replace(' ', '', $rawLead['phone_number']);
            $rawLead['phone_number'] = $this->rectifyPhone($rawLead['phone_number']);

            $dupLead = Ticket::where('phone_number', 'LIKE' . "%{$rawLead['phone_number']}%")
                ->where('phone_number', '!=', '')
                ->first();

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
                'status_id' => $dupLead ? $duplicatedStatus : $newStatus,
                'source_id' => $this->getSourceID($rawLead['platform']),
                'assigner_id' => $manual ? auth()->user()->id : null,
                'method' => ($manual ? 'Manual ' : 'Automatic ') . ucfirst($source)
            ]);

            $leads[] = $lead;
        }

        return $leads;
    }

    public function fetchLeadsFromZapier($sourceName, $manual = null): array
    {
        $sourceName = strtolower($sourceName);

        if ($sourceName === 'facebook') {
            // Get source IDs for Facebook + Instagram
            $sourceIds = Source::whereIn('name', ['Facebook', 'Instagram'])->pluck('id');
        } else {
            // Normal case
            $sourceIds = Source::whereRaw('LOWER(name) = ?', [$sourceName])->pluck('id');
        }

        $rawLeads = TempLead::whereIn('source_id', $sourceIds)->get();

        $newStatus = Status::where('slug', 'new')->first()->id;
        $duplicatedStatus = Status::whereName('duplicated')->first()->id;

        $leads = [];
        foreach ($rawLeads as $key => $rawLead) {
            // Phone number
            $rawLead->phone_number = str_replace(' ', '', $rawLead['phone_number']);
            $rawLead->phone_number = $this->rectifyPhone($rawLead['phone_number']);

            $lead = new Ticket([
                'number' => $rawLead->id,
                'ad_id' => $rawLead->ad_id,
                'ad_name' => $rawLead->ad_name,
                'adset_id' => $rawLead->ad_name,
                'adset_name' => $rawLead->adset_id,
                'campaign_id' => $rawLead->campaign_id,
                'campaign_name' => $rawLead->campaign_name,
                'form_id' => $rawLead->form_id,
                'form_name' => $rawLead->form_name ?? '',
                'is_organic' => $rawLead->is_organic ?? '',
                'platform' => $rawLead->platform,
                'full_name' => $rawLead->full_name,
                'phone_number' => $rawLead->phone_number,
                'email' => $rawLead->email,
                'job_title' => $rawLead->job_title ?? '',
                'status_id' => $rawLead->status->id,
                'source_id' => $this->getSourceID($rawLead->platform),
                'assigner_id' => $manual ? auth()->user()->id : null,
                'method' => ($manual ? 'Manual ' : 'Automatic ') . ucfirst($sourceName),
                'extra_data' => $rawLead->extra_data,
            ]);

            $lead->key = $rawLead->id;
            $lead->created_at = $rawLead->created_at;

            $leads[] = $lead;
        }

        return $leads;
    }

    public function emptyZapierLeadsSheet($source, $leadsLength)
    {
        [$spread, $sheet] = $this->getSpreadsheetDetails($source);

//        for ($i = 0; $i < $leadsLength; $i++) {
//            Sheets::spreadsheet(config('sheets.' . $spread))
//                ->sheet(config('sheets.' . $sheet))
//                ->range('A' . ($i + 2))
//                ->update([['', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '']]);
//        }
    }

    public function initiateImport($leads)
    {
        $statuses = Status::whereIn('slug', ['new', 'follow-up']) // re-shuffled has been removed 12/9/2022
        ->get()
            ->pluck('id')
            ->toArray();

        $useCamps = boolval(GeneralSettings::whereName('use_camps')->first()->value);

        if (!$useCamps) {
            return $this->importWithNoCampaigns($leads, $statuses);
        } else {
            return $this->importCampaignsBased($leads, $statuses);
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
            $phoneNumber = $uaePrefix . ' ' . $phoneNumber;
        } else if (str_starts_with($phoneNumber, '+971')) {
            $phoneNumber = substr($phoneNumber, 4);
            $phoneNumber = $uaePrefix . ' ' . $phoneNumber;
        } else if (str_starts_with($phoneNumber, '971')) {
            $phoneNumber = substr($phoneNumber, 3);
            $phoneNumber = $uaePrefix . ' ' . $phoneNumber;
        } else if (str_starts_with($phoneNumber, '05')) {
            $phoneNumber = substr($phoneNumber, 1);
            $phoneNumber = $uaePrefix . ' ' . $phoneNumber;
        } else if (str_starts_with($phoneNumber, '5')) {
            $phoneNumber = $uaePrefix . ' ' . $phoneNumber;
        }

        return $phoneNumber;
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
            case 'ga':  // 30/11/2024 - Google Ads
                return Source::where('name', 'GoogleAds')->first()->id;
            default:
                return Source::where('name', 'Unspecified')->first()->id;
        }
    }

    public function notifyUser($usersIds, $title, $message, $link)
    {
        $usersIds = array_map('intval', explode(',', $usersIds));

        try {
            $fcmTokens = User::whereNotNull('fcm_token')
                ->whereIn('id', $usersIds)
                ->pluck('fcm_token')
                ->toArray();

            auth()->user()
                ->notify(new SendPushNotification(
                    $title,
                    $message . '|' . $link,
                    $fcmTokens
                ));

            /*
            Larafirebase::withTitle($request->title)
                ->withBody($request->message)
                ->sendMessage($fcmTokens);
            */
            return response()->json(['Successful' => 'OK!'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e], 404);
            // report($e);
        }
    }

    public function removeTempLeads($leadIds)
    {
        return TempLead::whereIn('id', $leadIds)->delete();
    }

    public function sendFcmNotification(int $userId, string $title, string $body, ?string $url = null, ?int $ticketId = null)
    {
        $tokens = FcmToken::where('user_id', 97)->get()->pluck('token')->toArray();

        if (empty($tokens)) {
            return;
        }

        /** @var \Kreait\Firebase\Messaging $messaging */
        $messaging = app('firebase.messaging');

        $payloadData = [
            'title' => $title,
            'body' => $body,
            'url' => $url ?? url('/tickets'), // مثال
            'ticket_id' => $ticketId ?? 0,
        ];

        $uniqueTokens = array_values(array_unique($tokens));
        foreach ($uniqueTokens as $token) {
            try {
                $message = CloudMessage::withTarget('token', $token)
                    ->withData($payloadData);

                $messaging->send($message);
            } catch (MessagingException $e) {
            } catch (FirebaseException $e) {
                return ['error' => $e->getMessage()];
            }
        }


    }

    public function sendLeadMail($recipients, $data)
    {
        try {
            Mail::to($recipients)->send(new LeadNotifyMail($data));

            // Check for failures
            if (count(Mail::failures()) > 0) {
                // Handle failures (if any)
                // You can log or perform any other action here
                // Note: Failures will only be available if the driver supports it (e.g., SMTP)
            }

            // Continue execution

        } catch (\Exception $exception) {
            // Handle exceptions (if any)
            // Log or perform any other action
        }
    }


    public function assignLeadsBalanced(array $leadIds): array
    {
        // 0) تنظيف المدخلات
        $leadIds = array_values(array_unique(array_map('intval', $leadIds)));
        $leadIds = array_values(array_filter($leadIds, fn($id) => $id > 0));

        if (empty($leadIds)) {
            return [
                'ok' => false,
                'message' => 'No valid lead IDs provided.',
                'missing_ids' => [],
                'assigned' => [],
            ];
        }

        // 1) تحقق من وجود الـ IDs فعلاً
        $existingIds = Ticket::query()
            ->whereIn('id', $leadIds)
            ->pluck('id')
            ->all();

        $missingIds = array_values(array_diff($leadIds, $existingIds));

        if (!empty($missingIds)) {
            // إذا بدك تتجاهل المفقود وتكمل، شيل هالـ return وخليها warning.
            return [
                'ok' => false,
                'message' => 'Some lead IDs do not exist in tickets table.',
                'missing_ids' => $missingIds,
                'assigned' => [],
            ];
        }

        // 2) جيب الـ leads (Tickets)
        $leads = Ticket::query()
            ->whereIn('id', $leadIds)
            ->get(['id', 'user_id', 'status_id']); // زيد أعمدة إذا بدك

        // 3) statuses اللي بنحسب عليها الحمل
        $statusIds = Status::query()
            ->whereIn('slug', ['new', 'follow-up'])
            ->pluck('id')
            ->all();

        // 4) جيب المستخدمين (sale + tele-sale) permitted
        // ملاحظة: role() من Spatie تقبل array
        $users = User::query()
            ->role(['sale', 'tele-sale'])
            ->where('status', 'permitted')
            ->get(['id', 'name']);

        if ($users->isEmpty()) {
            return [
                'ok' => false,
                'message' => 'No eligible users (sale/tele-sale) found.',
                'missing_ids' => [],
                'assigned' => [],
            ];
        }

        $userIds = $users->pluck('id')->all();

        // 5) احسب الحمل الحالي لكل user باستعلام واحد
        $loads = Ticket::query()
            ->select('user_id', DB::raw('COUNT(*) as cnt'))
            ->whereIn('user_id', $userIds)
            ->whereIn('status_id', $statusIds)
            ->groupBy('user_id')
            ->pluck('cnt', 'user_id')   // [user_id => cnt]
            ->all();

        // جهز عدادات تبدأ بالحمل الحالي (اللي مو موجود نخليه 0)
        $current = [];
        foreach ($userIds as $uid) {
            $current[$uid] = (int)($loads[$uid] ?? 0);
        }

        // 6) التوزيع المتوازن (Greedy: دائماً اختار أقل user حمل)
        // راح نطلع assignments: [ticket_id => user_id]
        $assignments = [];
        $assignedCounts = array_fill_keys($userIds, 0);

        // لتحسين الأداء: رتّب users by current load مبدئياً
        // وبكل مرة نختار min (لأعداد صغيرة، هذا كافي. لو آلاف users نعمل heap)
        foreach ($leads as $lead) {
            // find user with min current load
            $minUserId = array_key_first($current);
            $minLoad = $current[$minUserId];

            foreach ($current as $uid => $load) {
                if ($load < $minLoad) {
                    $minLoad = $load;
                    $minUserId = $uid;
                }
            }

            $assignments[$lead->id] = $minUserId;
            $assignedCounts[$minUserId]++;

            // update load
            $current[$minUserId]++;
        }

        // 7) طبّق التحديثات (Bulk update عبر CASE WHEN)
        DB::transaction(function () use ($assignments) {
            if (empty($assignments)) return;

            $ids = array_keys($assignments);

            $caseSql = "CASE id ";
            $bindings = [];
            foreach ($assignments as $ticketId => $userId) {
                $caseSql .= "WHEN ? THEN ? ";
                $bindings[] = $ticketId;
                $bindings[] = $userId;
            }
            $caseSql .= "END";

            // update tickets set user_id = CASE ... WHERE id IN (...)
            DB::table('tickets')
                ->whereIn('id', $ids)
                ->update([
                    'user_id' => DB::raw($caseSql),
                    'updated_at' => now(),
                ], $bindings); // ⚠️ لو ORM ما يقبل bindings هون، نعمل statement مباشرة (أعطيكها إذا لزم)
        });

        // 8) رجّع تقرير واضح
        // (assignedCounts فيها فقط الجديد، current فيها (load بعد التوزيع))
        $result = [
            'ok' => true,
            'missing_ids' => [],
            'total_leads' => count($leadIds),
            'eligible_users' => $users->count(),
            'assigned_counts' => collect($assignedCounts)->filter(fn($v) => $v > 0)->all(),
            'final_loads' => $current, // الحمل بعد التوزيع (اختياري)
            'assignments' => $assignments, // ticket_id => user_id (اختياري)
        ];

        return $result;
    }

    public function reshuffleAndAssign(array $leadIds): array
    {
        // 0) Sanitize IDs
        $leadIds = array_values(array_unique(array_map('intval', $leadIds)));
        $leadIds = array_values(array_filter($leadIds, fn ($id) => $id > 0));

        if (empty($leadIds)) {
            return [
                'ok' => false,
                'message' => 'No valid lead IDs provided.',
                'missing_ids' => [],
            ];
        }

        // 1) Validate existence + fetch tickets (need phone_number for duplicate detection)
        $tickets = Ticket::query()
            ->whereIn('id', $leadIds)
            ->get(['id', 'user_id', 'status_id', 'phone_number']);

        $existingIds = $tickets->pluck('id')->all();
        $missingIds  = array_values(array_diff($leadIds, $existingIds));

        if (!empty($missingIds)) {
            return [
                'ok' => false,
                'message' => 'Some lead IDs do not exist in tickets table.',
                'missing_ids' => $missingIds,
            ];
        }

        // 2) Status IDs
        $newStatusId = Status::query()
            ->where('name', Status::NEW)
            ->value('id');

        $duplicatedStatusId = Status::query()
            ->where('name', Status::DUPLICATED)
            ->value('id');

        if (!$newStatusId || !$duplicatedStatusId) {
            return [
                'ok' => false,
                'message' => 'Required statuses not found (new/duplicated).',
                'missing_ids' => [],
            ];
        }

        $loadStatusIds = Status::query()
            ->whereIn('name', [Status::NEW, Status::FOLLOW_UP])
            ->pluck('id')
            ->all();

        // 3) Eligible users: sale + tele-sale (permitted)
        $eligibleUserIds = User::query()
            ->role(['sale', 'tele-sale'])
            ->where('status', 'permitted')
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->filter(fn ($v) => $v > 0)
            ->values()
            ->all();

        if (empty($eligibleUserIds)) {
            return [
                'ok' => false,
                'message' => 'No eligible users found (sale/tele-sale, permitted).',
                'missing_ids' => [],
            ];
        }

        // 4) Current load per user (new/follow-up) - one query
        $loads = Ticket::query()
            ->select('user_id', DB::raw('COUNT(*) as cnt'))
            ->whereIn('user_id', $eligibleUserIds)
            ->whereIn('status_id', $loadStatusIds)
            ->groupBy('user_id')
            ->pluck('cnt', 'user_id')
            ->all();

        $currentLoad = [];
        foreach ($eligibleUserIds as $uid) {
            $currentLoad[$uid] = (int) ($loads[$uid] ?? 0);
        }

        // 5) Sort eligible users by load ASC (tie-breaking baseline)
        uasort($currentLoad, fn ($a, $b) => $a <=> $b);
        $eligibleUserIds = array_keys($currentLoad);

        // 6) Detect which phone numbers are duplicated in DB (before updating)
        $phones = $tickets->pluck('phone_number')
            ->map(fn($p) => is_string($p) ? trim($p) : $p)
            ->filter(fn($p) => !empty($p))
            ->unique()
            ->values()
            ->all();

        $dupPhones = [];
        if (!empty($phones)) {
            $dupPhones = Ticket::query()
                ->whereIn('phone_number', $phones)
                ->where('phone_number', '!=', '')
                ->groupBy('phone_number')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('phone_number')
                ->all();
        }

        $dupPhonesSet = array_fill_keys($dupPhones, true);

        // 7) Build next_status per ticket
        $nextStatusByTicketId = [];
        foreach ($tickets as $t) {
            $phone = is_string($t->phone_number) ? trim($t->phone_number) : (string)($t->phone_number ?? '');
            $nextStatusByTicketId[$t->id] = isset($dupPhonesSet[$phone]) ? $duplicatedStatusId : $newStatusId;
        }

        // 8) Balanced assignment (pick least-loaded, but skip if same as prev_user)
        $assignments   = []; // [ticket_id => user_id]
        $assignedStats = []; // [user_id => newly_assigned_count]

        foreach ($tickets as $t) {
            $prevUserId = (int) ($t->user_id ?? 0);

            $selectedUserId = null;
            $selectedLoad   = null;

            foreach ($eligibleUserIds as $uid) {
                $uid = (int) $uid;

                if ($uid === $prevUserId) {
                    continue; // must NOT assign to same previous user
                }

                $load = $currentLoad[$uid] ?? 0;

                if ($selectedUserId === null || $load < $selectedLoad) {
                    $selectedUserId = $uid;
                    $selectedLoad   = $load;
                }
            }

            if ($selectedUserId === null) {
                return [
                    'ok' => false,
                    'message' => 'Reshuffle not possible: only one eligible user and it matches the current assigned user.',
                    'missing_ids' => [],
                ];
            }

            $assignments[$t->id] = $selectedUserId;
            $assignedStats[$selectedUserId] = ($assignedStats[$selectedUserId] ?? 0) + 1;
            $currentLoad[$selectedUserId]++; // update in-memory load
        }

        // 9) Apply changes atomically (tickets update + ticket_paths insert)
        DB::transaction(function () use ($tickets, $assignments, $nextStatusByTicketId) {
            $ids = array_keys($assignments);
            if (empty($ids)) return;

            $now = now();

            // CASE for user_id
            $caseUser = "CASE id ";
            foreach ($assignments as $ticketId => $userId) {
                $ticketId = (int) $ticketId;
                $userId   = (int) $userId;
                $caseUser .= "WHEN {$ticketId} THEN {$userId} ";
            }
            $caseUser .= "END";

            // CASE for status_id
            $caseStatus = "CASE id ";
            foreach ($nextStatusByTicketId as $ticketId => $statusId) {
                $ticketId = (int) $ticketId;
                $statusId = (int) $statusId;
                $caseStatus .= "WHEN {$ticketId} THEN {$statusId} ";
            }
            $caseStatus .= "END";

            DB::table('tickets')
                ->whereIn('id', $ids)
                ->update([
                    'user_id'    => DB::raw($caseUser),
                    'status_id'  => DB::raw($caseStatus),
                    'updated_at' => $now,
                ]);

            // Insert ticket_paths
            $rows = [];
            foreach ($tickets as $t) {
                if (!isset($assignments[$t->id])) {
                    continue;
                }

                $rows[] = [
                    'ticket_id'   => (int) $t->id,
                    'prev_user'   => $t->user_id,
                    'next_user'   => (int) $assignments[$t->id],
                    'prev_status' => $t->status_id,
                    'next_status' => (int) $nextStatusByTicketId[$t->id],
                    'comment'     => 'Reshuffled',
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ];
            }

            DB::table('ticket_paths')->insert($rows);
        });

        return [
            'ok' => true,
            'message' => 'OK',
            'missing_ids' => [],
            'total_fetched' => count($leadIds),
            'total_assigned' => array_sum($assignedStats),
            'assigned_stats' => $assignedStats,
        ];
    }
}
