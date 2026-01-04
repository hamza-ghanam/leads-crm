<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CorrelationId
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get Correlation ID from request header or generate a new one
        $correlationId = $request->header('X-Correlation-ID')
            ?? Str::uuid()->toString();

        // Store Correlation ID in the request lifecycle
        $request->attributes->set('correlation_id', $correlationId);

        // Inject Correlation ID into the global log context
        // This makes it automatically available in all Log:: calls
        Log::withContext([
            'correlation_id' => $correlationId,
        ]);

        // Continue request handling
        $response = $next($request);

        // Attach Correlation ID to response headers
        $response->headers->set('X-Correlation-ID', $correlationId);

        return $response;
    }
}
