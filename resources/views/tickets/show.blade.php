@extends('layouts.app')

@section('title')
    Lead Details
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('tickets.all') }}">Leads</a></li>
    <li class="breadcrumb-item active">Lead #{{ $ticket->id }}</li>
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

    {{-- ===================== BASIC DETAILS + BOOKING ===================== --}}
    <div class="row">

        {{-- Basic details --}}
        <div class="col-{{ $booking ? 6 : 12 }}">
            <div class="card shadow-sm">
                <div class="card-header" style="background-color: #2d3955;">
                    <h3 class="card-title text-white mb-0">
                        <i class="fas fa-user mr-2"></i>
                        Lead #{{ $ticket->id }}
                        @if($ticket->full_name)
                            — <i>{{ $ticket->full_name }}</i>
                        @endif
                    </h3>
                    <div class="card-tools">
                        <span class="badge"
                              style="color:#FFF; background-color: {{ Config::get('constants.status_colors.' . $ticket->status->slug) }};">
                            {{ $ticket->status->name }}
                        </span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <form name="f1" action="{{ route('tickets.update', $ticket->id) }}" method="post">
                        @csrf
                        @method('PUT')

                        <dl class="lead-detail-list mb-0">

                        @hasanyrole('super-admin')
                            <div class="lead-detail-row">
                                <dt>Number</dt>
                                <dd><input type="text" class="form-control form-control-sm" id="number" name="number" placeholder="Enter Number" value="{{ $ticket->number ?? '' }}"/></dd>
                            </div>
                            <div class="lead-detail-row">
                                <dt>AD ID</dt>
                                <dd><input type="text" class="form-control form-control-sm" id="ad_id" name="ad_id" placeholder="Enter Ad ID" value="{{ $ticket->ad_id ?? '' }}"/></dd>
                            </div>
                            <div class="lead-detail-row">
                                <dt>AD Name</dt>
                                <dd><input type="text" class="form-control form-control-sm" id="ad_name" name="ad_name" placeholder="Enter Ad Name" value="{{ $ticket->ad_name ?? '' }}"/></dd>
                            </div>
                            <div class="lead-detail-row">
                                <dt>AD Set ID</dt>
                                <dd><input type="text" class="form-control form-control-sm" id="adset_id" name="adset_id" placeholder="Enter Adset ID" value="{{ $ticket->adset_id ?? '' }}"/></dd>
                            </div>
                            <div class="lead-detail-row">
                                <dt>AD Set Name</dt>
                                <dd><input type="text" class="form-control form-control-sm" id="adset_name" name="adset_name" placeholder="Enter Adset Name" value="{{ $ticket->adset_name ?? '' }}"/></dd>
                            </div>
                            <div class="lead-detail-row">
                                <dt>Campaign ID</dt>
                                <dd><input type="text" class="form-control form-control-sm" id="campaign_id" name="campaign_id" placeholder="Enter Campaign ID" value="{{ $ticket->campaign_id ?? '' }}"/></dd>
                            </div>
                            <div class="lead-detail-row">
                                <dt>Campaign Name</dt>
                                <dd><input type="text" class="form-control form-control-sm" id="campaign_name" name="campaign_name" placeholder="Enter Campaign Name" value="{{ $ticket->campaign_name ?? '' }}"/></dd>
                            </div>
                            <div class="lead-detail-row">
                                <dt>Form ID</dt>
                                <dd><input type="text" class="form-control form-control-sm" id="form_id" name="form_id" placeholder="Enter Form ID" value="{{ $ticket->form_id ?? '' }}"/></dd>
                            </div>
                            <div class="lead-detail-row">
                                <dt>Form Name</dt>
                                <dd><input type="text" class="form-control form-control-sm" id="form_name" name="form_name" placeholder="Enter Form Name" value="{{ $ticket->form_name ?? '' }}"/></dd>
                            </div>
                            <div class="lead-detail-row">
                                <dt>Is Organic</dt>
                                <dd class="d-flex align-items-center">
                                    <input class="form-check-input m-0" type="checkbox" id="is_organic" name="is_organic" {{ $ticket->is_organic == 1 ? 'checked' : '' }}>
                                </dd>
                            </div>
                        @else
                            <div class="lead-detail-row">
                                <dt>Number</dt>
                                <dd>{{ $ticket->number ?? '—' }}</dd>
                            </div>
                            <div class="lead-detail-row">
                                <dt>Is Organic</dt>
                                <dd>{{ isset($ticket->is_organic) ? ($ticket->is_organic != 0 ? 'Yes' : 'No') : '—' }}</dd>
                            </div>
                        @endhasanyrole

                        {{-- Full Name --}}
                        <div class="lead-detail-row">
                            <dt>Full Name</dt>
                            <dd>
                                @hasanyrole('admin|super-admin|sale|tele-sale')
                                    <input type="text" class="form-control form-control-sm" id="full_name" name="full_name" placeholder="Enter Full Name" value="{{ $ticket->full_name ?? '' }}" required/>
                                @else
                                    {{ $ticket->full_name ?? '—' }}
                                @endhasanyrole
                            </dd>
                        </div>

                        {{-- Phone Number --}}
                        <div class="lead-detail-row">
                            <dt>Phone Number</dt>
                            <dd>
                                @hasanyrole('admin|super-admin')
                                    <input type="text" class="form-control form-control-sm" id="phone_number" name="phone_number" placeholder="Enter Phone Number" value="{{ $ticket->phone_number ?? '' }}" required/>
                                @else
                                    @if($ticket->phone_number)
                                        <a href="tel:{{ $ticket->phone_number }}" class="text-reset">{{ $ticket->phone_number }}</a>
                                    @else
                                        —
                                    @endif
                                @endhasanyrole
                            </dd>
                        </div>

                        {{-- Email --}}
                        <div class="lead-detail-row">
                            <dt>Email</dt>
                            <dd>
                                @hasanyrole('admin|super-admin')
                                    <input type="text" class="form-control form-control-sm" id="email" name="email" placeholder="Enter Email Address" value="{{ $ticket->email ?? '' }}"/>
                                @else
                                    @if($ticket->email)
                                        <a href="mailto:{{ $ticket->email }}" class="text-reset">{{ $ticket->email }}</a>
                                    @else
                                        —
                                    @endif
                                @endhasanyrole
                            </dd>
                        </div>

                        {{-- Job Title --}}
                        <div class="lead-detail-row">
                            <dt>Job Title</dt>
                            <dd>
                                @hasanyrole('super-admin')
                                    <input type="text" class="form-control form-control-sm" id="job_title" name="job_title" placeholder="Enter Job Title" value="{{ $ticket->job_title ?? '' }}"/>
                                @else
                                    {{ $ticket->job_title ?? '—' }}
                                @endhasanyrole
                            </dd>
                        </div>

                        {{-- Source --}}
                        <div class="lead-detail-row">
                            <dt>Source</dt>
                            <dd>
                                @hasanyrole('super-admin')
                                    <select name="source" id="source" class="form-control form-control-sm select2" required>
                                        @foreach($sources as $source)
                                            <option value="{{ $source->id }}" {{ ($ticket->source_id && $ticket->source_id == $source->id) ? 'selected' : '' }}>{{ $source->name }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    {{ $ticket->source_id ? $ticket->source->name : '—' }}
                                @endhasanyrole
                            </dd>
                        </div>

                        <div class="lead-detail-row">
                            <dt>Preferred Time to Call</dt>
                            <dd>{{ $ticket->preferred_time ?? '—' }}</dd>
                        </div>

                        <div class="lead-detail-row">
                            <dt>Remarks</dt>
                            <dd style="text-align:justify;">{{ $ticket->remarks ?? '—' }}</dd>
                        </div>

                        @hasrole('super-admin')
                            @if($ticket->method)
                                <div class="lead-detail-row">
                                    <dt>Assign Method</dt>
                                    <dd>{{ $ticket->method }}</dd>
                                </div>
                            @endif
                            @if($ticket->method && $ticket->assigner_id && str_contains($ticket->method, 'Manual'))
                                <div class="lead-detail-row">
                                    <dt>Assigned By</dt>
                                    <dd>{{ $ticket->assigner->name }}</dd>
                                </div>
                            @endif
                        @endhasrole

                        <div class="lead-detail-row">
                            <dt>Current User</dt>
                            <dd>{{ $ticket->user_id ? $ticket->user->name : '—' }}</dd>
                        </div>

                        <div class="lead-detail-row">
                            <dt>Current Status</dt>
                            <dd>
                                <span class="badge" style="color:#FFF; font-size:13px; background-color: {{ Config::get('constants.status_colors.' . $ticket->status->slug) }};">
                                    {{ $ticket->status->name }}
                                </span>
                            </dd>
                        </div>

                        @hasrole('super-admin')
                            <div class="lead-detail-row">
                                <dt>Created</dt>
                                <dd class="text-muted">{{ date('d/m/Y h:i A', strtotime($ticket->created_at)) }}</dd>
                            </div>
                        @endhasrole

                        <div class="lead-detail-row">
                            <dt>Last Updated</dt>
                            <dd class="text-muted">{{ date('d/m/Y h:i A', strtotime($ticket->updated_at)) }}</dd>
                        </div>

                        <div class="lead-detail-row lead-detail-row--top">
                            <dt>Extra Data</dt>
                            <dd>
                                @if($ticket->extra_data)
                                    <ul class="mb-0 pl-3">
                                        @foreach($ticket->extra_data as $key => $value)
                                            <li><strong>{{ $key }}</strong>: {{ is_array($value) ? implode(', ', $value) : $value }}</li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-muted"><i>No extra data available.</i></span>
                                @endif
                            </dd>
                        </div>

                        </dl>

                        @hasanyrole('admin|super-admin|sale|tele-sale')
                            <div class="d-flex justify-content-end p-3 border-top">
                                <button type="submit" class="btn btn-success btn-sm" id="basic-save-btn">
                                    <i class="fas fa-save mr-1"></i> Save Changes
                                </button>
                            </div>
                        @endhasanyrole

                    </form>
                </div>
            </div>
        </div>

        {{-- Booking details --}}
        @if($booking)
            <div class="col-6">

                {{-- Booking info --}}
                <div class="card shadow-sm">
                    <div class="card-header" style="background-color: #2d3955;">
                        <h3 class="card-title text-white mb-0">
                            <i class="fas fa-calendar-check mr-2"></i> Booking Details
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered table-sm mb-0">
                            <tr>
                                <th style="width:30%;">Project</th>
                                <td>{{ $booking->project_name ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Unit</th>
                                <td>{{ $booking->unit_number ?? '-' }}</td>
                            </tr>
                            <tr>
                                <th>Price (AED)</th>
                                <td>{{ $booking->price ?? '0' }}</td>
                            </tr>
                            <tr>
                                <th>Developer</th>
                                <td>{{ $booking->developer_name ?? '-' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                {{-- Invoice --}}
                @hasanyrole('admin|super-admin|sales-manager|accountant')
                <div class="card shadow-sm">
                    <div class="card-header" style="background-color: #2d3955;">
                        <h3 class="card-title text-white mb-0">
                            <i class="fas fa-file-invoice mr-2"></i> Invoice
                        </h3>
                    </div>
                    <div class="card-body">
                        @if($ticket->invoice !== null)
                            <a href="{{ route('tickets.download', ['type' => 'invoice', 'id' => $ticket->id]) }}"
                               target="_blank" class="btn btn-primary">
                                <i class="fas fa-eye mr-1"></i> View Invoice
                            </a>
                        @else
                            @hasanyrole('super-admin|accountant')
                            <form method="post" action="{{ route('tickets.makeInvoice', [$ticket->id]) }}"
                                  enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <label for="invoice">Invoice File <sup class="text-danger">*</sup></label>
                                    <input type="file" name="file" class="form-control" id="invoice" required/>
                                </div>
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-save mr-1"></i> Save
                                </button>
                            </form>
                            @endhasanyrole
                        @endif
                    </div>
                </div>
                @endhasanyrole

                {{-- Customer documents --}}
                @hasanyrole('admin|super-admin|sales-manager')
                <div class="card shadow-sm">
                    <div class="card-header" style="background-color: #2d3955;">
                        <h3 class="card-title text-white mb-0">
                            <i class="fas fa-passport mr-2"></i> Customer Documents
                        </h3>
                    </div>
                    <div class="card-body">
                        @if($ticket->passport !== null)
                            <a href="{{ route('tickets.download', ['type' => 'passport', 'id' => $ticket->id]) }}"
                               target="_blank" class="btn btn-primary mr-2">
                                <i class="fas fa-eye mr-1"></i> View Passport
                            </a>
                            <a href="{{ route('tickets.download', ['type' => 'res_form', 'id' => $ticket->id]) }}"
                               target="_blank" class="btn btn-info">
                                <i class="fas fa-eye mr-1"></i> View Reservation Form
                            </a>
                        @endif
                        @if(!$ticket->passport || $ticket->status->slug === 'rejected')
                            <form method="post" action="{{ route('tickets.attachPassport', [$ticket->id]) }}"
                                  enctype="multipart/form-data" class="mt-3">
                                @csrf
                                <div class="form-group">
                                    <label for="passport">
                                        Passport File <sup class="text-danger">*</sup>
                                        <small class="text-danger ml-1">(PNG, JPG, BMP, PDF — max 2MB)</small>
                                    </label>
                                    <input type="file" name="passport" class="form-control" id="passport" required/>
                                </div>
                                <div class="form-group">
                                    <label for="res-form">
                                        Reservation Form <sup class="text-danger">*</sup>
                                        <small class="text-danger ml-1">(PNG, JPG, BMP, PDF — max 2MB)</small>
                                    </label>
                                    <input type="file" name="res-form" class="form-control" id="res-form" required/>
                                </div>
                                <button type="submit" class="btn btn-success btn-sm">
                                    <i class="fas fa-save mr-1"></i> Save
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                @endhasanyrole

            </div>
        @endif

    </div>

    {{-- ===================== LEAD PATH ===================== --}}
    @can('show-path')
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header" style="background-color: #2d3955;">
                        <h3 class="card-title text-white mb-0">
                            <i class="fas fa-history mr-2"></i> Lead Path
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            @foreach($ticketPaths as $key => $path)
                                <div class="time-label">
                                    <span class="bg-info">{{ date('d/m/Y', strtotime($key)) }}</span>
                                </div>
                                @foreach($path as $date => $pathDetails)
                                    <div>
                                        <i class="{{ Config::get('constants.status_icons.' . $pathDetails->nextStatus->slug) }}"
                                           style="background-color: {{ Config::get('constants.status_colors.' . $pathDetails->nextStatus->slug) }}; color: white;"></i>
                                        <div class="timeline-item">
                                            <span class="time" style="font-size: 16px;">
                                                <i class="fas fa-clock"></i> {{ $pathDetails->created_at->format('h:i:s A') }}
                                            </span>
                                            <h3 class="timeline-header">
                                                @hasanyrole('super-admin|sales-manager')
                                                    <a target="_blank"
                                                       href="{{ $pathDetails->next_user ? route('users.edit', $pathDetails->next_user) : '#' }}">
                                                        {{ $pathDetails->nextUser->name ?? '-' }}
                                                    </a>
                                                @else
                                                    <b class="text-primary">{{ $pathDetails->nextUser ? $pathDetails->nextUser->name : '-' }}</b>
                                                @endhasanyrole
                                            </h3>
                                            <div class="timeline-body">
                                                <ul>
                                                    @if($pathDetails->show_prev_status_block)
                                                        <li>
                                                            <b>Previous Status:</b>
                                                            @if(isset($pathDetails->prevStatus))
                                                                <span class="badge"
                                                                      style="color:#FFF; background-color: {{ Config::get('constants.status_colors.' . $pathDetails->prevStatus->slug) }}">
                                                                    {{ $pathDetails->prevStatus->name }}
                                                                </span>
                                                            @else
                                                                —
                                                            @endif
                                                        </li>
                                                    @endif
                                                    <li>
                                                        <b>Current Status:</b>
                                                        @if(isset($pathDetails->nextStatus))
                                                            <span class="badge"
                                                                  style="color:#FFF; background-color: {{ Config::get('constants.status_colors.' . $pathDetails->nextStatus->slug) }}">
                                                                {{ $pathDetails->nextStatus->name }}
                                                            </span>
                                                        @else
                                                            —
                                                        @endif
                                                    </li>
                                                    @if($pathDetails->nextStatus->slug === 'meeting' && $pathDetails->meeting != null)
                                                        <li>
                                                            <b>Meeting Start:</b> {{ date('d/m/Y h:i A', strtotime($pathDetails->meeting->started_at)) }}
                                                            &nbsp;<b>End:</b> {{ date('d/m/Y h:i A', strtotime($pathDetails->meeting->ended_at)) }}
                                                        </li>
                                                    @endif
                                                    @if($pathDetails->nextStatus->slug === 'follow-up' && $pathDetails->reminder_at != null)
                                                        <li>
                                                            <b>Reminder at:</b> {{ date('d/m/Y h:i A', strtotime($pathDetails->reminder_at)) }}
                                                        </li>
                                                    @endif
                                                    @hasrole('super-admin|sales-manager')
                                                        <li>
                                                            <b>Previous User:</b>
                                                            <a target="_blank"
                                                               href="{{ $pathDetails->prevUser ? route('users.edit', $pathDetails->prevUser) : '#' }}">
                                                                {{ $pathDetails->prevUser ? $pathDetails->prevUser->name : '-' }}
                                                            </a>
                                                        </li>
                                                    @endhasrole
                                                    <li>
                                                        <b>Comment:</b>
                                                        <p style="text-align: justify;">{{ $pathDetails->comment }}</p>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    {{-- ===================== MOVE FORWARD ===================== --}}
    @if($ticket->status->slug !== 'dead-tele' && $ticket->status->slug !== 'sold')
        @can('change status')
            @if(auth()->user()->hasRole('accountant') && $ticket->status->slug !== 'approved')
                {{-- accountant with non-approved status: nothing to show --}}
            @else($ticket->status)
                @if(($ticket->status->slug === 'booking' || $ticket->status->slug === 'dead'
                    || $ticket->status->slug === 'reviewed' || $ticket->status->slug === 'approved'
                    || $ticket->status->slug === 'booking-tele' || $ticket->status->slug === 'dead-tele'
                    || $ticket->status->slug === 'sold' || $ticket->status->slug === 'pre-approved'
                    || $ticket->status->slug === 'rejected' || $ticket->status->slug === 'duplicated')
                    && (auth()->user()->hasRole('sale') || auth()->user()->hasRole('tele-sale')))

                    <div class="alert alert-danger alert-dismissible fade show mt-3">
                        <h4 class="text-center mb-0">Your role on this lead is over! Thank you.</h4>
                    </div>

                @else
                    <div class="row mt-2">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-header" style="background-color: #2d3955;">
                                    <h3 class="card-title text-white mb-0">
                                        <i class="fas fa-forward mr-2"></i> Move Forward
                                    </h3>
                                </div>
                                <div class="card-body">
                                    <form name="f1" action="{{ route('tickets.moveForward', [$ticket->id]) }}" method="post">
                                        @csrf

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="status">Next Status <sup class="text-danger">*</sup></label>

                                                    @php
                                                        $user = auth()->user();

                                                        $availableStatuses = $statuses->filter(function ($status) use ($user, $ticket) {
                                                            if ($user->hasRole('accountant')) {
                                                                return $status->slug === 'sold';
                                                            }
                                                            if ($user->hasRole('admin')) {
                                                                return $status->slug === 'reviewed';
                                                            }
                                                            if ($user->hasRole('sales-manager')) {
                                                                return in_array($status->slug, ['pre-approved', 'rejected']);
                                                            }
                                                            if ($status->slug === 'approved' && !$user->hasRole('super-admin')) {
                                                                return false;
                                                            }
                                                            if ($status->slug !== 'new' && $status->slug !== 'follow-up' && $status->id === $ticket->status->id) {
                                                                return false;
                                                            }
                                                            if ($status->slug === 'pre-approved' && !$user->hasAnyRole(['super-admin', 'sales-manager'])) {
                                                                return false;
                                                            }
                                                            return true;
                                                        });
                                                    @endphp

                                                    <select name="status" id="status" class="form-control select2" required>
                                                        <option value="-1" disabled selected>Please select…</option>
                                                        @foreach($availableStatuses as $status)
                                                            <option value="{{ $status->id }}" data-slug="{{ $status->slug }}">
                                                                {{ $status->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>

                                                    <p id="reminderPreview" class="form-text text-info mt-1" style="display:none;"></p>
                                                    <p id="meetingPreview"  class="form-text text-info mt-1" style="display:none;"></p>
                                                </div>
                                            </div>

                                            @hasanyrole('super-admin')
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="user">Assign To <sup class="text-danger">*</sup></label>
                                                    <select name="user" id="user" class="form-control select2" required>
                                                        <optgroup label="Sales">
                                                            @foreach($users as $user)
                                                                @if($user->role === 'sale')
                                                                    <option value="{{ $user->id }}"
                                                                        {{ ($ticket->user !== null && $user->id === $ticket->user->id) ? 'selected' : '' }}>
                                                                        {{ $user->name }}
                                                                    </option>
                                                                @endif
                                                            @endforeach
                                                        </optgroup>
                                                        <optgroup label="Tele-Sales">
                                                            @foreach($users as $user)
                                                                @if($user->role === 'tele-sale')
                                                                    <option value="{{ $user->id }}"
                                                                        {{ ($ticket->user !== null && $user->id === $ticket->user->id) ? 'selected' : '' }}>
                                                                        {{ $user->name }}
                                                                    </option>
                                                                @endif
                                                            @endforeach
                                                        </optgroup>
                                                        @if($ticket->status->slug === 'pre-approved')
                                                            <optgroup label="Accountants">
                                                                @foreach($users as $user)
                                                                    @if($user->role === 'accountant')
                                                                        <option value="{{ $user->id }}"
                                                                            {{ ($ticket->user !== null && $user->id === $ticket->user->id) ? 'selected' : '' }}>
                                                                            {{ $user->name }}
                                                                        </option>
                                                                    @endif
                                                                @endforeach
                                                            </optgroup>
                                                        @endif
                                                    </select>
                                                </div>
                                            </div>
                                            @endhasanyrole
                                        </div>

                                        <div class="form-group">
                                            <label for="comment">
                                                Comment
                                                @unlessrole('super-admin')
                                                    <sup class="text-danger">*</sup>
                                                @endunlessrole
                                            </label>
                                            <textarea rows="3" class="form-control" id="comment" name="comment"
                                                      placeholder="Enter comment"
                                                      @unlessrole('super-admin') required @endunlessrole>{{ old('comment') }}</textarea>
                                        </div>

                                        <div class="d-flex align-items-center">
                                            <a href="{{ route('tickets.all') }}" class="btn btn-outline-secondary mr-2">
                                                <i class="fas fa-arrow-left mr-1"></i> Back
                                            </a>
                                            <button type="submit" class="btn btn-primary" id="fwd-submit-btn">
                                                <i class="fas fa-forward mr-1"></i> Move Forward
                                            </button>
                                        </div>

                                        {{-- Modals --}}
                                        <div class="modal fade" id="modal-lg">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h4 class="modal-title">Booking Info</h4>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <label for="client-unit">Unit <sup class="text-danger">*</sup></label>
                                                            <input type="text" class="form-control" id="client-unit" name="client-unit"/>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="client-price">Price <sup class="text-danger">*</sup></label>
                                                            <input type="number" min="0" step="0.1" class="form-control" id="client-price" name="client-price"/>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="client-project">Project <sup class="text-danger">*</sup></label>
                                                            <input type="text" class="form-control" id="client-project" name="client-project"/>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="client-developer">Developer <sup class="text-danger">*</sup></label>
                                                            <input type="text" class="form-control" id="client-developer" name="client-developer"/>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer justify-content-between">
                                                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                                        <button type="button" class="btn btn-primary" id="saveBtn">Save Changes</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal fade" id="modal-lg2">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h4 class="modal-title">Meeting Details</h4>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <label>Date and Time Range:</label>
                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i class="far fa-clock"></i></span>
                                                                </div>
                                                                <input type="text" name="meeting_range"
                                                                       class="form-control float-right"
                                                                       id="reservationtime"/>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer justify-content-between">
                                                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                                        <button type="button" class="btn btn-primary" id="saveBtn2">Save Changes</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal fade" id="modal-lg3">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h4 class="modal-title">Follow-up Reminder</h4>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <label>Reminder Date and Time:</label>
                                                            <div class="input-group date" id="reminder_datetime-div"
                                                                 data-target-input="nearest">
                                                                <input type="text" id="reminder_datetime" name="reminder_datetime"
                                                                       class="form-control datetimepicker-input"
                                                                       data-target="#reminder_datetime-div"/>
                                                                <div class="input-group-append"
                                                                     data-target="#reminder_datetime-div"
                                                                     data-toggle="datetimepicker">
                                                                    <div class="input-group-text">
                                                                        <i class="fa fa-calendar"></i>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer justify-content-between">
                                                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                                                        <button type="button" class="btn btn-primary" id="saveBtn3">OK</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        @endcan
    @endif

    <style>
        .lead-detail-list { margin: 0; }
        .lead-detail-row {
            display: flex;
            align-items: center;
            padding: 7px 16px;
            border-bottom: 1px solid #f0f0f0;
            gap: 12px;
        }
        .lead-detail-row--top { align-items: flex-start; }
        .lead-detail-row dt {
            flex: 0 0 160px;
            font-weight: 600;
            font-size: 0.82rem;
            color: #495057;
            margin: 0;
        }
        .lead-detail-row dd {
            flex: 1;
            margin: 0;
            font-size: 0.875rem;
            min-width: 0;
        }
        @media (max-width: 575px) {
            .lead-detail-row { flex-direction: column; align-items: flex-start; gap: 4px; }
            .lead-detail-row dt { flex: none; }
        }
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

        $(function () {
            $('.select2').select2({ width: '100%' });

            $('input[name="meeting_range"]').daterangepicker({
                timePicker: true,
                startDate: moment().startOf('hour'),
                endDate: moment().startOf('hour').add(32, 'hour'),
                locale: { format: 'M/DD hh:mm A' }
            });
        });

        $('#reminder_datetime-div').datetimepicker({
            icons: {
                time: 'far fa-clock',
                date: 'far fa-calendar',
                up:   'fas fa-arrow-up',
                down: 'fas fa-arrow-down'
            }
        });

        // Basic details save spinner
        const basicSaveBtn = document.getElementById('basic-save-btn');
        if (basicSaveBtn) {
            basicSaveBtn.closest('form').addEventListener('submit', function () {
                basicSaveBtn.disabled = true;
                basicSaveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';
            });
        }

        // Move Forward
        function resetPreviews() {
            document.getElementById('reminderPreview').style.display = 'none';
            document.getElementById('reminderPreview').textContent = '';
            document.getElementById('meetingPreview').style.display = 'none';
            document.getElementById('meetingPreview').textContent = '';
            const bookingPreview = document.getElementById('bookingPreview');
            if (bookingPreview) {
                bookingPreview.style.display = 'none';
                bookingPreview.textContent = '';
            }
        }

        const statusModalMap = {
            'booking':   '#modal-lg',
            'meeting':   '#modal-lg2',
            'follow-up': '#modal-lg3',
        };

        const allModals = ['#modal-lg', '#modal-lg2', '#modal-lg3'];

        $('#status').on('change', function () {
            const slug = $(this).find(':selected').data('slug');
            resetPreviews();
            allModals.forEach(id => $(id).modal('hide'));
            const modalId = statusModalMap[slug];
            if (modalId) $(modalId).modal('show');
        });

        const saveBtn = document.getElementById('saveBtn');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                $('#modal-lg').modal('hide');
            });
        }

        const saveBtn2 = document.getElementById('saveBtn2');
        if (saveBtn2) {
            saveBtn2.addEventListener('click', () => {
                resetPreviews();
                const input   = document.getElementById('reservationtime');
                const preview = document.getElementById('meetingPreview');
                const value   = input.value.trim();
                if (!value) {
                    Toast.fire({ icon: 'warning', title: 'Please select meeting date and time range.' });
                    return;
                }
                preview.style.display = 'block';
                preview.textContent = 'Meeting: ' + value;
                $('#modal-lg2').modal('hide');
            });
        }

        const saveBtn3 = document.getElementById('saveBtn3');
        if (saveBtn3) {
            saveBtn3.addEventListener('click', () => {
                resetPreviews();
                const input   = document.getElementById('reminder_datetime');
                const preview = document.getElementById('reminderPreview');
                const value   = input.value.trim();
                if (!value) {
                    Toast.fire({ icon: 'warning', title: 'Please select reminder date & time.' });
                    return;
                }
                preview.style.display = 'block';
                preview.textContent = 'Reminder: ' + value;
                $('#modal-lg3').modal('hide');
            });
        }

        // Move Forward submit spinner
        const fwdSubmitBtn = document.getElementById('fwd-submit-btn');
        if (fwdSubmitBtn) {
            fwdSubmitBtn.closest('form').addEventListener('submit', function () {
                fwdSubmitBtn.disabled = true;
                fwdSubmitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';
            });
        }
    </script>
@endsection
