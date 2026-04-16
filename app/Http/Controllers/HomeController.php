<?php

namespace App\Http\Controllers;

use App\Helpers\LeadsHelper;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\TicketPath;
use Illuminate\Http\Request;
use App\Models\User;

class HomeController extends Controller
{
    private $leadsHelper;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->leadsHelper = new LeadsHelper();
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $filterParams = $this->leadsHelper->getLeadsFilterParams($request);

        // Guard: linkable mode is restricted to sales-manager and super-admin
        if ($filterParams['linkable'] && !$user->hasAnyRole(['sales-manager', 'super-admin'])) {
            return redirect()->to('/');
        }

        // Advanced stats flow (super-admin / sales-manager with filters)
        if ($user->hasAnyRole(['super-admin', 'sales-manager'])) {
            return $this->renderAdvancedStats($user, $filterParams);
        }

        // Simple stats flow (sale / tele-sale / accountant)
        return $this->renderSimpleStats($user);
    }

    protected function renderAdvancedStats($user, array $filterParams)
    {
        $statuses = $this->getVisibleStatuses($filterParams['status']);
        $saleIds  = $this->resolveSaleIds($user, $filterParams['sale']);

        if ($saleIds === false) {
            abort(403, 'Unauthorized sale filter');
        }

        $stats = [];
        foreach ($statuses as $status) {
            if ($user->hasRole('accountant') && !in_array($status->slug, ['approved', 'sold'])) {
                continue;
            }

            $tickets = $this->buildTicketsQuery($filterParams, $status, $saleIds, $user);

            $stats[$status->name] = $filterParams['linkable']
                ? $tickets->get()
                : $tickets->count();
        }

        $viewData = array_merge(
            $this->buildViewParams($filterParams, $stats),
            [
                'sales'    => $this->getSalesForUser($user),
                'statuses' => $this->getVisibleStatuses(),

                // Pass the raw user-selected value (scalar), not the resolved array.
                // This is what the dropdown needs to highlight the active option.
                'currentSale' => $filterParams['sale'] ?? 'all',

                'fcm_token' => $user->fcm_token,
            ]
        );

        $view = $filterParams['linkable'] ? 'tickets.report' : 'home';
        return view($view)->with($viewData);
    }

    protected function renderSimpleStats($user)
    {
        $statuses = Status::all();
        $stats    = [];

        foreach ($statuses as $status) {
            if (!$this->isStatusVisibleForUser($status, $user)) {
                continue;
            }

            $stats[$status->name] = $this->countLeadsForUser($status, $user);
        }

        return view('home')->with([
            'stats'     => $stats,
            'fcm_token' => $user->fcm_token,
        ]);
    }

    protected function isStatusVisibleForUser($status, $user): bool
    {
        // Only super-admin can see 'duplicated' leads
        if ($status->name === Status::DUPLICATED && !$user->hasRole('super-admin')) {
            return false;
        }

        // Regular sales reps don't see tele-specific statuses
        if ($user->hasRole('sale') && str_ends_with($status->slug, 'tele')) {
            return false;
        }

        // Accountants only care about approved/sold
        if ($user->hasRole('accountant')) {
            return in_array($status->name, [Status::APPROVED, Status::SOLD]);
        }

        return true;
    }

    protected function countLeadsForUser($status, $user): int
    {
        $query = Ticket::where('status_id', $status->id);

        if ($user->hasAnyRole(['sale', 'tele-sale'])) {
            return $query->where('user_id', $user->id)->count();
        }

        if ($user->hasRole('accountant')) {
            return $query->count();
        }

        if ($user->hasRole('sales-manager')) {
            // Manager sees only their direct reports
            $employeeIds = User::where('manager_id', $user->id)->pluck('id');
            return $query->whereIn('user_id', $employeeIds)->count();
        }

        return 0;
    }

    /**
     * Fetch visible statuses, excluding tele-specific ones by default.
     */
    protected function getVisibleStatuses(?string $slug = null)
    {
        $query = Status::query();

        if ($slug && $slug !== 'all') {
            return $query->where('slug', $slug)->get();
        }

        // Exclude statuses ending with 'tele'
        return $query->where('slug', 'not like', '%tele')->get();
    }

    /**
     * Resolve which sale IDs the current user is allowed to filter by.
     *
     * @return array|null|false  array = filter by these IDs,
     *                           null  = no sale filter (super-admin viewing all),
     *                           false = unauthorized attempt (caller must abort 403)
     */
    protected function resolveSaleIds($user, $filterSale)
    {
        // Sales reps can only ever see their own leads
        if ($user->hasAnyRole(['sale', 'tele-sale'])) {
            return [$user->id];
        }

        // Sales manager: restrict to their team
        if ($user->hasRole('sales-manager')) {
            $employeeIds   = User::where('manager_id', $user->id)->pluck('id')->toArray();
            $employeeIds[] = $user->id;

            if ($filterSale && $filterSale !== 'all') {
                // Security: ensure the requested sale belongs to this manager
                return in_array((int) $filterSale, $employeeIds, true)
                    ? [(int) $filterSale]
                    : false;
            }

            return $employeeIds;
        }

        // Super-admin: free to filter by any sale or none at all
        return ($filterSale && $filterSale !== 'all') ? [(int) $filterSale] : null;
    }

    /**
     * Build the base tickets query with all applied filters and role constraints.
     */
    protected function buildTicketsQuery(array $filterParams, $status, $saleIds, $user)
    {
        $tickets = Ticket::query();

        // Apply shared filters (date range, phone, fullName, camp, etc.)
        $tickets = $this->leadsHelper->filterLeads($filterParams, $tickets);

        // Sales manager must be constrained to their team
        if ($user->hasRole('sales-manager')) {
            $tickets->where('status_id', $status->id)
                ->whereIn('user_id', $saleIds);
        }

        // Match leads whose path transitioned into the target status (and optionally to a specific sale)
        $tickets->whereHas('paths', function ($query) use ($status, $saleIds) {
            $query->where('next_status', $status->id);

            if (!empty($saleIds)) {
                $query->whereIn('next_user', (array) $saleIds);
            }
        });

        return $tickets;
    }

    /**
     * List of sales reps shown in the dropdown based on viewer role.
     */
    protected function getSalesForUser($user)
    {
        if ($user->hasRole('super-admin')) {
            return User::role(['sale', 'tele-sale'])->get();
        }

        if ($user->hasRole('sales-manager')) {
            return User::where('manager_id', $user->id)->get();
        }

        return null;
    }

    /**
     * Flatten view parameters coming from request filters.
     */
    protected function buildViewParams(array $filterParams, array $stats): array
    {
        return [
            'stats'         => $stats,
            'currentStatus' => $filterParams['status'],
            'camp'          => $filterParams['camp'],
            'from'          => $filterParams['from'],
            'to'            => $filterParams['to'],
            'updatedFrom'   => $filterParams['updated_from'],
            'updatedTo'     => $filterParams['updated_to'],
            'fullName'      => $filterParams['fullName'],
            'phone'         => $filterParams['phone'],
        ];
    }


}
