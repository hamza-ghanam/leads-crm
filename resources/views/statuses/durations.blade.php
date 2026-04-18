@extends('layouts.app')

@section('title')
    Status Configuration
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item active">Status Configuration</li>
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

    <div class="row justify-content-center">
        <div class="col-12 col-md-8">
            <div class="card shadow-sm">
                <div class="card-header" style="background-color: {{ config('app.theme_color') }};">
                    <h3 class="card-title text-white mb-0">
                        <i class="fas fa-clock mr-2"></i> Status Durations
                    </h3>
                </div>

                <form method="POST" action="{{ route('statuses.save-durations') }}">
                    @csrf

                    <div class="card-body p-0">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="thead-light">
                            <tr>
                                <th style="width:40%;">Status</th>
                                <th style="width:30%;">Duration</th>
                                <th style="width:30%;">Unit</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($statuses as $status)
                                @php
                                    $durationValue = null;
                                    $durationUnit  = 'hour';

                                    if ($status->duration && preg_match('/^(\d+)([hd])$/', $status->duration, $m)) {
                                        $durationValue = (int) $m[1];
                                        $durationUnit  = $m[2] === 'h' ? 'hour' : 'day';
                                    }
                                @endphp
                                <tr>
                                    <td class="align-middle">
                                        <span class="badge mr-2"
                                              style="background-color: {{ Config::get('constants.status_colors.' . $status->slug) }}; color:#fff;">
                                            &nbsp;
                                        </span>
                                        <strong>{{ $status->name }}</strong>
                                        <input type="hidden"
                                               name="statuses[{{ $status->id }}][id]"
                                               value="{{ $status->id }}"/>
                                    </td>
                                    <td class="align-middle">
                                        <input type="number"
                                               name="statuses[{{ $status->id }}][duration]"
                                               class="form-control form-control-sm"
                                               style="max-width: 120px;"
                                               min="1"
                                               placeholder="—"
                                               value="{{ old('statuses.' . $status->id . '.duration', $durationValue) }}"/>
                                    </td>
                                    <td class="align-middle">
                                        <select name="statuses[{{ $status->id }}][unit]"
                                                class="form-control form-control-sm"
                                                style="max-width: 130px;">
                                            <option value="">-- Select --</option>
                                            <option value="hour"
                                                {{ old('statuses.' . $status->id . '.unit', $durationUnit) === 'hour' ? 'selected' : '' }}>
                                                Hour(s)
                                            </option>
                                            <option value="day"
                                                {{ old('statuses.' . $status->id . '.unit', $durationUnit) === 'day' ? 'selected' : '' }}>
                                                Day(s)
                                            </option>
                                        </select>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">
                                        <i class="fas fa-inbox mr-1"></i> No statuses found.
                                    </td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="card-footer bg-light d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary" id="submit-btn">
                            <i class="fas fa-save mr-1"></i> Save Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
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

        document.querySelector('form').addEventListener('submit', function () {
            const btn = document.getElementById('submit-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';
        });
    </script>
@endsection
