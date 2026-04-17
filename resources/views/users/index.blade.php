@extends('layouts.app')

@section('title')
    Users List
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item active">Users List</li>
@endsection

@section('content')

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <h5><i class="icon fas fa-ban"></i> Please fix the following:</h5>
            <ul class="mb-0 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===================== ACTION BUTTONS ===================== --}}
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{ route('users.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> Add New
            </a>
        </div>
    </div>

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

                <form role="form" id="filter-form" method="get" action="{{ route('users.all') }}">
                    <div class="card-body">

                        <div class="filter-section">
                            <h6 class="filter-section-title">
                                <i class="fas fa-user text-primary mr-1"></i> User Info
                            </h6>
                            <div class="row">
                                <div class="col-md-3 form-group mb-2 d-flex align-items-center">
                                    <label for="fullName" class="inline-label">Name</label>
                                    <input type="text" id="fullName" name="fullName"
                                           class="form-control form-control-sm flex-grow-1"
                                           placeholder="Search by name"
                                           value="{{ $fullName }}"/>
                                </div>
                                <div class="col-md-3 form-group mb-2 d-flex align-items-center">
                                    <label for="email" class="inline-label">Email</label>
                                    <input type="text" id="email" name="email"
                                           class="form-control form-control-sm flex-grow-1"
                                           placeholder="Search by email"
                                           value="{{ $email }}"/>
                                </div>
                                <div class="col-md-3 form-group mb-2 d-flex align-items-center">
                                    <label for="role" class="inline-label">Role</label>
                                    <select id="role" name="role" class="form-control form-control-sm select2 flex-grow-1">
                                        <option value="">All roles</option>
                                        @foreach($roles as $r)
                                            <option value="{{ $r }}" {{ $role === $r ? 'selected' : '' }}>
                                                {{ ucfirst($r) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3 form-group mb-2 d-flex align-items-center">
                                    <label for="status" class="inline-label">Status</label>
                                    <select id="status" name="status" class="form-control form-control-sm select2 flex-grow-1">
                                        <option value="">All statuses</option>
                                        <option value="permitted" {{ $status === 'permitted' ? 'selected' : '' }}>Permitted</option>
                                        <option value="banned"    {{ $status === 'banned'    ? 'selected' : '' }}>Banned</option>
                                        <option value="deleted"   {{ $status === 'deleted'   ? 'selected' : '' }}>Deleted</option>
                                    </select>
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
                        <i class="fas fa-users mr-1 text-secondary"></i>
                        Results
                        <span class="badge badge-secondary ml-1">{{ $users->count() }}</span>
                    </h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example2" class="table table-hover table-striped align-middle">
                            <thead class="thead-light">
                            <tr>
                                <th style="width:60px;">#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th style="width:80px;" class="text-center">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($users as $key => $user)
                                @php
                                    $statusColor = $user->deleted_at ? 'danger' : ($user->status === 'permitted' ? 'success' : 'warning');
                                    $statusLabel = $user->deleted_at ? 'Deleted' : ($user->status === 'permitted' ? 'Permitted' : 'Banned');
                                @endphp
                                <tr id="row_{{ $user->id }}" class="{{ $user->deleted_at ? 'table-danger' : '' }}">
                                    <td>{{ $key + 1 }}</td>
                                    <td>
                                        <a href="{{ route('users.edit', [$user->id]) }}" target="_blank" class="font-weight-bold">
                                            {{ $user->name }}
                                        </a>
                                    </td>
                                    <td>
                                        @if($user->email)
                                            <a href="mailto:{{ $user->email }}" class="text-reset">{{ $user->email }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        @if($user->phone)
                                            <a href="tel:{{ $user->phone }}" class="text-reset">{{ $user->phone }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-light border">{{ $user->role }}</span>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $statusColor }}">{{ $statusLabel }}</span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ date('d/m/Y h:i A', strtotime($user->created_at)) }}
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
                                                   href="{{ route('users.edit', [$user->id]) }}">
                                                    <i class="fas fa-pen text-primary mr-1"></i> Edit
                                                </a>
                                                @if(!$user->deleted_at)
                                                    <a class="dropdown-item"
                                                       href="{{ route('users.banPermit', [$user->id]) }}">
                                                        <i class="fas {{ $user->status === 'permitted' ? 'fa-ban text-warning' : 'fa-check-circle text-success' }} mr-1"></i>
                                                        {{ $user->status === 'permitted' ? 'Ban' : 'Permit' }}
                                                    </a>
                                                @endif
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item text-{{ $user->deleted_at ? 'success' : 'danger' }}"
                                                   href="#" onclick="deleteRestore({{ $user->id }}); return false;">
                                                    <i class="fas fa-trash{{ $user->deleted_at ? '-restore' : '' }} mr-1"></i>
                                                    {{ $user->deleted_at ? 'Restore' : 'Delete' }}
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
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
        .filter-card .filter-section-title {
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6c757d;
            margin-bottom: 0.75rem;
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

            $('#example2').DataTable({
                paging: true,
                lengthChange: false,
                searching: false,
                ordering: true,
                info: true,
                autoWidth: false,
                responsive: true,
                pageLength: 15,
            });

            $('#flip').on('click', function () {
                $('#filter-form').slideToggle('fast');
                $('#angle1').toggleClass('fa-angle-down fa-angle-up');
            });

            $('#ok-filter').on('click', () => {
                location.href = getParams('{{ route('users.all') }}', 'filter-form');
            });

            $('#reset-filter').on('click', function () {
                $('#filter-form')[0].reset();
                $('#filter-form select').val('').trigger('change');
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
        });

        async function deleteRestore(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, proceed!'
            }).then(async (result) => {
                if (!result.value) return;

                const token = '{{ csrf_token() }}';

                try {
                    Swal.fire({
                        title: 'Please wait!',
                        imageUrl: '{{ asset('dist/img/loading2.gif') }}',
                        imageWidth: 128,
                        imageHeight: 128,
                        imageAlt: 'Processing..',
                        showConfirmButton: false,
                    });

                    const resp = await axios.delete('/users/deleteRestore/' + id, {
                        withCredentials: true,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-Token': token,
                        }
                    });

                    Swal.close();

                    if (resp.data.OK) {
                        Toast.fire({ icon: 'success', title: 'Done!' });
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
