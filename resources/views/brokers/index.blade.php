@extends('layouts.app')

@section('title')
    Brokers List
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item active">Brokers List</li>
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

    {{-- ===================== ACTION BUTTONS ===================== --}}
    @can('add ticket')
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('brokers.add') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> Add New
            </a>
        </div>
    </div>
    @endcan

    {{-- ===================== FILTER CARD ===================== --}}
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

                <form role="form" id="filter-form" method="get" action="{{ route('brokers.index') }}">

                    <div class="card-body">

                        {{-- Section 1: Broker Info --}}
                        <div class="filter-section">
                            <h6 class="filter-section-title">
                                <i class="fas fa-tag text-primary mr-1"></i> Broker Info
                            </h6>
                            <div class="row">
                                <div class="col-md-4 form-group mb-2 d-flex align-items-center">
                                    <label for="type" class="inline-label">Type</label>
                                    <select id="type" name="type" class="form-control form-control-sm select2 flex-grow-1">
                                        <option value="">All types</option>
                                        <option value="Individual" {{ $type === 'Individual' ? 'selected' : '' }}>Individual</option>
                                        <option value="Company"    {{ $type === 'Company'    ? 'selected' : '' }}>Company</option>
                                    </select>
                                </div>
                                <div class="col-md-4 form-group mb-2 d-flex align-items-center">
                                    <label for="fullName" class="inline-label">
                                        <i class="fas fa-user mr-1"></i> Name
                                    </label>
                                    <input type="text" id="fullName" name="fullName"
                                           class="form-control form-control-sm flex-grow-1"
                                           placeholder="Search by name"
                                           value="{{ $fullName }}"/>
                                </div>
                                <div class="col-md-4 form-group mb-2 d-flex align-items-center">
                                    <label for="phone" class="inline-label">
                                        <i class="fas fa-phone mr-1"></i> Phone
                                    </label>
                                    <input type="text" id="phone" name="phone"
                                           class="form-control form-control-sm flex-grow-1"
                                           placeholder="Search by phone"
                                           value="{{ $phone }}"/>
                                </div>
                                <div class="col-md-4 form-group mb-2 d-flex align-items-center">
                                    <label for="email" class="inline-label">
                                        <i class="fas fa-envelope mr-1"></i> Email
                                    </label>
                                    <input type="text" id="email" name="email"
                                           class="form-control form-control-sm flex-grow-1"
                                           placeholder="Search by email"
                                           value="{{ $email }}"/>
                                </div>
                            </div>
                        </div>

                        <hr class="my-3">

                        {{-- Section 2: Date range --}}
                        <div class="filter-section">
                            <h6 class="filter-section-title">
                                <i class="far fa-calendar-alt text-primary mr-1"></i> Registration Date
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
                                    <div class="mt-1" style="padding-left: calc(3.5rem + 2px);">
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
                        <span class="badge badge-secondary ml-1">{{ $brokers->total() }}</span>
                    </h3>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example20" class="table table-hover table-striped align-middle">
                            <thead class="thead-light">
                            <tr>
                                <th style="width:60px;">ID</th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Type</th>
                                <th>Company</th>
                                <th>Registered</th>
                                <th style="width:80px;" class="text-center">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($brokers as $broker)
                                <tr id="row_{{ $broker->id }}">
                                    <td>
                                        <a href="{{ route('brokers.show', [$broker->id]) }}" class="font-weight-bold">
                                            #{{ $broker->id }}
                                        </a>
                                    </td>
                                    <td>
                                        <a href="{{ route('brokers.show', [$broker->id]) }}" class="text-dark">
                                            {{ $broker->full_name ?: '—' }}
                                        </a>
                                    </td>
                                    <td>
                                        @if($broker->phone)
                                            <a href="tel:{{ $broker->phone }}" class="text-reset">
                                                <i class="fas fa-phone-alt text-muted mr-1"></i>
                                                {{ $broker->phone }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if($broker->email)
                                            <a href="mailto:{{ $broker->email }}" class="text-reset">
                                                {{ $broker->email }}
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $broker->type ?: '—' }}</td>
                                    <td>{{ $broker->company ?: '—' }}</td>
                                    <td>
                                        <small class="text-muted">
                                            {{ date('d/m/Y h:i A', strtotime($broker->created_at)) }}
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-light border dropdown-toggle"
                                                    type="button" data-toggle="dropdown">
                                                <i class="fas fa-cogs"></i>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a class="dropdown-item"
                                                   href="{{ route('brokers.show', [$broker->id]) }}">
                                                    <i class="fas fa-info-circle text-info mr-1"></i> Details
                                                </a>
                                                @can('delete ticket')
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item text-danger" href="#"
                                                       onclick="deleteBroker({{ $broker->id }}); return false;">
                                                        <i class="fas fa-trash mr-1"></i> Delete
                                                    </a>
                                                @endcan
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Server-side pagination --}}
                    <div class="row mt-3">
                        <div class="col-sm-12 col-md-5">
                            <div class="dataTables_info" role="status" aria-live="polite">
                                Showing {{ $brokers->firstItem() }} to {{ $brokers->lastItem() }}
                                of {{ $brokers->total() }} entries
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-7">
                            <div class="dataTables_paginate float-right paging_simple_numbers">
                                {{ $brokers->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
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
            background-color: #2d3955 !important;
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
        table#example20 td, table#example20 th { vertical-align: middle; }
        table#example20 tbody tr { transition: background-color 0.15s ease-in-out; }
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

        @if(session()->has('successMsg'))
        Toast.fire({
            icon: 'success',
            title: '{{ session()->get('successMsg') }}',
        });
        @endif

        function getParams(url, formId) {
            const form = document.getElementById(formId);
            const formData = new FormData(form);
            const parts = [];

            for (const [key, value] of formData.entries()) {
                if (key === '_token' || value === '') continue;
                parts.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
            }

            return parts.length ? `${url}?${parts.join('&')}` : url;
        }

        $(function () {
            $('.select2').select2({ width: '100%' });

            $('#example20').DataTable({
                paging: false,
                lengthChange: false,
                searching: false,
                ordering: true,
                info: false,
                responsive: true,
                autoWidth: false,
            });

            $('#flip').on('click', function () {
                $('#filter-form').slideToggle('fast');
                $('#angle1').toggleClass('fa-angle-down fa-angle-up');
            });

            $('#ok-filter').on('click', () => {
                location.href = getParams('{{ route('brokers.index') }}', 'filter-form');
            });

            $('#reset-filter').on('click', function () {
                $('#filter-form')[0].reset();
                $('#filter-form select').val('').trigger('change');
                $('.date-preset').removeClass('active');
                updateActiveFiltersCount();
            });

            function updateActiveFiltersCount() {
                let count = 0;
                $('#filter-form').find('input, select').each(function () {
                    const name = $(this).attr('name');
                    const val  = $(this).val();
                    if (name && val) count++;
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
                    $('#from, #to').val('');
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
                $('#from').val(toISO(from));
                $('#to').val(toISO(today));

                $('.date-preset').removeClass('active');
                $(this).addClass('active');
                updateActiveFiltersCount();
            });
        });

        function deleteRow(rowId) {
            const row = document.getElementById(rowId);
            if (row) row.parentNode.removeChild(row);
        }

        async function deleteBroker(id) {
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

                    const resp = await axios.delete('/brokers/delete/' + id, {
                        withCredentials: true,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-Token': token,
                        }
                    });

                    Swal.close();

                    if (resp.data.OK) {
                        deleteRow('row_' + id);
                        Toast.fire({ icon: 'success', title: 'Broker deleted successfully!' });
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
