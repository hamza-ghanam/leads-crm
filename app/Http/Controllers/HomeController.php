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
        if (auth()->user()->hasAnyRole('super-admin')) {
            $filterParams = $this->leadsHelper->getLeadsFilterParams($request);

            $stats = [];

            if ($filterParams['linkable'] && !auth()->user()->hasAnyRole('sales-manager', 'super-admin')) {
                return redirect()->to('/');
            }

            // Set status(es)
            if ($filterParams['status'] && $filterParams['status'] !== 'all') {
                $status = Status::whereSlug($filterParams['status'])->get();
            } else {
                $status = Status::all();
                foreach ($status as $key => $oneStatus) {
                    if (str_ends_with($oneStatus->slug, 'tele')) {
                        unset($status[$key]);
                    }
                }
            }

            foreach ($status as $key => $singleStatus) {
                $tickets = Ticket::where('id', '>', 0);

                $tickets = $this->leadsHelper->filterLeads($filterParams, $tickets);

                $sale = [];
                if (auth()->user()->hasAnyRole('sale', 'tele-sale')) {
                    $sale = auth()->user()->id;
                } else if (auth()->user()->hasAnyRole('accountant') && $status->slug !== 'approved' && $singleStatus->slug !== 'sold') {
                    continue;
                } else if (auth()->user()->hasRole('sales-manager')) {
                    if ((!$filterParams['sale'] || $filterParams['sale'] == '' || $filterParams['sale'] === 'all')) {
                        $sale = User::where('manager_id', auth()->user()->id)
                            ->get()
                            ->pluck('id')
                            ->toArray();

                        $sale[] = auth()->user()->id;
                    }

                    $tickets = $tickets->where('status_id', $singleStatus->id)
                        ->whereIn('user_id', $sale);
                }

                $tickets = $tickets->whereHas('paths', function ($query) use ($sale, $singleStatus) {
                    $query->where('ticket_id', '>', 0);

                    // Status filter
                    $query = $query->where('next_status', $singleStatus->id);

                    if (($sale && $sale !== 'all')) {
                        // Sales filter
                        if ($sale && is_array($sale) && count($sale) > 0) {
                            $query = $query->whereIn('next_user', $sale);
                        } else if ($sale && !is_array($sale) && $sale !== '' && $sale !== 'all') {
                            $query = $query->where('next_user', $sale);
                        }
                    }
                });

                if (!$filterParams['linkable']) {
                    $tickets = $tickets->count();
                } else {
                    $tickets = $tickets->get();
                }

                $stats += [$singleStatus->name => $tickets];
            }

            $sales = null;
            if (auth()->user()->hasRole('super-admin')) {
                $sales = User::role(['sale', 'tele-sale'])->get();
            } elseif (auth()->user()->hasRole('sales-manager')) {
                $sales = User::whereManagerId(auth()->user()->id)->get();
            }

            $allStatuses = Status::all();
            foreach ($allStatuses as $key => $status) {
                if (str_ends_with($status->slug, 'tele')) {
                    unset($allStatuses[$key]);
                }
            }

            $resultParams = [
                'stats' => $stats,
                'sales' => $sales,
                'currentSale' => $sale,
                'currentStatus' => $filterParams['status'],
                'camp' => $filterParams['camp'],
                'from' => $filterParams['from'],
                'to' => $filterParams['to'],
                'updatedFrom' => $filterParams['updated_from'],
                'updatedTo' => $filterParams['updated_to'],
                'fullName' => $filterParams['fullName'],
                'phone' => $filterParams['phone'],
                'statuses' => $allStatuses,
                'fcm_token' => auth()->user()->fcm_token,
            ];

            if (!$filterParams['linkable']) {
                return view('home')->with($resultParams);
            } else {
                return view('tickets.report')->with($resultParams);
            }
        } else {
            $statuses = Status::all();
            $stats = [];

            foreach ($statuses as $key => $status) {
                if ($status->slug === 'duplicated' && !auth()->user()->hasRole('super-admin')) {
                    continue;
                }

                if (auth()->user()->hasAnyRole('sale', 'tele-sale')) {
                    if ((auth()->user()->hasRole('sale')) && str_ends_with($status->slug, 'tele')) {
                        continue;
                    }

                    $leads = Ticket::where('status_id', $status->id)
                        ->where('user_id', auth()->user()->id)
                        ->count();
                    $stats += [$status->name => $leads];
                } else if (auth()->user()->hasAnyRole('accountant')) {
                    if (str_ends_with($status->slug, 'tele') || ($status->slug !== 'approved' && $status->slug !== 'sold')) {
                        continue;
                    }

                    $leads = Ticket::where('status_id', $status->id)
                        ->count();
                    $stats += [$status->name => $leads];
                } else if (auth()->user()->hasRole('sales-manager')) {
                    $employeeIDs = User::where('manager_id', auth()->user()->id)
                        ->get()
                        ->pluck('id')
                        ->toArray();

                    $leads = Ticket::where('status_id', $status->id)
                        ->whereIn('user_id', $employeeIDs)
                        ->count();
                    $stats += [$status->name => $leads];
                }
            }

            return view('home')->with(['stats' => $stats, 'fcm_token' => auth()->user()->fcm_token]);
        }
    }
}
