<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="x-ua-compatible" content="ie=edge">

    <title>Leads CRM - Management Dashboard</title>
    <meta property="og:title" content="Leads CRM">
    <meta property="og:description" content="Manage you Leads from different sources using our advanced CRM">
    <meta property="og:image" content="{{ asset('dist/img/wrsae_thumb.png') }}">
    <meta property="og:url" content="{{ env('APP_URL') }}">
    <meta property="og:type" content="website">

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">

    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <!-- Tempusdominus Bootstrap 4 -->
    <link rel="stylesheet"
          href="{{ asset('plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css') }}">
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
        .sidebar {
            background-color: #2d3955 !important;
        }

        .brand-link {
            background-color: #2d3955 !important;
        }

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
    <link rel="icon" type="image/x-icon" href="{{ asset('dist/img/favicons/favicon.ico') }}">
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

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto" style="margin-right: 20px;">
            <!-- Notifications Dropdown -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-bell"></i>

                    <span
                        id="notif-count-badge"
                        class="badge badge-warning navbar-badge {{ ($headerUnreadCount ?? 0) > 0 ? '' : 'd-none' }}"
                        data-count="{{ $headerUnreadCount ?? 0 }}"
                    >
                        {{ $headerUnreadCount ?? 0 }}
                    </span>

                </a>

                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-item dropdown-header">
                        {{ isset($headerNotifications) ? $headerNotifications->count() : 0 }} Notifications
                    </span>

                    <div class="dropdown-divider"></div>

                    <div id="notif-dropdown-container">
                        @forelse($headerNotifications ?? [] as $notif)
                            <a href="{{ route('notifications.show', $notif->id) }}"
                               class="dropdown-item {{ $notif->is_read ? '' : 'font-weight-bold' }}">
                                <i class="fas fa-info-circle mr-2"></i>
                                {{ $notif->title }}

                                <br>
                                <small class="text-muted">
                                    {{ \Illuminate\Support\Str::limit($notif->body, 40) }}
                                </small>

                                <span class="float-right text-muted text-sm">
                                    {{ $notif->created_at->diffForHumans() }}
                                </span>
                            </a>

                            <div class="dropdown-divider"></div>
                        @empty
                            <span class="dropdown-item text-center text-muted"> No notifications </span>
                        @endforelse
                    </div>
                    <a href="{{ route('notifications.index') }}" class="dropdown-item dropdown-footer">
                        See All Notifications
                    </a>
                </div>
            </li>
        </ul>

    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="{{ auth()->user()->hasAnyRole(['super-admin', 'sales-manager']) ? URL::to('/?from=' . date('Y-m-01') . '&to=' . date("Y-m-d")) : URL::to('/') }}"
           class="brand-link">
            <img src="{{ asset('dist/img/wrsae_thumb.png') }}" alt="AdminLTE Logo"
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
                    <img src="{{ asset('dist/img/user_placeholder.jpg') }}" class="img-circle elevation-2"
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

                    <li class="nav-item has-treeview {{ (strpos(Route::currentRouteName(), 'settings') !== false)  ? 'menu-open' : '' }}">
                        <a href=""
                           class="nav-link {{ strpos(Route::currentRouteName(), 'settings') !== false  ? 'active' : ''}}">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>
                                System Settings
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('settings.index') }}"
                                   class="nav-link {{ strpos(Route::currentRouteName(), 'settings.index') !== false  ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>General</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('settings.status') }}"
                                   class="nav-link {{ strpos(Route::currentRouteName(), 'settings.status') !== false  ? 'active' : '' }}">
                                    <i class="far fa-circle nav-icon"></i>
                                    <p>Status</p>
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
<!-- Tempusdominus Bootstrap 4 -->
<script src="{{ asset('plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>

<!-- Select 2 -->
<script src="{{ asset('plugins/select2/js/select2.min.js') }}"></script>

<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js"></script>
<script src="https://www.gstatic.com/firebasejs/8.10.1/firebase-messaging.js"></script>
<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js')}}"></script>

<script type="module">
    var firebaseConfig = {
        apiKey: "AIzaSyDkHR17YYFalO2XmQJ9xqrg5madLpntIuc",
        authDomain: "wrs-ae-leads.firebaseapp.com",
        projectId: "wrs-ae-leads",
        storageBucket: "wrs-ae-leads.firebasestorage.app",
        messagingSenderId: "1039605684936",
        appId: "1:1039605684936:web:7fd3e40af79c0a2c13e3fd",
    };

    firebase.initializeApp(firebaseConfig);
    const messaging = firebase.messaging();

    // لازم تسجّل الـ service worker
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/firebase-messaging-sw.js')
            .then(function (registration) {
                // console.log("SW registered:", registration);
            })
            .catch((err) => console.log("SW registration failed:", err));
    }

    function initFirebaseMessagingRegistration() {
        messaging.getToken({
            vapidKey: 'BGxpOp_utb49WjMA5lMaOD5IM2n1MX_-i3wymmKnSMHuMIp4JuCMrPcLnfZN9IeJtPVwAcqNjo8eW_D_uWR-lYk'
        }).then((currentToken) => {
            if (currentToken) {
                if (!currentToken) {
                    console.log('No registration token available. Request permission to generate one.');
                    return;
                }

                // 👇 كشف إذا الجهاز موبايل
                var ua = navigator.userAgent || navigator.vendor || window.opera;
                var isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(ua);

                var deviceType = isMobile ? 'mobile-browser' : 'desktop'; // أو 'desktop' بدل 'web' لو حاب تفرق

                var lastToken = localStorage.getItem('fcm_token');
                var lastDeviceType = localStorage.getItem('fcm_device_type');

                // نفس التوكن ونفس النوع؟ لا ترسل شي
                if (lastToken === currentToken && lastDeviceType === deviceType) {
                    return;
                }

                fetch('{{ url('/fcm/token') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        token: currentToken,
                        device_type: deviceType
                    })
                })
                    .then(function (res) {
                        return res.json();
                    })
                    .then(function (data) {
                        console.log('FCM token saved:', data);
                        localStorage.setItem('fcm_token', currentToken);
                        localStorage.setItem('fcm_device_type', deviceType);
                    })
                    .catch(function (err) {
                        console.error('Error saving FCM token:', err);
                    });
            }
        }).catch((err) => {
            console.log('Error retrieving FCM token:', err);
        });
    }

    function askForNotificationPermission() {
        const status = Notification.permission; // granted, default, denied

        // Already enabled
        if (status === 'granted') {
            initFirebaseMessagingRegistration();
            return;
        }

        if (status === 'denied') {
            Swal.fire({
                icon: 'warning',
                title: 'Notifications are blocked!',
                text: 'Please enable notifications manually from your browser settings.',
            });
            return;
        }

        // status === 'default' → نطلب الإذن عبر Swal
        Swal.fire({
            title: 'Enable Notifications?',
            text: "We'll enable notifications permission.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Enable'
        }).then((result) => {
            if (result.value) {
                // ⚠️ مهم جداً: هذا طلب الإذن الآن داخل event ناتج عن المستخدم!
                Notification.requestPermission().then((permission) => {
                    if (permission === 'granted') {
                        initFirebaseMessagingRegistration();

                        Swal.fire(
                            'Enabled!',
                            'Desktop notifications have been enabled successfully.',
                            'success'
                        );
                    } else {
                        Swal.fire(
                            'Not enabled',
                            'Notifications permission was not granted.',
                            'info'
                        );
                    }
                });
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        askForNotificationPermission();
    });

    const Toast = Swal.mixin({
        toast: true,
        background: '#E3E5E8',
        position: 'top-end',
        showConfirmButton: false,
        timer: 4000,
    });

    function updateNotificationCountFromPayload(payload) {
        var badge = document.getElementById('notif-count-badge');
        if (!badge) {
            return;
        }

        var currentCount = parseInt(
            badge.getAttribute('data-count') || badge.textContent || '0',
            10
        );

        var newCount = currentCount;

        if (payload && payload.data && typeof payload.data.unread_count !== 'undefined') {
            newCount = parseInt(payload.data.unread_count, 10);

            if (isNaN(newCount) || newCount < 0) {
                newCount = 0;
            }
        } else {
            newCount = currentCount + 1;
        }

        badge.setAttribute('data-count', newCount);

        if (newCount === 0) {
            badge.classList.add('d-none');
            badge.textContent = '0';
        } else {
            badge.classList.remove('d-none');
            badge.textContent = newCount;
        }
    }

    function prependNotificationToDropdown(payload) {
        var container = document.getElementById('notif-dropdown-container');
        if (!container) return;

        // لو الإشعار بدون عنوان/جسم → تجاهله
        if (!payload?.data?.title) return;

        // تحضير الرابط عبر show()
        var notifId = payload?.data?.notification_id ?? null;
        var notifUrl = notifId
            ? '/notifications/' + notifId      // يمر عبر Laravel → يحدّث is_read & clicked_at
            : (payload?.data?.url ?? '#');

        // نص مختصر للجسم
        var bodyShort = payload.data.body.length > 40
            ? payload.data.body.substring(0, 40) + '...'
            : payload.data.body;

        // بناء HTML للإشعار الجديد (غير مقروء)
        var html = `
        <a href="${notifUrl}" class="dropdown-item font-weight-bold"
           style="background-color: #f5f7fa;">
            <i class="fas fa-info-circle mr-2"></i>
            ${payload.data.title}
            <div class="text-muted text-sm">${bodyShort}</div>
            <span class="float-right text-muted text-sm">Just now</span>
        </a>
        <div class="dropdown-divider"></div>
    `;

        // إضافة الإشعار أعلى القائمة (prepend)
        container.insertAdjacentHTML('afterbegin', html);
    }

    function trimNotificationDropdown(limit = 5) {
        var container = document.getElementById('notif-dropdown-container');
        if (!container) return;

        // اجلب كل العناصر من نوع dropdown-item (كل إشعار)
        var items = container.querySelectorAll('.dropdown-item');

        if (items.length <= limit) {
            return; // تمام، ما في شي لقصّه
        }

        // نحذف الزايد (من آخر القائمة)
        for (var i = limit; i < items.length; i++) {
            var item = items[i];

            // نحذف الـ divider اللي بعدو (إن وجد)
            var divider = item.nextElementSibling;
            if (divider && divider.classList.contains('dropdown-divider')) {
                divider.remove();
            }

            // نحذف الإشعار نفسه
            item.remove();
        }
    }

    // استقبال إشعارات foreground
    messaging.onMessage(function (payload) {
        console.log('Message received. ', payload);

        updateNotificationCountFromPayload(payload);
        prependNotificationToDropdown(payload);
        trimNotificationDropdown(10);

        Swal.fire({
            toast: true,
            title: payload.data.title,
            text: payload.data.body,
            imageUrl: "/dist/img/notif_icon.png",
            imageWidth: 40,
            imageHeight: 40,
            imageAlt: "Notifications",
            position: 'top-end',
            showConfirmButton: false,
            timer: 4000,

            onOpen: (toast) => {
                toast.style.cursor = 'pointer';
                toast.addEventListener('click', () => {
                    if (payload.data.url) {
                        window.open(payload.data.url, '_blank');
                    }
                });
            }
        });
    });

    /*
    window.addEventListener("load", (e) => {
        initFirebaseMessagingRegistration();
    });

    // Initialize Firebase
    const app = initializeApp(firebaseConfig);
    // const analytics = getAnalytics(app);
    const messaging = getMessaging(app);

    function initFirebaseMessagingRegistration() {
        getToken(messaging, { vapidKey: "BLzqPFU-kXeW-UR0UrpP2NZ2TEhBRRxPq-TXhVkJiWBVLPpBtY-8JT-tKGCL0w5sI9weAa_5EMD3_pFpkkTEnhY" })
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
    */
</script>

@yield('script')
</body>
</html>
