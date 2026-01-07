<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyKey
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Only apply to write operations
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return $next($request);
        }

        $key = $request->header('Idempotency-Key');

        // If no key provided, continue normally
        if (!$key) {
            return $next($request);
        }

        // Try to find an existing stored response
        $record = DB::table('idempotency_keys')
            ->where('key', $key)
            ->where('method', $request->method())
            ->where('uri', $request->path())
            ->first();

        if ($record) {
            // Return the previously stored response
            return response()->json(
                json_decode($record->response, true),
                $record->status_code
            );
        }

        // Process the request normally
        $response = $next($request);
        $content = $response->getContent();

        // Store the response for future identical requests
        DB::table('idempotency_keys')->insert([
            'key' => $key,
            'user_id' => $request->user()?->id,
            'method' => $request->method(),
            'uri' => $request->path(),
            'request_hash' => hash('sha256', json_encode($request->all())),
            'response' => $content,
            'status_code' => $response->getStatusCode(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $response;
    }
}
