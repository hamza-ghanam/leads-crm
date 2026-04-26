<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Response;

class NotificationController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('is_read')        // unread (0) أولاً، ثم read (1)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('notifications.index', compact('notifications'));
    }

    public function show(Notification $notification)
    {
        $user = auth()->user();

        // تأكد إن الإشعار تابع للمستخدم الحالي
        if ($notification->user_id !== $user->id && !$user->hasRole('super-admin')) {
            abort(Response::HTTP_FORBIDDEN);
        }

        // تحديث حالة القراءة والضغط
        $notification->update([
            'is_read'   => true,
            'clicked_at'=> now(),
        ]);

        // لو فيه URL نروح له، وإلا نرجع لصفحة الإشعارات
        if ($notification->url) {
            return redirect($notification->url);
        }

        return redirect()->route('notifications.index');
    }
}
