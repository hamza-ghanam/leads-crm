@extends('layouts.app')

@section('title')
    Import from Excel
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ route('tickets.all') }}">Leads</a></li>
    <li class="breadcrumb-item active">Import from Excel</li>
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
                        <i class="fas fa-file-excel mr-2"></i> Import from Excel
                    </h3>
                </div>

                <div class="card-body">
                    <form name="f1" id="f1" action="{{ route('tickets.excel') }}" method="post"
                          enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="operation" id="operation" value="view"/>

                        {{-- Template download --}}
                        <div class="alert alert-info d-flex align-items-center">
                            <i class="fas fa-info-circle mr-2"></i>
                            Need the template?
                            <a class="ml-1 font-weight-bold"
                               href="{{ route('tickets.download', ['type' => 'excel_temp', 'id' => 0]) }}"
                               target="_blank">
                                <i class="fas fa-download mr-1"></i> Download Excel template
                            </a>
                        </div>

                        {{-- File input --}}
                        <div class="form-group">
                            <label for="fileUpload">
                                Select Excel File <sup class="text-danger">*</sup>
                                <small class="text-muted ml-1">(CSV or XLSX)</small>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-file-excel"></i></span>
                                </div>
                                <input type="file" class="form-control" id="fileUpload" name="file"
                                       accept=".csv,.xlsx,.xls"/>
                            </div>
                        </div>

                        {{-- Action buttons --}}
                        <div class="d-flex flex-wrap align-items-center mb-3">
                            <button type="submit" id="b1" name="b1" class="btn btn-success submit-btn mr-2 mb-1">
                                <i class="fas fa-eye mr-1"></i> View
                            </button>
                            @isset($leads)
                                <button type="submit" id="b2" name="b2" class="btn btn-primary submit-btn mr-2 mb-1">
                                    <i class="fas fa-archive mr-1"></i> Save to Archive
                                </button>
                                <button type="submit" id="b3" name="b3" class="btn btn-secondary submit-btn mb-1">
                                    <i class="fas fa-save mr-1"></i> Save to Center
                                </button>
                            @endisset
                        </div>

                        <hr/>

                        {{-- Preview table --}}
                        <div id="fb-data-table">
                            <div class="table-responsive">
                                <table id="example2" class="table table-hover table-striped align-middle">
                                    <thead class="thead-light">
                                    <tr>
                                        <th style="width:50px;">#</th>
                                        <th>Campaign Name</th>
                                        <th>Full Name</th>
                                        <th>Phone Number</th>
                                        <th>Email</th>
                                        <th>Created Time</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @isset($leads)
                                        @foreach($leads as $key => $lead)
                                            <tr>
                                                <td>{{ $key + 1 }}</td>
                                                <td>{{ $lead['campaign_name'] ?? '—' }}</td>
                                                <td>{{ $lead['full_name'] ?? '—' }}</td>
                                                <td>
                                                    @if($lead['phone_number'])
                                                        <a href="tel:{{ $lead['phone_number'] }}" class="text-reset">
                                                            <i class="fas fa-phone-alt text-muted mr-1"></i>
                                                            {{ $lead['phone_number'] }}
                                                        </a>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($lead['email'])
                                                        <a href="mailto:{{ $lead['email'] }}" class="text-reset">
                                                            {{ $lead['email'] }}
                                                        </a>
                                                    @else
                                                        —
                                                    @endif
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        {{ $lead['created_time'] ? date('d/m/Y h:i A', strtotime($lead['created_time'])) : '—' }}
                                                    </small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endisset
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div id="loader" style="text-align: center; display: none;">
                            <img src="{{ asset('dist/img/loading2.gif') }}" width="100"/>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        table#example2 td, table#example2 th { vertical-align: middle; }
    </style>
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
        });

        document.querySelectorAll('.submit-btn').forEach(item => {
            item.addEventListener('click', function (e) {
                const name = e.target.name;
                if (name === 'b1') {
                    document.getElementById('operation').value = 'view';
                } else if (name === 'b2') {
                    document.getElementById('operation').value = 'archive';
                } else {
                    document.getElementById('operation').value = 'lead';
                }
            });
        });
    </script>
@endsection
