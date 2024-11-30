@extends('layouts.app')

@section('title')
    Import from Excel file
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item"><a href="{{ URL::to('/tickets') }}">Leads</a></li>
    <li class="breadcrumb-item">Import from Excel file</li>
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
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
                    <form name="f1" id="f1" action="{{ route('tickets.excel') }}" method="post"
                          enctype="multipart/form-data">
                        @csrf
                        <fieldset style="width: 50%">
                            <div class="form-group">
                                <label for="temp" class="text-lg">To download Excel file template</label>
                                <a class="text-lg" href="{{ route('tickets.download', ['type' => 'excel_temp', 'id' => 0]) }}"
                                   target="_blank"/>Click here</a>
                            </div>
                            <div class="form-group">
                                <label for="file">Select Excel (CSV or XLSX) file</label>
                                <input type="file" class="form-control" id="fileUpload" name="file"
                                       value="{{ old('file') }}"/>
                            </div>
                            <button type="submit" id="b1" name="b1" class="btn btn-success mr-4 submit-btn">View
                            </button>
                            @isset($leads)
                                <button type="submit" id="b2" name="b2" class="btn btn-primary mr-4 submit-btn">
                                    Save to archive
                                </button>
                                <button type="submit" id="b3" name="b3" class="btn btn-secondary submit-btn">
                                    Save to center
                                </button>
                            @endisset
                            <input type="hidden" name="operation" id="operation" value="view"/>
                        </fieldset>
                        <hr/>
                        <div id="fb-data-table">
                            <table id="example2" class="table table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Campaign Name</th>
                                    <th>Full Name</th>
                                    <th>Phone Number</th>
                                    <th>Email</th>
                                    <th>Created time</th>
                                </tr>
                                </thead>
                                <tbody>
                                @isset($leads)
                                    @foreach($leads as $key => $lead)
                                        <tr>
                                            <td>{{ $key+1 }}</td>
                                            <td>{{ $lead['campaign_name'] !== null ? $lead['campaign_name'] : '-' }}</td>
                                            <td>{{ $lead['full_name'] !== null ? $lead['full_name'] : '-' }}</td>
                                            <td>{{ $lead['phone_number'] !== null ? $lead['phone_number'] : '-' }}</td>
                                            <td>{{ $lead['email'] !== null ? $lead['email'] : '-' }}</td>
                                            <td>{{ $lead['created_time'] !== null ? date('d/m/Y h:i A', strtotime($lead['created_time'])) : '-' }}</td>
                                        </tr>
                                    @endforeach
                                @endisset
                                </tbody>
                            </table>
                        </div>
                        <div id="loader" style="text-align: center; display: none;">
                            <img src="{{asset('dist/img/loading2.gif')}}" width="100"/>
                        </div>
                    </form>
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
        });

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });

        let clickedBtn = '';

        const b1 = document.getElementById('b1');
        const b2 = document.getElementById('b2');

        document.querySelectorAll('.submit-btn').forEach(item => {
            item.addEventListener('click', function (e) {
                const target = e.target;
                clickedBtn = target.name;
                if (clickedBtn === 'b1') {
                    document.getElementById('operation').value = 'view';
                } else if (clickedBtn === 'b2') {
                    document.getElementById('operation').value = 'archive';
                } else {
                    document.getElementById('operation').value = 'lead';
                }
            }, false);
        });

        {{--async function viewFromExel() {--}}
        {{--    document.getElementById('fb-data-table').style.display = 'none';--}}
        {{--    document.getElementById('loader').style.display = '';--}}
        {{--    const formData = new FormData();--}}
        {{--    const fileField = document.querySelector('input[type="file"]');--}}
        {{--    formData.append('file', fileField.files[0]);--}}

        {{--    const token = '{{ csrf_token() }}';--}}
        {{--    try {--}}
        {{--        let response = await fetch('/tickets/viewExelLeads/', {--}}
        {{--            credentials: 'same-origin',--}}
        {{--            method: 'POST',--}}
        {{--            headers: {--}}
        {{--                "X-CSRF-TOKEN": token--}}
        {{--            },--}}
        {{--            body: formData--}}
        {{--        });--}}

        {{--        response = await response.json();--}}
        {{--        console.log(response);--}}
        {{--        if (response.error) {--}}
        {{--            alert(response.error);--}}
        {{--            console.log(response);--}}
        {{--        } else if (response.OK && response.OK > 0) {--}}
        {{--            document.getElementById('loader').style.display = 'none';--}}
        {{--            document.getElementById('example2').style.display = '';--}}

        {{--            Toast.fire({--}}
        {{--                icon: 'success',--}}
        {{--                title: response.OK + ' Facebook leads have been successfully imported'--}}
        {{--            })--}}
        {{--        } else if (response.OK == 0) {--}}
        {{--            document.getElementById('loader').style.display = 'none';--}}
        {{--            document.getElementById('example2').style.display = '';--}}

        {{--            Toast.fire({--}}
        {{--                icon: 'warning',--}}
        {{--                title: 'No leads for now!'--}}
        {{--            })--}}
        {{--        }--}}
        {{--    } catch (e) {--}}
        {{--        console.log(JSON.stringify(e));--}}
        {{--    }--}}
        {{--}--}}
    </script>
@endsection
