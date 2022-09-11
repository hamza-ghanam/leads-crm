<?php

namespace App\Http\Controllers;

use App\Models\Status;
use App\Models\Ticket;
use App\Models\TicketPath;
use Illuminate\Http\Request;
use App\Models\User;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
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
            $sale = $request->query('sales');
            $status = $request->query('fstatus');
            $camp = $request->query('camp');
            $from = $request->query('from');
            $to = $request->query('to');
            $fullName = $request->query('fullName');
            $phone = $request->query('phone');
            $linkable = $request->query('linkable');
            $stats = [];

            if ($linkable && !auth()->user()->hasAnyRole('sales-manager', 'super-admin')) {
                return redirect()->to('/');
            }

            // Set status(es)
            if ($status and $status !== 'all') {
                $status = Status::whereSlug($status)->get();
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
                if (($camp and $camp !== '')) {
                    $tickets = $tickets->where('campaign_name', 'LIKE', "%{$camp}%");
                }

                // Created at from & to filters
                if (($from and $from !== '') and ($to and $to !== '')) {
                    $from = date($from);
                    $to = date($to);
                    $tickets = $tickets->whereBetween('created_at', [$from, $to]);
                } else if (($from and $from !== '') and (!$to or $to == '')) {
                    $from = date($request->from);
                    $tickets = $tickets->where('created_at', '>=', $from);
                } else if ((!$from or $from == '') and ($to and $to !== '')) {
                    $to = date($request->to);
                    $tickets = $tickets->where('created_at', '<=', $to);
                }

                // Person Full_name filter
                if (($fullName and $fullName !== '')) {
                    $tickets = $tickets->where('full_name', 'LIKE', "%{$fullName}%");
                }

                // Person phone filter
                if (($phone and $phone !== '')) {
                    $tickets = $tickets->where('phone_number', 'LIKE', "%{$phone}%");
                }

                if (auth()->user()->hasAnyRole('sale', 'tele-sale')) {
                    $sale = auth()->user()->id;
                } else if (auth()->user()->hasAnyRole('accountant') and $status->slug !== 'approved' and $singleStatus->slug !== 'sold') {
                    continue;
                } else if (auth()->user()->hasRole('sales-manager')) {
                    if ((!$sale or $sale == '' or $sale === 'all')) {
                        $sale = [];
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

                if (!$linkable) {
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
                'currentStatus' => $request->query('fstatus'),
                'camp' => $camp,
                'from' => $from,
                'to' => $to,
                'fullName' => $fullName,
                'phone' => $phone,
                'statuses' => $allStatuses,
            ];

            if (!$linkable) {
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
