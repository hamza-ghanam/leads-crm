@extends('layouts.app')

@section('title')
    Add New Lead
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('tickets.all') }}">Leads</a></li>
    <li class="breadcrumb-item active">Add New Lead</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-7">
            <div class="card shadow-sm">
                <div class="card-header" style="background-color: {{ config('app.theme_color') }};">
                    <h3 class="card-title text-white mb-0">
                        <i class="fas fa-plus-circle mr-2"></i> Add New Lead
                    </h3>
                </div>
                <div class="card-body">

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

                    <form action="{{ route('tickets.store') }}" method="post">
                        @csrf

                        <div class="form-group">
                            <label for="campaign_name">Campaign Name <sup class="text-danger">*</sup></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-bullhorn"></i></span>
                                </div>
                                <input type="text" id="campaign_name" name="campaign_name"
                                       class="form-control @error('campaign_name') is-invalid @enderror"
                                       placeholder="Enter campaign name"
                                       value="{{ old('campaign_name') }}" required autofocus/>
                                @error('campaign_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="full_name">Full Name <sup class="text-danger">*</sup></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                </div>
                                <input type="text" id="full_name" name="full_name"
                                       class="form-control @error('full_name') is-invalid @enderror"
                                       placeholder="Enter full name"
                                       value="{{ old('full_name') }}" required/>
                                @error('full_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="phone_number">Phone Number <sup class="text-danger">*</sup></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                </div>
                                <input type="text" id="phone_number" name="phone_number"
                                       class="form-control @error('phone_number') is-invalid @enderror"
                                       placeholder="Enter phone number"
                                       value="{{ old('phone_number') }}"/>
                                @error('phone_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                </div>
                                <input type="email" id="email" name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       placeholder="Enter email address"
                                       value="{{ old('email') }}"/>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="source">Source <sup class="text-danger">*</sup></label>
                            <select name="source" id="source" class="form-control select2" required>
                                @foreach($sources as $source)
                                    <option value="{{ $source->id }}"
                                        {{ old('source') == $source->id ? 'selected' : '' }}>
                                        {{ $source->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="preferred-time">Preferred Time to Call <sup class="text-danger">*</sup></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-clock"></i></span>
                                </div>
                                <input type="text" id="preferred-time" name="preferred_time"
                                       class="form-control @error('preferred_time') is-invalid @enderror"
                                       placeholder="e.g. Morning, 9am–12pm"
                                       value="{{ old('preferred_time') }}" required/>
                                @error('preferred_time')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="remarks">Remarks</label>
                            <textarea class="form-control @error('remarks') is-invalid @enderror"
                                      id="remarks" name="remarks"
                                      placeholder="Enter remarks" rows="3">{{ old('remarks') }}</textarea>
                            @error('remarks')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        @unlessrole('sale|tele-sale')
                        <div class="form-group">
                            <label for="user">Assign To <sup class="text-danger">*</sup></label>
                            <select name="user" id="user" class="form-control select2" required>
                                <optgroup label="Sales">
                                    @foreach($users as $user)
                                        @if($user->role === 'sale')
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endif
                                    @endforeach
                                </optgroup>
                                <optgroup label="Tele-Sales">
                                    @foreach($users as $user)
                                        @if($user->role === 'tele-sale')
                                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                                        @endif
                                    @endforeach
                                </optgroup>
                            </select>
                        </div>
                        @endhasallroles

                        <div class="d-flex align-items-center mt-4">
                            <a href="{{ route('tickets.all') }}" class="btn btn-outline-secondary mr-2">
                                <i class="fas fa-arrow-left mr-1"></i> Back
                            </a>
                            <button type="submit" class="btn btn-primary" id="submit-btn">
                                <i class="fas fa-save mr-1"></i> Save Lead
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
        $(function () {
            $('.select2').select2({ width: '100%' });
        });

        document.querySelector('form').addEventListener('submit', function () {
            const btn = document.getElementById('submit-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';
        });
    </script>
@endsection
