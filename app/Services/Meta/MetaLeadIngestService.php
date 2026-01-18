<?php

namespace App\Services\Meta;

use App\Models\FacebookLead;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MetaLeadIngestService
{
    public function __construct(
        private MetaGraphClient $graph,
        private MetaLeadNormalizer $normalizer,
        private MetaWebhookSignatureVerifier $signatureVerifier, // اختياري
    ) {}

    public function handleWebhook(array $payload, array $headers): void
    {
        // 0) (اختياري) Verify signature
        // إذا ما بدك هالطبقة هلق، خلي method ترجع true دائماً.
        if (!$this->signatureVerifier->isValid($payload, $headers)) {
            Log::warning('Meta webhook signature invalid.');

            DB::table('logs')->insert([
                'text' => 'Meta webhook signature invalid.',
                'level' => 'error',
            ]);

            return;
        }

        // Meta webhook structure: entry[] -> changes[] -> value -> leadgen_id
        $entries = Arr::get($payload, 'entry', []);
        if (!is_array($entries)) return;

        foreach ($entries as $entry) {
            $pageId = Arr::get($entry, 'id');
            $changes = Arr::get($entry, 'changes', []);

            foreach ((array)$changes as $change) {
                $value = Arr::get($change, 'value', []);
                $leadgenId = Arr::get($value, 'leadgen_id');

                if (!$leadgenId) continue;

                $this->ingestLead((string)$leadgenId, (string)$pageId, $payload);
            }
        }
    }

    public function ingestLead(string $leadgenId, ?string $pageId, array $webhookPayload): FacebookLead
    {
        // 1) Idempotency: إذا موجود ما نعيد
        $existing = FacebookLead::where('leadgen_id', $leadgenId)->first();
        if ($existing && $existing->fetched_at) {
            return $existing;
        }

        // create stub row early (prevent duplicates under concurrency)
        $lead = FacebookLead::firstOrCreate(
            ['leadgen_id' => $leadgenId],
            ['page_id' => $pageId, 'webhook_payload' => $webhookPayload]
        );

        // 2) Fetch full lead from Graph
        $graphPayload = $this->graph->fetchLead($leadgenId);

        // 3) Normalize
        $answers = $this->normalizer->normalizeAnswers($graphPayload);
        $core = $this->normalizer->extractCoreFields($answers);

        // 4) Persist
        $lead->update([
            'page_id' => $pageId ?: $lead->page_id,
            'form_id' => Arr::get($graphPayload, 'form_id'),
            'ad_id' => Arr::get($graphPayload, 'ad_id'),
            'fb_created_time' => $this->parseFbTime(Arr::get($graphPayload, 'created_time')),
            'full_name' => $core['full_name'],
            'phone' => $core['phone'],
            'email' => $core['email'],
            'answers' => $answers,
            'graph_payload' => $graphPayload,
            'webhook_payload' => $webhookPayload, // keep latest
            'fetched_at' => now(),
        ]);

        return $lead;
    }

    private function parseFbTime(?string $time): ?Carbon
    {
        if (!$time) return null;
        try {
            return Carbon::parse($time);
        } catch (\Throwable) {
            return null;
        }
    }
}
