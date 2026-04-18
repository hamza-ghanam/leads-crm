@extends('layouts.app')

@section('title')
    General Settings
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item active">General Settings</li>
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
                        <i class="fas fa-cog mr-2"></i> General Settings
                    </h3>
                </div>

                <form method="POST" action="{{ route('settings.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="card-body p-0">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="thead-light">
                            <tr>
                                <th style="width:40%;">Setting</th>
                                <th>Value</th>
                            </tr>
                            </thead>
                            <tbody>

                            {{-- Working Days --}}
                            <tr>
                                @php
                                    $days = [
                                        'sat' => 'Saturday',
                                        'sun' => 'Sunday',
                                        'mon' => 'Monday',
                                        'tue' => 'Tuesday',
                                        'wed' => 'Wednesday',
                                        'thu' => 'Thursday',
                                        'fri' => 'Friday',
                                    ];
                                    $selectedDays = old('settings_values.working_days', $working_days ?? []);
                                @endphp
                                <td class="align-middle">
                                    <strong>Working Days</strong>
                                    <div class="text-muted small">Days on which leads are distributed</div>
                                </td>
                                <td>
                                    <div class="row">
                                        @foreach ($days as $key => $label)
                                            <div class="col-6 col-md-4">
                                                <div class="custom-control custom-checkbox mb-1">
                                                    <input type="checkbox"
                                                           class="custom-control-input"
                                                           id="day_{{ $key }}"
                                                           name="settings_values[working_days][]"
                                                           value="{{ $key }}"
                                                           @checked(in_array($key, $selectedDays, true))/>
                                                    <label class="custom-control-label" for="day_{{ $key }}">
                                                        {{ $label }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>

                            {{-- Start Time --}}
                            <tr>
                                <td class="align-middle">
                                    <strong>Start Time</strong>
                                    <div class="text-muted small">Beginning of the working day</div>
                                </td>
                                <td class="align-middle">
                                    <input type="time"
                                           id="start_time"
                                           name="settings_values[start_time]"
                                           class="form-control form-control-sm"
                                           style="max-width: 160px;"
                                           required step="60"
                                           value="{{ old('settings_values.start_time', $settingsByName['start_time']->value) }}"/>
                                </td>
                            </tr>

                            {{-- End Time --}}
                            <tr>
                                <td class="align-middle">
                                    <strong>End Time</strong>
                                    <div class="text-muted small">End of the working day</div>
                                </td>
                                <td class="align-middle">
                                    <input type="time"
                                           id="end_time"
                                           name="settings_values[end_time]"
                                           class="form-control form-control-sm"
                                           style="max-width: 160px;"
                                           required step="60"
                                           value="{{ old('settings_values.end_time', $settingsByName['end_time']->value) }}"/>
                                </td>
                            </tr>

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
