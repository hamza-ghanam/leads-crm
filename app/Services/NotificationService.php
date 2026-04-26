<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;

class NotificationService
{
    /**
     * send a notification to single user (Store in DB).
     *
     * @param \App\Models\User|int $user
     * @param string $title
     * @param string $body
     * @param string|null $url inner relationship /tickets/123
     * @param string|null $type e.g.: reminder, ticket_follow_up
     * @param array $extraData additional data of payload
     * @param string|null $icon
     * @return \App\Models\Notification
     */
    public function notifyUser($user, string $title, string $body, ?string $url = null, ?string $type = null, array $extraData = [], ?string $icon = null): Notification
    {
        if (is_numeric($user)) {
            $user = User::findOrFail($user);
        }

        // 1) نحفظ الإشعار في قاعدة البيانات
        $notification = Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'type' => $type,
            'icon' => $icon,
            'is_read' => false,
        ]);

        // 2) نجلب التوكنات تبع المستخدم
        $tokens = $user->deviceTokens()
            ->pluck('token')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (empty($tokens)) {
            Log::warning("No device tokens for user {$user->id} when sending notification {$notification->id}");
            return $notification;
        }

        $unreadCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        // 3) نجهّز الـ payload للـ FCM
        $payloadData = array_merge([
            'notification_id' => (string)$notification->id,
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'type' => $type,
            'unread_count'    => $unreadCount,
        ], $extraData);

        $this->sendFcm($tokens, $payloadData);

        // نحدّث وقت التسليم (افتراضيًا عند نجاح الطلب للـ FCM)
        $notification->update([
            'delivered_at' => Carbon::now(),
        ]);

        return $notification;
    }

    /**
     * إرسال إشعار لعدة مستخدمين دفعة واحدة.
     */
    public function notifyMany(array $userIds, string $title, string $body, ?string $url = null, ?string $type = null, array $extraData = [], ?string $icon = null): void
    {
        $users = User::whereIn('id', $userIds)
            ->with('deviceTokens')
            ->get();

        foreach ($users as $user) {
            $this->notifyUser($user, $title, $body, $url, $type, $extraData, $icon);
        }
    }

    /**
     * Send FCM
     */
    protected function sendFcm(array $tokens, array $data): void
    {
        if (empty($tokens)) {
            return;
        }

        /** @var \Kreait\Firebase\Messaging|null $messaging */
        $messaging = app('firebase.messaging');

        if (!$messaging) {
            Log::error('firebase.messaging key is not configured.');
            return;
        }

        // نجهّز الداتا الأساسية مرة واحدة
        $payloadData = [
            'title' => $data['title'] ?? '',
            'body' => $data['body'] ?? '',
            'url' => $data['url'] ?? url('/tickets'),
            'ticket_id' => $data['ticketId'] ?? 0,
            'type' => $data['type'] ?? null,
        ];

        $uniqueTokens = array_values(array_unique($tokens));

        foreach ($uniqueTokens as $token) {
            try {
                $message = CloudMessage::withTarget('token', $token)
                    ->withData($payloadData);

                $messaging->send($message);

            } catch (MessagingException $e) {
                // أخطاء متعلقة بالـ FCM (توكن منتهي، غير صحيح، إلخ)
                Log::warning('FCM MessagingException while sending to token: ' . $token, [
                    'message' => $e->getMessage(),
                    'user_data' => $payloadData,
                ]);

            } catch (FirebaseException $e) {
                // أخطاء عامة من Firebase SDK
                Log::error('FCM FirebaseException while sending to token: ' . $token, [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

            } catch (\Throwable $e) {
                // أي خطأ غير متوقّع
                Log::error('Unexpected FCM exception while sending to token: ' . $token, [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }
}
