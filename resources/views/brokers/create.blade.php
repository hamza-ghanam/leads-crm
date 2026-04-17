@extends('layouts.app')

@section('title')
    Invite Broker
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('brokers.index') }}">Brokers</a></li>
    <li class="breadcrumb-item active">Invite Broker</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-6">
            <div class="card shadow-sm">
                <div class="card-header" style="background-color: #2d3955;">
                    <h1 class="card-title text-white mb-0">
                        <i class="fas fa-envelope-open-text mr-2"></i> Invite a Broker
                    </h1>
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

                    <form action="{{ route('brokers.invite') }}" method="post">
                        @csrf
                        <div class="form-group">
                            <label for="broker-email">
                                Broker Email <sup class="text-danger">*</sup>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                </div>
                                <input type="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       id="broker-email"
                                       name="email"
                                       placeholder="broker@example.com"
                                       value="{{ old('email') }}"
                                       required autofocus/>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="text-muted">An invitation link will be sent to this address.</small>
                        </div>

                        <div class="d-flex align-items-center mt-4">
                            <a href="{{ route('brokers.index') }}" class="btn btn-outline-secondary mr-2">
                                <i class="fas fa-arrow-left mr-1"></i> Back
                            </a>
                            <button type="submit" class="btn btn-primary" id="submit-btn">
                                <i class="fas fa-paper-plane mr-1"></i> Send Invitation
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
        document.querySelector('form').addEventListener('submit', function () {
            const btn = document.getElementById('submit-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Sending...';
        });
    </script>
@endsection
