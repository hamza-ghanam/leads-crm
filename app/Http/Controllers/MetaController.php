<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessMetaLeadWebhookJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class MetaController extends Controller
{
    public function __invoke(Request $request): Response
    {
        // 1) Verification (GET)
        if ($request->isMethod('GET')) {
            $mode = $request->query('hub_mode') ?? $request->query('hub.mode');
            $token = $request->query('hub_verify_token') ?? $request->query('hub.verify_token');
            $challenge = $request->query('hub_challenge') ?? $request->query('hub.challenge');

            if ($mode === 'subscribe' && $token === (string)config('services.facebook.leads_verify_token')) {
                return response($challenge, SymfonyResponse::HTTP_OK)
                    ->header('Content-Type', 'text/plain');
            }

            return response('Forbidden', SymfonyResponse::HTTP_FORBIDDEN)
                ->header('Content-Type', 'text/plain');
        }

        // 2) Notifications (POST)
        // أفضل ممارسة: رجّع 200 بسرعة، وخلي المعالجة async (Queue) لاحقًا.
        Log::info('FB Lead Webhook received', [
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
        ]);

        DB::table('logs')->insert([
            'text' => json_encode([
                'headers' => $request->headers->all(),
                'payload' => $request->all()
            ], JSON_THROW_ON_ERROR),
            'level' => 'meta_log',
        ]);

        ProcessMetaLeadWebhookJob::dispatch($request->all(), $request->headers->all());

        return response('EVENT_RECEIVED', Response::HTTP_OK)->header('Content-Type', 'text/plain');
    }
}
