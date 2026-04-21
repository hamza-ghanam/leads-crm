@extends('layouts.app')

@section('title')
    <span class="sourceName"></span> Leads Import
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item active">Leads Import</li>
@endsection

@section('content')

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <h5><i class="icon fas fa-ban"></i> Please fix the following:</h5>
            <ul class="mb-0 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header" style="background-color: {{ config('app.theme_color') }};">
                    <h3 class="card-title text-white mb-0">
                        <i class="fas fa-file-import mr-2"></i>
                        <span class="sourceName"></span> Leads Import
                    </h3>
                    @can('add ticket')
                    <div class="card-tools d-flex align-items-center">
                        {{-- Auto-import toggle --}}
                        <form method="post" name="settings-form" id="settings-form"
                              action="{{ route('settings.update') }}" class="mr-3 mb-0">
                            @csrf
                            @method('PUT')
                            <div class="custom-control custom-switch mb-0">
                                <input type="hidden"
                                       name="settings_values[{{ $auto_import_key }}]"
                                       value="0"/>
                                <input type="checkbox" class="custom-control-input" id="customSwitch1"
                                       name="settings_values[{{ $auto_import_key }}]"
                                       value="1"
                                       @checked(old('settings_values.'.$auto_import_key, $auto_import_value) == '1')/>
                                <label class="custom-control-label text-white" for="customSwitch1">
                                    Auto <span class="sourceName"></span> import
                                </label>
                            </div>
                        </form>

                        <button type="button" onclick="ignoreLeads()" class="btn btn-sm btn-danger mr-2">
                            <i class="fas fa-ban mr-1"></i> Ignore Leads
                        </button>
                        <button type="button" id="start-btn" onclick="importTickets()" class="btn btn-sm btn-success">
                            <i class="fas fa-file-import mr-1"></i> Start Import
                        </button>
                    </div>
                    @endcan
                </div>

                <div class="card-body">
                    <div id="fb-data-table">
                        <form id="form1" method="POST" action="">
                            <div class="table-responsive">
                                <table id="example2" class="table table-hover table-striped align-middle">
                                    <thead class="thead-light">
                                    <tr>
                                        <th style="width:40px;">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" checked value="" id="select-all">
                                                <label id="select-all-lbl" class="form-check-label" for="select-all">
                                                    Deselect all
                                                </label>
                                            </div>
                                        </th>
                                        <th style="width:50px;">#</th>
                                        <th>Campaign</th>
                                        <th>Full Name</th>
                                        <th>Phone</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                        <th>Assign To</th>
                                        <th>Created</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @php $k = 1; @endphp
                                    @foreach($tickets as $key => $ticket)
                                        <tr id="row_{{ $key }}">
                                            <td>
                                                <div class="form-check">
                                                    <input type="checkbox" name="lead_ids" id="lead-{{ $ticket->key }}"
                                                           value="{{ $ticket->key }}" class="form-check-input" checked/>
                                                </div>
                                            </td>
                                            <td>{{ $k }}</td>
                                            <td>{{ $ticket['campaign_name'] ?? '—' }}</td>
                                            <td>{{ $ticket['full_name'] ?? '—' }}</td>
                                            <td>
                                                @if($ticket['phone_number'])
                                                    <a href="tel:{{ $ticket['phone_number'] }}" class="text-reset">
                                                        <i class="fas fa-phone-alt text-muted mr-1"></i>
                                                        {{ $ticket['phone_number'] }}
                                                    </a>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>
                                                @if($ticket['email'])
                                                    <a href="mailto:{{ $ticket['email'] }}" class="text-reset">
                                                        {{ $ticket['email'] }}
                                                    </a>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $ticket->status->name === 'New' ? 'primary' : 'danger' }}">
                                                    {{ $ticket->status->name }}
                                                </span>
                                            </td>
                                            <td>
                                                <select class="form-control form-control-sm select2" required
                                                        name="sales_ids" id="sales_ids_{{ $key }}">
                                                    <option value=""></option>
                                                    @foreach ($sales as $role => $employees)
                                                        <optgroup label="{{ ucfirst(str_replace('-', ' ', $role)) }}">
                                                            @foreach ($employees as $salesEmp)
                                                                <option value="{{ $salesEmp['id'] }}">
                                                                    {{ $salesEmp['name'] }}
                                                                </option>
                                                            @endforeach
                                                        </optgroup>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ date('d/m/Y h:i A', strtotime($ticket['created_at'])) }}
                                                </small>
                                            </td>
                                        </tr>
                                        @php $k++; @endphp
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </form>
                    </div>

                    <div id="loader" style="text-align: center; display: none;">
                        <img src="{{ asset('dist/img/loading2.gif') }}" width="100"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

    <script>
        $(function () {
            $('#example2').DataTable({
                paging: true,
                lengthChange: false,
                searching: false,
                ordering: true,
                info: true,
                autoWidth: false,
                responsive: true,
                pageLength: 50,
            });

            $('.select2').select2({
                placeholder: 'Select sales',
                width: '100%',
            });
        });

        const Toast = Swal.mixin({
            toast: true,
            background: '#E3E5E8',
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
        });

        const selectAll = document.getElementById('select-all');
        selectAll.checked = true;

        selectAll.addEventListener('click', () => {
            const isChecked = selectAll.checked;
            document.querySelectorAll('.form-check-input').forEach(box => { box.checked = isChecked; });
            document.getElementById('select-all-lbl').innerHTML = isChecked ? 'Deselect all' : 'Select all';
        }, false);

        function deleteRow(rowId) {
            const row = document.getElementById(rowId);
            if (row) row.parentNode.removeChild(row);
        }

        const urlString = window.location.href;
        const urlParts  = urlString.replace(/\/\s*$/, '').split('/');
        const source    = urlParts.at(-1);
        const sourceLabel = source.charAt(0).toUpperCase() + source.slice(1);

        document.querySelectorAll('.sourceName').forEach(elem => {
            elem.textContent = sourceLabel;
        });

        document.getElementById('customSwitch1').addEventListener('change', async (e) => {
            const form     = document.forms['settings-form'];
            const formData = new FormData(form);

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });

                const jsonRes = await res.json();

                Toast.fire({ icon: 'success', title: jsonRes.message });
            } catch (err) {
                console.error(err);
                Toast.fire({ icon: 'error', title: 'Failed to save setting.' });
            }
        });

        async function importTickets() {
            document.getElementById('fb-data-table').style.display = 'none';
            document.getElementById('loader').style.display = '';

            const token = '{{ csrf_token() }}';

            try {
                const form     = document.getElementById('form1');
                const formData = new FormData(form);

                const leadIds  = formData.getAll('lead_ids').filter(id => id !== '');
                const salesIds = formData.getAll('sales_ids').filter(id => id !== '');

                if (leadIds.length === 0 && salesIds.length === 0) {
                    document.getElementById('loader').style.display = 'none';
                    document.getElementById('fb-data-table').style.display = '';
                    Toast.fire({ icon: 'warning', title: 'No leads for now!' });
                } else if (salesIds.length !== leadIds.length) {
                    document.getElementById('loader').style.display = 'none';
                    document.getElementById('fb-data-table').style.display = '';
                    Toast.fire({ icon: 'warning', title: 'Please select sales for all leads!' });
                } else {
                    let data = { details: {}, manual: 'Manual' };

                    for (let i = 0; i < leadIds.length; i++) {
                        data.details[leadIds[i]] = salesIds[i];
                    }

                    let resp = await axios.post('/tickets/importLeads/' + source, data, {
                        withCredentials: true,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-Token': token,
                        }
                    });

                    let result = resp.data;

                    if (result.OK) {
                        document.getElementById('loader').style.display = 'none';
                        document.getElementById('fb-data-table').style.display = '';
                        const table = $('#example2').DataTable();
                        table.clear().draw();

                        let title = result.OK > 0
                            ? result.OK + ' ' + sourceLabel + ' lead' + (result.OK > 1 ? 's have' : ' has') + ' been successfully imported'
                            : 'No leads for now!';

                        Toast.fire({ icon: 'success', title });
                        setTimeout(() => window.location.reload(), 2000);
                    }
                }
            } catch (e) {
                const errors = Object.values(e.response?.data ?? {}).join('\n') || 'An unexpected error occurred.';
                Toast.fire({ icon: 'warning', title: errors });
                document.getElementById('fb-data-table').style.display = '';
            }
        }

        async function ignoreLeads() {
            try {
                const form     = document.getElementById('form1');
                const formData = new FormData(form);
                const leadIds  = formData.getAll('lead_ids');

                if (leadIds.length === 0) {
                    Toast.fire({ icon: 'warning', title: 'No selected leads!' });
                    return;
                }

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
                    if (!result.value) return;

                    const token = '{{ csrf_token() }}';

                    let resp = await axios.put('/tickets/ignoreLeads/temp', { lead_ids: leadIds }, {
                        withCredentials: true,
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-Token': token,
                        }
                    });

                    let response = resp.data;

                    if (response.OK) {
                        let title = response.OK > 0
                            ? response.OK + ' lead' + (response.OK > 1 ? 's have' : ' has') + ' been successfully ignored'
                            : 'No leads for now!';

                        Toast.fire({ icon: 'success', title });
                        setTimeout(() => window.location.reload(), 2000);
                    }
                });
            } catch (e) {
                const data = e.response?.data;
                let msgs = [];
                if (data?.errors) {
                    msgs = Object.values(data.errors).flat();
                } else if (data?.message) {
                    msgs = [data.message];
                } else {
                    msgs = ['Request failed. Please try again.'];
                }
                Toast.fire({ icon: 'warning', title: msgs.join('\n') });
            }
        }
    </script>
@endsection
