@extends('layouts.app')

@section('title')
    Change user password
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ URL::to('/users') }}">Users list</a></li>
    <li class="breadcrumb-item">Change user password</li>
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
                    <form name="f1" action="{{ route('users.password.update', [$user->id]) }}" method="post" style="width: 50%;">
                        @method('PUT')
                        @csrf
                        <fieldset>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="password" autocomplete="new-password" />
                            </div>

                            <div class="form-group">
                                <label for="password_confirmation">Password Confirmation</label>
                                <input type="password" class="form-control" id="password_confirmation" autocomplete="new-password"
                                       name="password_confirmation" />
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
