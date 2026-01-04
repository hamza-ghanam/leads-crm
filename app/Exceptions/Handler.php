<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;
use Illuminate\Validation\ValidationException;
use App\Helpers\ApiResponse;
use App\Enums\ApiErrorCode;
use Symfony\Component\HttpFoundation\Response;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var string[]
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var string[]
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->renderable(function (ValidationException $e, $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error(
                    ApiErrorCode::VALIDATION_ERROR,
                    status: Response::HTTP_UNPROCESSABLE_ENTITY
                )->setData([
                    'success' => false,
                    'code' => ApiErrorCode::VALIDATION_ERROR->value,
                    'message' => ApiErrorCode::VALIDATION_ERROR->message(),
                    'data' => [
                        $e->errors(),
                    ]
                ]);
            }

            return null;
        });

        // 🔐 401 Unauthorized (Not logged in/missing token)
        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error(
                    ApiErrorCode::UNAUTHORIZED,
                    status: Response::HTTP_UNAUTHORIZED
                );
            }

            return null;
        });

        // 🚫 403 Forbidden (No Permissions)
        $this->renderable(function (AuthorizationException $e, $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error(
                    ApiErrorCode::FORBIDDEN,
                    status: Response::HTTP_FORBIDDEN
                );
            }

            return null;
        });

        // 🔍 404 Not Found (Model not found)
        $this->renderable(function (ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error(
                    ApiErrorCode::NOT_FOUND,
                    status: Response::HTTP_NOT_FOUND
                );
            }

            return null;
        });

        // 🌐 404 General (Route not found)
        $this->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error(
                    ApiErrorCode::NOT_FOUND,
                    status: Response::HTTP_NOT_FOUND
                );
            }

            return null;
        });

        // 🚦 Rate limit exceeded (429)
        $this->renderable(function (TooManyRequestsHttpException $e, $request) {
            if ($request->expectsJson()) {
                return ApiResponse::error(
                    ApiErrorCode::RATE_LIMITED,
                    status: Response::HTTP_TOO_MANY_REQUESTS
                );
            }

            return null;
        });

        // 💥 Global 500 (Unhandled Throwable)
        $this->renderable(function (Throwable $e, $request) {
            if ($request->expectsJson()) {

                // 📝 Log debug & tracking
                Log::error('Unhandled exception', [
                    'exception' => $e,
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'user_id' => optional($request->user())->id,
                ]);

                return ApiResponse::error(
                    ApiErrorCode::SERVER_ERROR,
                    status: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            return null;
        });

        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
