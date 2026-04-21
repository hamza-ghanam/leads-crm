<?php

namespace App\Http\Controllers;

use App\Models\FcmToken;
use Illuminate\Http\Request;

class FcmController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'device_type' => 'nullable|string|in:web,desktop,mobile',
        ]);

        $user = $request->user();

        if (!$user) {
            // لو المستخدم مش مسجل دخول
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $deviceType = $request->input('device_type', 'desktop');
        $userAgent = $request->header('User-Agent');

        $fcmToken = FcmToken::withTrashed()
            ->where('user_id', $user->id)
            ->where('token', $request->token)
            ->first();

        if ($fcmToken) {
            // لو التوكن موجود (حتى لو محذوف soft delete) نحدّثه
            if ($fcmToken->trashed()) {
                $fcmToken->restore();
            }

            $fcmToken->device_type = $deviceType;
            $fcmToken->user_agent = $userAgent;
            $fcmToken->last_used_at = now();
            $fcmToken->save();
        } else {
            // توكن جديد لهذا المستخدم
            FcmToken::create([
                'user_id' => $user->id,
                'token' => $request->token,
                'device_type' => $deviceType,
                'user_agent' => $userAgent,
                'last_used_at' => now(),
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
