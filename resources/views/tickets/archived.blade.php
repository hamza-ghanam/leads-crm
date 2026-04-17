@extends('layouts.app')

@section('title')
    Archived Leads
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item active">Archived Leads</li>
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
                <div class="card-header" style="background-color: #2d3955;">
                    <h3 class="card-title text-white mb-0">
                        <i class="fas fa-archive mr-2"></i> Archived Leads
                    </h3>
                    @can('add ticket')
                    <div class="card-tools">
                        <button type="button" onclick="ignoreLeads()" class="btn btn-sm btn-danger mr-2">
                            <i class="fas fa-ban mr-1"></i> Ignore Leads
                        </button>
                        <button type="button" onclick="restoreTickets()" class="btn btn-sm btn-success">
                            <i class="fas fa-undo mr-1"></i> Restore
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
                                                <input class="form-check-input lead-checkbox" type="checkbox"
                                                       checked value="" id="select-all">
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
                                    @foreach($archivedLeads as $key => $lead)
                                        <tr id="row_{{ $lead->id }}">
                                            <td>
                                                <div class="form-check">
                                                    <input type="checkbox" name="lead_ids[]"
                                                           id="lead-{{ $lead->id }}"
                                                           value="{{ $lead->id }}"
                                                           class="lead-checkbox form-check-input" checked/>
                                                </div>
                                            </td>
                                            <td>{{ $k }}</td>
                                            <td>{{ $lead->campaign_name ?? '—' }}</td>
                                            <td>{{ $lead->full_name ?? '—' }}</td>
                                            <td>
                                                @if($lead->phone_number)
                                                    <a href="tel:{{ $lead->phone_number }}" class="text-reset">
                                                        <i class="fas fa-phone-alt text-muted mr-1"></i>
                                                        {{ $lead->phone_number }}
                                                    </a>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>
                                                @if($lead->email)
                                                    <a href="mailto:{{ $lead->email }}" class="text-reset">
                                                        {{ $lead->email }}
                                                    </a>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $lead->status->name === 'New' ? 'primary' : 'danger' }}">
                                                    {{ $lead->status->name }}
                                                </span>
                                            </td>
                                            <td>
                                                <select class="form-control form-control-sm select2 sales-select"
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
                                            <td>
                                                <small class="text-muted">
                                                    {{ date('d/m/Y h:i A', strtotime($lead->created_at)) }}
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
        const Toast = Swal.mixin({
            toast: true,
            background: '#E3E5E8',
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
        });

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

        const selectAll = document.getElementById('select-all');
        const label     = document.getElementById('select-all-lbl');

        selectAll.checked  = true;
        label.textContent  = 'Deselect all';

        selectAll.addEventListener('click', () => {
            const checked = selectAll.checked;
            document.querySelectorAll('.lead-checkbox').forEach(box => box.checked = checked);
            label.textContent = checked ? 'Deselect all' : 'Select all';
        });

        function deleteRow(rowId) {
            const row = document.getElementById(rowId);
            if (row) row.parentNode.removeChild(row);
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

            if (!confirmResult.value) return;

            const token = '{{ csrf_token() }}';

            try {
                Swal.fire({
                    title: 'Please wait!',
                    imageUrl: '{{ asset('dist/img/loading2.gif') }}',
                    imageWidth: 128,
                    imageHeight: 128,
                    imageAlt: 'Deleting..',
                    showConfirmButton: false,
                });

                const resp   = await axios.delete('/tickets/delete/' + id, {
                    withCredentials: true,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-Token': token,
                    }
                });
                const result = resp.data;

                Swal.close();

                if (result.OK) {
                    const table = $('#example2').DataTable();
                    const row   = document.getElementById('row_' + id);
                    if (row) table.row(row).remove().draw();
                    Toast.fire({ icon: 'success', title: 'Deleted successfully!' });
                } else {
                    Toast.fire({ icon: 'warning', title: 'Could not delete the ticket.' });
                }
            } catch (e) {
                Swal.close();
                const errors = Object.values(e.response?.data ?? {}).join('\n') || 'Unexpected error occurred.';
                Toast.fire({ icon: 'warning', title: errors });
            }
        }

        async function restoreTickets() {
            document.getElementById('fb-data-table').style.display = 'none';
            document.getElementById('loader').style.display = '';

            const token    = '{{ csrf_token() }}';
            const form     = document.getElementById('form1');
            const formData = new FormData(form);
            const leadIds  = formData.getAll('lead_ids[]').filter(id => id !== '');

            if (leadIds.length === 0) {
                document.getElementById('loader').style.display = 'none';
                document.getElementById('fb-data-table').style.display = '';
                Toast.fire({ icon: 'warning', title: 'No leads selected!' });
                return;
            }

            let data = { details: {}, manual: 'Manual' };

            for (const leadId of leadIds) {
                const salesId = formData.get(`sales_ids[${leadId}]`);
                if (!salesId) {
                    document.getElementById('loader').style.display = 'none';
                    document.getElementById('fb-data-table').style.display = '';
                    Toast.fire({ icon: 'warning', title: 'Please select sales for all selected leads!' });
                    return;
                }
                data.details[leadId] = salesId;
            }

            try {
                const resp   = await axios.post('/tickets/restoreLeads/', data, {
                    withCredentials: true,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-Token': token,
                    }
                });
                const result = resp.data;

                if (result.OK !== undefined) {
                    document.getElementById('loader').style.display = 'none';
                    const title = result.OK > 0
                        ? result.OK + ' lead' + (result.OK > 1 ? 's have' : ' has') + ' been successfully restored'
                        : 'No leads for now!';
                    Toast.fire({ icon: 'success', title });
                    setTimeout(() => window.location.href = window.location.pathname + '?r=' + Date.now(), 2000);
                }
            } catch (e) {
                Swal.close();
                const errors = Object.values(e.response?.data ?? {}).join('\n') || 'Unexpected error occurred.';
                Toast.fire({ icon: 'warning', title: errors });
            }
        }

        async function ignoreLeads() {
            const form     = document.getElementById('form1');
            const formData = new FormData(form);
            const leadIds  = formData.getAll('lead_ids[]').filter(id => id !== '');

            if (leadIds.length === 0) {
                Toast.fire({ icon: 'warning', title: 'No selected leads!' });
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

            if (!confirmResult.value) return;

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

                const resp     = await axios.put('/tickets/ignoreLeads/archive', { lead_ids: leadIds }, {
                    withCredentials: true,
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-Token': token,
                    }
                });
                const response = resp.data;

                Swal.close();

                if (response.OK !== undefined) {
                    const title = response.OK > 0
                        ? response.OK + ' archived lead' + (response.OK > 1 ? 's have' : ' has') + ' been successfully ignored'
                        : 'No leads for now!';
                    Toast.fire({ icon: 'success', title });
                    setTimeout(() => window.location.href = window.location.pathname + '?r=' + Date.now(), 2000);
                }
            } catch (e) {
                Swal.close();
                const errors = Object.values(e.response?.data ?? {}).join('\n') || 'Unexpected error occurred.';
                Toast.fire({ icon: 'warning', title: errors });
            }
        }
    </script>
@endsection
