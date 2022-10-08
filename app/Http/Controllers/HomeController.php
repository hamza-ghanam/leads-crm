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
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {

        if (auth()->user()->hasAnyRole('super-admin')) {
            $filterParams = $this->leadsHelper->getLeadsFilterParams($request);

            $filterParams = $this->leadsHelper->getLeadsFilterParams($request);

            $stats = [];

            if ($filterParams['linkable'] && !auth()->user()->hasAnyRole('sales-manager', 'super-admin')) {
                return redirect()->to('/');
            }

            // Set status(es)
            if ($filterParams['status'] and $filterParams['status'] !== 'all') {
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

                // Campaign filter
                if (($filterParams['camp'] and $filterParams['camp'] !== '')) {
                    $tickets = $tickets->where('campaign_name', 'LIKE', "%{$filterParams['camp']}%");
                }

                // Created at from & to filters
                if (($filterParams['from'] and $filterParams['from'] !== '') and ($filterParams['to'] and $filterParams['to'] !== '')) {
                    $from = date($filterParams['from']);
                    $to = date($filterParams['to']);
                    $tickets = $tickets->whereBetween('created_at', [$from, $to]);
                } else if (($filterParams['from'] and $filterParams['from'] !== '') and (!$filterParams['to'] or $filterParams['to'] == '')) {
                    $from = date($filterParams['from']);
                    $tickets = $tickets->where('created_at', '>=', $from);
                } else if ((!$filterParams['from'] or $filterParams['from'] == '') and ($filterParams['to'] and $filterParams['to'] !== '')) {
                    $to = date($filterParams['to']);
                    $tickets = $tickets->where('created_at', '<=', $to);
                }

                // Person Full_name filter
                if (($filterParams['fullName'] and $filterParams['fullName'] !== '')) {
                    $tickets = $tickets->where('full_name', 'LIKE', "%{$filterParams['fullName']}%");
                }

                // Person phone filter
                if (($filterParams['phone'] and $filterParams['phone'] !== '')) {
                    $tickets = $tickets->where('phone_number', 'LIKE', "%{$filterParams['phone']}%");
                }

                $sale = [];
                if (auth()->user()->hasAnyRole('sale', 'tele-sale')) {
                    $sale = auth()->user()->id;
                } else if (auth()->user()->hasAnyRole('accountant') and $status->slug !== 'approved' and $singleStatus->slug !== 'sold') {
                    continue;
                } else if (auth()->user()->hasRole('sales-manager')) {
                    if ((!$filterParams['sale'] or $filterParams['sale'] == '' or $filterParams['sale'] === 'all')) {
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

                    if (($sale and $sale !== 'all')) {
                        // Sales filter
                        if ($sale and is_array($sale) and count($sale) > 0) {
                            $query = $query->whereIn('next_user', $sale);
                        } else if ($sale and !is_array($sale) and $sale !== '' and $sale !== 'all') {
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
                'fullName' => $filterParams['fullName'],
                'phone' => $filterParams['phone'],
                'statuses' => $allStatuses,
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
                if ($status->slug === 'duplicated' and !auth()->user()->hasRole('super-admin')) {
                    continue;
                }

                if (auth()->user()->hasAnyRole('sale', 'tele-sale')) {
                    if ((auth()->user()->hasRole('sale')) and str_ends_with($status->slug, 'tele')) {
                        continue;
                    }

                    $leads = Ticket::where('status_id', $status->id)
                        ->where('user_id', auth()->user()->id)
                        ->count();
                    $stats += [$status->name => $leads];
                } else if (auth()->user()->hasAnyRole('accountant')) {
                    if (str_ends_with($status->slug, 'tele') or ($status->slug !== 'approved' and $status->slug !== 'sold')) {
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

            return view('home')->with(['stats' => $stats]);
        }
    }
}
