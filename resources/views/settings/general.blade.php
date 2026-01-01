@extends('layouts.app')

@section('title')
    General Settings
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item">General Settings</li>
@endsection

@section('content')
    <!-- Info boxes -->
    <div class="row">
        <!-- fix for small devices only -->
        <div class="clearfix hidden-md-up"></div>
    </div>
    <!-- /.row -->

    @if ($errors->any())
        <div class="row">
            <div class="col-12">
                <div class="alert alert-danger">
                    <ul style="list-style: none;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-12 col-sm-12">
            <div class="card card-primary card-outline card-tabs">
                <div class="card-body">
                    <form method="POST" action="{{ route('settings.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="card-body p-0">
                            <table class="table table-bordered table-hover mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th style="width: 40%">Setting</th>
                                    <th style="width: 30%">Value</th>
                                </tr>
                                </thead>
                                <tbody>
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

                                        // جاي من الكنترولر كـ array
                                        $selectedDays = old(
                                            'settings_values.working_days',
                                            $working_days ?? []
                                        );
                                    @endphp
                                    <td>
                                        <strong>Working Days</strong>
                                    </td>

                                    <td>
                                        @foreach ($days as $key => $label)
                                            <div class="custom-control custom-checkbox">
                                                <input
                                                    type="checkbox"
                                                    class="custom-control-input"
                                                    id="day_{{ $key }}"
                                                    name="settings_values[working_days][]"
                                                    value="{{ $key }}"
                                                    @checked(in_array($key, $selectedDays, true))
                                                >
                                                <label class="custom-control-label" for="day_{{ $key }}">
                                                    {{ $label }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>Start Time</strong>
                                    </td>

                                    <td>
                                        <input
                                            type="time"
                                            id="start_time"
                                            name="settings_values[start_time]"
                                            class="form-control"
                                            required
                                            step="60"
                                            value="{{ old('settings_values.start_time', $settingsByName['start_time']->value) }}"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <strong>End Time</strong>
                                    </td>

                                    <td>
                                        <input
                                            type="time"
                                            id="end_time"
                                            name="settings_values[end_time]"
                                            class="form-control"
                                            required
                                            step="60"
                                            value="{{ old('settings_values.end_time', $settingsByName['end_time']->value) }}"
                                        />
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary">
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });

        @if(session()->has('successMsg'))
        Toast.fire({
            icon: 'success',
            title: '{{ session()->get('successMsg') }}'
        });
        @endif
    </script>
@endsection
