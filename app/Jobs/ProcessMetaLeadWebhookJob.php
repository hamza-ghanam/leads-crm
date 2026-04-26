<?php

namespace App\Jobs;

use App\Services\Meta\MetaLeadIngestService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessMetaLeadWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public array $payload,
        public array $headers
    ) {}

    public function handle(MetaLeadIngestService $service): void
    {
        $service->handleWebhook($this->payload, $this->headers);
    }
}
