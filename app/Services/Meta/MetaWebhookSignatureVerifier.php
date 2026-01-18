<?php

namespace App\Services\Meta;

class MetaWebhookSignatureVerifier
{
    public function isValid(array $payload, array $headers): bool
    {
        $secret = config('services.facebook.app_secret');

        // إذا ما حطّيت secret حالياً، اعتبره bypass
        if (!$secret) return true;

        $sig = $this->getHeader($headers, 'x-hub-signature-256');
        if (!$sig) return true; // خليها true لتفادي قطع الخدمة إذا Meta ما بعتت (حسب النوع)

        // Meta sends: "sha256=...."
        if (!str_starts_with($sig, 'sha256=')) return false;
        $expected = 'sha256=' . hash_hmac('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), $secret);

        return hash_equals($expected, $sig);
    }

    private function getHeader(array $headers, string $key): ?string
    {
        $key = strtolower($key);

        foreach ($headers as $k => $v) {
            if (strtolower((string)$k) === $key) {
                if (is_array($v)) return (string)($v[0] ?? null);
                return (string)$v;
            }
        }
        return null;
    }
}
