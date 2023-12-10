@extends('layouts.app')

@section('title')
    Edit user
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ URL::to('/users') }}">Users list</a></li>
    <li class="breadcrumb-item">Edit user</li>
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
                    <form name="f1" action="{{ route('users.update', [$user->id]) }}" method="post" style="width: 50%;">
                        @method('PUT')
                        @csrf
                        <fieldset>
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" class="form-control" id="name" name="name" placeholder="Enter Name"
                                       value="{{ $user->name }}">
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="text" class="form-control" id="email" name="email"
                                       placeholder="Enter Email" value="{{ $user->email }}">
                            </div>
                            <div class="form-group">
                                <label for="phone_number">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone"
                                       placeholder="Enter Phone" value="{{ $user->phone }}">
                            </div>
                            <div class="form-group">
                                <label for="role">Role</label>
                                <select class="form-control" name="role" id="role">
                                    @foreach($roles as $role)
                                        <option value="{{$role->id}}" {{$user->role == $role->id ? 'selected' : ''}}>{{$role->name}}</option>
                                    @endforeach
                                </select>
                            </div>

                            @if($user->role == 3 OR $user->role == 4)
                                <div class="form-group" id="mgrs">
                                    <label for="mgrs-list">Sales Manager</label>
                                    <select class="form-control" name="manager" id="mgrs-list">
                                        @foreach($salesManagers as $salesManager)
                                            <option {{ $user->manager_id !== null && $user->manager_id == $salesManager->id ? 'selected' : '' }} value="{{$salesManager->id}}">{{$salesManager->name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="form-group">
                                <label for="password">Password</label>
                                <small class="text-danger text-bold"> (If you don't want to change it, keep it empty.)</small>
                                <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" />
                            </div>

                            <div class="form-group">
                                <label for="password_confirmation">Password Confirmation</label>
                                <small class="text-danger text-bold"> (If you don't want to change it, keep it empty.)</small>
                                <input type="password" class="form-control" id="password_confirmation" autocomplete="new-password"
                                       name="password_confirmation" />
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                  <input type="checkbox"  class="custom-control-input" id="customSwitch1" name="ban_check" {{ $user->status === 'permitted' ? 'checked' : '' }} />
                                  <label class="custom-control-label" for="customSwitch1">{{ $user->status === 'banned' ? 'Banned' : 'Permitted' }}</label>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                                    <input type="checkbox" class="custom-control-input" id="customSwitch3" name="delete_check" {{ !$user->deleted_at ? 'checked' : '' }} />
                                    <label class="custom-control-label" for="customSwitch3">{{ $user->deleted_at ? 'Deleted (' . date('Y-m-d h:i:s A', strtotime($user->deleted_at)) . ')' : 'Available' }}</label>
                                </div>
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
