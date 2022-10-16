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
                        <a href="#" id="start-btn" onclick="importTickets()" class="btn btn-primary">Start import</a>
                    @endcan
                </div>
                <!-- /.card-header -->
                <div class="card-body">
                    <div id="fb-data-table">
                        <table id="example2" class="table table-bordered table-hover">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>AD Name</th>
                                <th>AD Set Name</th>
                                <th>Campaign Name</th>
                                <th>Full Name</th>
                                <th>Phone Number</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Created at</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($tickets as $key => $ticket)
                                <tr>
                                    <td>{{ $key }}</td>
                                    <td>{{ $ticket['ad_name'] !== null ? $ticket['ad_name'] : '-' }}</td>
                                    <td>{{ $ticket['adset_name'] !== null ? $ticket['adset_name'] : '-' }}</td>
                                    <td>{{ $ticket['campaign_name'] !== null ? $ticket['campaign_name'] : '-' }}</td>
                                    <td>{{ $ticket['full_name'] !== null ? $ticket['full_name'] : '-' }}</td>
                                    <td>{{ $ticket['phone_number'] !== null ? $ticket['phone_number'] : '-' }}</td>
                                    <td>{{ $ticket['email'] !== null ? $ticket['email'] : '-' }}</td>
                                    <td>
                                        <span class="badge bg-primary">New</span>
                                    </td>
                                    <td>{{ date('d/m/Y h:i A', strtotime($ticket['created_time'])) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
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
                "pageLength": 15
            });
        });

        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });

        async function deleteTicket(id) {
            if (confirm('Are you sure?')) {
                const token = '{{ csrf_token() }}';
                try {
                    let response = await fetch('/tickets/delete/' + id, {
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
                        console.log(response);
                    } else if (response.OK) {
                        location.reload();
                    }
                } catch (e) {
                    console.log(e);
                }
            }
        }

        const urlString = window.location.href;
        const urlParts = urlString.replace(/\/\s*$/,'').split('/');
        const source = urlParts.at(-1);

        const sourceLabel = source.charAt(0).toUpperCase() + source.slice(1);

        document.getElementById('sourceName').innerHTML = sourceLabel;

        async function importTickets() {
            document.getElementById('fb-data-table').style.display = 'none';
            document.getElementById('loader').style.display = '';

            const token = '{{ csrf_token() }}';
            try {
                let response = await fetch('/tickets/importLeads/' + source, {
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
                    console.log(response);
                } else if (response.OK && response.OK > 0) {
                    document.getElementById('loader').style.display = 'none';
                    document.getElementById('example2').style.display = '';

                    Toast.fire({
                        icon: 'success',
                        title: response.OK + ' ' + sourceLabel + ' leads ' + (response.OK > 1 ? 'have' : 'has') + ' been successfully imported'
                    })
                } else if (response.OK == 0) {
                    document.getElementById('loader').style.display = 'none';
                    document.getElementById('example2').style.display = '';

                    Toast.fire({
                        icon: 'warning',
                        title: 'No leads for now!'
                    })
                }
            } catch (e) {
                console.log(e);
            }
        }
    </script>
@endsection
