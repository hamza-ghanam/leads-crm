@extends('layouts.app')

@section('title')
    Add New User
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('users.all') }}">Users</a></li>
    <li class="breadcrumb-item active">Add New User</li>
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-7">
            <div class="card shadow-sm">
                <div class="card-header" style="background-color: {{ config('app.theme_color') }};">
                    <h3 class="card-title text-white mb-0">
                        <i class="fas fa-user-plus mr-2"></i> Add New User
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

                    <form action="{{ route('users.store') }}" method="post">
                        @csrf

                        <div class="form-group">
                            <label for="name">Name <sup class="text-danger">*</sup></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                </div>
                                <input type="text" id="name" name="name"
                                       class="form-control @error('name') is-invalid @enderror"
                                       placeholder="Full name"
                                       value="{{ old('name') }}" autofocus/>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email <sup class="text-danger">*</sup></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                </div>
                                <input type="email" id="email" name="email"
                                       class="form-control @error('email') is-invalid @enderror"
                                       placeholder="email@example.com"
                                       value="{{ old('email') }}"/>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                </div>
                                <input type="text" id="phone" name="phone"
                                       class="form-control @error('phone') is-invalid @enderror"
                                       placeholder="Phone number"
                                       value="{{ old('phone') }}"/>
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password">Password <sup class="text-danger">*</sup></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                </div>
                                <input type="password" id="password" name="password"
                                       class="form-control @error('password') is-invalid @enderror"
                                       placeholder="Password" autocomplete="new-password"/>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation">Confirm Password <sup class="text-danger">*</sup></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                </div>
                                <input type="password" id="password_confirmation" name="password_confirmation"
                                       class="form-control"
                                       placeholder="Confirm password" autocomplete="new-password"/>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="role">Role <sup class="text-danger">*</sup></label>
                            <select class="form-control select2" name="role" id="role">
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}"
                                        {{ old('role') == $role->id ? 'selected' : '' }}>
                                        {{ $role->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group" id="mgrs" style="display: none;">
                            <label for="mgrs-list">Sales Manager <sup class="text-danger">*</sup></label>
                            <select class="form-control select2" name="manager" id="mgrs-list">
                                @foreach($salesManagers as $salesManager)
                                    <option value="{{ $salesManager->id }}">{{ $salesManager->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="d-flex align-items-center mt-4">
                            <a href="{{ route('users.all') }}" class="btn btn-outline-secondary mr-2">
                                <i class="fas fa-arrow-left mr-1"></i> Back
                            </a>
                            <button type="submit" class="btn btn-primary" id="submit-btn">
                                <i class="fas fa-save mr-1"></i> Save User
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

        const roleSelect = document.getElementById('role');

        function toggleManager() {
            const val = parseInt(roleSelect.value);
            document.getElementById('mgrs').style.display = (val === 3 || val === 4) ? '' : 'none';
        }

        toggleManager();
        roleSelect.addEventListener('change', toggleManager);

        document.querySelector('form').addEventListener('submit', function () {
            const btn = document.getElementById('submit-btn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';
        });
    </script>
@endsection
