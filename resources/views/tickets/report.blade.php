@extends('layouts.app')

@section('title')
Report Details
@endsection

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Reports</a></li>
<li class="breadcrumb-item">Report Details</li>
@endsection

@section('content')
<!-- Info boxes -->
<div class="row">
    <!-- fix for small devices only -->
    <div class="clearfix hidden-md-up"></div>
</div>
<!-- /.row -->

@if ($errors->any())
<div class="row">
    <div class="col-12">
        <div class="alert alert-danger">
            <ul style="list-style: none;">
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endif

<!-- *** Add a new lead removed -->

<!-- *** Search by ID removed -->

<div class="row">
    <div class="col-12">
        <div class="card card-secondary">
            <div id="flip" class="card-header" style="cursor: pointer;">
                <h3 class="card-title">Filter</h3>
                <span class="float-right"><i id="angle1" class="fas fa-angle-down"></i></span>
            </div>
            <!-- /.card-header -->
            <!-- form start -->
            <form role="form" id="filter-form" method="post" action="">
                @csrf
                <div class="card-body">
                    <div class="form-group row">
                        <label for="person" class="col-sm-2 col-form-label">Lead info</label>
                        <label for="fstatus" class="col-sm-1 col-form-label-sm">Status:</label>
                        <div class="col-sm-2">
                            <select id="fstatus" name="fstatus" class="form-control form-control-sm select2" data-dropdown-css-class="select2-info">
                                <option value="all">All</option>
                                @foreach($statuses as $status)
                                @if (auth()->user()->hasRole('accountant') AND ($status->slug != 'booking' AND $status->slug != 'approved' AND $status->slug != 'sold'))
                                @continue
                                @endif
                                <option {{($currentStatus == $status->slug) ? 'selected' : ''}} value="{{$status->slug}}">{{$status->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        @hasanyrole('super-admin|sales-manager')
                        <label for="sales" class="col-sm-1 col-form-label-sm">Sales:</label>
                        <div class="col-sm-2">
                            <select id="sales" name="sales" class="form-control form-control-sm select2" data-dropdown-css-class="select2-info">
                                <option value="all">All</option>
                                @foreach($sales as $sale)
                                <option {{($currentSale == $sale->id) ? 'selected' : ''}} value="{{$sale->id}}">{{$sale->name}}</option>
                                @endforeach
                            </select>
                        </div>
                        @endhasanyrole
                        <label for="camp" class="col-sm-1 col-form-label-sm">Campaign:</label>
                        <div class="col-sm-3">
                            <input type="text" class="form-control form-control-sm" id="camp" name="camp" value="{{$camp}}" />
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="filter" class="col-sm-2 col-form-label">Creation date</label>
                        <label for="from" class="col-sm-1 col-form-label-sm">From:</label>
                        <div class="col-sm-3">
                            <input type="date" class="form-control form-control-sm" name="from" id="from" value="{{ $from }}" />
                        </div>
                        <label for="to" class="col-sm-1 col-form-label-sm">To:</label>
                        <div class="col-sm-3">
                            <input type="date" class="form-control form-control-sm" name="to" id="to" value="{{ $to }}" />
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="person" class="col-sm-2 col-form-label">Personal info</label>
                        <label for="fullName" class="col-sm-1 col-form-label-sm">Full Name:</label>
                        <div class="col-sm-3">
                            <input type="text" class="form-control form-control-sm" id="fullName" name="fullName" value="{{ $fullName }}" />
                        </div>
                        <label for="phone" class="col-sm-1 col-form-label-sm">Phone:</label>
                        <div class="col-sm-3">
                            <input type="text" class="form-control form-control-sm" id="phone" name="phone" value="{{ $phone }}" />
                        </div>
                    </div>
                </div>
                <!-- /.card-body -->

                <div class="card-footer">
                    <div class="float-right">
                        <button type="button" id="ok-filter" class="btn btn-success">OK</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <table id="example2" class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Campaign Name</th>
                            <th>Full Name</th>
                            <th>Phone Number</th>
                            <th>Email</th>
                            <th>Current Status</th>
                            <th>Current User</th>
                            <th>Latest Follow-up</th>
                            <th>Created at</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stats as $key0 => $tickets)
                            @foreach($tickets as $key => $ticket)
                                <tr @if($ticket->status->slug === 'duplicated') class="table-danger"
                                @elseif($ticket->status->slug == 'dead' OR $ticket->status->slug == 'dead-tele') class="table-warning" @endif)>
                                <td><a href="{{ route('tickets.show', [$ticket['id']]) }}">{{ $ticket->id }}</a></td>
                                <td><a href="{{ route('tickets.show', [$ticket['id']]) }}">{{ $ticket['campaign_name'] !== null ? $ticket['campaign_name'] : '-' }}</a></td>
                                <td>{{ $ticket['full_name'] !== null ? $ticket['full_name'] : '-' }}</td>
                                <td>{{ $ticket['phone_number'] !== null ? $ticket['phone_number'] : '-' }}</td>
                                <td>{{ $ticket['email'] !== null ? $ticket['email'] : '-' }}</td>
                                <td>
                                    <span class="badge" style="color: #FFF; background-color: {{ Config::get('constants.status_colors.' . $ticket->status->slug) }}">
                                        {{$ticket->status->name}}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ $ticket->user ? route('users.edit', [$ticket->user['id']]) : '#' }}" target="_blank">{{ $ticket->user ? $ticket->user->name : '-' }}</a>
                                </td>
                                <td>
                                    {{ $ticket->lastFollowUp }}
                                </td>
                                <td>{{ date('d/m/Y h:i A', strtotime($ticket['created_at'])) }}</td>
                                <td>
                                    <ul class="nav nav-pills ml-auto p-2">
                                        <li class="nav-item dropdown" style="line-height: 1;">
                                            <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#">
                                                Actions <span class="caret"></span>
                                            </a>
                                            <div class="dropdown-menu">
                                                @can('show ticket')
                                                <a class="dropdown-item" tabindex="-1" href="{{ route('tickets.show', [$ticket['id']]) }}"><i class="fas fa-info-circle"></i> Details</a>
                                                @endcan
                                                <div class="dropdown-divider"></div>
                                                @can('delete ticket')
                                                <a class="dropdown-item" tabindex="-1" href="#" onclick="deleteTicket({{$ticket->id}})" style="color: red;">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                                @endcan
                                            </div>
                                        </li>
                                    </ul>
                                </td>
                            </tr>
                            @endforeach
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
<script src="{{ asset('dist/js/pages/dashboard2.js') }}"></script>
<!-- Select2 -->
<script src="{{ asset('plugins/select2/js/select2.full.min.js') }}"></script>

<script>
    function getParams(url, formId) {
        console.log(formId);
        const form = document.getElementById(formId);
        const formData = new FormData(form);

        let i = 0;
        for (var pair of formData.entries()) {
            if (pair[0] === '_token') {
                continue;
            }

            if (pair[1] !== '') {
                if (i == 0) {
                    url += '?' + pair[0] + '=' + pair[1];
                } else {
                    url += '&' + pair[0] + '=' + pair[1];
                }
                i++;
            }
        }

        return url;
    }

    $(function() {
        $("#ok-id").on('click', () => {
            const url = getParams('/tickets/all/', 'form-id');
            location.href = url;
        });

        $("#ok-filter").on('click', () => {
            let url = getParams('/', 'filter-form');
            url += '&linkable=1';
            location.href = url;
        });

        $("#flip").click(function() {
            $("#filter-form").slideToggle("slow");
            const element = document.getElementById('angle1');
            const style = element.getAttribute('class');
            const attr = style === 'fas fa-angle-down' ? 'fas fa-angle-up' : 'fas fa-angle-down';
            element.setAttribute('class', attr);
        });

        //Initialize Select2 Elements
        $('.select2').select2();

        //Initialize Select2 Elements
        $('.select2bs4').select2({
            theme: 'bootstrap4'
        });

        $('#example2').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": false,
            "ordering": true,
            "info": true,
            "autoWidth": false,
            "responsive": true,
            "pageLength": 15,
            "order": [
                [0, "desc"]
            ]
        });
    });

    async function deleteTicket(id) {
        if (confirm('Are you sure?')) {
            const token = '{{ csrf_token() }}';
            try {
                let response = await fetch('/tickets/delete/' + id, {
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
                    console.log(response);
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
