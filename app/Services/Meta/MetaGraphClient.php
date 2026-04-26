<?php

namespace App\Services\Meta;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MetaGraphClient
{
    private function http(): PendingRequest
    {
        $token = config('services.facebook.system_user_token');
        if (!$token) {
            throw new RuntimeException('FB_SYSTEM_USER_TOKEN is missing.');
        }

        return Http::baseUrl('https://graph.facebook.com/' . config('services.facebook.graph_version'))
            ->withToken($token)
            ->acceptJson()
            ->timeout(15)
            ->retry(3, 400, throw: false);
    }

    public function fetchLead(string $leadgenId): array
    {
        // fields مهمة: field_data فيها كل الأسئلة + الإجابات
        $fields = implode(',', [
            'id',
            'created_time',
            'ad_id',
            'form_id',
            'field_data',
            'campaign_id',
            'adset_id',
            'platform',
        ]);

        $resp = $this->http()->get("/{$leadgenId}", [
            'fields' => $fields,
        ]);

        if (!$resp->successful()) {
            throw new RuntimeException("Meta Graph fetch lead failed: {$resp->status()} {$resp->body()}");
        }

        return $resp->json();
    }
}
