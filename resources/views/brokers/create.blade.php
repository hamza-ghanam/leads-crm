@extends('layouts.app')

@section('title')
    Invite Broker
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ URL::to('/brokers') }}">Brokers</a></li>
    <li class="breadcrumb-item">Add new broker</li>
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
                    <form name="f1" action="{{ route('brokers.invite') }}" method="post" style="width: 90%;">
                        @csrf
                        <fieldset>
                            <div class="form-group">
                                <label for="broker-email">Broker Email</label><sup>*</sup>
                                <input
                                    type="email"
                                    class="form-control"
                                    id="broker-email"
                                    name="email"
                                    placeholder="Enter broker email"
                                    value="{{ old('email') }}"
                                    required
                                />
                            </div>

                            <button type="submit" class="btn btn-primary" onclick="this.disabled=true; this.form.submit();">
                                Invite
                            </button>
                        </fieldset>
                    </form>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
    </div>
@endsection
