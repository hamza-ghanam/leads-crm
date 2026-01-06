<?php

namespace App\Http\Controllers\Api\V1;

use App\Helpers\PaginatedResponse;
use App\Http\Controllers\Controller;
use App\Models\TicketPath;
use App\Services\LeadAccess;
use App\Services\LeadListQuery;
use Illuminate\Http\Request;

class LeadPathController extends Controller
{
    public function __construct(
        private readonly LeadAccess $leadAccess,
    )
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/leads/{id}/paths",
     *     operationId="getLeadPaths",
     *     tags={"Lead Paths"},
     *     summary="Get lead history (paths)",
     *     description="Returns paginated lead history. Sales/Tele-sales see only their own paths.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Lead ID",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", example=1, minimum=1)
     *     ),
     *
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page (max 100)",
     *         required=false,
     *         @OA\Schema(type="integer", example=20, minimum=1, maximum=100)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Paginated lead paths",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/LeadPath")
     *             ),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"),
     *             @OA\Property(property="links", ref="#/components/schemas/PaginationLinks")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Lead not found or not visible",
     *         @OA\JsonContent(ref="#/components/schemas/ApiError")
     *     )
     * )
     */
    public function index(Request $request, int $id)
    {
        $user = $request->user();

        // 404 if not visible
        $this->leadAccess->findVisibleLeadOrFail($user, $id);

        $perPage = min(100, max(1, (int)$request->query('per_page', 20)));
        $page = max(1, (int)$request->query('page', 1));

        $query = TicketPath::query()
            ->where('ticket_id', $id)
            ->when(!$this->leadAccess->canSeeAllPaths($user), function ($q) use ($user) {
                $q->where('next_user', $user->id);
            })
            ->with(['prevUser', 'nextUser', 'prevStatus', 'nextStatus', 'meeting'])
            ->orderByDesc('created_at');

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return PaginatedResponse::fromPaginator($paginator);
    }
}
