<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use App\Enums\ApiErrorCode;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/login",
     *     tags={"Authentication"},
     *     summary="Login user",
     *     description="Authenticate user using email and password",
     *
     *     @OA\Parameter(ref="#/components/parameters/CorrelationId"),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="crm-sadmin@wrsae.ae"),
     *             @OA\Property(property="password", type="string", format="password", example="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="token", type="string"),
     *                 @OA\Property(
     *                     property="user",
     *                     type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="email", type="string")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials",
     *         @OA\JsonContent(ref="#/components/schemas/ApiError")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Account banned",
     *         @OA\JsonContent(ref="#/components/schemas/ApiError")
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Too many login attempts",
     *         @OA\JsonContent(ref="#/components/schemas/ApiError")
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ApiValidationError")
     *     )
     * )
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            if (!Auth::attempt($request->only('email', 'password'))) {
                return ApiResponse::error(
                    ApiErrorCode::INVALID_CREDENTIALS,
                    status: ResponseAlias::HTTP_UNAUTHORIZED
                );
            }

            $user = $request->user();

            if ($user->status !== 'permitted') {
                Auth::logout();

                return ApiResponse::error(
                    ApiErrorCode::ACCOUNT_BANNED,
                    status: ResponseAlias::HTTP_UNAUTHORIZED
                );
            }

            $user->tokens()->delete();

            $token = $user->createToken('mobile')->plainTextToken;

            return ApiResponse::success([
                'token' => $token,
                'user' => $user,
            ]);

        } catch (\Throwable $e) {

            // ✅ ADD IT HERE (temporary debug)
            Log::error('Login exception', [
                'exception' => $e->getMessage(),
                'correlation_id' => request()->attributes->get('correlation_id'),
            ]);

            // Let the global handler return SERVER_ERROR
            throw $e;
        }
    }
}
