<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Models\Notification;

class ViewServiceProvider extends ServiceProvider
{
    public function boot()
    {
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();

            if (!$user) {
                return;
            }

            // Latest 10 notifications
            $notifications = Notification::where('user_id', $user->id)
                ->latest()
                ->limit(10)
                ->get();

            // Count unread notifications
            $unread = Notification::where('user_id', $user->id)
                ->where('is_read', false)
                ->count();

            $view->with([
                'headerNotifications' => $notifications,
                'headerUnreadCount'   => $unread,
            ]);
        });
    }
}
