<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title>Leads CRM - Management Dashboard</title>
    <meta property="og:title" content="Leads CRM">
    <meta property="og:description" content="Manage you Leads from different sources using our advanced CRM">
    <meta property="og:image" content="{{ asset('dist/img/leads-logo-bg.png') }}">
    <meta property="og:url" content="{{ env('APP_URL') }}">
    <meta property="og:type" content="website">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">

    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">

    <link rel="stylesheet" href="{{ asset('plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <!-- Theme style -->

    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="{{ asset('plugins/overlayScrollbars/css/OverlayScrollbars.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('dist/css/adminlte.min.css') }}">
    <!-- Google Font: Source Sans Pro -->
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="{{ asset('plugins/sweetalert2/sweetalert2.min.css') }}">
    <!-- daterange picker -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css"/>

    <style>
        .custom-map-control-button {
            appearance: button;
            background-color: #fff;
            border: 0;
            border-radius: 2px;
            box-shadow: 0 1px 4px -1px rgba(0, 0, 0, 0.3);
            cursor: pointer;
            margin: 10px;
            padding: 0 0.5em;
            height: 40px;
            font: 400 18px Roboto, Arial, sans-serif;
            overflow: hidden;
        }

        .custom-map-control-button:hover {
            background: #ebebeb;
        }

        sup {
            color: red !important;
        }
    </style>

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('dist/img/favicons/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('dist/img/favicons/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('dist/img/favicons/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('dist/img/favicons/site.webmanifest') }}">
</head>
<body class=" hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed
    ">
<div class="wrapper">
    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ auth()->user()->hasAnyRole(['super-admin', 'sales-manager']) ? URL::to('/?from=' . date('Y-m-01') . '&to=' . date("Y-m-d")) : URL::to('/') }}"
                   class="nav-link"><i class="fas fa-chart-area"></i> Reports</a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a class="nav-link"
                   href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    <i class="fas fa-sign-out-alt"></i> {{ __('Logout') }}
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST"
                      style="display: none;">
                    @csrf
                </form>
            </li>
        </ul>

    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="{{ auth()->user()->hasAnyRole(['super-admin', 'sales-manager']) ? URL::to('/?from=' . date('Y-m-01') . '&to=' . date("Y-m-d")) : URL::to('/') }}"
           class="brand-link">
            <img src="{{ asset('dist/img/leads-logo-bg.png') }}" alt="AdminLTE Logo"
                 class="brand-image img-circle elevation-3"
                 style="opacity: .8">
            <span class="brand-text font-weight-light"><strong
                    style="font-weight: bold;">Leads</strong> <strong>CRM</strong></span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel (optional) -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="{{ asset('dist/img/user2-160x160.jpg') }}" class="img-circle elevation-2"
                         alt="User Image">
                </div>
                <div class="info">
                    <a href="#" class="d-block">{{ auth()->user()->name }}</a>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                    data-accordion="false">
                    <!-- Add icons to the links using the .nav-icon class
                         with font-awesome or any other icon font library -->
                    <li class="nav-item">
                        <a href="{{ auth()->user()->hasAnyRole(['super-admin', 'sales-manager']) ? URL::to('/?from=' . date('Y-m-01') . '&to=' . date("Y-m-d")) : URL::to('/') }}"
                           class="nav-link {{ Route::currentRouteName() == ''  ? 'active' : ''}}">
                            <i class="nav-icon fas fa-home"></i>
                            <p>
                                Reports
                            </p>
                        </a>
                    </li>

                    @if(auth()->user()->hasRole('super-admin'))
                        <li class="nav-item has-treeview {{ (strpos(Route::currentRouteName(), 'users') !== false)  ? 'menu-open' : '' }}">
                            <a href="#"
                               class="nav-link {{ strpos(Route::currentRouteName(), 'users') !== false  ? 'active' : ''}}">
                                <i class="nav-icon fas fa-user"></i>
                                <p>
                                    Users
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('users.all') }}"
                                       class="nav-link {{ strpos(Route::currentRouteName(), 'users.all') !== false  ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Show All</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('users.create') }}"
                                       class="nav-link {{ strpos(Route::currentRouteName(), 'users.create') !== false  ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Add New User</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif
                    <li class="nav-item has-treeview {{ (strpos(Route::currentRouteName(), 'tickets') !== false && strpos(Route::currentRouteName(), 'showImports') === false)  ? 'menu-open' : '' }}">
                        <a href="#"
                           class="nav-link {{ strpos(Route::currentRouteName(), 'tickets') !== false && strpos(Route::currentRouteName(), 'showImports') === false  ? 'active' : ''}}">
                            <i class="nav-icon fas fa-ticket-alt"></i>
                            <p>
                                Leads Center
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                @can('list tickets')
                                    <a href="{{ route('tickets.all', [ 'from' => date('Y-m-01'), 'to' => date("Y-m-d")]) }}"
                                       class="nav-link {{ strpos(Route::currentRouteName(), 'tickets.all') !== false  ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Show All</p>
                                    </a>
                                @endcan
                                @can('list tickets')
                                    <a href="/tickets/all?fstatus=re-shuffled"
                                       class="nav-link {{ strpos(Route::currentRouteName(), 're-shuffled') !== false  ? 'active' : '' }}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Re-shuffled Leads</p>
                                    </a>
                                @endcan
                                @can('create invoice')
                                    @if(auth()->user()->hasRole('accountant'))
                                        <a href="{{ route('tickets.all') }}"
                                           class="nav-link {{ strpos(Route::currentRouteName(), 'tickets.all') !== false  ? 'active' : ''}}">
                                            <i class="far fa-circle nav-icon"></i>
                                            <p>Show All</p>
                                        </a>
                                    @endif
                                @endcan
                            </li>
                            @can('add ticket')
                                <li class="nav-item">
                                    <a href="{{ route('tickets.create') }}"
                                       class="nav-link {{ strpos(Route::currentRouteName(), 'tickets.create') !== false  ? 'active' : ''}}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Add New Lead</p>
                                    </a>
                                </li>
                            @endcan
                            @hasrole('super-admin')
                            <li class="nav-item">
                                <a href="{{ route('tickets.archived') }}"
                                   class="nav-link {{ strpos(Route::currentRouteName(), 'tickets.archived') !== false  ? 'active' : ''}}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Archived Leads</p>
                                </a>
                            </li>
                            @endhasrole
                        </ul>
                    </li>

                    @hasrole('super-admin')
                    <li class="nav-item has-treeview {{ (strpos(Route::currentRouteName(), 'showImports') !== false)  ? 'menu-open' : '' }}">
                        <a href="#"
                           class="nav-link {{ strpos(Route::currentRouteName(), 'showImports') !== false  ? 'active' : ''}}">
                            <i class="nav-icon fas fa-download"></i>
                            <p>
                                Leads Import
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">

                            @can('excel import')
                                <li class="nav-item">
                                    <a href="{{ route('tickets.showImports', ['excel']) }}"
                                       class="nav-link {{ strpos(Route::currentRouteName(), 'tickets.showImports') !== false && strpos(request()->route('source'), 'excel') !== false  ? 'active' : ''}}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Excel import</p>
                                    </a>
                                </li>
                            @endcan
                            @can('facebook import')
                                <li class="nav-item">
                                    <a href="{{ route('tickets.showImports', ['facebook']) }}"
                                       class="nav-link {{ strpos(Route::currentRouteName(), 'tickets.showImports') !== false && strpos(request()->route('source'), 'facebook') !== false  ? 'active' : ''}}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>Facebook import</p>
                                    </a>
                                </li>
                            @endcan

                            @can('facebook import')
                                <li class="nav-item">
                                    <a href="{{ route('tickets.showImports', ['tiktok']) }}"
                                       class="nav-link {{ strpos(Route::currentRouteName(), 'tickets.showImports') !== false && strpos(request()->route('source'), 'tiktok') !== false ? 'active' : ''}}">
                                        <i class="far fa-circle nav-icon"></i>
                                        <p>TikTok import</p>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>

                    <li class="nav-item has-treeview {{ (strpos(Route::currentRouteName(), 'salesCamps') !== false)  ? 'menu-open' : '' }}">
                        <a href="#"
                           class="nav-link {{ strpos(Route::currentRouteName(), 'salesCamps') !== false  ? 'active' : ''}}">
                            <i class="nav-icon fas fa-map-signs"></i>
                            <p>
                                Sales Campaigns
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('salesCamps.index') }}"
                                   class="nav-link {{ strpos(Route::currentRouteName(), 'salesCamps.index') !== false  ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Show All</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endhasrole
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0 text-dark">@yield('title')</h1>
                    </div><!-- /.col -->
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            @yield('breadcrumb')
                            <!--
                            <li class="breadcrumb-item"><a href="#">Reports</a></li>
                            <li class="breadcrumb-item active">Dashboard v2</li>
                            -->
                        </ol>
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.container-fluid -->
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                @yield('content')
            </div>
        </section>
    </div>

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
        <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->

    <!-- Main Footer -->
    <footer class="main-footer">
        <strong>Copyright &copy; 2021 - {{ date("Y") }} | <a href="#">Leads CRM</a>.</strong>
        All rights reserved.
        <div class="float-right d-none d-sm-inline-block">
            <b>Version</b> {{ env('APP_VERSION') }}
        </div>
    </footer>
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
<!-- Bootstrap 4 -->
<script src="{{ asset('plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<!-- DataTables -->
<script src="{{ asset('plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
<!-- InputMask -->
<script src="{{ asset('plugins/inputmask/min/jquery.inputmask.bundle.min.js') }}"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<!-- date-range-picker -->
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<!-- AdminLTE App -->
<script src="{{ asset('dist/js/adminlte.min.js') }}"></script>
<!-- AdminLTE for demo purposes -->
<script src="{{ asset('dist/js/demo.js') }}"></script>

<script src="https://unpkg.com/axios/dist/axios.min.js"></script>
<!-- Select 2 -->
<script src="{{ asset('plugins/select2/js/select2.min.js') }}"></script>
<!-- Toastr -->
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js')}}"></script>

<script type="module">
    // Import the functions you need from the SDKs you need
    import { initializeApp } from "https://www.gstatic.com/firebasejs/9.0.2/firebase-app.js";
    import { getAnalytics } from "https://www.gstatic.com/firebasejs/9.0.2/firebase-analytics.js";
    import { getMessaging, getToken, onMessage } from "https://www.gstatic.com/firebasejs/9.0.2/firebase-messaging.js";

    var firebaseConfig = {
        apiKey: 'AIzaSyBAjmDC4nj9quHfgqil8cSUEsAUjVk0ZKI',
        authDomain: 'wjhatna-747c4.firebaseapp.com',
        projectId: 'wjhatna-747c4',
        storageBucket: 'wjhatna-747c4.appspot.com',
        messagingSenderId: '323952113414',
        appId: '1:323952113414:web:1e1b329ffe90807d4e5087',
        measurementId: 'G-WLZCRW5X2R',
    };

    window.addEventListener("load", (e) => {
        if (Notification.permission !== "granted") {
            Swal.fire({
                title: 'Pleas allow the notifications.',
                icon: 'info',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'موافق',
            }).then((result) => {
                if (result.value) {
                    initFirebaseMessagingRegistration();
                }
            });
        }
    });

    // Initialize Firebase
    const app = initializeApp(firebaseConfig);
    // const analytics = getAnalytics(app);
    const messaging = getMessaging(app);

    function initFirebaseMessagingRegistration() {
        getToken(messaging, { vapidKey: "BOY5nuv995I4uv81RYIDeH4iXWfNR8aflRYy8Vkrt40a3IfYDSTX1mBhhMUwRPx3FDyWlCqYutJlaRPB4Ocm6AY" })
            .then((token) => {
                axios.post("{{ route('update.token') }}",{
                    _method:"PATCH",
                    token
                }).then(({data})=>{
                    console.log(data)
                }).catch(({response:{data}})=>{
                    console.error(data)
                });

            })
        .catch(function (err) {
            //alert(err);
            console.log("Didn't get notification permission", err);
        });

        onMessage(messaging, (payload) => {
            console.log("Message received. ", payload.data);
            // alert(payload.data.notification);
        });
    }
</script>

@yield('script')
</body>
</html>
