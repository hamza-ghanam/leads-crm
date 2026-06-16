@extends('layouts.app')

@section('title')
    Sales Campaigns
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item active">Sales Campaigns</li>
@endsection

@section('content')

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

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header" style="background-color: {{ config('app.theme_color') }};">
                    <h3 class="card-title text-white mb-0">
                        <i class="fas fa-bullhorn mr-2"></i> Sales Campaigns
                    </h3>
                    <div class="card-tools d-flex align-items-center">
                        {{-- Shuffle toggle --}}
                        <form method="post" name="settings-form" id="settings-form"
                              action="{{ route('settings.update') }}" class="mr-3 mb-0">
                            @csrf
                            @method('PUT')
                            <div class="custom-control custom-switch mb-0">
                                <input type="hidden"
                                       name="settings_values[{{ $camp_assign_key }}]"
                                       value="0"/>
                                <input type="checkbox" class="custom-control-input" id="customSwitch1"
                                       name="settings_values[{{ $camp_assign_key }}]"
                                       value="1"
                                       @checked(old('settings_values.'.$camp_assign_key, $camp_assign_value) == '1')/>
                                <label class="custom-control-label text-white" for="customSwitch1">
                                    Enable shuffle by campaigns
                                </label>
                            </div>
                        </form>

                        <button type="button" id="add-sacamp" class="btn btn-sm btn-success">
                            <i class="fas fa-plus mr-1"></i> Add
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="example2" class="table table-hover table-striped align-middle">
                            <thead class="thead-light">
                            <tr>
                                <th style="width:50px;">#</th>
                                <th>Sales Name</th>
                                <th>Campaign Name</th>
                                <th>Assigned At</th>
                                <th style="width:80px;" class="text-center">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($salesCamps as $key => $salesCamp)
                                <tr>
                                    <td>{{ $key + 1 }}</td>
                                    <td>
                                        <a target="_blank" href="{{ route('users.edit', [$salesCamp->user_id]) }}">
                                            <i class="fas fa-user-circle text-muted mr-1"></i>
                                            {{ optional($salesCamp->user)->name ?? 'Deleted User' }}
                                        </a>
                                    </td>
                                    <td>{{ $salesCamp->campaign_name }}</td>
                                    <td>
                                        <small class="text-muted">
                                            {{ date('d/m/Y h:i A', strtotime($salesCamp->created_at)) }}
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-danger"
                                                onclick="deleteSalesCamps({{ $salesCamp->id }})"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
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

    {{-- Add modal --}}
    <div class="modal fade" id="modal-lg">
        <form name="ff" action="{{ route('salesCamps.store') }}" method="post">
            @csrf
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header" style="background-color: {{ config('app.theme_color') }};">
                        <h4 class="modal-title text-white">
                            <i class="fas fa-plus-circle mr-1"></i> Assign Sales to Campaign
                        </h4>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="campaign_name">Campaign Name <sup class="text-danger">*</sup></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-bullhorn"></i></span>
                                </div>
                                <input type="text" class="form-control" name="campaign_name" id="campaign_name"
                                       placeholder="Enter campaign name"
                                       value="{{ old('campaign_name') }}" required/>
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label for="sales">Sales Employee <sup class="text-danger">*</sup></label>
                            <select name="sales" id="sales" class="form-control select2-modal" required>
                                <optgroup label="Sales">
                                    @foreach($sales as $user)
                                        @if($user->getRoleNames()[0] === 'sale')
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endif
                                    @endforeach
                                </optgroup>
                                <optgroup label="Tele-Sales">
                                    @foreach($sales as $user)
                                        @if($user->getRoleNames()[0] === 'tele-sale')
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endif
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="saveBtn">
                            <i class="fas fa-save mr-1"></i> Submit
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <style>
        table#example2 td, table#example2 th { vertical-align: middle; }
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
                title: '{{ session()->get('successMsg') }}'
            });
        @endif

        $(function () {
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

            $('.select2-modal').select2({
                dropdownParent: $('#modal-lg'),
                placeholder: 'Select sales employee',
                width: '100%',
            });
        });

        document.getElementById('add-sacamp').addEventListener('click', () => {
            $('#modal-lg').modal('show');
        });

        document.getElementById('customSwitch1').addEventListener('change', async (e) => {
            const form     = document.forms['settings-form'];
            const formData = new FormData(form);

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });

                const jsonRes = await res.json();
                Toast.fire({ icon: 'success', title: jsonRes.message });
            } catch (err) {
                console.error(err);
                Toast.fire({ icon: 'error', title: 'Failed to save setting.' });
            }
        });

        async function deleteSalesCamps(id) {
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

                    const resp = await axios.delete('/salesCamps/delete/' + id, {
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

        {{ Session::forget('successMsg') }}
    </script>
@endsection
