@extends('layouts.app')

@section('title')
    Reports
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
@endsection

@section('content')

    {{-- ===================== FILTER CARD ===================== --}}
    @hasrole('super-admin')
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary shadow-sm filter-card">
                <div id="flip" class="card-header d-flex align-items-center" style="cursor: pointer;">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-filter mr-2"></i>
                        Filters
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
                                            <option value="{{ $status->slug }}"
                                                {{ $currentStatus == $status->slug ? 'selected' : '' }}>
                                                {{ $status->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-4 form-group mb-2 d-flex align-items-center">
                                    <label for="sales" class="inline-label">
                                        <i class="fas fa-user-tie mr-1"></i> Sales Rep
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

                                <div class="col-md-4 form-group mb-2 d-flex align-items-center">
                                    <label for="camp" class="inline-label">
                                        <i class="fas fa-bullhorn mr-1"></i> Campaign
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
    @endhasrole

    {{-- ===================== STAT BOXES ===================== --}}
    <div class="row">
        @foreach($stats as $key => $value)
            <div class="col-lg-3 col-6">
                <div class="small-box" style="background-color: {{ Config::get('constants.status_colors.' . $key) }};">
                    <div class="inner">
                        @hasanyrole('super-admin')
                        <h3>
                            <a href="#" class="text-light filter-leads" data-status="{{ strtolower($key) }}">
                                {{ $value }}
                            </a>
                        </h3>
                        <p>
                            <a href="#" class="text-light filter-leads" data-status="{{ strtolower($key) }}">
                                {{ $key }}
                            </a>
                        </p>
                        @else
                        <h3>
                            <a href="/tickets/all?fstatus={{ strtolower($key) }}" class="text-light">{{ $value }}</a>
                        </h3>
                        <p>
                            <a href="/tickets/all?fstatus={{ strtolower($key) }}" class="text-light">{{ $key }}</a>
                        </p>
                        @endhasanyrole
                    </div>
                    <div class="icon">
                        <i class="{{ Config::get('constants.status_icons.' . strtolower($key)) }}"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===================== STYLES ===================== --}}
    <style>
        .filter-card .filter-section-title {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            margin-bottom: 0.75rem;
        }
        .filter-card hr { border-top: 1px dashed #dee2e6; }
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
            background-color: {{ config('app.theme_color') }} !important;
            color: #fff;
        }
        .filter-card .card-header .card-title { color: #fff; }
        .inline-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #6c757d;
            white-space: nowrap;
            margin: 0 0.5rem 0 0;
        }
    </style>
@endsection

@section('script')
    <script>
        function getParams(url, formId, overrides) {
            overrides = overrides || {};
            const form = document.getElementById(formId);
            const formData = new FormData(form);
            const parts = [];
            const seen = {};

            for (const [key, value] of formData.entries()) {
                if (key === '_token' || value === '' || value === 'all') continue;
                const v = overrides[key] !== undefined ? overrides[key] : value;
                parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(v));
                seen[key] = true;
            }

            // Append any overrides not already in the form
            for (const [key, value] of Object.entries(overrides)) {
                if (!seen[key]) {
                    parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
                }
            }

            return parts.length ? `${url}?${parts.join('&')}` : url;
        }

        $(function () {
            $('.select2').select2({ width: '100%' });
            $('.select2bs4').select2({ theme: 'bootstrap4', width: '100%' });

            $('#flip').on('click', function () {
                $('#filter-form').slideToggle('fast');
                $('#angle1').toggleClass('fa-angle-down fa-angle-up');
            });

            $('#ok-filter').on('click', () => {
                location.href = getParams('/', 'filter-form');
            });

            $('#reset-filter').on('click', function () {
                $('#filter-form')[0].reset();
                $('#filter-form select').val('all').trigger('change');
                $('.date-preset').removeClass('active');
                updateActiveFiltersCount();
            });

            function updateActiveFiltersCount() {
                let count = 0;
                $('#filter-form').find('input, select').each(function () {
                    const name = $(this).attr('name');
                    const val  = $(this).val();
                    if (name && name !== '_token' && val && val !== 'all') count++;
                });
                const $badge = $('#active-filters-count');
                count > 0 ? $badge.text(count).removeClass('d-none') : $badge.addClass('d-none');
            }

            updateActiveFiltersCount();
            $('#filter-form').on('change input', 'input, select', updateActiveFiltersCount);
            $('#filter-form').on('keydown', 'input', function (e) {
                if (e.key === 'Enter') $('#ok-filter').trigger('click');
            });

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
                    case 'today':  from = today; break;
                    case 'week':   from.setDate(today.getDate() - today.getDay()); break;
                    case 'month':  from = new Date(today.getFullYear(), today.getMonth(), 1); break;
                    case 'last30': from.setDate(today.getDate() - 30); break;
                }

                const toISO = d => d.toISOString().split('T')[0];
                $('#updated-from').val(toISO(from));
                $('#updated-to').val(toISO(today));

                $('.date-preset').removeClass('active');
                $(this).addClass('active');
                updateActiveFiltersCount();
            });

            // Stat box click: navigate to report with that status + current filter params
            $('.filter-leads').on('click', function () {
                const status = $(this).data('status');
                let url = getParams('/', 'filter-form', { fstatus: status });
                url += (url.includes('?') ? '&' : '?') + 'linkable=1';
                location.href = url;
            });
        });
    </script>
@endsection
