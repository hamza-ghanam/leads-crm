<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Status;
use Illuminate\Http\Request;

class StatusController extends Controller
{
    private const SALES_BLOCKED_SLUGS = ['reviewed', 'approved', 'pre-approved', 'sold'];
    private const SALE_EXTRA_BLOCKED  = ['dead', 're-shuffled'];

    /**
     * @OA\Get(
     *   path="/api/v1/statuses",
     *   operationId="getStatuses",
     *   tags={"Statuses"},
     *   summary="List statuses available to the authenticated user",
     *   description="Returns role-filtered, DB-ordered statuses intended for dropdown usage. Tele statuses are excluded. Ordering is controlled by statuses.sort_order in DB.",
     *   security={{"sanctum":{}}},
     *
     *   @OA\Response(
     *     response=200,
     *     description="OK",
     *     @OA\JsonContent(ref="#/components/schemas/StatusesListResponse")
     *   ),
     *
     *   @OA\Response(
     *     response=401,
     *     description="Unauthenticated",
     *     @OA\JsonContent(ref="#/components/schemas/ApiError")
     *   ),
     *
     *   @OA\Response(
     *     response=403,
     *     description="Forbidden",
     *     @OA\JsonContent(ref="#/components/schemas/ApiError")
     *   )
     * )
     */
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $statuses = Status::query()
            ->select(['id', 'name', 'slug'])
            ->orderByRaw('sort_order IS NULL')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (Status $status) => $this->isVisibleToUser($status->slug, $user))
            ->values()
            ->map(fn (Status $s) => [
                'id'   => $s->id,
                'name' => $s->name,
                'slug' => $s->slug,
            ])
            ->all();

        return ApiResponse::success($statuses);
    }

    private function isVisibleToUser(string $slug, $user): bool
    {
        // TELE: ignore all tele-related statuses
        if (str_ends_with($slug, '-tele') || str_starts_with($slug, 'tele')) {
            return false;
        }

        if ($slug === 'rejected') {
            return $user->hasAnyRole(['super-admin', 'sales-manager', 'admin']);
        }

        if ($slug === 'duplicated' || $slug === 'new') {
            return $user->hasRole('super-admin');
        }

        if (in_array($slug, self::SALES_BLOCKED_SLUGS, true)) {
            if ($user->hasAnyRole(['sale', 'tele-sale'])) {
                return false;
            }
        }

        if (in_array($slug, self::SALE_EXTRA_BLOCKED, true)) {
            if ($user->hasRole('sale')) {
                return false;
            }
        }

        return true;
    }
}
