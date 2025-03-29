@extends('layouts.app')

@section('title')
    <span id="sourceName"></span> Leads List
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item">Leads import list</li>
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
                        <a href="javascript:void(0);" id="start-btn" onclick="importTickets()" class="btn btn-primary">Start
                            import</a>
                            <a href="javascript:void(0);" id="start-btn" onclick="ignoreLeads()"
                               class="btn btn-danger ml-4">Ignore Leads</a>
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
                                            <input class="form-check-input" type="checkbox" checked value=""
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
                                @foreach($tickets as $key => $ticket)
                                    <tr id="row_{{ $key }}">
                                        <td class="fwd-leads">
                                            <div class="form-check">
                                                <input type="checkbox" name="lead_ids" id="lead-{{ $ticket->key }}"
                                                       value="{{ $ticket->key }}" class="form-check-input" checked/>
                                            </div>
                                        </td>
                                        <td>
                                            {{ $k }}
                                        </td>
                                        <td>{{ $ticket['campaign_name'] !== null ? $ticket['campaign_name'] : '-' }}</td>
                                        <td>{{ $ticket['full_name'] !== null ? $ticket['full_name'] : '-' }}</td>
                                        <td>{{ $ticket['phone_number'] !== null ? $ticket['phone_number'] : '-' }}</td>
                                        <td>{{ $ticket['email'] !== null ? $ticket['email'] : '-' }}</td>
                                        <td>
                                            <span
                                                class="badge bg-{{ $ticket->status->name === 'New' ? 'primary' : 'danger' }}">{{ $ticket->status->name }}</span>
                                        </td>
                                        <td>
                                            <select class="form-control select2" required name="sales_ids" id="sales_ids_{{ $key }}">
                                                <option value=""></option> <!-- Placeholder option -->
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
                                        <td>{{ date('d/m/Y h:i A', strtotime($ticket['created_at'])) }}</td>
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
    <script src="{{ asset('dist/js/pages/dashboard2.js') }}"></script>
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
            });


        });

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });

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
                        //  console.log(e.response);
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

        const urlString = window.location.href;
        const urlParts = urlString.replace(/\/\s*$/, '').split('/');
        const source = urlParts.at(-1);

        const sourceLabel = source.charAt(0).toUpperCase() + source.slice(1);

        document.getElementById('sourceName').innerHTML = sourceLabel;

        async function importTickets() {
            document.getElementById('fb-data-table').style.display = 'none';
            document.getElementById('loader').style.display = '';

            const token = '{{ csrf_token() }}';

            try {
                const form = document.getElementById('form1');
                const formData = new FormData(form);

                const leadIds = formData.getAll('lead_ids').filter(id => id !== '');
                const salesIds = formData.getAll('sales_ids').filter(id => id !== '');

                if (leadIds.length === 0 && salesIds.length === 0) {
                    document.getElementById('loader').style.display = 'none';
                    document.getElementById('example2').style.display = '';
                    document.getElementById('fb-data-table').style.display = '';

                    Toast.fire({
                        icon: 'warning',
                        title: 'No leads for now!'
                    });
                } else if (salesIds.length !== leadIds.length) {
                    document.getElementById('loader').style.display = 'none';
                    document.getElementById('example2').style.display = '';
                    document.getElementById('fb-data-table').style.display = '';

                    Toast.fire({
                        icon: 'warning',
                        title: 'Please select sales for all leads!'
                    });
                } else {
                    let data = {
                        details: {},
                        manual: 'Manual',
                    };

                    for (let i = 0; i < leadIds.length; i++) {
                        data.details[leadIds[i]] = salesIds[i];
                    }

                    //      console.log('data', data)

                    let resp = await axios.post('/tickets/importLeads/' + source, data, {
                        withCredentials: true,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-Token': token,
                        }
                    });

                    let result = resp.data;

                    //  console.log(result);

                    if (result.OK) {
                        document.getElementById('loader').style.display = 'none';
                        document.getElementById('example2').style.display = '';
                        const table = $('#example2').DataTable();
                        document.getElementById('fb-data-table').style.display = '';
                        table.clear().draw();

                        let title = '';
                        if (result.OK > 0) {
                            title = result.OK + ' ' + sourceLabel + ' leads ' + (result.OK > 1 ? 'have' : 'has') + ' been successfully imported';
                        } else if (result.OK && result.OK === 0) {
                            title = 'No leads for now!';
                        }

                        Toast.fire({
                            icon: 'success',
                            title
                        });

                        setTimeout(function () {
                            window.location.reload();
                        }, 2000);
                    }
                }
            } catch (e) {
                //  console.log(e.response);
                let errors = '';
                Object.keys(e.response?.data).forEach(valInd => {
                    errors += (e.response?.data[valInd] + '\n');
                });

                Toast.fire({
                    icon: 'warning',
                    title: errors,
                });

                document.getElementById('fb-data-table').style.display = '';
            }
        }

        async function ignoreLeads() {
            try {
                const form = document.getElementById('form1');
                const formData = new FormData(form);

                const leadIds = formData.getAll('lead_ids');

                if (leadIds.length === 0) {
                    document.getElementById('loader').style.display = 'none';
                    document.getElementById('example2').style.display = '';
                    document.getElementById('fb-data-table').style.display = '';

                    Toast.fire({
                        icon: 'warning',
                        title: 'No selected leads!'
                    });
                } else {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You CANNOT restore ignored leads.",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, proceed',
                        cancelButtonText: 'Cancel'
                    }).then(async (result) => {
                        if (result.value) {
                            const token = '{{ csrf_token() }}';

                            let resp = await axios.put('/tickets/ignoreLeads/', { leadIds }, {
                                withCredentials: true,
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-Token': token,
                                }
                            });

                            let response = resp.data;

                            console.log('result', response);

                            if (response.OK) {
                                let title = '';
                                if (response.OK > 0) {
                                    title = response.OK + ' ' + ' leads ' + (response.OK > 1 ? 'have' : 'has') + ' been successfully ignored';
                                } else if (response.OK && response.OK === 0) {
                                    title = 'No leads for now!';
                                }

                                Toast.fire({
                                    icon: 'success',
                                    title
                                });

                                setTimeout(function () {
                                    window.location.reload();
                                }, 2000);
                            }

                        }
                    });
                }
            } catch (e) {
                //  console.log(e.response);
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

        function deleteTableRows(tid) {
            const table = document.getElementById(tid);
            while (table.rows.length > 0) {
                table.deleteRow(0);
            }
        }
    </script>
@endsection
