<?php

namespace App\Http\Controllers;

use App\Facades\Notifier;
use App\Helpers\LeadsHelper;
use App\Imports\TicketsImport;
use App\Mail\LeadNotifyMail;
use App\Models\Booking;
use App\Models\GeneralSettings;
use App\Models\Meeting;
use App\Models\Source;
use App\Models\Status;
use App\Models\TempLead;
use App\Models\Ticket;
use App\Models\TicketPath;
use App\Models\User;
use App\Services\LeadAutoAssignService;
use Mccarlosen\LaravelMpdf\Facades\LaravelMpdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Mail;
use App\Models\ArchivedLead;
use Mpdf\MpdfException;
use Spatie\Permission\Models\Role;

//use Carbon\Carbon;

class TicketController extends Controller
{
    private LeadsHelper $leadsHelper;
    private LeadAutoAssignService $assignService;

    /**
     * Create a new OrderController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->leadsHelper = new LeadsHelper();
        $this->assignService = new LeadAutoAssignService($this->leadsHelper);
        $this->middleware('auth')->except(['devTest', 'storeLead']);
    }

    /**
     * Display a listing of the resource.
     * @param \Illuminate\Http\Request $request
     * @param string $status
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $wnotif = new WebNotificationController();
        //$wnotif->sendNotification([58], 'Hello 1st alert', 'This is the first alert from WolfGrey!');

        //////////////////////////////////////////
        $roles = Role::all()->pluck('name')->toArray();

        if (!auth()->user()->hasPermissionTo('list tickets') && !auth()->user()->hasPermissionTo('create invoice') && !auth()->user()->hasAnyRole($roles)) {
            return abort(404);
        }

        $filterParams = $this->leadsHelper->getLeadsFilterParams($request);

        $leadId = $request->query('lead_id');

        $tickets = null;

        // Status filter
        if (isset($filterParams['status']) && !empty($filterParams['status']) && $filterParams['status'] !== 'all') {
            $theStatus = Status::whereSlug($filterParams['status'])->first();

            $tickets = Ticket::whereStatusId($theStatus->id);

            if (auth()->user()->hasRole('admin')) {
                $statuses = Status::whereIn('slug', ['booking', 'rejected'])
                    ->get()
                    ->pluck('id')
                    ->toArray();

                $tickets = Ticket::whereIn('status_id', $statuses);
            } else if (auth()->user()->hasRole('accountant') and ($filterParams['status'] !== 'booking' and $filterParams['status'] !== 'approved' and $filterParams['status'] !== 'sold')) {
                $statuses = Status::whereIn('slug', ['approved', 'sold'])
                    ->get()
                    ->pluck('id')
                    ->toArray();

                $tickets = Ticket::whereIn('status_id', $statuses); //->whereUserId(auth()->user()->id)
            } elseif (auth()->user()->hasRole('sales-manager')) {
                $employeeIDs = User::where('manager_id', auth()->user()->id)
                    ->get()
                    ->pluck('id')
                    ->toArray();

                $employeeIDs[] = auth()->user()->id;
                $tickets = $tickets->whereIn('user_id', $employeeIDs);
            } else if (auth()->user()->hasAnyRole(['sale', 'tele-sale'])) {
                $tickets = $tickets->whereUserId(auth()->user()->id);
            }
        } else {
            $tickets = Ticket::where('id', '>', 0);
            if (auth()->user()->hasRole('admin')) {
                $statuses = Status::whereIn('slug', ['booking', 'rejected'])
                    ->get()
                    ->pluck('id')
                    ->toArray();

                $tickets = $tickets->whereIn('status_id', $statuses);
            } else if (auth()->user()->hasAnyRole(['sale', 'tele-sale'])) {
                $status = Status::whereIn('slug', ['rejected'])->first();
                $tickets = Ticket::whereUserId(auth()->user()->id)->where('status_id', '!=', $status);
            } else if (auth()->user()->hasRole('accountant')) {
                $statuses = Status::whereIn('slug', ['approved', 'sold'])
                    ->get()
                    ->pluck('id')
                    ->toArray();

                $tickets = Ticket::whereIn('status_id', $statuses); //->whereUserId(auth()->user()->id)
                //->whereUserId(auth()->user()->id)
            } elseif (auth()->user()->hasRole('sales-manager')) {
                $employeeIDs = User::where('manager_id', auth()->user()->id)
                    ->get()
                    ->pluck('id')
                    ->toArray();

                $employeeIDs[] = auth()->user()->id;
                $tickets = Ticket::whereIn('user_id', $employeeIDs);
            }
        }

        // ID filter
        if (($leadId and $leadId !== '')) {
            $tickets = $tickets->where('id', '=', $leadId);
        }

        // Sales filter
        if (($filterParams['sale'] and $filterParams['sale'] !== '' and $filterParams['sale'] !== 'all')) {
            $tickets = $tickets->where('user_id', '=', $filterParams['sale']);
        }

        $tickets = $this->leadsHelper->filterLeads($filterParams, $tickets);

        if (!auth()->user()->hasRole('super-admin')) {
            $dupStatus = Status::whereSlug('duplicated')->first();
            $tickets = $tickets->where('status_id', '!=', $dupStatus->id);

            $deadStatus = Status::whereSlug('dead')->first();
            $tickets = $tickets->where('status_id', '!=', $deadStatus->id);
        }

        $tickets = $tickets->orderBy('created_at', 'DESC')->with('user')
            //->get();
            ->paginate(50)
            ->appends(request()->query());

        //dd($tickets);

        $statuses = Status::all();

        foreach ($statuses as $key => $status) {
            if ($status->slug === 'rejected') {
                if (!auth()->user()->hasRole(['super-admin', 'sales-manager', 'admin'])) {
                    unset($statuses[$key]);
                }
            }

            if ($status->slug === 'duplicated' or $status->slug === 'dead' or $status->slug === 'dead-tele') {
                if (!auth()->user()->hasRole(['super-admin'])) {
                    unset($statuses[$key]);
                }
            }

            // Now, we don't have TELE
            if (str_ends_with($status->slug, 'tele')) {
                unset($statuses[$key]);
            }

            // 10/12/2023 - Sales => No 'dead', 're-shuffled', 'reviewed', 'approved', 'sold' and 'pre-approved'
            $salesBlocked = ['dead', 're-shuffled', 'reviewed', 'approved', 'sold', 'pre-approved'];
            if (in_array($status->slug, $salesBlocked)) {
                if (auth()->user()->hasAnyRole('sale')) {
                    unset($statuses[$key]);
                }
            }
        }

        $sales = null;
        if (auth()->user()->hasRole('super-admin')) {
            $sales = User::role(['sale', 'tele-sale'])
                ->where('status', 'permitted')
                ->get();
        } elseif (auth()->user()->hasRole('sales-manager')) {
            $sales = User::whereManagerId(auth()->user()->id)
                ->where('status', 'permitted')
                ->get();
        }

        $fUpStatus = Status::whereSlug('follow-up')->first();

        // ---- last follow-up preview (no N+1) ---- //

        $ticketIds = collect($tickets->items())->pluck('id')->values();

        $latestFollowUps = $ticketIds->isEmpty()
            ? collect()
            : TicketPath::query()
                ->select(['ticket_id', 'comment', 'next_user', 'updated_at'])
                ->whereIn('ticket_id', $ticketIds)
                ->where('next_status', $fUpStatus->id)
                ->orderBy('updated_at', 'DESC')
                ->get()
                ->groupBy('ticket_id')
                ->map(fn($rows) => $rows->first());

        $isSalesUser = auth()->user()->hasAnyRole(['sale', 'tele-sale']);
        $authUserId = (int)auth()->id();

        foreach ($tickets as $ticket) {
            $ticket->user = $ticket->user ?: [];
            $ticket->lastFollowUp = '-';

            $tPath = $latestFollowUps->get($ticket->id);
            if (!$tPath) {
                continue;
            }

            // Sales & tele‑sale can only see their own follow‑ups
            if ($isSalesUser && (int)$tPath->next_user !== $authUserId) {
                continue;
            }

            $comment = (string)($tPath->comment ?? '');
            $ticket->lastFollowUp = mb_strlen($comment) <= 75
                ? $comment
                : mb_substr($comment, 0, 75) . '...';
        }

        $resultParams = [
            'sales'         => $sales,
            'tickets'       => $tickets,
            'statuses'      => $statuses,
            'currentStatus' => $filterParams['status'],
            'currentSale'   => $filterParams['sale'],
            'from'          => $filterParams['from'],
            'to'            => $filterParams['to'],
            'updatedFrom'   => $filterParams['updated_from'],
            'updatedTo'     => $filterParams['updated_to'],
            'camp'          => $filterParams['camp'],
            'fullName'      => $filterParams['fullName'],
            'phone'         => $filterParams['phone'],
        ];

        session()->flashInput($request->input());

        if ($request->isMethod('get')) {
            return view('tickets.index')->with($resultParams);
        } else {
            return view('tickets.index')
                ->with($resultParams)
                ->withInput($request->all());
        }
    }

    /**
     * Show om  form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function create()
    {
        parent::hasPermission('add ticket');

        $statuses = Status::all();

        // Now, we don't have TELE
        foreach ($statuses as $key => $status) {
            if (str_ends_with($status->slug, 'tele')) {
                unset($statuses[$key]);
            }
        }

        $users = null;

        if (!auth()->user()->hasAnyRole(['sale', 'tele-sale'])) {
            $users = User::role(['sale', 'tele-sale'])
                ->where('status', 'permitted')
                ->get();

            foreach ($users as $key => $user) {
                $user->role = count($user->roles) > 0 ? $user->roles[0]->name : '-';

                $meeting = Status::whereSlug('meeting')->first();
                $userTickets = Ticket::where('user_id', $user->id)->where('status_id', $meeting->id)->get();

                if (count($userTickets) > 0) {
                    unset($users[$key]);
                }
            }
        }

        $sources = Source::all();

        return view('tickets.create')->with([
            'statuses' => $statuses,
            'users' => $users,
            'sources' => $sources
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        parent::hasPermission('add ticket');

        $this->validateLead($request);

        $source = Source::find($request->source);

        if (!$source) {
            return back()->withErrors(['msg' => 'Source is not exists!'])->withInput($request->all());
        }

        if (auth()->user()->hasAnyRole(['sale', 'tele-sale'])) {
            $user = auth()->user();
            if ($user->status === 'banned') {
                return back()->withErrors(['msg' => "You cannot create a lead while you're banned!"])
                    ->withInput($request->all());
            }

            if (isset($request->user))
                return back()->withErrors(['msg' => 'You cannot assign a lead to others!'])
                    ->withInput($request->all());
            else {
                $sender = 'you';
            }
        } else {
            $user = User::find($request->user);
            $sender = auth()->user()->getRoleNames()[0];
        }

        if (!$user) {
            return back()->withErrors(['msg' => 'User is not exists!'])->withInput($request->all());
        }

        $statusNew = Status::where('slug', 'new')->first();
        $duplicatedStatus = Status::whereName('duplicated')->first();

        // Phone number
        $request->phone_number = str_replace(' ', '', $request->phone_number);
        $request->phone_number = $this->leadsHelper->rectifyPhone($request->phone_number);

        $newTicket = Ticket::create([
            'campaign_name' => $request->campaign_name,
            'full_name' => $request->full_name,
            'email' => $request->email ?? null,
            'phone_number' => $request->phone_number,
            'user_id' => $user->id,
            'source_id' => $source->id,
            'status_id' => $statusNew->id,
            'assigner_id' => auth()->user()->id,
            'method' => 'Manual',
            'preferred_time' => $request->preferred_time ?? null,
            'remarks' => $request->remarks ?? null,
        ]);

        $dupLead = Ticket::wherePhoneNumber($request->phone_number)
            ->where('phone_number', '!=', '')
            ->where('id', '!=', $newTicket->id)
            ->first();

        if ($dupLead !== null) {
            $newTicket->status_id = $duplicatedStatus->id;
            $newTicket->user_id = null;
        } else {
            TicketPath::create([
                'next_user' => $user->id,
                'next_status' => $statusNew->id,
                'ticket_id' => $newTicket->id,
                'comment' => 'Initial ticket creation.',
            ]);
        }

        $saved = $newTicket->save();

        if ($saved) {
            //////// Notify Users

            // Super admin
            $data = [
                'title' => 'New Lead',
                'message' => 'A new lead has been assigned by ' . $sender . ' to user: ',
                'user' => $user->name,
                'ticket' => $newTicket->id
            ];

            $superAdmins = User::role('super-admin')
                ->get()
                ->pluck('email')
                ->toArray();

            //Mail::to($superAdmins)->send(new LeadNotifyMail($data));

            $this->sendLeadMail($superAdmins, $data);


            if ($newTicket->user) {
                // User himself
                $data = [
                    'title' => 'New Lead',
                    'message' => 'A new lead has been assigned by super-admin to you!',
                    'user' => '',
                    'ticket' => $newTicket->id
                ];

                // Mail::to($user->email)->send(new LeadNotifyMail($data));

                $this->sendLeadMail($user->email, $data);

            }

            return redirect()->route('tickets.all');
        }

        return back()->withErrors(['msg' => 'Unable to create ticket.'])->withInput($request->all());
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\Response
     */
    public function show($id)
    {
        parent::hasPermission('show ticket');

        $ticket = Ticket::find($id);

        if (!$ticket) {
            return abort(404);
        }

        $ticket->loadMissing([
            'paths.prevUser:id,name',
            'paths.nextUser:id,name',
            'paths.prevStatus:id,name,slug',
        ]);

        if (auth()->user()->hasAnyRole(['sale', 'tele-sale']) and $ticket->user_id != auth()->user()->id) {
            return back()->withErrors(['msg' => 'Unauthorised Access.']);
        }

        // 10/12/2023 - Accountant -> Approved
        $roles = ['sale', 'tele-sale'];

        if (auth()->user()->hasRole(['super-admin']) && $ticket->status->slug === 'pre-approved') {
            array_push($roles, 'accountant');
        }

        $users = User::role($roles)
            ->where('status', 'permitted')
            ->get();

        foreach ($users as $user) {
            $user->role = count($user->roles) > 0 ? $user->roles[0]->name : '-';
        }

        $dead = Status::where('slug', 'dead')->first();

        $statuses = Status::all();

        foreach ($statuses as $key => $status) {
            if ($status->slug === 'rejected') {
                if (!auth()->user()->hasRole(['super-admin', 'sales-manager', 'admin'])) {
                    unset($statuses[$key]);
                }
            }

            if ($status->slug === 'duplicated' or $status->slug === 'new') {
                if (!auth()->user()->hasRole(['super-admin'])) {
                    unset($statuses[$key]);
                }
            }

            if ($status->slug == 'reviewed' || $status->slug == 'approved' || $status->slug == 'pre-approved' || $status->slug == 'sold') {
                if (auth()->user()->hasAnyRole('sale', 'tele-sale')) {
                    unset($statuses[$key]);
                }
            }

            // Now, we don't have TELE
            if (str_ends_with($status->slug, 'tele')) {
                unset($statuses[$key]);
            }

            // 10/12/2023 - Sales => No Dead & Re-shuffle
            if ($status->slug == 'dead' || $status->slug == 're-shuffled') {
                if (auth()->user()->hasAnyRole('sale')) {
                    unset($statuses[$key]);
                }
            }
        }

        if (auth()->user()->hasRole('admin') && $ticket->status->slug === 'rejected') {
            $revStatus = Status::where('slug', 'reviewed')->get();
            $statuses = $revStatus->merge($statuses);
        }

        $booking = Booking::where('ticket_id', $ticket->id)->first();
        $invoice = null;
        $passport = null;
        $sources = Source::all();

        $user = auth()->user();
        $isSalesUser = $user->hasAnyRole($roles);

        $pathsQuery = $ticket->paths()
            ->with([
                'prevUser:id,name',
                'nextUser:id,name',
                'prevStatus:id,name,slug',
            ]);

        if ($isSalesUser) {
            $pathsQuery->where('next_user', $user->id);
        }

        $paths = $pathsQuery->get();

        $paths->transform(function ($path) use ($isSalesUser) {
            $sameUser = $path->prevUser && $path->nextUser
                && (int)$path->prevUser->id === (int)$path->nextUser->id;

            $path->show_prev_status_block = $isSalesUser ? $sameUser : true;

            return $path;
        });

        $ticketPaths = $paths->groupBy(fn($path) => $path->created_at->toDateString());

        $ticket->extra_data = json_decode($ticket->extra_data, true); // Decode JSON

        $results = [
            'ticket' => $ticket,
            'ticketPaths' => $ticketPaths,
            'users' => $users,
            'dead' => $dead,
            'statuses' => $statuses,
            'booking' => $booking,
            'invoice' => $invoice,
            'sources' => $sources,
        ];

        return view('tickets.show')->with($results);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        // parent::hasPermission('edit ticket');

        $ticket = Ticket::find($id);

        $user = User::find($ticket->user_id);

        $rules = [
            'full_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:255'],
        ];

        $messages = [
            'integer' => 'The :attribute field should be integer.',
            'string' => 'The :attribute field should be string.',
            'gt:0' => 'The :attribute field should be positive.'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors())->withInput($request->all());
        }

        if (auth()->user()->hasAnyRole(['sale', 'tele-sale'])) {
            if (count($request->all()) > 3) {
                return back()->withErrors(['msg' => 'You can change only full name.'])
                    ->withInput($request->all());
            } else {
                if ($request->has('full_name')) {
                    $ticket->full_name = $request->full_name;
                } else {
                    return back()->withErrors(['msg' => 'Please provide valid full name.'])
                        ->withInput($request->all());
                }
            }
        } elseif (auth()->user()->hasAnyRole(['super-admin', 'admin'])) {
            if ($request->has('number')) {
                $ticket->number = $request->number;
            }

            if ($request->has('ad_id')) {
                $ticket->ad_id = $request->ad_id;
            }

            if ($request->has('ad_name')) {
                $ticket->ad_name = $request->ad_name;
            }

            if ($request->has('adset_id')) {
                $ticket->adset_id = $request->adset_id;
            }

            if ($request->has('adset_name')) {
                $ticket->adset_name = $request->adset_name;
            }

            if ($request->has('campaign_id')) {
                $ticket->campaign_id = $request->campaign_id;
            }

            if ($request->has('campaign_name')) {
                $ticket->campaign_name = $request->campaign_name;
            }

            if ($request->has('form_id')) {
                $ticket->form_id = $request->form_id;
            }

            if ($request->has('form_name')) {
                $ticket->form_name = $request->form_name;
            }

            if ($request->has('is_organic')) {
                $ticket->is_organic = 1;
            } else {
                $ticket->is_organic = 0;
            }

            if ($request->has('full_name')) {
                $ticket->full_name = $request->full_name;
            }

            if ($request->has('email')) {
                $ticket->email = $request->email;
            }

            if ($request->has('phone_number')) {
                // Phone number
                $request->phone_number = str_replace(' ', '', $request->phone_number);
                $request->phone_number = $this->leadsHelper->rectifyPhone($request->phone_number);

                $ticket->phone_number = $request->phone_number;
            }
        } else {
            return back()->withErrors(['msg' => 'Unauthorized Access.'])->withInput($request->all());
        }

        $saved = $ticket->save();

        if ($saved) {
            /** Notify Users  */

            $data = [
                'title' => 'Lead Update',
                'message' => 'Lead has been updated by user: ',
                'user' => $user ? $user->name : '_',
                'ticket' => $ticket->id
            ];

            $superAdmins = User::role('super-admin')
                ->get()
                ->pluck('email')
                ->toArray();

            // Mail::to($superAdmins)->send(new LeadNotifyMail($data));

            $this->sendLeadMail($superAdmins, $data);

            if ($user) {
                // User himself
                $data = [
                    'title' => 'Lead Update',
                    'message' => 'Lead has been updated by you!',
                    'user' => '',
                    'ticket' => $ticket->id
                ];

                // Mail::to($user->email)->send(new LeadNotifyMail($data));

                $this->sendLeadMail($user->email, $data);
            }

            return redirect()->route('tickets.show', $ticket->id);
        }

        return back()->withErrors(['msg' => 'Unable to create ticket.'])->withInput($request->all());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        parent::hasPermission('delete ticket');

        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json(['error' => 'No such ticket.'], 404);
        }

        Ticket::destroy($id);

        $data = [
            'title' => 'Lead Delete',
            'message' => 'Lead has been deleted by user: ',
            'user' => auth()->user()->name,
            'ticket' => $ticket->id
        ];

        $superAdmins = User::role('super-admin')
            ->get()
            ->pluck('email')
            ->toArray();

        //            dd($data);

        // Mail::to($superAdmins)->send(new LeadNotifyMail($data));

        $this->sendLeadMail($superAdmins, $data);


        // User himself
        $data = [
            'title' => 'Lead Delete',
            'message' => 'Lead has been deleted by you!',
            'user' => '',
            'ticket' => $ticket->id
        ];

        // Mail::to(auth()->user()->email)->send(new LeadNotifyMail($data));

        $this->sendLeadMail(auth()->user()->email, $data);

        return response()->json(['OK' => 'Deleted. ' . $id], 200);
    }

    public function showImportLeads($source)
    {
        parent::hasPermission('facebook import');

        if (!in_array($source, ['excel', 'facebook', 'tiktok', 'googleAds'])) { // 30-11-2024 GoogleAds
            return redirect()->route('tickets.all');
        }

        if ($source === 'excel') {
            $leads = Session::get('leads');

            return view('tickets.excel')
                ->with(['leads' => $leads]);
        }

        $leads = $this->leadsHelper->fetchLeadsFromZapier($source, 'Manual');

        $sales = null;

        if (auth()->user()->hasRole('super-admin')) {
            $salesAll = User::role(['sale', 'tele-sale'])
                ->where('status', 'permitted')
                ->get();

            $sales = $salesAll->groupBy(function ($salesEmp) {
                return $salesEmp->getRoleNames()->first();
            });
        } elseif (auth()->user()->hasRole('sales-manager')) {
            $salesAll = User::whereManagerId(auth()->user()->id)
                ->where('status', 'permitted')
                ->get();

            $sales = $salesAll->groupBy(function ($salesEmp) {
                return $salesEmp->getRoleNames()[0];
            });
        }

        $key = 'auto_import_' . $source;
        $autoImportValue = optional(
            GeneralSettings::whereName($key)->first()
        )->value ?? '0';

        return view('tickets.showImports')->with([
            'tickets' => $leads,
            'sales' => $sales ? $sales->map(function ($group) {
                return $group->toArray();
            })->toArray() : [],
            'source' => $source,
            'auto_import_key' => $key,
            'auto_import_value' => $autoImportValue, // 0 or 1
        ]);
    }

    public function importFromExcelFile(Request $request): \Illuminate\Http\RedirectResponse
    {
        parent::hasPermission('excel import');

        /// View leads
        if ($request->operation === 'view') {
            $rules = [
                'file' => 'required',
                'file.*' => 'file|mimes:csv,xls,xlsx|max:2048'
            ];

            $messages = [
                'required' => 'The :attribute field is required.',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            if ($validator->fails()) {
                return back()->withErrors($validator->errors())->withInput($request->all());
            }

            if ($request->file()) {
                try {
                    $array = Excel::toArray(new TicketsImport, $request->file('file'));

                    $cols = [
                        'id',
                        'created_time',
                        'ad_id',
                        'ad_name',
                        'adset_id',
                        'adset_name',
                        'campaign_id',
                        'campaign_name',
                        'form_id',
                        'form_name',
                        'is_organic',
                        'platform',
                        'interested_in',
                        'preferred_time',
                        'phone_number',
                        'full_name',
                        'email',
                    ];

                    if (sizeof($array[0]) === 0) {
                        return back()->withErrors(['msg' => 'File is empty.'])->withInput($request->all());
                    }

                    $file_cols = array_keys($array[0][0]);

                    if ($cols !== $file_cols) {
                        return back()->withErrors(['msg' => 'Invalid file columns.'])->withInput($request->all());
                    }

                    $array = array_merge(...$array);
                    $leads = [];

                    foreach ($array as $row) {
                        $row['id'] = $this->removeColon($row['id']);
                        $row['ad_id'] = $this->removeColon($row['ad_id']);
                        $row['adset_id'] = $this->removeColon($row['adset_id']);
                        $row['campaign_id'] = $this->removeColon($row['campaign_id']);
                        $row['form_id'] = $this->removeColon($row['form_id']);
                        $row['preferred_time'] = $this->removeColon($row['preferred_time']);
                        $row['interested_in'] = $this->removeColon($row['interested_in']);

                        // Phone number
                        $row['phone_number'] = $this->leadsHelper->rectifyPhone($this->removeColon($row['phone_number']));

                        $lead = [
                            'number' => $row['id'] ?? '',
                            'ad_id' => $row['ad_id'] ?? '',
                            'ad_name' => $row['ad_name'],
                            'adset_id' => $row['ad_name'] ?? '',
                            'adset_name' => $row['adset_id'],
                            'campaign_id' => $row['campaign_id'] ?? '',
                            'campaign_name' => $row['campaign_name'],
                            'form_id' => $row['form_id'] ?? '',
                            'form_name' => $row['form_name'],
                            'is_organic' => $row['is_organic'],
                            'platform' => $row['platform'],
                            'full_name' => $row['full_name'],
                            'phone_number' => $row['phone_number'],
                            'email' => $row['email'],
                            'job_title' => $row['job_title'] ?? null,
                            'created_time' => date('Y-m-d H:i:s', strtotime($row['created_time'])),
                            'source' => $row['platform'],
                            'preferred_time' => $row['preferred_time'] ?? '',
                            'remarks' => $row['interested_in'] ?? '',
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ];

                        $leads[] = $lead;
                    }
                } catch (\Exception $e) {
                    return back()->withErrors(['msg' => 'Invalid file.'])->withInput($request->all());
                }

                Cache::put('leads', $leads, now()->addMinutes(10));

                return redirect()->back()->withInput($request->all())->with(['leads' => $leads]);
            }

            return redirect()->back()->withErrors(['msg' => 'Missing data file.'])->withInput($request->all());
        } elseif ($request->operation === 'archive') {
            $leads = Cache::get('leads');
            ArchivedLead::insert($leads);

            return redirect()->route('tickets.archived');
        } else {
            $leadsArr = Cache::get('leads');
            $leads = [];

            $newStatus = Status::where('slug', 'new')->first()->id;
            $duplicateStatus = Status::whereName('duplicated')->first()->id;

            foreach ($leadsArr as $key => $lead) {
                $lead['source_id'] = $this->leadsHelper->getSourceID($lead['source']);
                unset($lead['created_time']);

                $lead = new Ticket($lead);
                $lead->assigner_id = auth()->user()->id;
                $lead->method = 'Manual Excel';

                $dupLead = Ticket::wherePhoneNumber($lead->phone_number)
                    ->where('phone_number', '!=', '')
                    ->where('id', '!=', $lead->id)
                    ->first();

                if ($dupLead) {
                    $lead->status_id = $duplicateStatus;
                } else {
                    $lead->status_id = $newStatus;
                }

                $leads[] = $lead;
            }

            $this->leadsHelper->initiateImport($leads);

            return redirect()->route('tickets.all');
        }
    }

    public function importLeadsFromZapierV3($source, Request $request): \Illuminate\Http\JsonResponse
    {
        parent::hasPermission('facebook import');

        if (!in_array($source, ['facebook', 'tiktok', 'googleAds'])) {
            return response()->json(['ERROR' => 'Unknown Source'], 404);
        }

        $rules = [
            'details' => 'required|array|min:1',
            'details.*' => 'required|integer|gt:0',
        ];

        $messages = [
            'required' => 'The :attribute field is required.',
            'gt:0' => 'The :attribute field should be positive.',
            'min:1' => 'The :attribute should have at least one item.'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        /**** Call helper function (28/11/2042) ****/
        DB::beginTransaction();

        try {
            if (is_array($request->details)) {
                $leadIds = array_keys($request->details);
            } else {
                return response()->json(['error' => 'Invalid details form!'], 422);
            }

            $leads = TempLead::whereIn('id', $leadIds)->get();

            // return response()->json(['OK' => $request->details], 200);
            $newStatus = Status::where('name', Status::NEW)->first()->id;
            $duplicatedStatus = Status::where('name', Status::DUPLICATED)->first()->id;

            foreach ($leads as $rawLead) {
                if (!User::find($request->details[$rawLead->id])) {
                    return response()->json(['msg' => 'Please check all users.'], 400);
                }

                $dupLead = Ticket::where('phone_number', 'LIKE' . "%{$rawLead->phone_number}%")
                    ->where('phone_number', '!=', '')
                    ->first();

                $dupTempLead = TempLead::where('phone_number', 'LIKE', "%{$rawLead->phone_number}%")
                    ->where('phone_number', '!=', '')
                    ->where('id', '!=', $rawLead->id)
                    ->first();

                //  if (!$dupLead && !$dupTempLead) {
                $lead = Ticket::create([
                    'number' => $rawLead->number,
                    'user_id' => $request->details[$rawLead->id],
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
                    'full_name' => $rawLead->full_name ?? 'N/A',
                    'phone_number' => $rawLead->phone_number ?? '0',
                    'email' => $rawLead->email,
                    'job_title' => $rawLead->job_title ?? '',
                    'status_id' => ($dupLead || $dupTempLead) ? $duplicatedStatus : $newStatus,
                    'source_id' => $this->leadsHelper->getSourceID($rawLead->platform),
                    'assigner_id' => $request->manual ? auth()->user()->id : null,
                    'method' => ($request->manual ? 'Manual ' : 'Automatic ') . ucfirst($source),
                    'extra_data' => $rawLead->extra_data,
                ]);

                $this->leadsHelper->createAndAssignLead($lead, $lead->status_id);
                // }

                TempLead::destroy($rawLead->id);
            }

            DB::commit();

            return response()->json(['OK' => count($leads)], 200);
        } catch (\Exception $e) {
            // Rollback the transaction if there's an error
            DB::rollBack();

            return response()->json(['error' => 'Failed to import a lead or more: ' . $e->getMessage()], 500);
        }
    }

    public function importLeadsFromZapierV2($source, Request $request): \Illuminate\Http\JsonResponse
    {
        parent::hasPermission('facebook import');

        if (!in_array($source, ['facebook', 'tiktok'])) {
            return response()->json(['ERROR' => 0], 404);
        }

        $rules = [
            'details' => 'required|array|min:1',
            'details.*' => 'required|integer|gt:0',
        ];

        $messages = [
            'required' => 'The :attribute field is required.',
            'gt:0' => 'The :attribute field should be positive.',
            'min:1' => 'The :attribute should have at least one item.'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        /**** Call helper function (03/09/2022) ****/
        $leads = Cache::get('leads');

        // return response()->json(['OK' => $request->details], 200);
        $duplicatedStatus = Status::whereName('duplicated')->first()->id;

        foreach ($request->details as $leadIndex => $userId) {
            if (!User::find($userId)) {
                return response()->json(['msg' => 'Please check all users.'], 400);
            }

            $leads[$leadIndex]->user_id = $userId;
            $rowIndex = $leads[$leadIndex]->key_index;
            Arr::forget($leads[$leadIndex], 'key_index');
            $leads[$leadIndex]->save();

            if ($leads[$leadIndex]->status_id !== $duplicatedStatus) {
                $this->leadsHelper->createAndAssignLead($leads[$leadIndex], $leads[$leadIndex]->status_id);
            }
        }

        $this->leadsHelper->emptyZapierLeadsSheet($source, count($leads));

        return response()->json(['OK' => count($leads)], 200);
    }

    public function importLeadsFromZapier($source): \Illuminate\Http\JsonResponse
    {
        parent::hasPermission('facebook import');

        if (!in_array($source, ['facebook', 'tiktok'])) {
            return response()->json(['ERROR' => 0], 404);
        }

        /**** Call helper function (03/09/2022) ****/
        $leads = $this->leadsHelper->fetchLeadsFromZapier($source, 'Manual');
        $this->leadsHelper->initiateImport($leads);
        $this->leadsHelper->emptyZapierLeadsSheet($source, count($leads));

        return response()->json(['OK' => count($leads)], 200);
    }

    public function moveForward(Request $request, $id): \Illuminate\Http\RedirectResponse
    {
        // Check permission
        parent::hasPermission('change status');

        // Retrieve ticket early; return error if not found.
        $ticket = Ticket::find($id);
        if (!$ticket) {
            return back()->withErrors(['msg' => 'Lead does not exist!'])->withInput($request->all());
        }

        // Determine status id: use request value if provided, otherwise use ticket's next status.
        $statusId = $request->input('status') ?? ($ticket->status->next->id ?? null);
        $theStatus = Status::find($statusId);
        if (!$theStatus) {
            return back()->withErrors(['msg' => 'No such status.'])->withInput($request->all());
        }

        $slug = $theStatus->slug;
        $isBooking = stripos($slug, 'book') !== false;
        $isMeeting = stripos($slug, 'meet') !== false;
        $isFollowUp = ($slug === 'follow-up');
        $isNotInterested = ($slug === 'not-interested');

        $role = request()->user()->getRoleNames()->first();
        $commentRule = $role !== 'super-admin' ? 'required' : 'nullable';

        // Build base validation rules.
        $rules = [
            'comment' => [$commentRule, 'string'],
            'user' => ['integer', 'gt:0', 'exists:users,id'],
            'status' => ['required', 'integer', 'gt:0', 'exists:statuses,id'],
        ];

        // If status name contains 'book', add additional booking validation rules.
        if ($isBooking) {
            $rules = array_merge($rules, [
                'client-unit' => ['required', 'string'],
                'client-price' => ['required', 'numeric', 'gt:0'],
                'client-project' => ['required', 'string'],
                'client-developer' => ['required', 'string'],
            ]);
        }

        if ($isFollowUp) {
            $rules = array_merge($rules, [
                'reminder_datetime' => ['required', 'date', 'after:now'],
            ]);
        }

        if ($isMeeting) {
            $rules = array_merge($rules, [
                'meeting_range' => ['required', 'string'],
            ]);
        }

        $messages = [
            'required' => 'The :attribute field is required.',
            'integer' => 'The :attribute field must be an integer.',
            'string' => 'The :attribute field must be a string.',
            'gt' => 'The :attribute field must be greater than zero.',
            'exists' => 'The selected :attribute is invalid.',
            'after' => 'The :attribute must be a future date and time.',
            'date' => 'The :attribute must be a valid date and time.',
            'numeric' => 'The :attribute field must be a number.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return back()->withErrors($validator->errors())->withInput($request->all());
        }

        // Role-specific status restrictions.
        if ($theStatus->slug === 'approved' && !auth()->user()->hasRole('super-admin')) {
            return back()->withErrors(['msg' => 'Unauthorized Operation!'])->withInput($request->all());
        }

        // Determine user: if not provided use ticket's user.
        $userId = $request->input('user') ?? ($ticket->user->id ?? null);
        $user = User::find($userId);
        if (!$user) {
            return back()->withErrors(['msg' => 'User does not exist!'])->withInput($request->all());
        }

        // Additional restrictions for accountant and admin roles.
        if (auth()->user()->hasRole('accountant')) {
            $soldStatus = Status::whereSlug('sold')->first();
            if ($request->input('status') != $soldStatus->id) {
                return back()->withErrors(['msg' => 'Accountant can only change lead to sold status!'])->withInput($request->all());
            }
        }

        if (auth()->user()->hasRole('admin')) {
            $reviewStatus = Status::whereSlug('reviewed')->first();
            if ($request->input('status') != $reviewStatus->id) {
                return back()->withErrors(['msg' => 'Admin can only change lead to reviewed status!'])->withInput($request->all());
            }
        }

        // Begin a DB transaction to ensure atomicity.
        DB::beginTransaction();

        try {
            // If meeting scheduling is needed.
            if ($isMeeting) {
                $range = $request->input('meeting_range');
                $rangeParts = explode(' - ', $range);

                if (count($rangeParts) < 2) {
                    throw new \Exception('Invalid meeting datetime format.');
                }
                $startDate = Carbon::parse(trim($rangeParts[0]));
                $endDate = Carbon::parse(trim($rangeParts[1]));

                if ($endDate->lessThanOrEqualTo($startDate)) {
                    throw new \Exception('Start date & time must be before end date & time.');
                }

                $mtng = Meeting::create([
                    'started_at' => $startDate->toDateTimeString(),
                    'ended_at' => $endDate->toDateTimeString(),
                    'method' => 'automatic',
                    'reminder_at' => $startDate->copy()->subMinutes(30)->toDateTimeString(),
                ]);
            }

            // Create ticket path record.
            $tPath = TicketPath::create([
                'prev_user' => $ticket->user->id ?? null,
                'next_user' => $user->id,
                'prev_status' => $ticket->status->id,
                'next_status' => $statusId,
                'ticket_id' => $ticket->id,
                'comment' => $request->comment ?? '-',
                'reminder_at' => $isFollowUp
                    ? Carbon::parse($request->input('reminder_datetime'))
                    : null,
            ]);

            // Adjust ticket user based on status.
            $mgmtStatuses = Status::whereIn('slug', ['reviewed', 'sold', 'pre-approved', 'rejected'])
                ->pluck('id')
                ->toArray();
            $dead = Status::where('slug', 'dead')->first();
            $deadTele = Status::where('slug', 'dead-tele')->first();

            if ($request->input('status') == $dead->id || $request->input('status') == $deadTele->id) {
                $ticket->user_id = null;
            } elseif (in_array($request->input('status'), $mgmtStatuses)) {
                $ticket->user_id = auth()->user()->id;
                $tPath->next_user = auth()->user()->id;
            } else {
                $ticket->user_id = $user->id;
            }
            $tPath->save();

            // Update ticket status.
            $ticket->status_id = $statusId;

            $ticket->save();

            // If a meeting was created, link it to the ticket path.
            if (isset($mtng)) {
                $mtng->ticket_path_id = $tPath->id;
                $mtng->save();
            }

            ///// If Not Interested -> kill the lead immediately!
            if ($isNotInterested) {
                $tPath = TicketPath::create([
                    'prev_user' => $ticket->user->id ?? null,
                    'next_user' => $ticket->user->id ?? null,
                    'prev_status' => $theStatus->id,
                    'next_status' => $dead->id,
                    'ticket_id' => $ticket->id,
                    'comment' => 'Lead is now DEAD as the client is Not Interested.',
                ]);

                $ticket->update([
                    'status_id' => $dead->id,
                ]);
            }

            // Update ticket user's status based on meeting.
            $ticketUser = User::find($ticket->user_id);
            if ($ticketUser) {
                if (stripos($theStatus->name, 'meet') !== false) {
                    $ticketUser->status = 'banned';
                } elseif ($ticketUser->status === 'banned') {
                    $ticketUser->status = 'permitted';
                }
                $ticketUser->save();
            }

            // Notify users.
            $prevStatusName = Status::find($tPath->prev_status)->name;
            $nextStatusName = Status::find($tPath->next_status)->name;
            $data = [
                'title' => 'Lead Status Update',
                'message' => 'A new lead status has been updated from "' . $prevStatusName . '" to "' . $nextStatusName . '"',
                'user' => auth()->user()->name,
                'ticket' => $ticket->id,
            ];

            // Notify super-admins.
            $superAdmins = User::role('super-admin')->pluck('email')->toArray();
            $this->sendLeadMail($superAdmins, $data);

            // Notify assigned user.
            $this->sendLeadMail($user->email, [
                'title' => 'Lead Status Update',
                'message' => 'Your lead status has been updated from "' . $prevStatusName . '" to "' . $nextStatusName . '".',
                'user' => '',
                'ticket' => $ticket->id,
            ]);

            // Create booking if status indicates booking.
            if ($isBooking) {
                $booking = Booking::create([
                    'project_name' => $request->input('client-project'),
                    'unit_number' => $request->input('client-unit'),
                    'price' => $request->input('client-price'),
                    'developer_name' => $request->input('client-developer'),
                    'user_id' => auth()->user()->id,
                    'ticket_id' => $ticket->id,
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['msg' => $e->getMessage()])->withInput($request->all());
        }

        return redirect()->route('tickets.show', [$ticket->id]);
    }

    function removeColon($inputString): string
    {
        if (str_contains($inputString, ':')) {
            $inputString = trim(explode(':', $inputString)[1]);
        }

        return $inputString ?? '';
    }

    public function makeInvoice($id, Request $request)
    {
        parent::hasPermission('create invoice');

        $rules = [
            'file' => 'required|mimes:pdf,png,jpg,bmp|max:2048',
        ];

        $messages = [
            'required' => 'The :attribute field is required.',
            'mimes' => 'The :attribute field should be one of (pdf,png,jpg or bmp).',
            'max' => 'The max size is 2 MB.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors())->withInput($request->all());
        }

        $fileName = time() . '.' . $request->file->extension();

        //$request->file->move(public_path('uploads'), $fileName);
        $request->file->storeAs('uploads', $fileName);

        $ticket = Ticket::find($id);

        $ticket->invoice = $fileName;

        $ticket->save();

        return redirect()->route('tickets.show', $id);
    }

    public function attachPassport($id, Request $request)
    {
        parent::hasPermission('attach passport');

        $rules = [
            'res-form' => 'required|mimes:pdf,png,jpg,bmp|max:2048',
            'passport' => 'required|mimes:pdf,png,jpg,bmp|max:2048',
        ];

        $messages = [
            'required' => 'The :attribute field is required.',
            'mimes' => 'The :attribute field should be one of (pdf,png,jpg or bmp).',
            'max' => 'The max size is 2 MB.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors())->withInput($request->all());
        }

        $fileName = 'passport_' . time() . '.' . $request->passport->extension();

        //$request->file->move(public_path('uploads'), $fileName);
        $request->passport->storeAs('uploads', $fileName);

        $fileName2 = 'res_form_' . time() . '.' . $request['res-form']->extension();
        $request['res-form']->storeAs('uploads', $fileName2);

        $ticket = Ticket::find($id);

        $ticket->passport = $fileName;
        $ticket->res_form = $fileName2;

        $ticket->save();

        return redirect()->route('tickets.show', $id);
    }

    // Generate PDF

    /**
     * @throws MpdfException
     */
    public function createPDF()
    {
        // retrieve all records from db
        $data = Ticket::all();

        // share data to view
        view()->share('ticket', $data);
        $pdf = LaravelMpdf::loadView('ticketPDF', $data, [], [
            'format' => 'A4',
            'allow_url_fopen' => true,
        ]);

        // in blade put: <img src="{{ public_path('images/logo.png') }}" style="width:120px">
        // download PDF file with download method
        return $pdf->download('pdf_file.pdf');
    }

    public function downloadAttachment($type, $id)
    {
        if ($type === 'excel_temp') {
            $pathToFile = storage_path('app/public/uploads/Excel_file_template.csv');
        } else {
            $ticket = Ticket::findOrFail($id);

            switch ($type) {
                case 'invoice':
                    $pathToFile = storage_path('app/public/uploads/' . $ticket->invoice);
                    break;
                case 'passport':
                    $pathToFile = storage_path('app/public/uploads/' . $ticket->passport);
                    break;
                case 'res_form':
                    $pathToFile = storage_path('app/public/uploads/' . $ticket->res_form);
                    break;
            }
        }


        if (file_exists($pathToFile)) {
            return response()->download($pathToFile);
        } else {
            return back()->withErrors(['msg' => 'File not found.']);
        }
    }

    ////// WRS AE | Updated 25/11/25
    public function indexArchived()
    {
        if (!auth()->user()->hasRole('super-admin')) {
            return back()->withErrors(['msg' => 'Unauthorized access.']);
        }

        $archivedLeads = ArchivedLead::all();

        $salesAll = User::role(['sale', 'tele-sale'])
            ->where('status', 'permitted')
            ->get();

        $sales = $salesAll->groupBy(function ($user) {
            return $user->hasRole('sale') ? 'sale' : 'tele-sale';
        });

        return view('tickets.archived', compact('archivedLeads', 'sales'));
    }

    public function multipleForward(Request $request)
    {
        if (!auth()->user()->hasAnyRole(['super-admin', 'sales-manager'])) {
            return back()->withErrors(['msg' => 'Unauthorized access.']);
        }

        $rules = [
            'sales' => ['required', 'integer', Rule::in(User::role(['sale', 'tele-sale'])->get()->pluck('id')->toArray())],
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'required|integer|gt:0',
        ];

        $messages = [
            'required' => 'The :attribute field is required.',
            'min' => 'Select at least one lead.',
            'gt:0' => 'The :attribute field should be positive.',
            'integer' => 'The :attribute field should be integer.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return back()->withErrors($validator->errors())->withInput($request->all());
        }

        // $reShuffledId = Status::where('slug', 're-shuffled')->first()->id;
        $newStatusId = Status::where('slug', 'new')->first()->id;

        foreach ($request->lead_ids as $lead_id) {
            $lead = Ticket::findOrFail($lead_id);
            $lead->user_id = $request->sales;
            $lead->status_id = $newStatusId; // Changed to New (1/9/2022)

            $tPath = TicketPath::where('ticket_id', $lead->id)
                ->orderBy('updated_at', 'DESC')
                ->first();

            // Check whether all tickets should go to Tele-sales or not?!

            $leadPath = TicketPath::create([
                'prev_user' => $tPath->next_user,
                'next_user' => $request->sales,
                'prev_status' => $tPath->next_status, // Check
                'next_status' => $newStatusId, // Check what to put???
                'ticket_id' => $lead->id,
                'comment' => 'A new lead has been assigned to you by a super-admin.',
            ]);

            $lead->save();
            $leadPath->save();

            /** Notify Users */

            // Super admin
            $data = [
                'title' => 'New Lead',
                'message' => 'A new lead has been assigned by a super-admin to user: ',
                'user' => $lead->user,
                'ticket' => $lead->id
            ];

            $superAdmins = User::role('super-admin')
                ->get()
                ->pluck('email')
                ->toArray();

            // Mail::to($superAdmins)->send(new LeadNotifyMail($data));

            $this->sendLeadMail($superAdmins, $data);

            if ($lead->user) {
                // User himself
                $data = [
                    'title' => 'New Lead',
                    'message' => 'A new lead has been assigned by super-admin to you!',
                    'user' => '',
                    'ticket' => $lead->id
                ];

                // Mail::to($lead->user->email)->send(new LeadNotifyMail($data));
                $this->sendLeadMail($lead->user->email, $data);

                // Notification
                Notifier::notifyUser(
                    $lead->user,
                    'New ticket',
                    "A new ticket has been assigned to you #{$lead->id}",
                    route('tickets.show', $lead->id),
                    'ticket_new',
                    ['ticket_id' => $lead->id],
                    null
                );

            }
        }

        return back()->with('successMsg', 'Leads have been forwarded.');
    }

    public function devTest()
    {
        return response()->json('OFF!', 200);

        $leadsHelper = app()->make(LeadsHelper::class);

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

            $leadIds = array_column($leads, 'number');

            $leadsHelper->removeTempLeads($leadIds);
        }

        return response()->json('DONE!', 200);

        $noAnswerStatusPeriod = '3d';
        $statusMap = Status::pluck('id', 'name');   // ['new' => 1, 'follow-up' => 2]
        $newStatusId = $statusMap->get(Status::NEW);
        $noAnswerStatusId = $statusMap->get(Status::NO_ANSWER);
        $superAdmins = User::role('super-admin')
            ->where('status', 'permitted')
            ->get();

        $res = $this->assignService->autoReassignFromStatus(
            $noAnswerStatusId,
            $newStatusId,
            $noAnswerStatusPeriod,
            $superAdmins,
            $statusMap
        );

        return response()->json($res, 200);
        /*
                Notifier::notifyUser(
                    97,
                    'Follow up reminder',
                    "You have a follow up on ticket #3333333",
                    route('tickets.show', 327925),
                    'ticket_follow_up',
                    ['ticket_id' => 327925],
                    null
                );
        */

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
            ->chunkById(100, function ($tickets) {
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

                    $this->leadsHelper->sendLeadMail($ticket->user->email, $data);
                    /*
                                        Notifier::notifyUser(
                                            $ticket->user,
                                            'Follow up reminder',
                                            "You have a follow up on ticket #{$ticket->id}",
                                            route('tickets.show', $ticket->id),
                                            'ticket_follow_up',
                                            ['ticket_id' => $ticket->id],
                                            null
                                        );
                    */
                    $ticket->latestPath->reminder_sent_at = now();
                    $ticket->latestPath->save();
                }
            });

        return response()->json('OK', 200);

        $this->leadsHelper->sendFcmNotification(97, 'Archived Lead!', "A lead has been archived!");


        $this->leadsHelper->sendFcmNotification(13, 'Hello there!', "It's working fine!");

        $pullDate = date('Y-m-d H:i:s');
        $dateBegin = date('Y-m-d H:i:s', strtotime(date("Y") . '-' . date("m") . '-' . date("d") . " 10:29:57"));
        $dateEnd = date('Y-m-d H:i:s', strtotime(date("Y") . '-' . date("m") . '-' . date("d") . " 23:00:03"));
        if (!($pullDate >= $dateBegin && $pullDate <= $dateEnd)) {
            return response()->json(['msg' => 'Out of time!'], 422);
        }

        // Facebook
        $leads = $this->leadsHelper->fetchLeadsFromZapier('facebook');

        [$assignmentsCountJR, $assignmentsCountSR, $tempLeads] = $this->leadsHelper->initiateImport($leads);
        //$res = $this->leadsHelper->initiateImport($leads);

        $this->leadsHelper->removeTempLeads($tempLeads);

        return response()->json([$tempLeads], 200);


        /*
        $rules = [
            'details' => 'required|array|min:1',
            'details.*' => 'required|integer|gt:0',
        ];

        $messages = [
            'required' => 'The :attribute field is required.',
            'gt:0' => 'The :attribute field should be positive.',
            'min:1' => 'The :attribute should have at least one item.'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        if (!User::find(13)) {
            return response()->json(['msg' => 'Please check all users.'], 400);
        } else {
            return response()->json(['OK' => 555], 200);
        }

        $lead = Ticket::find(2300);

        return response()->json(mt_rand(1, 10000000), 200);

        $leads = [
            [
                "number" => "203924041859471",
                "ad_id" => "23849663707460193",
                "ad_name" => "New Lead generation Ad",
                "adset_id" => "New Lead generation Ad",
                "adset_name" => "23849663707360193",
                "campaign_id" => "23849663707210193",
                "campaign_name" => "Azizi OFfer",
                "form_id" => "3052487005069533",
                "form_name" => "Azizi high offer",
                "is_organic" => false,
                "platform" => "ig",
                "full_name" => "Essa Alamiri",
                "phone_number" => "+971 556007008",
                "email" => "ealamiri@gmail.com",
                "job_title" => "مالك",
                "source" => "ig",
                "created_at" => new Carbon('2022-01-23 11:53:20'),
                "updated_at" => new Carbon('2022-01-23 11:53:20'),
            ],
            1 => [
                "number" => "646888869778409",
                "ad_id" => "23849663707460193",
                "ad_name" => "New Lead generation Ad",
                "adset_id" => "New Lead generation Ad",
                "adset_name" => "23849663707360193",
                "campaign_id" => "23849663707210193",
                "campaign_name" => "Azizi OFfer",
                "form_id" => "3052487005069533",
                "form_name" => "Azizi high offer",
                "is_organic" => false,
                "platform" => "ig",
                "full_name" => "Saleh Almarzooqi",
                "phone_number" => "+971 506225561",
                "email" => "Salehaky@yahoo.com",
                "job_title" => "Business man",
                "created_time" => "2021-12-22 15:22:52",
                "source" => "ig",
                "created_at" => new Carbon('2022-01-23 11:53:20'),
                "updated_at" => new Carbon('2022-01-23 11:53:20'),
            ],
            2 => [
                "number" => "1097714450964310",
                "ad_id" => "23849663707460193",
                "ad_name" => "New Lead generation Ad",
                "adset_id" => "New Lead generation Ad",
                "adset_name" => "23849663707360193",
                "campaign_id" => "23849663707210193",
                "campaign_name" => "Azizi OFfer",
                "form_id" => "3052487005069533",
                "form_name" => "Azizi high offer",
                "is_organic" => false,
                "platform" => "ig",
                "full_name" => "Adnan",
                "phone_number" => "+971 502956023",
                "email" => "adnan_abdeen@hotmail.com",
                "job_title" => "General manager",
                "created_time" => "2021-12-22 14:37:12",
                "source" => "ig",
                "created_at" => new Carbon('2022-01-23 11:53:20'),
                "updated_at" => new Carbon('2022-01-23 11:53:20'),
            ],
            3 => [
                "number" => "939534820267648",
                "ad_id" => "23849663707460193",
                "ad_name" => "New Lead generation Ad",
                "adset_id" => "New Lead generation Ad",
                "adset_name" => "23849663707360193",
                "campaign_id" => "23849663707210193",
                "campaign_name" => "Azizi OFfer",
                "form_id" => "3052487005069533",
                "form_name" => "Azizi high offer",
                "is_organic" => false,
                "platform" => "ig",
                "full_name" => "🇦🇪إتَقي الله حيثُ ماكُنت 🇦🇪",
                "phone_number" => "+971 559000006",
                "email" => "7s@live.ca",
                "job_title" => "Thank you",
                "created_time" => "2021-12-22 14:04:29",
                "source" => "ig",
                "created_at" => new Carbon('2022-01-23 11:53:20'),
                "updated_at" => new Carbon('2022-01-23 11:53:20'),
            ],
            4 => [
                "number" => "644773326968327",
                "ad_id" => "23849663707460193",
                "ad_name" => "New Lead generation Ad",
                "adset_id" => "New Lead generation Ad",
                "adset_name" => "23849663707360193",
                "campaign_id" => "23849663707210193",
                "campaign_name" => "Azizi OFfer",
                "form_id" => "3052487005069533",
                "form_name" => "Azizi high offer",
                "is_organic" => false,
                "platform" => "ig",
                "full_name" => "Jumaa",
                "phone_number" => "+971 504447511",
                "email" => "j4447511@gmail.com",
                "job_title" => "Thanks",
                "created_time" => "2021-12-22 11:46:22",
                "source" => "ig",
                "created_at" => new Carbon('2022-01-23 11:53:20'),
                "updated_at" => new Carbon('2022-01-23 11:53:20'),
            ],
            5 => [
                "number" => "644773326968393",
                "ad_id" => "23849663707460193",
                "ad_name" => "New Lead generation Ad",
                "adset_id" => "New Lead generation Ad",
                "adset_name" => "23849663707360193",
                "campaign_id" => "",
                "campaign_name" => "",
                "form_id" => "3052487005069533",
                "form_name" => "",
                "is_organic" => false,
                "platform" => "ig",
                "full_name" => "Heba Gh",
                "phone_number" => "+971 562201775",
                "email" => "heba.gh@gmail.com",
                "job_title" => "Software",
                "created_time" => "2021-12-22 13:46:22",
                "source" => "ig",
                "created_at" => new Carbon('2022-01-23 11:53:20'),
                "updated_at" => new Carbon('2022-01-23 11:53:20'),
            ]
        ];

        $tickets = [];
        $newStatusId = Status::where('slug', 'new')->first()->id;
        $duplicateStatus = Status::whereName('duplicated')->first()->id;

        foreach ($leads as $lead) {
            $lead['phone_number'] = str_replace(' ', '', $lead['phone_number']);
            $lead['phone_number'] = $this->rectifyPhone($lead['phone_number']);
            $lead['source_id'] = $lead['source'] ? Source::where('name', 'Instagram')->first()->id : ($lead['platform'] === 'fb' ? Source::where('name', 'Facebook')->first()->id : Source::where('name', 'Unspecified')->first()->id);
            unset($lead['created_time']);


            $lead = new Ticket($lead);
            $lead->assigner_id = auth()->user()->id;
            $lead->method = 'Test Manual';

            $dupLead = Ticket::wherePhoneNumber($lead->phone_number)
                ->where('phone_number', '!=', '')
                ->where('id', '!=', $lead->id)
                ->first();

            if ($dupLead) {
                $lead->status_id = $duplicateStatus;
            } else {
                $lead->status_id = $newStatusId;
            }

            $tickets[] = $lead;
        }

        $this->leadsHelper->initiateImport($tickets);
        */

        // $fcmTokens = User::whereNotNull('fcm_token')->pluck('fcm_token')->toArray();

//        Larafirebase::withTitle("New Lead")
//        ->withBody("A new lead has been assigned to you!")
//        ->sendMessage($fcmTokens);

        // Notification::send(null,new SendPushNotification("New Lead", "A new lead has been assigned to you!", $fcmTokens));

        // auth()->user()->notify(new SendPushNotification("New Lead", "A new lead has been assigned to you!", $fcmTokens));
        //   dd($this->leadsHelper->rectifyPhone('966505228708'));
        //return redirect()->route('home');
    }

    private function sendLeadMail($recipients, $data)
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

    public function storeLead(Request $request)
    {
        if (!$request->header('X-Make-Token') || $request->header('X-Make-Token') !== config('app.auth_token')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        /*
        $tl = TempLead::create([
            'full_name' => 'test',
            'phone_number' => '099999999',
            'status_id' => Status::where('slug', 'new')->first()->id,
            'source_id' => $this->leadsHelper->getSourceID('fb'),
            'extra_data' => json_encode($request->all(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        ]);
        return response()->json($tl, 200);
        */

        // Flatten nested JSON fields (e.g. `data`, `mappable_field_data`) to the top level.
        // Priority: existing top-level keys > first nested field that defines the key.
        $existing = $request->all();
        $flattened = [];
        foreach ($existing as $value) {
            if (!is_array($value)) {
                continue;
            }
            // Array of {name, value} objects (e.g. mappable_field_data)
            if (isset($value[0]) && is_array($value[0]) && array_key_exists('name', $value[0]) && array_key_exists('value', $value[0])) {
                foreach ($value as $item) {
                    if (!array_key_exists($item['name'], $existing) && !array_key_exists($item['name'], $flattened)) {
                        $flattened[$item['name']] = $item['value'];
                    }
                }
            } elseif (array_keys($value) !== range(0, count($value) - 1)) {
                // Associative array (e.g. `data` object)
                foreach ($value as $subKey => $subValue) {
                    if (!array_key_exists($subKey, $existing) && !array_key_exists($subKey, $flattened)) {
                        $flattened[$subKey] = $subValue;
                    }
                }
            }
        }
        if (!empty($flattened)) {
            $request->merge($flattened);
        }

        try {
            DB::beginTransaction();

            //$this->validateLead($request);

            $newStatus = Status::where('slug', 'new')->first()->id;
            $duplicatedStatus = Status::whereName('duplicated')->first()->id;

            $dupLead = Ticket::where('phone_number', 'LIKE' . "%{$request->phone_number}%")
                ->where('phone_number', '!=', '')
                ->first();

            $dupTempLead = TempLead::where('phone_number', 'LIKE', "%{$request->phone_number}%")
                ->where('phone_number', '!=', '')
                ->first();

            $commonKeys = [
                'id', 'lead_id',
                'ad_id',
                'ad_name',
                'adset_id', 'adgroup_id',
                'adset_name', 'adgroup_name',
                'campaign_id',
                'campaign_name',
                'form_id',
                'form_name',
                'is_organic',
                'platform',
                'full_name', 'name', 'first_name',
                'phone_number',
                'email',
                'status_id',
                'source_id',
                'method',
                'created_time', 'create_time',
                'page_id',
                'page_name',
                'retailer_item_id',
            ];

            $payload = $request->input('extra_data');

            if (is_array($payload)) {
                $filteredData = collect($payload)->reject(function ($value, $key) use ($commonKeys) {
                    return str_starts_with($key, 'raw') || in_array($key, $commonKeys);
                })->toArray();

                $payload = json_encode($filteredData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }

            // If it's an array, encode it:
            $sourceId = $this->leadsHelper->getSourceID($request->platform);

            $lead = TempLead::create([
                'number' => $request->id ?? $request->lead_id ?? 0,
                'ad_id' => $request->ad_id ?? 0,
                'ad_name' => $request->ad_name,
                'adset_id' => $request->adset_id ?? $request->adgroup_id,
                'adset_name' => $request->adset_name ?? $request->adgroup_name,
                'campaign_id' => $request->campaign_id,
                'campaign_name' => $request->campaign_name,
                'form_id' => $request->form_id,
                'form_name' => $request->form_name ?? '',
                'is_organic' => $request->is_organic ?? '',
                'platform' => $request->platform,
                'full_name' => $request->full_name ?: $request->name ?: $request->first_name ?: 'N/A',
                'phone_number' => $request->phone_number ?? '0',
                'email' => $request->email,
                'job_title' => $request->job_title ?? '',
                'status_id' => ($dupLead || $dupTempLead) ? $duplicatedStatus : $newStatus,
                'source_id' => $sourceId,
                'extra_data' => $payload,
                'method' => 'Automatic ' . Source::find($sourceId)->name . ' - Webhook',
            ]);

            DB::commit();

            // Return a success response
            return response()->json(['message' => 'Lead stored successfully', 'lead' => $lead], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Rollback the transaction if there's an error
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            // Rollback the transaction if there's an error
            DB::rollBack();

            return response()->json(['error' => 'Request execution failed: ' . $e->getMessage()], 500);
        }
    }

    public function restoreArchivedLeads(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'manual' => 'nullable|string',
            'details' => 'required|array|min:1',
            'details.*' => 'required|integer|exists:users,id',
        ], [
            'details.required' => 'No leads were submitted.',
            'details.array' => 'Invalid details format.',
            'details.*.exists' => 'One or more selected sales users do not exist.',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toArray(), 422);
        }

        $details = $request->input('details', []);
        $leadIds = array_keys($details);
        $salesIds = array_values($details);
        $assignerId = Auth::id();

        $validSalesCount = User::whereIn('id', $salesIds)
            ->where('status', 'permitted')
            ->count();

        if ($validSalesCount !== count($salesIds)) {
            return response()->json([
                'sales_ids' => 'One or more selected sales users are not permitted.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $archivedLeads = ArchivedLead::whereIn('id', $leadIds)->get()->keyBy('id');

        if ($archivedLeads->count() !== count($leadIds)) {
            return response()->json([
                'lead_ids' => 'One or more archived leads were not found.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $restoredCount = 0;

        DB::beginTransaction();

        try {
            foreach ($details as $archivedLeadId => $salesUserId) {
                /** @var \App\Models\ArchivedLead $archivedLead */
                $archivedLead = $archivedLeads->get($archivedLeadId);

                if (!$archivedLead) {
                    continue;
                }

                // 4) بناء بيانات الـ Ticket الجديدة من ArchivedLead
                $ticketData = [
                    'number' => $archivedLead->number,
                    'ad_id' => $archivedLead->ad_id,
                    'ad_name' => $archivedLead->ad_name,
                    'adset_id' => $archivedLead->adset_id,
                    'adset_name' => $archivedLead->adset_name,
                    'campaign_id' => $archivedLead->campaign_id,
                    'campaign_name' => $archivedLead->campaign_name,
                    'form_id' => $archivedLead->form_id,
                    'form_name' => $archivedLead->form_name,
                    'is_organic' => $archivedLead->is_organic,
                    'platform' => $archivedLead->platform,
                    'full_name' => $archivedLead->full_name,
                    'phone_number' => $archivedLead->phone_number,
                    'email' => $archivedLead->email,
                    'invoice' => $archivedLead->invoice,
                    'passport' => $archivedLead->passport,
                    'res_form' => $archivedLead->res_form,
                    'job_title' => $archivedLead->job_title,
                    'user_id' => $salesUserId,         // 🔹 السيلز المسند له
                    'status_id' => $archivedLead->status_id,
                    'source_id' => $archivedLead->source_id,
                    'assigner_id' => $assignerId,          // 🔹 مين عمل الريستور
                    'method' => $archivedLead->method,
                    'preferred_time' => $archivedLead->preferred_time,
                    'remarks' => $archivedLead->remarks,
                    'extra_data' => $archivedLead->extra_data,
                ];

                $restoredLead = Ticket::create($ticketData);
                $this->leadsHelper->createAndAssignLead($restoredLead, $archivedLead->status_id, 'Lead restored from archive.');

                // 5) حذف ArchivedLead بعد التحويل
                $archivedLead->delete(); // لو عندك SoftDeletes على ArchivedLead وإلا use forceDelete()

                $restoredCount++;
            }

            DB::commit();

            return response()->json([
                'OK' => $restoredCount,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            // ممكن تضيف Log هنا لو حاب
            // Log::error('Restore leads failed', ['error' => $e->getMessage()]);

            return response()->json([
                'error' => 'Failed to restore leads. Please try again later.',
            ], 500);
        }
    }

    public function ignoreLeads(Request $request, $type)
    {
        switch ($type) {
            case 'archive':
                $modelClass = ArchivedLead::class;
                break;

            case 'temp':
                $modelClass = TempLead::class;
                break;

            default:
                return response()->json([
                    'error' => 'Invalid type. Allowed values are: archive, temp.',
                ], 400);
        }

        $modelInstance = new $modelClass;
        $table = $modelInstance->getTable(); // يعطيك اسم الجدول: archived_leads أو temp_leads

        // 1) Validation
        $validated = $request->validate([
            'lead_ids' => 'required|array|min:1',
            'lead_ids.*' => 'required|integer|exists:' . $table . ',id',
        ], [
            'lead_ids.required' => 'No selected leads!',
            'lead_ids.*.exists' => 'One or more selected leads do not exist.',
        ]);

        $leadIds = $validated['lead_ids'];

        $deletedRowsCount = $modelClass::whereIn('id', $leadIds)->delete();

        return response()->json(['OK' => $deletedRowsCount], 200);
    }

    public function reshuffle(Request $request)
    {
        $data = $request->validate([
            'lead_ids' => ['required', 'array', 'min:1'],
            'lead_ids.*' => ['integer', 'min:1'],
        ]);

        $result = $this->leadsHelper->reshuffleAndAssign($data['lead_ids']);

        if (!$result['ok']) {
            return response()->json([
                'message' => $result['message'],
                'missing_ids' => $result['missing_ids'] ?? [],
            ], 422);
        }

        return response()->json([
            'message' => 'Reshuffled successfully',
            'total_fetched' => $result['total_fetched'],
            'total_assigned' => $result['total_assigned'],
            'assigned_stats' => $result['assigned_stats'],
        ]);
    }

    private function validateLead(Request $request)
    {
        $rules = [
            'full_name' => ['required', 'string', 'max:255'],
            'campaign_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone_number' => ['required', 'string', 'max:255'],
            'source' => ['required', 'integer', 'gt:0'],
            'user' => ['nullable', 'integer', 'gt:0'],
            'preferred_time' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:65535'],
        ];

        $messages = [
            'required' => 'The :attribute field is required.',
            'integer' => 'The :attribute field should be integer.',
            'string' => 'The :attribute field should be string.',
            'gt:0' => 'The :attribute field should be positive.'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

}
