@extends('layouts.app')

@section('title')
    Report Details
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Reports</a></li>
    <li class="breadcrumb-item active">Report Details</li>
@endsection

@section('content')

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="row">
            <div class="col-12">
                <div class="alert alert-danger alert-dismissible fade show shadow-sm">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <h5><i class="icon fas fa-ban"></i> Please fix the following:</h5>
                    <ul class="mb-0 pl-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- ===================== FILTER CARD ===================== --}}
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary shadow-sm filter-card">
                <div id="flip" class="card-header d-flex align-items-center" style="cursor: pointer;">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-filter mr-2"></i>
                        Filters
                        {{-- Active filters counter, updated via JS --}}
                        <span id="active-filters-count" class="badge badge-pill badge-info ml-2 d-none">0</span>
                    </h3>
                    <div class="ml-auto">
                        <i id="angle1" class="fas fa-angle-down text-white"></i>
                    </div>
                </div>

                <form role="form" id="filter-form" method="post" action="">
                    @csrf

                    <div class="card-body">

                        {{-- Section 1: Lead info --}}
                        <div class="filter-section">
                            <h6 class="filter-section-title">
                                <i class="fas fa-tag text-primary mr-1"></i> Lead Info
                            </h6>
                            <div class="row">
                                <div class="col-md-4 form-group mb-2 d-flex align-items-center">
                                    <label for="fstatus" class="inline-label">Status</label>
                                    <select id="fstatus" name="fstatus" class="form-control form-control-sm select2 flex-grow-1">
                                        <option value="all">All statuses</option>
                                        @foreach($statuses as $status)
                                            @if (auth()->user()->hasRole('accountant')
                                                && !in_array($status->slug, ['booking', 'approved', 'sold']))
                                                @continue
                                            @endif
                                            <option value="{{ $status->slug }}"
                                                {{ $currentStatus == $status->slug ? 'selected' : '' }}>
                                                {{ $status->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                @hasanyrole('super-admin|sales-manager')
                                <div class="col-md-4 form-group mb-2 d-flex align-items-center">
                                    <label for="sales" class="inline-label">
                                        <i class="fas fa-user-tie"></i> Sales Rep
                                    </label>
                                    <select id="sales" name="sales" class="form-control form-control-sm select2 flex-grow-1">
                                        <option value="all"
                                            {{ (empty($currentSale) || $currentSale === 'all') ? 'selected' : '' }}>
                                            All sales
                                        </option>
                                        @foreach($sales as $sale)
                                            <option value="{{ $sale->id }}"
                                                {{ (string) $currentSale === (string) $sale->id ? 'selected' : '' }}>
                                                {{ $sale->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @endhasanyrole

                                <div class="col-md-4 form-group mb-2 d-flex align-items-center">
                                    <label for="camp" class="inline-label">
                                        <i class="fas fa-bullhorn"></i> Campaign
                                    </label>
                                    <input type="text" id="camp" name="camp"
                                           class="form-control form-control-sm flex-grow-1"
                                           placeholder="Campaign name"
                                           value="{{ $camp }}"/>
                                </div>
                            </div>
                        </div>

                        <hr class="my-3">

                        {{-- Section 2: Date ranges --}}
                        <div class="filter-section">
                            <h6 class="filter-section-title">
                                <i class="far fa-calendar-alt text-primary mr-1"></i> Date Range
                            </h6>

                            <div class="row align-items-start">
                                <div class="col-md-6 mb-2 d-flex align-items-center">
                                    <label class="inline-label">Created</label>
                                    <div class="input-group input-group-sm flex-grow-1">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text">From</span>
                                        </div>
                                        <input type="date" name="from" id="from"
                                               class="form-control" value="{{ $from }}"/>
                                        <div class="input-group-prepend input-group-append">
                                            <span class="input-group-text">To</span>
                                        </div>
                                        <input type="date" name="to" id="to"
                                               class="form-control" value="{{ $to }}"/>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-2">
                                    <div class="d-flex align-items-center">
                                        <label class="inline-label">Updated</label>
                                        <div class="input-group input-group-sm flex-grow-1">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">From</span>
                                            </div>
                                            <input type="date" name="updated_from" id="updated-from"
                                                   class="form-control" value="{{ $updatedFrom }}"/>
                                            <div class="input-group-prepend input-group-append">
                                                <span class="input-group-text">To</span>
                                            </div>
                                            <input type="date" name="updated_to" id="updated-to"
                                                   class="form-control" value="{{ $updatedTo }}"/>
                                        </div>
                                    </div>
                                    {{-- Quick date presets, affect the Updated range --}}
                                    <div class="mt-2 ml-auto" style="padding-left: calc(3.5rem + 2px);">
                                        <small class="text-muted mr-2">Quick:</small>
                                        <button type="button" class="btn btn-xs btn-outline-secondary date-preset" data-range="today">Today</button>
                                        <button type="button" class="btn btn-xs btn-outline-secondary date-preset" data-range="week">This week</button>
                                        <button type="button" class="btn btn-xs btn-outline-secondary date-preset" data-range="month">This month</button>
                                        <button type="button" class="btn btn-xs btn-outline-secondary date-preset" data-range="last30">Last 30 days</button>
                                        <button type="button" class="btn btn-xs btn-outline-danger date-preset" data-range="clear">Clear</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-3">

                        {{-- Section 3: Personal info --}}
                        <div class="filter-section">
                            <h6 class="filter-section-title">
                                <i class="fas fa-user text-primary mr-1"></i> Personal Info
                            </h6>
                            <div class="row">
                                <div class="col-md-6 form-group mb-2 d-flex align-items-center">
                                    <label for="fullName" class="inline-label">Full Name</label>
                                    <div class="input-group input-group-sm flex-grow-1">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        </div>
                                        <input type="text" id="fullName" name="fullName"
                                               class="form-control"
                                               placeholder="Search by name"
                                               value="{{ $fullName }}"/>
                                    </div>
                                </div>
                                <div class="col-md-6 form-group mb-2 d-flex align-items-center">
                                    <label for="phone" class="inline-label">Phone</label>
                                    <div class="input-group input-group-sm flex-grow-1">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        </div>
                                        <input type="text" id="phone" name="phone"
                                               class="form-control"
                                               placeholder="Search by phone"
                                               value="{{ $phone }}"/>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-light d-flex align-items-center">
                        <button type="button" id="reset-filter" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-undo mr-1"></i> Reset
                        </button>
                        <button type="button" id="ok-filter" class="btn btn-primary ml-auto">
                            <i class="fas fa-search mr-1"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ===================== RESULTS TABLE ===================== --}}
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h3 class="card-title">
                        <i class="fas fa-list mr-1 text-secondary"></i>
                        Results
                        @php
                            // Total count across all status buckets
                            $totalCount = collect($stats)->sum(fn($bucket) => is_countable($bucket) ? count($bucket) : 0);
                        @endphp
                        <span class="badge badge-secondary ml-1">{{ $totalCount }}</span>
                    </h3>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example2" class="table table-hover table-striped align-middle">
                            <thead class="thead-light">
                            <tr>
                                <th style="width:60px;">ID</th>
                                <th>Campaign</th>
                                <th>Full Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Assigned</th>
                                <th>Last Follow-up</th>
                                @unlessrole('sale|tele-sale')
                                <th>Created</th>
                                <th>Updated</th>
                                @endunlessrole
                                <th style="width:80px;" class="text-center">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($stats as $bucketKey => $tickets)
                                @foreach($tickets as $ticket)
                                    @php
                                        // Row highlight mapping by status slug
                                        if ($ticket->status->slug === 'duplicated') {
                                            $rowClass = 'table-danger';
                                        } elseif (in_array($ticket->status->slug, ['dead', 'dead-tele'], true)) {
                                            $rowClass = 'table-warning';
                                        } else {
                                            $rowClass = '';
                                        }
                                    @endphp
                                    <tr class="{{ $rowClass }}">
                                        <td>
                                            <a href="{{ route('tickets.show', [$ticket->id]) }}" class="font-weight-bold">
                                                #{{ $ticket->id }}
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{ route('tickets.show', [$ticket->id]) }}" class="text-dark">
                                                {{ $ticket->campaign_name ?: '—' }}
                                            </a>
                                        </td>
                                        <td>{{ $ticket->full_name ?: '—' }}</td>
                                        <td>
                                            @if($ticket->phone_number)
                                                <a href="tel:{{ $ticket->phone_number }}" class="text-reset">
                                                    <i class="fas fa-phone-alt text-muted mr-1"></i>
                                                    {{ $ticket->phone_number }}
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            @if($ticket->email)
                                                <a href="mailto:{{ $ticket->email }}" class="text-reset">
                                                    {{ $ticket->email }}
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                                <span class="badge status-badge"
                                                      style="color:#FFF; background-color: {{ Config::get('constants.status_colors.' . $ticket->status->slug) }};">
                                                    {{ $ticket->status->name }}
                                                </span>
                                        </td>
                                        <td>
                                            @if($ticket->user)
                                                <a href="{{ route('users.edit', [$ticket->user->id]) }}" target="_blank">
                                                    <i class="fas fa-user-circle text-muted mr-1"></i>
                                                    {{ $ticket->user->name }}
                                                </a>
                                            @else
                                                <span class="text-muted">Unassigned</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small>{{ $ticket->lastFollowUp ?: '—' }}</small>
                                        </td>
                                        @unlessrole('sale|tele-sale')
                                        <td>
                                            <small class="text-muted">
                                                {{ date('d/m/Y h:i A', strtotime($ticket->created_at)) }}
                                            </small>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ date('d/m/Y h:i A', strtotime($ticket->latestPath->created_at)) }}
                                            </small>
                                        </td>
                                        @endunlessrole
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light border dropdown-toggle"
                                                        type="button" data-toggle="dropdown">
                                                    <i class="fas fa-cogs"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-right">
                                                    @can('show ticket')
                                                        <a class="dropdown-item"
                                                           href="{{ route('tickets.show', [$ticket->id]) }}">
                                                            <i class="fas fa-info-circle text-info mr-1"></i> Details
                                                        </a>
                                                    @endcan
                                                    @can('delete ticket')
                                                        <div class="dropdown-divider"></div>
                                                        <a class="dropdown-item text-danger" href="#"
                                                           onclick="deleteTicket({{ $ticket->id }}); return false;">
                                                            <i class="fas fa-trash mr-1"></i> Delete
                                                        </a>
                                                    @endcan
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===================== STYLES ===================== --}}
    <style>
        /* Filter section styling */
        .filter-card .filter-section-title {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            margin-bottom: 0.75rem;
        }
        .filter-card hr {
            border-top: 1px dashed #dee2e6;
        }
        .filter-card .btn-xs {
            padding: 0.15rem 0.55rem;
            font-size: 0.75rem;
            border-radius: 0.2rem;
        }
        .filter-card .date-preset.active {
            background-color: #007bff;
            color: #fff;
            border-color: #007bff;
        }
        .filter-card .card-header {
            background-color: #2d3955 !important;
            color: #fff;
        }
        .filter-card .card-header .card-title { color: #fff; }

        /* Inline label */
        .inline-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #6c757d;
            white-space: nowrap;
            margin: 0 0.5rem 0 0;
        }

        /* Status badge styling */
        .status-badge {
            padding: 0.4em 0.65em;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-radius: 0.25rem;
        }

        /* Table polishing */
        table#example2 td, table#example2 th { vertical-align: middle; }
        table#example2 tbody tr { transition: background-color 0.15s ease-in-out; }
    </style>
@endsection

@section('script')
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script>
        const Toast = Swal.mixin({
            toast: true,
            background: '#E3E5E8',
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
        });
    </script>
    <script>
        /**
         * Build a GET URL from a form's fields, skipping empty values and the CSRF token.
         */
        function getParams(url, formId) {
            const form = document.getElementById(formId);
            const formData = new FormData(form);
            const parts = [];

            for (const [key, value] of formData.entries()) {
                if (key === '_token' || value === '' || value === 'all') continue;
                parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
            }

            return parts.length ? `${url}?${parts.join('&')}` : url;
        }

        $(function () {
            // Initialize Select2 dropdowns
            $('.select2').select2({ width: '100%' });
            $('.select2bs4').select2({ theme: 'bootstrap4', width: '100%' });

            // Initialize DataTable
            $('#example2').DataTable({
                paging: true,
                lengthChange: false,
                searching: false,
                ordering: true,
                info: true,
                autoWidth: false,
                responsive: true,
                pageLength: 15,
                order: [[0, 'desc']]
            });

            // Filter card toggle
            $("#flip").on('click', function () {
                $("#filter-form").slideToggle("fast");
                const $icon = $('#angle1');
                $icon.toggleClass('fa-angle-down fa-angle-up');
            });

            // Apply filter button
            $("#ok-filter").on('click', () => {
                let url = getParams('/', 'filter-form');
                url += (url.includes('?') ? '&' : '?') + 'linkable=1';
                location.href = url;
            });

            // Reset filter button
            $("#reset-filter").on('click', function () {
                $('#filter-form')[0].reset();
                $('#filter-form select').val('all').trigger('change');
                $('.date-preset').removeClass('active');
                updateActiveFiltersCount();
            });

            // Count and display active filters in the header badge
            function updateActiveFiltersCount() {
                let count = 0;
                $('#filter-form').find('input, select').each(function () {
                    const name = $(this).attr('name');
                    const val  = $(this).val();
                    if (name && name !== '_token' && val && val !== 'all') count++;
                });

                const $badge = $('#active-filters-count');
                if (count > 0) {
                    $badge.text(count).removeClass('d-none');
                } else {
                    $badge.addClass('d-none');
                }
            }

            updateActiveFiltersCount();
            $('#filter-form').on('change input', 'input, select', updateActiveFiltersCount);
            $('#filter-form').on('keydown', 'input', function (e) {
                if (e.key === 'Enter') $('#ok-filter').trigger('click');
            });

            // Quick date presets, applied to the "Created" range
            $('.date-preset').on('click', function () {
                const range = $(this).data('range');
                const today = new Date();
                let from    = new Date();

                if (range === 'clear') {
                    $('#from, #to, #updated-from, #updated-to').val('');
                    $('.date-preset').removeClass('active');
                    updateActiveFiltersCount();
                    return;
                }

                switch (range) {
                    case 'today':
                        from = today;
                        break;
                    case 'week':
                        from.setDate(today.getDate() - today.getDay());
                        break;
                    case 'month':
                        from = new Date(today.getFullYear(), today.getMonth(), 1);
                        break;
                    case 'last30':
                        from.setDate(today.getDate() - 30);
                        break;
                }

                const toISO = (d) => d.toISOString().split('T')[0];
                $('#updated-from').val(toISO(from));
                $('#updated-to').val(toISO(today));

                $('.date-preset').removeClass('active');
                $(this).addClass('active');
                updateActiveFiltersCount();
            });

            // Legacy handler for the (removed) search-by-ID form
            $("#ok-id").on('click', () => {
                const url = getParams('/tickets/all/', 'form-id');
                location.href = url;
            });
        });

        async function deleteTicket(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then(async (result) => {
                if (!result.value) return;

                const token = '{{ csrf_token() }}';

                try {
                    Swal.fire({
                        title: 'Please wait!',
                        imageUrl: '{{ asset('dist/img/loading2.gif') }}',
                        imageWidth: 128,
                        imageHeight: 128,
                        imageAlt: 'Deleting..',
                        showConfirmButton: false,
                    });

                    const resp = await axios.delete('/tickets/delete/' + id, {
                        withCredentials: true,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-Token': token,
                        }
                    });

                    Swal.close();

                    if (resp.data.OK) {
                        Toast.fire({ icon: 'success', title: 'Deleted successfully!' });
                        setTimeout(() => location.reload(), 1500);
                    } else if (resp.data.error) {
                        Toast.fire({ icon: 'warning', title: resp.data.error });
                    }
                } catch (e) {
                    Swal.close();
                    const errors = Object.values(e.response?.data ?? {}).join('\n') || 'An unexpected error occurred.';
                    Toast.fire({ icon: 'error', title: errors });
                }
            });
        }
    </script>
@endsection
