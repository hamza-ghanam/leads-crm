@extends('layouts.app')

@section('title')
    Brokers list
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item">Brokers list</li>
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
                                <a href="{{ route('brokers.add') }}"
                                   class="btn btn-primary btn-block">
                                    Add New
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- /.card-header -->
                </div>
            </div>
        </div>
    @endcan

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
                                    <th>Name</th>
                                    <th>Phone Number</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Company (if exist)</th>
                                    <th>Registration Date</th>
                                    <th></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($brokers as $key => $broker)
                                    <tr id="row_{{ $broker->id }}">
                                        <td class="fwd-leads" style="display:none !important;">
                                            <div class="form-check">
                                                <input type="checkbox" name="lead_ids[]" id="lead-{{ $broker->id }}"
                                                       value="{{ $broker->id }}"
                                                       class="form-check-input lead-checkbox"/>
                                            </div>
                                        </td>
                                        <td><a href="{{ route('brokers.show', [$broker->id]) }}">{{ $broker->id }}</a>
                                        </td>
                                        <td>
                                            <a href="{{ route('brokers.show', [$broker->id]) }}">{{ $broker['full_name'] !== null ? $broker['full_name'] : '-' }}</a>
                                        </td>
                                        <td>{{ $broker['phone'] !== null ? $broker['phone'] : '-' }}</td>
                                        <td>{{ $broker['email'] !== null ? $broker['email'] : '-' }}</td>
                                        <td>{{ $broker['type'] !== null ? $broker['type'] : '-' }}</td>
                                        <td>{{ $broker['company'] !== null ? $broker['company'] : '-' }}</td>
                                        <td>{{ date('d/m/Y h:i A', strtotime($broker['created_at'])) }}</td>
                                        <td>
                                            <ul class="nav nav-pills ml-auto p-2">
                                                <li class="nav-item dropdown" style="line-height: 1;">
                                                    <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#">
                                                        Actions <span class="caret"></span>
                                                    </a>
                                                    <div class="dropdown-menu">
                                                        @can('show ticket')
                                                            <a class="dropdown-item" tabindex="-1"
                                                               href="{{ route('tickets.show', [$broker->id]) }}"><i
                                                                    class="fas fa-info-circle"></i> Details</a>
                                                        @endcan
                                                        <div class="dropdown-divider"></div>
                                                        @can('delete ticket')
                                                            <a class="dropdown-item" tabindex="-1" href="#"
                                                               onclick="deleteBroker({{$broker->id}})"
                                                               style="color: red;">
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
                            </table>
                        </div>

                    <!-- Changing from here -->
                    <div class="row mt-3">
                        <div class="col-sm-12 col-md-5">
                            <div class="dataTables_info" role="status" aria-live="polite">
                                Showing {{ $brokers->firstItem() }} to {{ $brokers->lastItem() }}
                                of {{ $brokers->total() }} entries
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-7">
                            <div class="dataTables_paginate float-right paging_simple_numbers">
                                {{ $brokers->links() }}
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

        async function deleteBroker(id) {
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


                        let resp = await axios.delete('/brokers/delete/' + id, {
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
                                text: 'The broker has been deleted.',
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
