@extends('layouts.app')

@section('title')
    Leads list
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item">Leads list</li>
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

    @can('add ticket')
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row">

                            {{-- Add New --}}
                            <div class="col-6 col-md-3 mb-2">
                                <a href="{{ route('tickets.create') }}"
                                   class="btn btn-primary btn-block">
                                    Add New
                                </a>
                            </div>

                            @hasanyrole('super-admin|sales-manager')
                            {{-- Enable multi forward --}}
                            <div class="col-6 col-md-3 mb-2">
                                <button type="button" id="enable-fwd"
                                        class="btn btn-secondary btn-block">
                                    Forward
                                </button>
                            </div>


                            {{-- Excel Import --}}
                            <div class="col-6 col-md-3 mb-2">
                                <a href="#" id="reshuffle-btn"
                                   class="btn btn-info btn-block">
                                    Reshuffle
                                </a>
                            </div>

                            @endhasanyrole
                        </div>
                    </div>
                    <!-- /.card-header -->
                </div>
            </div>
        </div>
    @endcan

    @hasanyrole('super-admin|sales-manager|sale|tele-sale')
    <div class="row">
        <div class="col-12 col-sm-12">
            <div class="card card-primary card-outline card-tabs">
                <div class="card-header p-0 pt-1 border-bottom-0">
                    <div id="flip" style="cursor: pointer;">
                        <span class="float-right">
                            <i id="angle1" class="fas fa-angle-down" style="margin-left: -25px !important;"></i>
                        </span>
                    </div>
                    <ul class="nav nav-tabs" id="custom-tabs-three-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="custom-tabs-filter-tab" data-toggle="pill"
                               href="#custom-tabs-filter" role="tab" aria-controls="custom-tabs-filter"
                               aria-selected="true">Filter</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="custom-tabs-search-id-tab" data-toggle="pill"
                               href="#custom-tabs-search-id" role="tab" aria-controls="custom-tabs-search-id"
                               aria-selected="false">Search by ID</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="custom-tabs-three-tabContent">
                        <div class="tab-pane fade show active" id="custom-tabs-filter" role="tabpanel"
                             aria-labelledby="custom-tabs-filter-tab">
                            <!-- form start -->
                            <form role="form" class="filter-form" id="filter-form" method="post" action="">
                                @csrf
                                <div class="card-body">
                                    <div class="form-group row">
                                        <label for="person" class="col-sm-2 col-form-label">Lead info</label>
                                        <label for="fstatus" class="col-sm-1 col-form-label-sm">Status:</label>
                                        <div class="col-sm-2">
                                            <select id="fstatus" name="fstatus"
                                                    class="form-control form-control-sm select2">
                                                <option value="all">All</option>
                                                @foreach($statuses as $status)
                                                    @if (auth()->user()->hasRole('accountant') AND ($status->slug != 'booking' AND $status->slug != 'approved' AND $status->slug != 'sold'))
                                                        @continue
                                                    @endif
                                                    <option
                                                        {{(old('fstatus') === $status->slug) ? 'selected' : ''}} value="{{$status->slug}}">{{$status->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @hasanyrole('super-admin|sales-manager')
                                        <label for="sales" class="col-sm-1 col-form-label-sm">Sales:</label>
                                        <div class="col-sm-2">
                                            <select id="sales" name="sales" class="form-control form-control-sm select2"
                                            >
                                                <option value="all">All</option>
                                                @foreach($sales as $sale)
                                                    <option
                                                        {{(old('sales') AND old('sales') == $sale->id) ? 'selected' : ''}} value="{{$sale->id}}">{{$sale->name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @endhasanyrole
                                        <label for="camp" class="col-sm-1 col-form-label-sm">Campaign:</label>
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control form-control-sm" id="camp"
                                                   name="camp"
                                                   value="{{old('camp')}}"/>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="filter" class="col-sm-2 col-form-label">Creation date</label>
                                        <label for="from" class="col-sm-1 col-form-label-sm">From:</label>
                                        <div class="col-sm-3">
                                            <input type="date" class="form-control form-control-sm" name="from"
                                                   id="from"
                                                   value="{{old('from')}}"/>
                                        </div>
                                        <label for="to" class="col-sm-1 col-form-label-sm">To:</label>
                                        <div class="col-sm-3">
                                            <input type="date" class="form-control form-control-sm" name="to" id="to"
                                                   value="{{old('to')}}"/>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label for="person" class="col-sm-2 col-form-label">Personal info</label>
                                        <label for="fullName" class="col-sm-1 col-form-label-sm">Full Name:</label>
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control form-control-sm" id="fullName"
                                                   name="fullName"
                                                   value="{{old('fullName')}}"/>
                                        </div>
                                        <label for="phone" class="col-sm-1 col-form-label-sm">Phone:</label>
                                        <div class="col-sm-3">
                                            <input type="text" class="form-control form-control-sm" id="phone"
                                                   name="phone"
                                                   value="{{old('phone')}}"/>
                                        </div>
                                    </div>

                                    <!-- /.card-body -->
                                    <div class="float-right">
                                        <button type="button" id="ok-filter" class="btn btn-success">OK</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="custom-tabs-search-id" role="tabpanel"
                             aria-labelledby="custom-tabs-search-id-tab">
                            <form role="form" class="filter-form" method="post" action="" id="form-id">
                                @csrf
                                <div class="card-body">
                                    <div class="form-group row">
                                        <label for="person" class="col-sm-2 col-form-label">Lead ID</label>
                                        <div class="col-sm-4">
                                            <input type="text" class="form-control form-control-sm" name="lead_id"
                                                   id="lead_id"
                                                   value="{{old('lead_id')}}"/>
                                        </div>
                                        <div class="col-sm-2">
                                            <button type="button" id="ok-id" class="btn btn-success btn-sm">OK</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- /.card -->
            </div>
        </div>
    </div>
    @endhasanyrole

    <div class="row">
        <div class="col-12">
            <div class="card card-secondary">

                <!-- /.card-header -->

            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body table-responsive">
                    @hasanyrole('super-admin|sales-manager')
                    <form name="fwd-form" action="{{ route('tickets.multipleForward') }}" method="post" id="fwd-form">
                        @csrf
                        @endhasanyrole
                        <div class="table-responsive">
                            <table id="example20" style="width: 100%;"
                               class="table table-bordered table-hover">
                            <thead>
                            <tr>
                                <th class="fwd-leads" style="display:none !important;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="" id="select-all">
                                        <label id="select-all-lbl" class="form-check-label" for="select-all">
                                            Select all
                                        </label>
                                    </div>
                                </th>
                                <th>ID</th>
                                <th>Campaign Name</th>
                                <th>Full Name</th>
                                <th>Phone Number</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Current User</th>
                                <th>Latest Follow-up</th>
                                @unlessrole('sale|tele-sale')
                                    <th>Created at</th>
                                @endunlessrole
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($tickets as $key => $ticket)
                                <tr id="row_{{ $ticket->id }}"
                                    @if($ticket->status->slug === 'duplicated') class="table-danger"
                                    @elseif($ticket->status->slug === 'dead' || $ticket->status->slug === 'dead-tele') class="table-warning" @endif>
                                    <td class="fwd-leads" style="display:none !important;">
                                        <div class="form-check">
                                            <input type="checkbox" name="lead_ids[]" id="lead-{{ $ticket->id }}"
                                                   value="{{ $ticket->id }}" class="form-check-input lead-checkbox"/>
                                        </div>
                                    </td>
                                    <td><a href="{{ route('tickets.show', [$ticket->id]) }}">{{ $ticket->id }}</a>
                                    </td>
                                    <td>
                                        <a href="{{ route('tickets.show', [$ticket->id]) }}">{{ $ticket['campaign_name'] !== null ? $ticket['campaign_name'] : '-' }}</a>
                                    </td>
                                    <td>{{ $ticket['full_name'] !== null ? $ticket['full_name'] : '-' }}</td>
                                    <td>{{ $ticket['phone_number'] !== null ? $ticket['phone_number'] : '-' }}</td>
                                    <td>{{ $ticket['email'] !== null ? $ticket['email'] : '-' }}</td>
                                    <td>
                                <span class="badge"
                                      style="color: #FFF; background-color: {{ Config::get('constants.status_colors.' . $ticket->status->slug) }}">
                                    {{$ticket->status->name}}
                                </span>
                                    </td>
                                    <td>
                                        <a href="{{ $ticket->user ? route('users.edit', [$ticket->user['id']]) : '#' }}"
                                           target="_blank">{{ $ticket->user ? $ticket->user->name : '-' }}</a>
                                    </td>
                                    <td>
                                        {{ $ticket->lastFollowUp }}
                                    </td>
                                    @unlessrole('sale|tele-sale')
                                        <td>{{ date('d/m/Y h:i A', strtotime($ticket['created_at'])) }}</td>
                                    @endunlessrole
                                    <td>
                                        <ul class="nav nav-pills ml-auto p-2">
                                            <li class="nav-item dropdown" style="line-height: 1;">
                                                <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#">
                                                    Actions <span class="caret"></span>
                                                </a>
                                                <div class="dropdown-menu">
                                                    @can('show ticket')
                                                        <a class="dropdown-item" tabindex="-1"
                                                           href="{{ route('tickets.show', [$ticket->id]) }}"><i
                                                                class="fas fa-info-circle"></i> Details</a>
                                                    @endcan
                                                    <div class="dropdown-divider"></div>
                                                    @can('delete ticket')
                                                        <a class="dropdown-item" tabindex="-1" href="#"
                                                           onclick="deleteTicket({{$ticket->id}})" style="color: red;">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </a>
                                                    @endcan
                                                </div>
                                            </li>
                                        </ul>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot style="display: none !important;" class="fwd-leads">
                            <tr>
                                <td colspan="11">
                                    <div class="form-group row">
                                        <div class="col-sm-12">
                                            <button type="button" id="submit-fwd" class="btn btn-success float-right">
                                                Submit forward
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                        </div>
                        @hasanyrole('super-admin|sales-manager')
                        <div class="modal fade" id="modal-lg">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title">Forward multiple leads</h4>
                                        <button type="button" class="close" data-dismiss="modal"
                                                aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="client-unit">Sales employee</label><sup>*</sup>
                                            <select name="sales" id="sales" class="form-control" required>
                                                <optgroup label="Sales">
                                                    @foreach($sales as $salesEmp)
                                                        @if ($salesEmp->getRoleNames()[0] === 'sale')
                                                            <option value="{{ $salesEmp->id }}">
                                                                {{ $salesEmp->name }}
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </optgroup>

                                                <optgroup label="Tele-Sales">
                                                    @foreach($sales as $salesEmp)
                                                        @if ($salesEmp->getRoleNames()[0] === 'tele-sale')
                                                            <option value="{{ $salesEmp->id }}">
                                                                {{ $salesEmp->name }}
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </optgroup>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer justify-content-between">
                                        <button type="button" class="btn btn-default"
                                                data-dismiss="modal">
                                            Close
                                        </button>
                                        <button type="submit" class="btn btn-primary" id="saveBtn">
                                            Submit
                                        </button>
                                    </div>
                                </div>
                                <!-- /.modal-content -->
                            </div>
                            <!-- /.modal-dialog -->
                        </div>
                    </form>
                    @endhasanyrole

                    <!-- Changing from here -->
                    <div class="row mt-3">
                        <div class="col-sm-12 col-md-5">
                            <div class="dataTables_info" role="status" aria-live="polite">
                                Showing {{ $tickets->firstItem() }} to {{ $tickets->lastItem() }}
                                of {{ $tickets->total() }} entries
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-7">
                            <div class="dataTables_paginate float-right paging_simple_numbers">
                                {{ $tickets->links() }}
                            </div>
                        </div>
                    </div>

                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
    </div>
@endsection

@section('script')

    <!-- Toastr -->
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js')}}"></script>

    <script>
        const Toast = Swal.mixin({
            toast: true,
            background: '#E3E5E8',
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
        });

        @if(session()->has('successMsg'))
        Toast.fire({
            icon: 'success',
            title: '{{ session()->get('successMsg') }}',
        });
        @endif

        const fromElem = document.getElementById('from') || false;
        const toElem = document.getElementById('to') || false;

        if (fromElem && !fromElem.value) {
            fromElem.value = formatDate(getFstDayOfMonFnc());

        }

        if (toElem && !toElem.value) {
            toElem.value = formatDate(new Date());
        }

        function getFstDayOfMonFnc() {
            const date = new Date();
            return new Date(date.getFullYear(), date.getMonth(), 1)
        }

        function formatDate(date) {
            let d = new Date(date),
                month = '' + (d.getMonth() + 1),
                day = '' + d.getDate(),
                year = d.getFullYear();

            if (month.length < 2)
                month = '0' + month;
            if (day.length < 2)
                day = '0' + day;

            return [year, month, day].join('-');
        }

        function getParams(url, formId) {
            const form = document.getElementById(formId);
            const formData = new FormData(form);

            let i = 0;
            for (const pair of formData.entries()) {
                if (pair[0] === '_token') {
                    continue;
                }

                if (pair[1] !== '') {
                    if (i === 0) {
                        url += '?' + pair[0] + '=' + pair[1];
                    } else {
                        url += '&' + pair[0] + '=' + pair[1];
                    }
                    i++;
                }
            }

            return url;
        }

        $(function () {
            $("#ok-id").on('click', () => {
                const url = getParams('/tickets/all/', 'form-id');
                location.href = url;
            });

            $("#ok-filter").on('click', () => {
                const url = getParams('/tickets/all/', 'filter-form');
                location.href = url;
            });

            $("#flip").click(function () {
                $(".filter-form").slideToggle("slow");
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

        function deleteRow(rowId) {
            const row = document.getElementById(rowId);
            row.parentNode.removeChild(row);
        }

        async function deleteTicket(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then(async (result) => {
                if (result.value) {
                    const token = '{{ csrf_token() }}';

                    try {
                        Swal.fire({
                            title: 'Please wait!',
                            imageUrl: '{{asset('dist/img/loading2.gif')}}',
                            imageWidth: 128,
                            imageHeight: 128,
                            imageAlt: 'Deleting..',
                            showConfirmButton: false,
                        });


                        let resp = await axios.delete('/tickets/delete/' + id, {
                            withCredentials: true,
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-Token': token,
                            }
                        });

                        let result = resp.data;

                        Swal.close();

                        if (result.OK) {
                            deleteRow('row_' + id);

                            Toast.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: 'The ticket has been deleted.',
                            });
                        }
                    } catch (e) {
                        console.log(e.response);
                        let errors = '';
                        Object.keys(e.response?.data).forEach(valInd => {
                            errors += (e.response?.data[valInd] + '\n');
                        });

                        Toast.fire({
                            icon: 'warning',
                            title: errors,
                        });
                    }
                }
            });
        }


        const fwdBtn = document.getElementById('enable-fwd');
        const reshufBtn = document.getElementById('reshuffle-btn');

        const boxes = document.querySelectorAll('.fwd-leads');

        let enabledByBtn = false;

        function initBoxes(boxes) {
            if (!enabledByBtn) {
                boxes.forEach(box => {
                    box.style.display = 'none';
                    box.classList.remove('sorting_desc');
                });
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            initBoxes(boxes);

            $('#example20').DataTable({
                paging: false,
                lengthChange: false,
                searching: false,
                ordering: true,
                info: false,
                responsive: true,
                autoWidth: false
            });
        }, false);

        fwdBtn.addEventListener('click', () => {
            const checked = document.querySelectorAll('.lead-checkbox:checked');

            if (checked.length === 0) {
                Toast.fire({
                    icon: 'warning',
                    title: 'No selected leads!'
                });

                return;
            }

            $('#modal-lg').modal('show');
        }, false);

        reshufBtn.addEventListener('click', () => {
            const checked = document.querySelectorAll('.lead-checkbox:checked');

            if (checked.length === 0) {
                Toast.fire({
                    icon: 'warning',
                    title: 'No selected leads!'
                });

                return;
            }

            const leadIds = Array.from(checked).map(cb => parseInt(cb.value));

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, reshuffle!'
            }).then(async (result) => {
                if (result.value) {
                    reshuffleLeads(leadIds);
                }
            });
        }, false);

        const selectAll = document.getElementById('select-all');
        selectAll.checked = false;

        document.getElementById('select-all').addEventListener('click', () => {
            const checkBoxes = document.querySelectorAll('.form-check-input');
            const isSelectAllChecked = selectAll.checked;

            checkBoxes.forEach(box => {
                box.checked = isSelectAllChecked;
            });

            document.getElementById('select-all-lbl').innerHTML = isSelectAllChecked ? 'Deselect all' : 'Select all';
        }, false);

        function reshuffleLeads(leadIds) {
            const token = '{{ csrf_token() }}';

            axios.post('/tickets/reshuffle', {
                lead_ids: leadIds
            }, {
                withCredentials: true,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-Token': token,
                }
            })
                .then(response => {
                    const data = response.data;

                    console.log('Reshuffle result:', data);

                    Toast.fire({
                        icon: 'success',
                        title: `Reshuffled successfully. Assigned: ${data.total_assigned}`,
                    });

                    setTimeout(function () {
                        window.location.reload();
                    }, 2000);
                })
                .catch(error => {
                    console.error(error);

                    if (error.response && error.response.data) {
                        Toast.fire({
                            icon: 'error',
                            title: error.response.data.message || 'Reshuffle failed.'
                        });
                    } else {
                        Toast.fire({
                            icon: 'error',
                            title: 'Network or server error.'
                        });
                    }
                });
        }
    </script>
@endsection
