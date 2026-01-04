<?php

namespace App\Http\Controllers\Api\V1;

use App\Adapters\LeadsFilterAdapter;
use App\Helpers\ApiResponse;
use App\Helpers\PaginatedResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\LeadResource;
use App\Services\LeadListQuery;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function __construct(private readonly LeadListQuery $leadListQuery)
    {
    }

    /**
     * @OA\Get(
     *     path="/api/v1/leads",
     *     tags={"Leads"},
     *     summary="List leads",
     *     description="Returns a paginated list of leads based on the user's role with filtering support.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by lead status (same values as web)",
     *         required=false,
     *         @OA\Schema(type="string", example="Follow-up")
     *     ),
     *
     *     @OA\Parameter(
     *         name="assignee_id",
     *         in="query",
     *         description="Filter by assigned sales user ID",
     *         required=false,
     *         @OA\Schema(type="integer", example=12)
     *     ),
     *
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Filter leads created from this date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2026-01-01")
     *     ),
     *
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Filter leads created until this date (YYYY-MM-DD)",
     *         required=false,
     *         @OA\Schema(type="string", format="date", example="2026-01-31")
     *     ),
     *
     *     @OA\Parameter(
     *         name="lead_id",
     *         in="query",
     *         description="Filter by exact lead ID",
     *         required=false,
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
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Lead")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 ref="#/components/schemas/PaginationMeta"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(ref="#/components/schemas/ApiError")
     *     ),
     *
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(ref="#/components/schemas/ApiError")
     *     ),
     *
     *     @OA\Response(
     *         response=429,
     *         description="Too many requests",
     *         @OA\JsonContent(ref="#/components/schemas/ApiError")
     *     )
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $filterParams = LeadsFilterAdapter::fromApi($request);

        $leadId = $request->query('lead_id')
            ? (int)$request->query('lead_id')
            : null;

        $query = $this->leadListQuery->build($user, $filterParams, $leadId);

        $query = app('App\Helpers\LeadsHelper')
            ->filterLeads($filterParams, $query);

//        $sql = $query->toSql();
//        $bindings = $query->getBindings();
//        dd($sql, $bindings);

        // Pagination contract: page/per_page
        $perPage = min(100, max(1, (int)$request->query('per_page', 20)));
        $page = max(1, (int)$request->query('page', 1));

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return PaginatedResponse::fromPaginator($paginator);
    }

    private function lastFollowUpPreview($ticket, $user): string
    {
        $path = $ticket->latestFollowUpPath;

        if (!$path || !$path->comment) {
            return '-';
        }

        // web rule: sales/tele sees it only if assigned to him
        if ($user->hasAnyRole(['sale', 'tele-sale'])) {
            if ((int)$path->next_user !== (int)$user->id) {
                return '-';
            }
        }

        $comment = (string)$path->comment;
        return mb_strlen($comment) < 75 ? $comment : (mb_substr($comment, 0, 75) . '...');
    }
}
