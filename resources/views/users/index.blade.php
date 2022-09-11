@extends('layouts.app')

@section('title')
Users list
@endsection

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
<li class="breadcrumb-item">Users list</li>
@endsection

@section('content')
<!-- Info boxes -->
<div class="row">
    <!-- fix for small devices only -->
    <div class="clearfix hidden-md-up"></div>
</div>
<!-- /.row -->

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <a href="{{ route('users.create') }}" class="btn btn-primary">Add</a>
            </div>
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
                <table id="example2" class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone Number</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Registered at</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $key => $user)
                        @php
                            $statusColor = $user->deleted_at ? 'danger' : ($user->status === 'permitted' ? 'success' : 'warning');
                            $statusLabel = $user->deleted_at ? 'Deleted' : ($user->status === 'permitted' ? 'Permitted' : 'Banned');
                        @endphp
                        <tr>
                            <td>{{ $key + 1 }}</td>
                            <td><a target="_blank" href="{{ route('users.edit', [$user->id]) }}"> {{ $user['name'] }}</a></td>
                            <td>{{ !empty($user['email']) ? $user['email'] : '-' }}</td>
                            <td>{{ $user['phone'] }}</td>
                            <td>{{ $user->role }}</td>
                            <td><span class="badge badge-{{ $statusColor }}">
                                    {{ $statusLabel }}
                                </span></td>
                            <td>{{ date('d/m/Y h:i A', strtotime($user['created_at'])) }}</td>
                            <td>
                                <ul class="nav nav-pills ml-auto p-2">
                                    <li class="nav-item dropdown" style="line-height: 1;">
                                        <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#">
                                            Actions <span class="caret"></span>
                                        </a>
                                        <div class="dropdown-menu">
                                            @if (!$user->deleted_at)
                                                <a class="dropdown-item" tabindex="-1" href="{{ route('users.banPermit', [$user['id']]) }}">
                                                    <i class="fas {{ $user->status === 'permitted' ? 'fa-ban' : 'fa-check-circle' }}"></i>
                                                    {{ $user->status === 'permitted' ? 'Ban' : 'Permit' }}
                                                </a>
                                            @endif
                                            <a class="dropdown-item" tabindex="-1" href="{{ route('users.edit', [$user['id']]) }}"><i class="fas fa-pen"></i> Edit</a>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item text-{{ $user->deleted_at ? 'success' : 'danger' }}" tabindex="-1" href="#" onclick="deleteRestore({{$user->id}})">
                                                <i class="fas fa-trash{{ $user->deleted_at ? '-restore' : '' }}"></i> {{ $user->deleted_at ? 'Restore' : 'Delete' }}
                                            </a>
                                        </div>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <!-- /.card-body -->
        </div>
        <!-- /.card -->
    </div>
</div>
@endsection

@section('script')
<!-- PAGE SCRIPTS -->
<script src="{{ asset('public/dist/js/pages/dashboard2.js') }}"></script>
<script>
    $(function() {
        $('#example2').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": false,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "pageLength": 15
        });
    });

    async function deleteRestore(id) {
        if (confirm('Are you sure?')) {
            const token = '{{ csrf_token() }}';

            try {
                let response = await fetch('/users/deleteRestore/' + id, {
                    method: 'DELETE',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-Token': token,
                    },
                });

                response = await response.json();

                if (response.error) {
                    alert(response.error);
                } else if (response.OK) {
                    location.reload();
                }
            } catch (e) {
                console.log(e);
            }
        }
    }
</script>
@endsection
