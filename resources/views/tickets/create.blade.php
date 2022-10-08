@extends('layouts.app')

@section('title')
    Add new ticket
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ URL::to('/tickets') }}">Leads list</a></li>
    <li class="breadcrumb-item">Add new ticket</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <!-- /.card-header -->
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul style="list-style: none;">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form name="f1" action="{{ route('tickets.store') }}" method="post" style="width: 50%;">
                        @csrf
                        <fieldset>
                            <div class="form-group">
                                <label for="campaign_name">Campaign Name</label><sup>*</sup>
                                <input type="text" class="form-control" id="campaign_name" name="campaign_name"
                                       placeholder="Enter Campaign Name"
                                       value="{{ old('campaign_name') }}" required>
                            </div>
                            <div class="form-group">
                                <label for="full_name">Full Name</label><sup>*</sup>
                                <input type="text" class="form-control" id="full_name" name="full_name"
                                       placeholder="Enter Full Name"
                                       value="{{ old('full_name') }}" required>
                            </div>
                            <div class="form-group">
                                <label for="phone_number">Phone Number</label><sup>*</sup>
                                <input type="text" class="form-control" id="phone_number" name="phone_number"
                                       placeholder="Enter Phone Number"
                                       value="{{ old('phone_number') }}">
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="text" class="form-control" id="email" name="email"
                                       placeholder="Enter Email Address"
                                       value="{{ old('email') }}">
                            </div>
                            <div class="form-group">
                                <label for="source">Source</label><sup>*</sup>
                                <select name="source" id="source" class="form-control" required>
                                    @foreach($sources as $source)
                                        <option value="{{$source->id}}">{{$source->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="preferred-time">Preferred time to call</label><sup>*</sup>
                                <input type="text" class="form-control" id="preferred-time" name="preferred_time"
                                       placeholder="Enter preferred time to call"
                                       value="{{ old('preferred_time') }}" required>
                            </div>
                            <div class="form-group">
                                <label for="remarks">Remarks</label>
                                <textarea class="form-control" id="remarks" name="remarks"
                                          placeholder="Enter remarks" cols="10" rows="3">{{ old('remarks') }}</textarea>
                            </div>
                            @unlessrole('sale|tele-sale')
                            <div class="form-group">
                                <label for="user">Assign to</label><sup>*</sup>
                                <select name="user" id="user" class="form-control" required>
                                    <optgroup label="Sales">
                                        @foreach($users as $user)
                                            @if ($user->role === 'sale')
                                                <option value="{{$user->id}}">{{$user->name}}</option>
                                            @endif
                                        @endforeach
                                    </optgroup>
                                    <optgroup label="Tele-Sales">
                                        @foreach($users as $user)
                                            @if ($user->role === 'tele-sale')
                                                <option value="{{$user->id}}">{{$user->name}}</option>
                                            @endif
                                        @endforeach
                                    </optgroup>

                                </select>
                            </div>
                            @endhasallroles
                            <button type="submit" class="btn btn-primary">Save</button>
                            <a class="btn btn-dark ml-3" href="{{route('tickets.all')}}">Cancel</a>
                        </fieldset>
                    </form>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
    </div>
@endsection
