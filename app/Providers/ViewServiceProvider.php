<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Models\Notification;

class ViewServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $limit = 5;
        View::composer('layouts.app', function ($view) use ($limit) {
            $user = auth()->user();

            if (!$user) {
                return;
            }

            // Latest 10 notifications
            $notifications = Notification::where('user_id', $user->id)
                ->latest()
                ->limit($limit)
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
