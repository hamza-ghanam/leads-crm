@extends('layouts.app')

@section('title')
    Add new user
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ URL::to('/users') }}">Users list</a></li>
    <li class="breadcrumb-item">Add new user</li>
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
                    <form name="f1" action="{{ route('users.store') }}" method="post" style="width: 50%;">
                        @csrf
                        <fieldset>
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Enter Name"
                                       value="{{ old('name') }}">
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="text" class="form-control" id="email" name="email"
                                       placeholder="Enter Email" value="{{ old('email') }}">
                            </div>
                            <div class="form-group">
                                <label for="phone_number">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone"
                                       placeholder="Enter Phone" value="{{ old('phone') }}">
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="password"
                                       placeholder="Password" autocomplete="new-password">
                            </div>
                            <div class="form-group">
                                <label for="password">Password Confirmation</label>
                                <input type="password" class="form-control" id="password_confirmation"
                                       name="password_confirmation" placeholder="Password Confirmation" autocomplete="new-password">
                            </div>
                            <div class="form-group">
                                <label for="role">Role</label>
                                <select class="form-control" name="role" id="role">
                                    @foreach($roles as $role)
                                        <option value="{{$role->id}}">{{$role->name}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group" id="mgrs" style="display: none;">
                                <label for="mgrs-list">Sales Manager</label>
                                <select class="form-control" name="manager" id="mgrs-list">
                                    @foreach($salesManagers as $salesManager)
                                        <option value="{{$salesManager->id}}">{{$salesManager->name}}</option>
                                    @endforeach
                                </select>
                            </div>

                            <button type="submit" class="btn btn-primary">Save</button>
                            <a class="btn btn-dark ml-3" href="{{route('users.all')}}">Cancel</a>
                        </fieldset>
                    </form>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
    </div>
@endsection

@section('script')
    <script>
        const e = document.getElementById("role");
        let strUser = e.value; // 2

        if (strUser != 3 && strUser != 4) {
            document.getElementById('mgrs').style.display = 'none';
        } else {
            document.getElementById('mgrs').style.display = '';
        }

        e.addEventListener('change', function () {
            strUser = e.value;

            if (strUser != 3 && strUser != 4) {
                document.getElementById('mgrs').style.display = 'none';
            } else {
                document.getElementById('mgrs').style.display = '';
            }
        });
    </script>
@endsection
