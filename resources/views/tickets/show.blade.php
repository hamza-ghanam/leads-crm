@extends('layouts.app')

@section('title')
    Lead Details
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ URL::to('/tickets/all') }}">Leads list</a></li>
    <li class="breadcrumb-item">Show ticket details</li>
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul style="list-style: none;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-{{$booking ? 6 : 12}}">
            <div class="card">
                <!-- /.card-header -->
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <h3>Basic details</h3>
                            <form name="f1" action="{{ route('tickets.update', $ticket->id) }}" method="post">
                                @csrf
                                @method('PUT')
                                <table class="table">
                                    <tr>
                                        <th style="width: 30%;">Number</th>
                                        <td>
                                            @hasanyrole('super-admin')
                                            <input type="text" class="form-control" id="number" name="number"
                                                   placeholder="Enter Number"
                                                   value="{{ isset($ticket->number) ? $ticket->number : '' }}"/>
                                            @else
                                                {{isset($ticket->number) ? $ticket->number : '-'}}
                                                @endhasanyrole
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>AD ID</th>
                                        <td>
                                            @hasanyrole('super-admin')
                                            <input type="text" class="form-control" id="ad_id" name="ad_id"
                                                   placeholder="Enter Ad ID"
                                                   value="{{ isset($ticket->ad_id) ? $ticket->ad_id : '' }}"/>
                                            @else
                                                {{isset($ticket->ad_id) ? $ticket->ad_id : '-'}}
                                                @endhasanyrole
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>AD Name</th>
                                        <td>
                                            @hasanyrole('super-admin')
                                            <input type="text" class="form-control" id="ad_name" name="ad_name"
                                                   placeholder="Enter Ad Name"
                                                   value="{{ isset($ticket->ad_name) ? $ticket->ad_name : '' }}"/>
                                            @else
                                                {{isset($ticket->ad_name) ? $ticket->ad_name : '-'}}
                                                @endhasanyrole
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>AD Set ID</th>
                                        <td>
                                            @hasanyrole('super-admin')
                                            <input type="text" class="form-control" id="adset_id" name="adset_id"
                                                   placeholder="Enter Adset ID"
                                                   value="{{ isset($ticket->adset_id) ? $ticket->adset_id : '' }}"/>
                                            @else
                                                {{isset($ticket->adset_id) ? $ticket->adset_id : '-'}}
                                                @endhasanyrole
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>AD Set Name</th>
                                        <td>
                                            @hasanyrole('super-admin')
                                            <input type="text" class="form-control" id="adset_name" name="adset_name"
                                                   placeholder="Enter Adset Name"
                                                   value="{{ isset($ticket->adset_name) ? $ticket->adset_name : '' }}"/>
                                            @else
                                                {{isset($ticket->adset_name) ? $ticket->adset_name : '-'}}
                                                @endhasanyrole
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Campaign ID</th>
                                        <td>
                                            @hasanyrole('super-admin')
                                            <input type="text" class="form-control" id="campaign_id" name="campaign_id"
                                                   placeholder="Enter Campaign ID"
                                                   value="{{ isset($ticket->campaign_id) ? $ticket->campaign_id : '' }}"/>
                                            @else
                                                {{isset($ticket->campaign_id) ? $ticket->campaign_id : '-'}}
                                                @endhasanyrole
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Campaign Name</th>
                                        <td>
                                            @hasanyrole('super-admin')
                                            <input type="text" class="form-control" id="campaign_name"
                                                   name="campaign_name"
                                                   placeholder="Enter Campaign Name"
                                                   value="{{ isset($ticket->campaign_name) ? $ticket->campaign_name : '' }}"/>
                                            @else
                                                {{isset($ticket->campaign_name) ? $ticket->campaign_name : '-'}}
                                                @endhasanyrole
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Form ID</th>
                                        <td>
                                            @hasanyrole('super-admin')
                                            <input type="text" class="form-control" id="form_id" name="form_id"
                                                   placeholder="Enter Form ID"
                                                   value="{{ isset($ticket->form_id) ? $ticket->form_id : '' }}"/>
                                            @else
                                                {{isset($ticket->form_id) ? $ticket->form_id : '-'}}
                                                @endhasanyrole
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Form Name</th>
                                        <td>
                                            @hasanyrole('super-admin')
                                            <input type="text" class="form-control" id="form_name" name="form_name"
                                                   placeholder="Enter Form Name"
                                                   value="{{ isset($ticket->form_name) ? $ticket->form_name : '' }}"/>
                                            @else
                                                {{isset($ticket->form_name) ? $ticket->form_name : '-'}}
                                                @endhasanyrole
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Is Organic</th>
                                        <td>
                                            @hasanyrole('super-admin')
                                            <div class="form-check">
                                                <input class="form-check-input"
                                                       {{ $ticket->is_organic == 1 ? 'checked' : '' }}  type="checkbox"
                                                       id="is_organic" name="is_organic">
                                            </div>
                                            @else
                                                {{isset($ticket->is_organic) ? ($ticket->is_organic != 0 ? 'Yes' : 'No') : '-'}}
                                                @endhasanyrole
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Full Name</th>
                                        <td>
                                            @hasanyrole('admin|super-admin|sale|tele-sale')
                                            <input type="text" class="form-control" id="full_name" name="full_name"
                                                   placeholder="Enter Full Name"
                                                   value="{{ isset($ticket->full_name) ? $ticket->full_name : '' }}"
                                                   required>
                                            @else
                                                {{isset($ticket->full_name) ? $ticket->full_name : '-'}}
                                                @endhasanyrole
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Phone Number</th>
                                        <td>
                                            @hasanyrole('admin|super-admin')
                                            <input type="text" class="form-control" id="phone_number"
                                                   name="phone_number"
                                                   placeholder="Enter Phone Number"
                                                   value="{{ isset($ticket->phone_number) ? $ticket->phone_number : '' }}"
                                                   required>
                                            @else
                                                {{isset($ticket->phone_number) ? $ticket->phone_number : '-'}}
                                                @endhasanyrole
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Email</th>
                                        <td>
                                            @hasanyrole('admin|super-admin')
                                            <input type="text" class="form-control" id="email" name="email"
                                                   placeholder="Enter Email Address"
                                                   value="{{ isset($ticket->email) ? $ticket->email : '' }}">
                                            @else
                                                {{isset($ticket->email) ? $ticket->email : '-'}}
                                                @endhasanyrole
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Job Title</th>
                                        <td>
                                            @hasanyrole('super-admin')
                                            <input type="text" class="form-control" id="job_title" name="job_title"
                                                   placeholder="Enter Job title"
                                                   value="{{ isset($ticket->job_title) ? $ticket->job_title : '' }}"/>
                                            @else
                                                {{isset($ticket->job_title) ? $ticket->job_title : '-'}}
                                                @endhasanyrole
                                        </td>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Source</th>
                                        <td>
                                            @hasanyrole('super-admin')
                                            <select name="source" id="source" class="form-control" required>
                                                @foreach($sources as $source)
                                                    <option
                                                        {{($ticket->source_id AND $ticket->source_id == $source->id) ? 'selected' : ''}} value="{{$source->id}}">{{$source->name}}</option>
                                                @endforeach
                                            </select>
                                            @else
                                                {{isset($ticket->source_id) ? $ticket->source->name : '-'}}
                                                @endhasanyrole
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Preferred time to call</th>
                                        <td> {{ $ticket->preferred_time ?? '-' }} </td>
                                    </tr>
                                    <tr>
                                        <th>Remarks</th>
                                        <td style="text-align: justify;"> {{ $ticket->remarks ?? '-' }} </td>
                                    </tr>
                                    @hasrole('super-admin')
                                    @if ($ticket->method)
                                        <tr>
                                            <th>Assign Method</th>
                                            <td>
                                                {{ $ticket->method }}
                                            </td>
                                        </tr>
                                    @endif
                                    @if ($ticket->method AND $ticket->assigner_id AND str_contains($ticket->method, 'Manual'))
                                        <tr>
                                            <th>Assigned By</th>
                                            <td>
                                                {{ $ticket->assigner->name }}
                                            </td>
                                        </tr>
                                    @endif
                                    @endhasrole
                                    <tr>
                                        <th>Current User</th>
                                        <td>{{isset($ticket->user_id) ? $ticket->user->name : '-'}}</td>
                                    </tr>
                                    <tr>
                                        <th>Current Status</th>
                                        <td>
                                            <span class="badge"
                                                  style="color: #FFF; background-color: {{ Config::get('constants.status_colors.' . $ticket->status->slug) }}">
                                            {{isset($ticket->status_id) ? $ticket->status->name : '-'}}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Creation date</th>
                                        <td>{{ date('d/m/Y h:i A', strtotime($ticket['created_at'])) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Last update date</th>
                                        <td>{{ date('d/m/Y h:i A', strtotime($ticket['updated_at'])) }}</td>
                                    </tr>
                                    <tr>
                                        <th>Extra data</th>
                                        <td>
                                            @if($ticket->extra_data)
                                                <ul>
                                                    @foreach($ticket->extra_data as $key => $value)
                                                        <li>
                                                            <strong>{{ $key }}</strong>: {{ is_array($value) ? implode(', ', $value) : $value }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <i>No extra data available.</i>
                                            @endif
                                        </td>
                                    </tr>
                                    @hasanyrole('admin|super-admin|sale|tele-sale')
                                    <tr>
                                        <td colspan="2">
                                            <button type="submit" class="btn btn-success">Save changes</button>
                                        </td>
                                    </tr>
                                    @endhasanyrole
                                </table>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>

        @if($booking)
            <div class="col-{{$booking ? 6 : 12}}">
                <div class="card">
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <h3>Booking details</h3>
                                <table class="table">
                                    <tr>
                                        <th style="width: 20%;">Project</th>
                                        <td>{{isset($booking->project_name) ? $booking->project_name : '-'}}</td>
                                    </tr>
                                    <tr>
                                        <th style="width: 20%;">Unit</th>
                                        <td>{{isset($booking->unit_number) ? $booking->unit_number : '-'}}</td>
                                    </tr>
                                    <tr>
                                        <th style="width: 20%;">Price (AED)</th>
                                        <td>{{isset($booking->price) ? $booking->price : '0'}}</td>
                                    </tr>
                                    <tr>
                                        <th style="width: 20%;">Developer</th>
                                        <td>{{isset($booking->developer_name) ? $booking->developer_name : '-'}}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <hr/>
                        <div class="row">
                            <div class="col-12">
                                @if($ticket->invoice !== null)
                                    @hasanyrole('admin|super-admin|sales-manager|accountant')
                                    <h3>Invoice details</h3>
                                    <a href="{{ route('tickets.download', ['type' => 'invoice', 'id' => $ticket->id]) }}"
                                       target="_blank"
                                       class="btn btn-primary">View
                                        Invoice File..</a>
                                    @endhasanyrole
                                @else
                                    @hasanyrole('super-admin|accountant')
                                    <h3>Attach Invoice</h3>
                                    <form method="post" name="f123"
                                          action="{{ route('tickets.makeInvoice', [$ticket->id]) }}"
                                          enctype="multipart/form-data">
                                        @csrf
                                        <label for="invoice">Invoice File</label>
                                        <input type="file" name="file" class="form-control" id="invoice" required>
                                        <br/>
                                        <input type="submit" class="btn btn-success" value="Save">
                                    </form>
                                    @endhasanyrole
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @hasanyrole('admin|super-admin|sales-manager')
                <div class="card">
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12">
                                <h3>Customer details</h3>
                                <hr/>
                                @if($ticket->passport !== null)
                                    <a href="{{ route('tickets.download', ['type' => 'passport', 'id' => $ticket->id]) }}"
                                       target="_blank"
                                       class="btn btn-primary">View Passport File..</a>
                                    <a href="{{ route('tickets.download', ['type' => 'res_form', 'id' => $ticket->id]) }}"
                                       target="_blank"
                                       class="btn btn-info ml-3">View Reservation Form..</a>
                                @endif
                                @if(!$ticket->passport OR $ticket->status->slug === 'rejected')
                                    <form method="post" name="f1234"
                                          action="{{ route('tickets.attachPassport', [$ticket->id]) }}"
                                          enctype="multipart/form-data" class="mt-3">
                                        @csrf
                                        <label for="passport">Passport File</label>
                                        <small class="text-danger text-bold"> (PNG, JPG, BMP and PDF only. Max size: 2
                                            MB)
                                        </small>
                                        <input type="file" name="passport" class="form-control mb-3" id="passport"
                                               required>
                                        <label for="res-form" class="mt-2">Reservation Form</label>
                                        <small class="text-danger text-bold"> (PNG, JPG, BMP and PDF only. Max size: 2
                                            MB)
                                        </small>
                                        <input type="file" name="res-form" class="form-control" id="res-form" required>
                                        <br/>
                                        <input type="submit" class="btn btn-success" value="Save">
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                @endhasanyrole
            </div>
        @endif

    </div>

    @can('show-path')
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <ul class="list-group">
                            <h3 class="mb-4">Lead Path</h3>
                            <!-- The time line -->
                            <div class="timeline">
                                <!-- timeline time label -->
                                @foreach($ticket->paths as $key => $path)
                                    <div class="time-label">
                                        <span class="bg-info">{{date('d/m/Y', strtotime($key))}}</span>
                                    </div>
                                    <!-- /.timeline-label -->
                                    @foreach($path as $key2 => $pathDetails)
                                        <!-- timeline item -->
                                        <div>
                                            <i class="{{ Config::get('constants.status_icons.' . $pathDetails->nextStatus->slug) }}"
                                               style="background-color: {{ Config::get('constants.status_colors.' . $pathDetails->nextStatus->slug) }}; color: white;"></i>
                                            <div class="timeline-item">
                                                <span class="time" style="font-size: 16px;"><i class="fas fa-clock"></i> {{$pathDetails->created_at->format('h:i:s A')}}</span>
                                                <h3 class="timeline-header">
                                                    @hasanyrole('super-admin|sales-manager')
                                                    <a target="_blank"
                                                       href="{{ ($pathDetails->next_user) ? route('users.edit', $pathDetails->next_user) : '#' }}">
                                                        {{ isset($pathDetails->nextUser) ? $pathDetails->nextUser->name : '-' }}
                                                    </a>
                                                    @else
                                                        <b class="text-primary">{{ $pathDetails->nextUser ? $pathDetails->nextUser->name : '-' }}</b>
                                                        @endhasanyrole
                                                </h3>

                                                <div class="timeline-body">
                                                    <ul>
                                                        <li>
                                                            <b>Previous Status:</b>
                                                            @if(isset($pathDetails->prevStatus))
                                                                <span
                                                                    style="color: #FFF; background-color: {{ Config::get('constants.status_colors.' . $pathDetails->prevStatus->slug) }}"
                                                                    class="badge">
                                                                {{ $pathDetails->prevStatus->name }}
                                                                </span>
                                                            @else
                                                                -
                                                            @endif
                                                        </li>
                                                        <li>
                                                            <b>Current status:</b>
                                                            @if(isset($pathDetails->nextStatus))
                                                                <span
                                                                    style="color: #FFF; background-color: {{ Config::get('constants.status_colors.' . $pathDetails->nextStatus->slug) }}"
                                                                    class="badge">
                                                                {{ $pathDetails->nextStatus->name }}
                                                                </span>
                                                            @else
                                                                -
                                                            @endif
                                                        </li>
                                                        @if($pathDetails->nextStatus->slug === 'meeting' AND $pathDetails->meeting != null)
                                                            <li>
                                                                <b>Meeting Start time:</b>
                                                                {{ date('d/m/Y h:i A', strtotime($pathDetails->meeting->started_at)) }}
                                                                <b> & Meeting End time:</b>
                                                                {{ date('d/m/Y h:i A', strtotime($pathDetails->meeting->ended_at)) }}
                                                            </li>
                                                        @endif
                                                        @hasrole('super-admin|sales-manager')
                                                        <li>
                                                            <b>Previous User:</b>
                                                            <a target="_blank"
                                                               href="{{ $pathDetails->prevUser ? route('users.edit', $pathDetails->prevUser) : '#' }}">
                                                                {{$pathDetails->prevUser ? $pathDetails->prevUser->name : '-'}}
                                                            </a>
                                                        </li>
                                                        @endhasrole
                                                        <li><b>Comment:</b><br>
                                                            <p style="text-align: justify">
                                                                {{$pathDetails->comment}}
                                                            </p>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endcan

    @if($ticket->status->slug !== 'dead-tele' AND $ticket->status->slug !== 'sold')
        @can('change status')
            @if(auth()->user()->hasRole('accountant') AND $ticket->status->slug !== 'approved')
                <br/>
            @else($ticket->status)
                @if(($ticket->status->slug === 'booking' OR $ticket->status->slug === 'dead' OR $ticket->status->slug === 'reviewed'
                    OR $ticket->status->slug === 'approved' OR $ticket->status->slug === 'booking-tele' OR $ticket->status->slug === 'dead-tele'
                    OR $ticket->status->slug === 'sold' OR $ticket->status->slug === 'pre-approved' OR $ticket->status->slug === 'rejected'
                    OR $ticket->status->slug === 'duplicated') AND (auth()->user()->hasRole('sale') OR (auth()->user()->hasRole('tele-sale'))))

                    <div class="alert alert-dismissible alert-danger">
                        <h4 style="text-align: center;">Your role on this lead is over! Thank you.</h4>
                    </div>

                @else
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h3>Move Forward</h3>
                                    <form name="f1" action="{{ route('tickets.moveForward', [$ticket->id]) }}"
                                          method="post"
                                          style="width: 50%;">
                                        Move ticket to the next status:
                                        @csrf
                                        <fieldset>
                                            <div class="form-group">
                                                <label for="status">Next status</label><sup>*</sup>
                                                @hasrole('accountant')
                                                <select name="status" id="status" class="form-control"
                                                        required="required">
                                                    <option value="-1" disabled selected>Please select..</option>
                                                    @foreach($statuses as $status)
                                                        @if($status->slug === 'sold')
                                                            <option value="{{$status->id}}">{{$status->name}}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                                @else
                                                    @hasrole('admin')
                                                    <select name="status" id="status" class="form-control"
                                                            required="required">
                                                        <option value="-1" disabled selected>Please select..</option>
                                                        @foreach($statuses as $status)
                                                            @if($status->slug === 'reviewed')
                                                                <option
                                                                    value="{{$status->id}}">{{$status->name}}</option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                    @else
                                                        @hasrole('sales-manager')
                                                        <select name="status" id="status" class="form-control"
                                                                required="required">
                                                            <option value="-1" disabled selected>Please select..
                                                            </option>
                                                            @foreach($statuses as $status)
                                                                @if($status->slug === 'pre-approved' OR $status->slug === 'rejected')
                                                                    <option
                                                                        value="{{$status->id}}">{{$status->name}}</option>
                                                                @endif
                                                            @endforeach
                                                        </select>
                                                        @else
                                                            <select name="status" id="status" class="form-control"
                                                                    required="required">
                                                                <option value="-1" disabled selected>Please select..
                                                                </option>
                                                                @foreach($statuses as $status)
                                                                    @if($status->slug === 'approved')
                                                                        @unlessrole('super-admin')
                                                                        @continue
                                                                        @endunlessrole
                                                                    @endif
                                                                    @if($status->slug != 'new' AND $status->id == $ticket->status->id AND $status->slug !== 'follow-up')
                                                                        @continue
                                                                    @endif
                                                                    @if($status->slug === 'pre-approved' AND !auth()->user()->hasAnyRole(['super-admin', 'sales-manager']))
                                                                        @continue
                                                                    @endif
                                                                    <option
                                                                        value="{{$status->id}}">{{$status->name}}</option>
                                                                @endforeach
                                                            </select>
                                                            @endhasrole
                                                            @endhasrole
                                                            @endhasrole
                                            </div>
                                            <div class="form-group">
                                                <label for="comment">Comment</label><sup>*</sup>
                                                <textarea cols="2" rows="3" class="form-control" id="comment"
                                                          name="comment"
                                                          placeholder="Enter Comment"
                                                          required>{{ old('comment') }}</textarea>
                                            </div>
                                            @hasanyrole('super-admin')
                                            <div class="form-group">
                                                <label for="user">Assign to</label><sup>*</sup>
                                                <select name="user" id="user" class="form-control" required>
                                                    <optgroup label="Sales">
                                                        @foreach($users as $user)
                                                            @if ($user->role === 'sale')
                                                                <option value="{{$user->id}}"
                                                                    {{ ($ticket->user !== null && $user->id === $ticket->user->id) ? 'selected' : '' }}>
                                                                    {{$user->name}}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </optgroup>
                                                    <optgroup label="Tele-Sales">
                                                        @foreach($users as $user)
                                                            @if ($user->role === 'tele-sale')
                                                                <option value="{{$user->id}}"
                                                                    {{ ($ticket->user !== null && $user->id === $ticket->user->id) ? 'selected' : '' }}>
                                                                    {{$user->name}}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </optgroup>
                                                    @if($ticket->status->slug == 'pre-approved')
                                                        <optgroup label="Accountants">
                                                            @foreach($users as $user)
                                                                @if ($user->role === 'accountant')
                                                                    <option value="{{$user->id}}"
                                                                        {{ ($ticket->user !== null && $user->id === $ticket->user->id) ? 'selected' : '' }}>
                                                                        {{$user->name}}
                                                                    </option>
                                                                @endif
                                                            @endforeach
                                                        </optgroup>
                                                    @endif
                                                </select>
                                            </div>
                                            @endhasanyrole
                                            <button type="submit" class="btn btn-primary">Save</button>
                                            <a class="btn btn-dark ml-3" href="{{route('tickets.all')}}">Cancel</a>
                                        </fieldset>
                                        <div class="modal fade" id="modal-lg">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h4 class="modal-title">Booking info</h4>
                                                        <button type="button" class="close" data-dismiss="modal"
                                                                aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <label for="client-unit">Unit</label><sup>*</sup>
                                                            <input type="text" class="form-control" id="client-unit"
                                                                   name="client-unit"/>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="client-unit">Price</label><sup>*</sup>
                                                            <input type="number" min="0" step="0.1" class="form-control"
                                                                   id="client-price"
                                                                   name="client-price"/>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="client-project">Project</label><sup>*</sup>
                                                            <input type="text" class="form-control" id="client-project"
                                                                   name="client-project"/>
                                                        </div>
                                                        <div class="form-group">
                                                            <label for="client-developer">Developer</label><sup>*</sup>
                                                            <input type="text" class="form-control"
                                                                   id="client-developer"
                                                                   name="client-developer"/>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer justify-content-between">
                                                        <button type="button" class="btn btn-default"
                                                                data-dismiss="modal">
                                                            Close
                                                        </button>
                                                        <button type="button" class="btn btn-primary" id="saveBtn">Save
                                                            changes
                                                        </button>
                                                    </div>
                                                </div>
                                                <!-- /.modal-content -->
                                            </div>
                                            <!-- /.modal-dialog -->
                                        </div>

                                        <div class="modal fade" id="modal-lg2">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h4 class="modal-title">Meeting Details</h4>
                                                        <button type="button" class="close" data-dismiss="modal"
                                                                aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <label>Date and time range:</label>

                                                            <div class="input-group">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text"><i
                                                                            class="far fa-clock"></i></span>
                                                                </div>
                                                                <input type="text" name="datetimes"
                                                                       class="form-control float-right"
                                                                       id="reservationtime">
                                                            </div>
                                                            <!-- /.input group -->
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer justify-content-between">
                                                        <button type="button" class="btn btn-default"
                                                                data-dismiss="modal">
                                                            Close
                                                        </button>
                                                        <button type="button" class="btn btn-primary" id="saveBtn2">Save
                                                            changes
                                                        </button>
                                                    </div>
                                                </div>
                                                <!-- /.modal-content -->
                                            </div>
                                            <!-- /.modal-dialog -->
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
@endsection

@section('script')
    <script>
        $(function () {
            $('input[name="datetimes"]').daterangepicker({
                timePicker: true,
                startDate: moment().startOf('hour'),
                endDate: moment().startOf('hour').add(32, 'hour'),
                locale: {
                    format: 'M/DD hh:mm A'
                }
            });
        });

        var modal = document.getElementById("modal-lg");
        var modal2 = document.getElementById("modal-lg2");

        document.querySelector("button").addEventListener("click", function (e) {
            modal.classList.remove("hidden");
            modal2.classList.remove("hidden");
        });

        const areaSelect = document.querySelector(`[id="status"]`);
        areaSelect.addEventListener(`change`, (e) => {
            const select = e.target;
            const desc = select.selectedOptions[0].text;

            if (desc.toLocaleLowerCase().includes('book')) {
                $('#modal-lg').modal('show');
            } else {
                if (desc.toLocaleLowerCase().includes('meet')) {
                    $('#modal-lg2').modal('show');
                } else {
                    $('#modal-lg2').modal('hide');
                }

                $('#modal-lg').modal('hide');
            }
        });

        const saveBtn = document.querySelector(`[id="saveBtn"]`);
        saveBtn.addEventListener(`click`, () => {
            $("#modal-lg").modal('hide');
        });

        const saveBtn2 = document.querySelector(`[id="saveBtn2"]`);
        saveBtn2.addEventListener(`click`, () => {
            $("#modal-lg2").modal('hide');
        });
    </script>
@endsection
