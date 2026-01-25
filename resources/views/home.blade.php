@extends('layouts.app')

@section('title')
    Reports
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
@endsection


@section('content')
    @hasrole('super-admin')

    <div class="row">
        <div class="col-12">
            <div class="card card-secondary">
                <div id="flip" class="card-header" style="cursor: pointer; background-color: #2d3955 !important;">
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
                                <select id="fstatus" name="fstatus" class="form-control form-control-sm select2">
                                    <option value="all">All</option>
                                    @foreach($statuses as $status)
                                        @if (auth()->user()->hasRole('accountant') AND ($status->slug != 'booking' AND $status->slug != 'approved' AND $status->slug != 'sold'))
                                            @continue
                                        @endif
                                        <option
                                            {{($currentStatus == $status->slug) ? 'selected' : ''}} value="{{$status->slug}}">{{$status->name}}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <label for="sales" class="col-sm-1 col-form-label-sm">Sales:</label>
                            <div class="col-sm-2">
                                <select id="sales" name="sales" class="form-control form-control-sm select2">
                                    <option value="all">All</option>
                                    @foreach($sales as $sale)
                                        <option
                                            {{($currentSale == $sale->id) ? 'selected' : ''}} value="{{$sale->id}}">{{$sale->name}}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <label for="camp" class="col-sm-1 col-form-label-sm">Campaign:</label>
                            <div class="col-sm-3">
                                <input type="text" class="form-control form-control-sm" id="camp" name="camp"
                                       value="{{$camp}}"/>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="filter" class="col-sm-2 col-form-label">Creation date</label>
                            <label for="from" class="col-sm-1 col-form-label-sm">From:</label>
                            <div class="col-sm-3">
                                <input type="date" class="form-control form-control-sm" name="from" id="from"
                                       value="{{ $from }}"/>
                            </div>
                            <label for="to" class="col-sm-1 col-form-label-sm">To:</label>
                            <div class="col-sm-3">
                                <input type="date" class="form-control form-control-sm" name="to" id="to"
                                       value="{{ $to }}"/>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="filter" class="col-sm-2 col-form-label">Update date</label>
                            <label for="from" class="col-sm-1 col-form-label-sm">From:</label>
                            <div class="col-sm-3">
                                <input type="date" class="form-control form-control-sm" name="updated_from" id="updated-from"
                                       value="{{ $updatedFrom }}"/>
                            </div>
                            <label for="to" class="col-sm-1 col-form-label-sm">To:</label>
                            <div class="col-sm-3">
                                <input type="date" class="form-control form-control-sm" name="updated_to" id="updated-to"
                                       value="{{ $updatedTo }}"/>
                            </div>
                        </div>
                        <div class="form-group row">
                            <label for="person" class="col-sm-2 col-form-label">Personal info</label>
                            <label for="fullName" class="col-sm-1 col-form-label-sm">Full Name:</label>
                            <div class="col-sm-3">
                                <input type="text" class="form-control form-control-sm" id="fullName" name="fullName"
                                       value="{{ $fullName }}"/>
                            </div>
                            <label for="phone" class="col-sm-1 col-form-label-sm">Phone:</label>
                            <div class="col-sm-3">
                                <input type="text" class="form-control form-control-sm" id="phone" name="phone"
                                       value="{{ $phone }}"/>
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
    @endhasrole

    <!-- Small boxes (Stat box) -->
    <div class="row">
        @foreach($stats as $key => $value)
            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box" style="background-color: {{ Config::get('constants.status_colors.' . $key) }};">
                    <div class="inner">
                        @hasanyrole('super-admin')
                        <h3><a href="#" class="text-light filter-leads"
                               data-status="{{ strtolower($key) }}">{{$value}}</a></h3>
                        <p><a href="#" class="text-light filter-leads" data-status="{{ strtolower($key) }}">{{$key}}</a>
                        </p>
                        @else
                            <h3><a href="/tickets/all?fstatus={{ strtolower($key) }}" class="text-light">{{ $value }}</a></h3>
                            <p><a href="/tickets/all?fstatus={{ strtolower($key) }}" class="text-light">{{ $key }}</a></p>
                            @endhasanyrole
                    </div>
                    <div class="icon">
                        <i class="{{ Config::get('constants.status_icons.' . strtolower($key)) }}"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
    <!-- /.row -->
@endsection

@section('script')
    <!-- PAGE SCRIPTS -->
    <script>
        const fromElem = document.getElementById('updated-from') || false;
        const toElem = document.getElementById('updated-to') || false;

        if (fromElem && fromElem.value === '') {
            fromElem.value = formatDate(getFstDayOfMonFnc());
        }

        if (toElem && toElem.value === '') {
            toElem.value = formatDate(new Date());
        }

        if (fromElem && toElem && fromElem.value && toElem.value) {
            if (new Date(fromElem.value) > new Date(toElem.value)) {
                toElem.value = fromElem.value;
            }
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

        function getParams(url, fstatus = null) {
            const form = document.getElementById('filter-form');
            const formData = new FormData(form);

            let i = 0;
            for (var pair of formData.entries()) {
                if (pair[0] === '_token') {
                    continue;
                }

                if (pair[1] !== '') {
                    if (fstatus !== null && pair[0] == 'fstatus') {
                        pair[1] = fstatus;
                    }

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

        $(function () {
            $("#ok-filter").on('click', () => {
                const url = getParams('/');
                location.href = url;
            });

            $(".filter-leads").on('click', function () {
                let status = $(this).data('status');
                console.log(status)
                let url = getParams('/', status);
                url += '&linkable=1';
                location.href = url;
            });

            $("#flip").click(function () {
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
    </script>
@endsection
