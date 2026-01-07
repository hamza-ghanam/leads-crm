<?php

namespace App\Http\Middleware;

use App\Enums\ApiErrorCode;
use App\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use function PHPUnit\Framework\returnArgument;

class RequireIdempotencyKey
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (ResponseAlias) $next
     */
    public function handle(Request $request, Closure $next): ResponseAlias
    {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return $next($request);
        }

        if (!$request->header('Idempotency-Key')) {
            return ApiResponse::error(
                ApiErrorCode::IDEMPOTENCY_KEY_REQUIRED,
                ResponseAlias::HTTP_BAD_REQUEST
            );
        }

        return $next($request);
    }
}
