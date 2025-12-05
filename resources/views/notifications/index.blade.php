@extends('layouts.app')

@section('title')
    Your Notifications
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ URL::to('/') }}">Home</a></li>
    <li class="breadcrumb-item">Notifications</li>
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

    <div class="container">
        @forelse($notifications as $notif)
            <div class="card mb-2 {{ $notif->is_read ? '' : 'border-primary' }}"
                 @if(!$notif->is_read)
                     style="background-color: #f5f7fa;"
                @endif>
                <div class="card-body">
                    <h5 class="{{ $notif->is_read ? '' : 'font-weight-bold' }}">
                        <a href="{{ route('notifications.show', $notif->id) }}"
                           style="text-decoration: none; color: inherit;">
                            {{ $notif->title }}
                        </a>
                    </h5>

                    <p class="mb-2">
                        <a href="{{ route('notifications.show', $notif->id) }}"
                           style="text-decoration: none; color: inherit;">
                            {{ $notif->body }}
                        </a>
                    </p>

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            @if(!$notif->is_read)
                                {{ $notif->is_read }}<span class="badge badge-info ml-2">New</span>
                            @endif
                        </div>

                        <small class="text-muted">
                            {{ $notif->created_at->diffForHumans() }}
                        </small>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-muted">No notifications available.</p>
        @endforelse

        @if($notifications->hasPages())
            <div class="mt-3">
                {{-- تحسين عرض الروابط حول الصفحة الحالية --}}
                {{ $notifications->onEachSide(1)->links() }}
            </div>
        @endif
    </div>

@endsection
