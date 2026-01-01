@extends('layouts.app')

@section('title')
    Status Configuration
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item">Status Configuration</li>
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

                    <form method="POST" action="{{ route('statuses.save-durations') }}">
                        @csrf

                        <div class="card-body p-0">
                            <table class="table table-bordered table-hover mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th style="width: 40%">Status</th>
                                    <th style="width: 30%">Duration</th>
                                    <th style="width: 30%">Unit</th>
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
                                        <td>
                                            <strong>{{ $status->name }}</strong>

                                            <input type="hidden"
                                                   name="statuses[{{ $status->id }}][id]"
                                                   value="{{ $status->id }}">
                                        </td>

                                        <td>
                                            <input type="number"
                                                   name="statuses[{{ $status->id }}][duration]"
                                                   class="form-control" min="1"
                                                   value="{{ old("statuses.$status->id.duration", $durationValue) }}">
                                        </td>

                                        <td>
                                            <select name="statuses[{{ $status->id }}][unit]"
                                                    class="form-select"
                                                    required>
                                                <option value="">-- Select unit --</option>
                                                <option value="hour"
                                                    {{ old("statuses.$status->id.unit", $durationUnit) === 'hour' ? 'selected' : '' }}>
                                                    Hour(s)
                                                </option>

                                                <option value="day"
                                                    {{ old("statuses.$status->id.unit", $durationUnit) === 'day' ? 'selected' : '' }}>
                                                    Day(s)
                                                </option>
                                            </select>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">
                                            No statuses found
                                        </td>
                                    </tr>
                                @endforelse
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
