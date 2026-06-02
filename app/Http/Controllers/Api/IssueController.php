<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddCommentRequest;
use App\Http\Requests\AssignIssueRequest;
use App\Http\Requests\CreateIssueRequest;
use App\Http\Requests\UpdateIssueStatusRequest;
use App\Models\Issue;
use App\Services\IssueService;
use App\Services\IssueStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IssueController extends Controller
{
    public function __construct(
        private readonly IssueService       $issues,
        private readonly IssueStatusService $statusService,
    ) {}

    // ── List ──────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'company_id', 'status', 'severity', 'category',
            'assigned_to', 'search',
        ]);

        $perPage = min((int) ($request->input('per_page', 20)), 100);
        $items   = $this->issues->getListQuery($request->user(), $filters)
                                ->paginate($perPage);

        return response()->json($items);
    }

    // ── Create ────────────────────────────────────────────────────

    public function store(CreateIssueRequest $request): JsonResponse
    {
        $issue = $this->issues->create($request->user(), $request->validated());
        $issue->load(['company', 'locker', 'reporter', 'assignee']);

        return response()->json(['message' => 'Issue created.', 'issue' => $issue], 201);
    }

    // ── Detail ────────────────────────────────────────────────────

    public function show(Request $request, Issue $issue): JsonResponse
    {
        $this->issues->authorizeAccess($request->user(), $issue);

        $issue->load([
            'company', 'locker', 'reporter', 'assignee',
            'comments.user',
            'assignments.performer',
            'assignments.assignedUser',
        ]);

        return response()->json($issue);
    }

    // ── Assign ────────────────────────────────────────────────────

    public function assign(AssignIssueRequest $request, Issue $issue): JsonResponse
    {
        $this->issues->authorizeAccess($request->user(), $issue);

        $data     = $request->validated();
        $updated  = $this->issues->assign(
            $issue,
            $request->user(),
            $data['assigned_to'] ?? null,
            $data['note'] ?? null,
        );

        return response()->json([
            'message' => 'Issue assignment updated.',
            'issue'   => $updated->load(['assignee']),
        ]);
    }

    // ── Comment ───────────────────────────────────────────────────

    public function addComment(AddCommentRequest $request, Issue $issue): JsonResponse
    {
        $this->issues->authorizeAccess($request->user(), $issue);

        $comment = $this->issues->addComment(
            $issue,
            $request->user(),
            $request->validated('body'),
            (bool) $request->validated('is_internal', false),
        );

        return response()->json([
            'message' => 'Comment added.',
            'comment' => $comment->load('user'),
        ], 201);
    }

    // ── Status (state-machine transition) ────────────────────────

    public function updateStatus(UpdateIssueStatusRequest $request, Issue $issue): JsonResponse
    {
        // validateActor() + canTransition() handled inside transition()
        $history = $this->statusService->transition(
            $issue,
            $request->user(),
            $request->validated('to_status'),
            $request->validated('note'),
            'api',
        );

        $issue->refresh()->load(['company', 'locker', 'reporter', 'assignee']);

        return response()->json([
            'message' => 'Status transitioned successfully.',
            'issue'   => $issue,
            'history' => $history->load('changedBy'),
        ]);
    }

    // ── Status history ────────────────────────────────────────────

    public function statusHistory(Request $request, Issue $issue): JsonResponse
    {
        $this->issues->authorizeAccess($request->user(), $issue);

        $histories = $issue->statusHistories()
                           ->with('changedBy')
                           ->orderBy('created_at')
                           ->get();

        return response()->json([
            'issue_id'  => $issue->id,
            'histories' => $histories,
            'allowed_transitions' => $this->statusService->getAllowedTransitions($issue, $request->user()),
        ]);
    }

    // ── Stats ─────────────────────────────────────────────────────

    public function stats(Request $request): JsonResponse
    {
        $stats = $this->issues->getStats(
            $request->user(),
            $request->integer('company_id') ?: null,
        );

        return response()->json($stats);
    }
}
