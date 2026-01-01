@extends('layouts.app')

@section('title')
    Sales Campaigns list
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item">Sales Campaigns list</li>
@endsection

@section('content')
    <!-- Info boxes -->
    <div class="row">
        <!-- fix for small devices only -->
        <div class="clearfix hidden-md-up"></div>
    </div>
    <!-- /.row -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <a href="javascript:void(0);" class="btn btn-primary" id="add-sacamp">Add</a>
                    <form method="post" name="settings-form" id="settings-form" class="float-right" action="{{ route('settings.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="hidden"
                                       name="settings_values[{{ $camp_assign_key }}]"
                                       value="0">

                                <input type="checkbox" class="custom-control-input" id="customSwitch1"
                                       name="settings_values[{{ $camp_assign_key }}]"
                                       value="1"
                                    @checked(
                                        old('settings_values.'.$camp_assign_key, $camp_assign_value) == '1'
                                    )
                                />
                                <label class="custom-control-label" for="customSwitch1">Enable shuffle based on campaigns</label>
                            </div>
                        </div>
                    </form>
                </div>
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
                    <table id="example2" class="table table-bordered table-hover">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Sales name</th>
                            <th>Campaign name</th>
                            <th>Assigned at</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($salesCamps as $key => $salesCamp)
                            <tr>
                                <td>{{ $key + 1 }}</td>
                                <td><a target="_blank"
                                       href="{{ route('users.edit', [$salesCamp->user_id]) }}"> {{ $salesCamp->user->name }}</a>
                                </td>
                                <td>{{ $salesCamp->campaign_name }}</td>
                                <td>{{ date('d/m/Y h:i A', strtotime($salesCamp->created_at)) }}</td>
                                <td>
                                    <a class="btn btn-danger" tabindex="-1" href="javascript:void(0);"
                                       onclick="deleteSalesCamps({{ $salesCamp->id }})">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <div class="modal fade" id="modal-lg">
                        <form name="ff" action="{{ route('salesCamps.store') }}" method="post">
                            @csrf
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h4 class="modal-title">Assign sales to campaign</h4>
                                        <button type="button" class="close" data-dismiss="modal"
                                                aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="client-unit">Campaign name</label><sup>*</sup>
                                            <input type="text" class="form-control form-control-sm" name="campaign_name" id="campaign_name"
                                                   value="{{ old('campaign_name') }}" />
                                        </div>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label for="client-unit">Sales employee</label><sup>*</sup>
                                            <select name="sales" id="sales" class="form-control" required>
                                                <optgroup label="Sales">
                                                    @foreach($sales as $user)
                                                        @if ($user->getRoleNames()[0] === 'sale')
                                                            <option value="{{$user->id}}">
                                                                {{$user->name}}
                                                            </option>
                                                        @endif
                                                    @endforeach
                                                </optgroup>
                                                <optgroup label="Tele-Sales">
                                                    @foreach($sales as $user)
                                                        @if ($user->getRoleNames()[0] === 'tele-sale')
                                                            <option value="{{$user->id}}">
                                                                {{$user->name}}
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
                        </form>
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

    <script>
        document.getElementById('customSwitch1').addEventListener('change', async (e) => {
            const form = document.forms['settings-form'];
            const formData = new FormData(form);

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {'X-Requested-With': 'XMLHttpRequest'},
                });

                const jsonRes = await res.json();

                // show success notification
                Toast.fire({
                    icon: 'success',
                    title: jsonRes.message
                });
            } catch (err) {
                console.error(err);
                alert('Failed to save setting.');
            }
        });

        $(function () {
            $('#example2').DataTable({
                "paging": true,
                "lengthChange": false,
                "searching": false,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "pageLength": 15
            });
        });

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });

        @if(session()->has('successMsg'))
            Toast.fire({
                icon: 'success',
                title: '{{ session()->get('successMsg') }}'
            });
        @endif

        async function deleteSalesCamps(id) {
            if (confirm('Are you sure?')) {
                const token = '{{ csrf_token() }}';

                try {
                    let response = await fetch('/salesCamps/delete/' + id, {
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
                    } else if (response.OK) {
                        location.reload();
                    }
                } catch (e) {
                    console.log(e);
                }
            }
        }

        document.getElementById('add-sacamp').addEventListener('click', () => {
            $('#modal-lg').modal('show');
        }, false);

        {{ Session::forget('successMsg') }}
    </script>
@endsection
