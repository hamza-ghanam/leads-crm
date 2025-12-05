@extends('layouts.app')

@section('title')
    <span class="sourceName"></span> Archived Leads List
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item">Archived Leads list</li>
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

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    @can('add ticket')
                        <a href="javascript:void(0);" id="start-btn" onclick="restoreTickets()" class="btn btn-primary">
                            Restore
                        </a>
                        <a href="javascript:void(0);" id="start-btn" onclick="ignoreLeads()"
                           class="btn btn-danger ml-4">Ignore Leads
                        </a>
                    @endcan
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div id="fb-data-table">
                        <form id="form1" method="POST" action="">
                            <table id="example2" class="table table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th class="fwd-leads">
                                        <div class="form-check">
                                            <input class="form-check-input lead-checkbox" type="checkbox" checked
                                                   value=""
                                                   id="select-all">
                                            <label id="select-all-lbl" class="form-check-label" for="flexCheckDefault">
                                                Deselect all
                                            </label>
                                        </div>
                                    </th>
                                    <th>#</th>
                                    <th>Campaign Name</th>
                                    <th>Full Name</th>
                                    <th>Phone Number</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Sales</th>
                                    <th>Created at</th>
                                </tr>
                                </thead>
                                <tbody>

                                @php $k = 1; @endphp
                                @foreach($archivedLeads as $key => $lead)
                                    <tr id="row_{{ $lead->id }}">
                                        <td class="fwd-leads">
                                            <div class="form-check">
                                                <input type="checkbox" name="lead_ids[]"
                                                       id="lead-{{ $lead->id }}"
                                                       value="{{ $lead->id }}"
                                                       class="lead-checkbox form-check-input"
                                                       checked>
                                            </div>
                                        </td>


                                        <td>
                                            {{ $k }}
                                        </td>
                                        <td>{{ $lead->campaign_name !== null ? $lead->campaign_name : '-' }}</td>
                                        <td>{{ $lead->full_name !== null ? $lead->full_name : '-' }}</td>
                                        <td>{{ $lead->phone_number !== null ? $lead->phone_number : '-' }}</td>
                                        <td>{{ $lead->email !== null ? $lead->email : '-' }}</td>
                                        <td>
                                            <span
                                                class="badge bg-{{ $lead->status->name === 'New' ? 'primary' : 'danger' }}">{{ $lead->status->name }}</span>
                                        </td>
                                        <td>
                                            <select class="form-control select2 sales-select"
                                                    required
                                                    name="sales_ids[{{ $lead->id }}]"
                                                    id="sales_ids_{{ $lead->id }}">
                                                <option value=""></option>
                                                @foreach ($sales as $role => $employees)
                                                    <optgroup label="{{ ucfirst(str_replace('-', ' ', $role)) }}">
                                                        @foreach ($employees as $salesEmp)
                                                            <option value="{{ $salesEmp->id }}">
                                                                {{ $salesEmp->name }}
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>{{ date('d/m/Y h:i A', strtotime($lead->created_at)) }}</td>
                                    </tr>
                                    @php $k++; @endphp
                                @endforeach
                                </tbody>
                            </table>
                        </form>
                    </div>
                    <div id="loader" style="text-align: center; display: none;">
                        <img src="{{asset('dist/img/loading2.gif')}}" width="100"/>
                    </div>
                </div>
                <!-- /.card-body -->
            </div>
            <!-- /.card -->
        </div>
    </div>
@endsection

@section('script')
    <!-- PAGE SCRIPTS -->
    <!-- Toastr -->
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js')}}"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script>
        $(function () {
            $('#example2').DataTable({
                "paging": true,
                "lengthChange": false,
                "searching": false,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "pageLength": 50
            });

            $('.select2').select2({
                placeholder: 'Select sales',
                width: '100%',
            });
        });

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });

        const selectAll = document.getElementById('select-all');
        const label = document.getElementById('select-all-lbl');

        selectAll.checked = true;
        label.textContent = 'Deselect all';

        selectAll.addEventListener('click', () => {
            const leadBoxes = document.querySelectorAll('.lead-checkbox');
            const checked = selectAll.checked;

            leadBoxes.forEach(box => box.checked = checked);

            label.textContent = checked ? 'Deselect all' : 'Select all';
        });

        function deleteRow(rowId) {
            const row = document.getElementById(rowId);
            row.parentNode.removeChild(row);
        }

        async function deleteTicket(id) {
            const confirmResult = await Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            });


            if (!confirmResult.value) {
                return;
            }

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
                    const table = $('#example2').DataTable();
                    const row = document.getElementById('row_' + id);

                    if (row) {
                        table.row(row).remove().draw();
                    }

                    Toast.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'The ticket has been deleted.',
                    });
                } else {
                    // لو رجع OK = false أو ما رجع شي واضح
                    Toast.fire({
                        icon: 'warning',
                        title: 'Could not delete the ticket.',
                    });
                }
            } catch (e) {
                //  console.log(e.response);
                Swal.close();

                let errors = '';
                if (e.response && e.response.data) {
                    const errData = e.response.data;

                    if (typeof errData === 'string') {
                        errors = errData;
                    } else {
                        Object.keys(errData).forEach(key => {
                            errors += (errData[key] + '\n');
                        });
                    }
                } else {
                    errors = 'Unexpected error occurred. Please try again.';
                }

                Toast.fire({
                    icon: 'warning',
                    title: errors,
                });
            }


        }

        async function restoreTickets() {
            document.getElementById('fb-data-table').style.display = 'none';
            document.getElementById('loader').style.display = '';

            const token = '{{ csrf_token() }}';

            try {
                const form = document.getElementById('form1');
                const formData = new FormData(form);

                const leadIds = formData.getAll('lead_ids[]').filter(id => id !== '');
                const salesIds = formData.getAll('sales_ids[]').filter(id => id !== '');

                if (leadIds.length === 0 && salesIds.length === 0) {
                    document.getElementById('loader').style.display = 'none';
                    document.getElementById('example2').style.display = '';
                    document.getElementById('fb-data-table').style.display = '';

                    Toast.fire({
                        icon: 'warning',
                        title: 'No leads for now!'
                    });
                    return;
                }

                let data = {
                    details: {},
                    manual: 'Manual',
                };

                for (let i = 0; i < leadIds.length; i++) {
                    const leadId = leadIds[i];

                    const salesId = formData.get(`sales_ids[${leadId}]`);

                    if (!salesId) {
                        document.getElementById('loader').style.display = 'none';
                        document.getElementById('example2').style.display = '';
                        document.getElementById('fb-data-table').style.display = '';

                        Toast.fire({
                            icon: 'warning',
                            title: `Please select sales for all selected leads!`
                        });
                        return;
                    }

                    data.details[leadId] = salesId;
                }

                console.log('data', data);

                let resp = await axios.post('/tickets/restoreLeads/', data, {
                    withCredentials: true,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-Token': token,
                    }
                });

                let result = resp.data;

                if (result.OK !== undefined) {
                    document.getElementById('loader').style.display = 'none';

                    let title = '';
                    if (result.OK > 0) {
                        title = result.OK + ' leads ' + (result.OK > 1 ? 'have' : 'has') + ' been successfully restored';
                        console.log(title)
                    } else if (result.OK === 0) {
                        title = 'No leads for now!';
                    }

                    Toast.fire({
                        icon: 'success',
                        title
                    });

                    setTimeout(function () {
                        window.location.href = window.location.pathname + '?r=' + Date.now();
                    }, 2000);
                }
            } catch (e) {
                Swal.close();

                let errors = '';

                if (e.response && e.response.data) {
                    const errData = e.response.data;

                    if (typeof errData === 'string') {
                        errors = errData;
                    } else {
                        Object.keys(errData).forEach(key => {
                            errors += (errData[key] + '\n');
                        });
                    }
                } else {
                    errors = 'Unexpected error occurred. Please try again.';
                }

                Toast.fire({
                    icon: 'warning',
                    title: errors,
                });
            }
        }

        async function ignoreLeads() {
            const form = document.getElementById('form1');
            const formData = new FormData(form);

            const leadIds = formData.getAll('lead_ids[]').filter(id => id !== '');

            if (leadIds.length === 0) {
                document.getElementById('loader').style.display = 'none';
                document.getElementById('example2').style.display = '';
                document.getElementById('fb-data-table').style.display = '';

                Toast.fire({
                    icon: 'warning',
                    title: 'No selected leads!'
                });

                return;
            }

            const confirmResult = await Swal.fire({
                title: 'Are you sure?',
                text: "You CANNOT restore ignored leads.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, proceed',
                cancelButtonText: 'Cancel'
            });

            if (!confirmResult.value) {
                return;
            }

            const token = '{{ csrf_token() }}';
            try {
                Swal.fire({
                    title: 'Please wait!',
                    imageUrl: '{{ asset('dist/img/loading2.gif') }}',
                    imageWidth: 128,
                    imageHeight: 128,
                    imageAlt: 'Processing...',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                });

                let resp = await axios.put('/tickets/ignoreLeads/archive', {
                        lead_ids: leadIds
                    }, {
                        withCredentials: true,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-Token': token,
                        }
                    });

                let response = resp.data;

                Swal.close();

                console.log('result', response);

                if (response.OK !== undefined) {
                    let title = '';
                    if (response.OK > 0) {
                        title = response.OK + ' ' + ' archived leads ' + (response.OK > 1 ? 'have' : 'has') + ' been successfully ignored';
                    } else if (response.OK && response.OK === 0) {
                        title = 'No leads for now!';
                    }

                    Toast.fire({
                        icon: 'success',
                        title
                    });

                    setTimeout(function () {
                        window.location.href = window.location.pathname + '?r=' + Date.now();
                    }, 2000);
                }
            } catch (e) {
                Swal.close();
                let errors = '';

                if (e.response && e.response.data) {
                    const errData = e.response.data;

                    if (typeof errData === 'string') {
                        errors = errData;
                    } else {
                        Object.keys(errData).forEach(key => {
                            errors += (errData[key] + '\n');
                        });
                    }
                } else {
                    errors = 'Unexpected error occurred. Please try again.';
                }

                Toast.fire({
                    icon: 'warning',
                    title: errors,
                });
                console.log(errors);

            }
        }

        function deleteTableRows(tid) {
            const table = document.getElementById(tid);
            while (table.rows.length > 0) {
                table.deleteRow(0);
            }
        }
    </script>
@endsection
