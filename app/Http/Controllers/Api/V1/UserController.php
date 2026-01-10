<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ApiErrorCode;
use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserLiteResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *   path="/api/v1/users/by-roles",
     *   operationId="getUsersGroupedByRoles",
     *   tags={"Users"},
     *   summary="Get users grouped by roles",
     *   description="Returns users belonging to the provided roles, grouped under each role name. Uses a single DB query and returns standard ApiResponse envelope.",
     *   security={{"sanctum":{}}},
     *
     *   @OA\Parameter(
     *     name="roles[]",
     *     in="query",
     *     required=true,
     *     description="Roles to include. Repeat roles[] multiple times.",
     *     @OA\Schema(type="array", @OA\Items(type="string")),
     *     style="form",
     *     explode=true,
     *     example={"sale","tele-sale"}
     *   ),
     *
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(ref="#/components/schemas/GroupedUsersByRolesResponse")
     *   ),
     *
     *   @OA\Response(
     *     response=401,
     *     description="Unauthenticated",
     *     @OA\JsonContent(ref="#/components/schemas/ApiError")
     *   ),
     *
     *   @OA\Response(
     *     response=422,
     *     description="Validation error (roles missing)",
     *     @OA\JsonContent(ref="#/components/schemas/ApiError")
     *   )
     * )
     */
    public function groupedByRoles(Request $request)
    {
        $roles = $request->input('roles', []);

        // Guard clause – API discipline
        if (empty($roles)) {
            return ApiResponse::error(
                ApiErrorCode::VALIDATION_ERROR,
                status: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // Single query – performant
        $users = User::query()
            ->whereHas('roles', function ($q) use ($roles) {
                $q->whereIn('name', $roles);
            })
            ->with('roles:name')
            ->select(['id', 'name', 'email', 'status'])
            ->get();

        // Group users by role
        $grouped = [];
        foreach ($roles as $role) {
            $grouped[$role] = UserLiteResource::collection(
                $users->filter(fn($u) => $u->roles->contains('name', $role))->values()
            );
        }

        return ApiResponse::success($grouped);
    }

    private function groupUsersByRole(Collection $users, array $roles): array
    {
        $result = [];

        foreach ($roles as $role) {
            $result[$role] = $users
                ->filter(fn ($user) =>
                $user->roles->contains('name', $role)
                )
                ->values();
        }

        return $result;
    }
}
