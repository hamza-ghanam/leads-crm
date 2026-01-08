<?php

namespace App\Http\Controllers\Api\V1;

use App\Adapters\LeadsFilterAdapter;
use App\Helpers\ApiResponse;
use App\Helpers\PaginatedResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\LeadMoveForwardRequest;
use App\Http\Requests\LeadStoreRequest;
use App\Http\Resources\LastFollowUpResource;
use App\Http\Resources\LeadResource;
use App\Http\Resources\TicketPathResource;
use App\Models\Status;
use App\Models\TicketPath;
use App\Services\LeadAccess;
use App\Services\LeadCreateService;
use App\Services\LeadListQuery;
use App\Services\LeadMoveForwardService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LeadController extends Controller
{
    public function __construct(
        private readonly LeadListQuery          $leadListQuery,
        private readonly LeadAccess             $leadAccess,
        private readonly LeadMoveForwardService $moveFwdService,
        private readonly LeadCreateService      $leadCreateService,
    )
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
     *         name="assigned_to",
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

        $paginator->setCollection(
            LeadResource::collection($paginator->getCollection())->collection
        );

        return PaginatedResponse::fromPaginator($paginator);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/leads/{id}",
     *     operationId="getLeadDetails",
     *     tags={"Leads"},
     *     summary="Get lead details",
     *     description="Returns lead details with last follow-up and permissions",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Lead ID",
     *         @OA\Schema(type="integer", example=123)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Lead details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="lead",
     *                     ref="#/components/schemas/Lead"
     *                 ),
     *                 @OA\Property(
     *                     property="last_follow_up",
     *                     ref="#/components/schemas/LastFollowUp"
     *                 ),
     *                 @OA\Property(
     *                     property="permissions",
     *                     ref="#/components/schemas/LeadPermissions"
     *                 )
     *             )
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
    public function show(Request $request, int $id)
    {
        $user = $request->user();

        // Access control (404 if not visible)
        $lead = $this->leadAccess->findVisibleLeadOrFail($user, $id);

        $lead->load([
            'user',
            'status',
            'source',
            'assigner',
        ]);

        // Follow-up status
        $followUpStatusId = Status::where('slug', 'follow-up')->value('id');

        $lastFollowUpQuery = TicketPath::query()
            ->where('ticket_id', $lead->id)
            ->when($followUpStatusId, fn($q) => $q->where('next_status', $followUpStatusId))
            ->orderByDesc('created_at');

        // sales / tele-sales: only their own paths
        if (!$this->leadAccess->canSeeAllPaths($user)) {
            $lastFollowUpQuery->where('next_user', $user->id);
        }

        $lastFollowUp = $lastFollowUpQuery
            ->with(['nextUser', 'nextStatus'])
            ->first();

        return ApiResponse::success([
            'lead' => new LeadResource($lead),
            'last_follow_up' => $lastFollowUp
                ? new LastFollowUpResource(
                    $lastFollowUp->load(['nextUser', 'nextStatus'])
                )
                : null,
            'permissions' => [
                'can_view' => true,
                'can_view_paths' => true,
                'can_view_all_paths' => $this->leadAccess->canSeeAllPaths($user),
                'can_update_status' => $user->hasPermissionTo('change status'),
                'can_add_follow_up' => $user->hasPermissionTo('change status'),
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/leads/{id}/move-forward",
     *     operationId="moveLeadForward",
     *     tags={"Leads"},
     *     summary="Move lead forward (change status)",
     *     security={{"sanctum":{}}},
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
     *         name="Idempotency-Key",
     *         in="header",
     *         required=true,
     *         description="Idempotency key to prevent duplicate execution",
     *         @OA\Schema(type="string", example="3f2c8c8e-1a34-4a0e-9c7b-0f2d2e7c4b91")
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"status_id","comment"},
     *             @OA\Property(property="status_id", type="integer", example=2),
     *             @OA\Property(property="assigned_to", type="integer", nullable=true, example=13),
     *             @OA\Property(property="comment", type="string", example="Client asked to follow up next week"),
     *             @OA\Property(property="reminder_datetime", type="string", format="date-time", nullable=true, example="2026-01-10T10:30:00Z"),
     *             @OA\Property(property="meeting_range", type="string", nullable=true, example="2026-01-10 10:00 - 2026-01-10 10:30"),
     *             @OA\Property(property="client_unit", type="string", nullable=true, example="A-1203"),
     *             @OA\Property(property="client_price", type="number", nullable=true, example=1500000),
     *             @OA\Property(property="client_project", type="string", nullable=true, example="Saray Towers"),
     *             @OA\Property(property="client_developer", type="string", nullable=true, example="WR")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Moved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="lead", ref="#/components/schemas/Lead"),
     *                 @OA\Property(property="latest_path", ref="#/components/schemas/LeadPath")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Not found or not visible", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=400, description="Bad request", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=500, description="Server error", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function moveForward(LeadMoveForwardRequest $request, int $id)
    {
        $user = $request->user();

        // 404 if not visible (prevents ID discovery for sales/tele-sales)
        $lead = $this->leadAccess->findVisibleLeadOrFail($user, $id);

        $result = $this->moveFwdService->moveForward(
            actor: $user,
            lead: $lead,
            payload: $request->validated()
        );

        return ApiResponse::success([
            'lead' => new LeadResource($result['lead']),
            'latest_path' => new TicketPathResource($result['path']),
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/leads",
     *     operationId="createLead",
     *     tags={"Leads"},
     *     summary="Create a new lead",
     *     description="Creates a new lead (admin/super-admin only). assigned_to is required.",
     *     security={{"sanctum":{}}},
     *
     *     @OA\Parameter(
     *          name="Idempotency-Key",
     *          in="header",
     *          required=true,
     *          description="Idempotency key to prevent duplicate execution",
     *          @OA\Schema(type="string", example="3f2c8c8e-1a34-4a0e-9c7b-0f2d2e7c4b91")
     *      ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"full_name","phone_number","source_id","assigned_to"},
     *             @OA\Property(property="campaign_name", type="string", nullable=true, example="Meta - Jan Campaign"),
     *             @OA\Property(property="full_name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", format="email", nullable=true, example="john@example.com"),
     *             @OA\Property(property="phone_number", type="string", example="+971501234567"),
     *             @OA\Property(property="source_id", type="integer", example=3),
     *             @OA\Property(property="assigned_to", type="integer", example=25),
     *             @OA\Property(property="preferred_time", type="string", nullable=true, example="18:00"),
     *             @OA\Property(property="remarks", type="string", nullable=true, example="Call after 6pm")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Lead created",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="lead", ref="#/components/schemas/Lead")
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function store(LeadStoreRequest $request)
    {
        $actor = $request->user();

        $result = $this->leadCreateService->create($actor, $request->validated());

        $result->lead->load(['user', 'status', 'source', 'assigner']);

        return ApiResponse::success([
            'lead' => new LeadResource($result->lead),
            'is_duplicated' => $result->isDuplicated,
        ], Response::HTTP_CREATED);
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
