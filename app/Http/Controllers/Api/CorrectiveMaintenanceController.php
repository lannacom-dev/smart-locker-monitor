<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateMaintenanceRequest;
use App\Http\Requests\UpdateMaintenanceRequest;
use App\Models\CorrectiveMaintenance;
use App\Services\CorrectiveMaintenanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CorrectiveMaintenanceController extends Controller
{
    public function __construct(
        private readonly CorrectiveMaintenanceService $service,
    ) {}

    // ── List ──────────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'company_id', 'status', 'priority',
            'locker_id', 'issue_id', 'technician_id', 'search',
        ]);
        $perPage = min((int) $request->input('per_page', 20), 100);
        $items   = $this->service->getListQuery($request->user(), $filters)->paginate($perPage);

        return response()->json($items);
    }

    // ── Stats ─────────────────────────────────────────────────────

    public function stats(Request $request): JsonResponse
    {
        return response()->json(
            $this->service->getStats($request->user(), $request->integer('company_id') ?: null)
        );
    }

    // ── Create ────────────────────────────────────────────────────

    public function store(CreateMaintenanceRequest $request): JsonResponse
    {
        $maintenance = $this->service->create($request->user(), $request->validated());
        $maintenance->load(['locker', 'company', 'technician', 'creator', 'issue']);

        return response()->json(['message' => 'Maintenance created.', 'maintenance' => $maintenance], 201);
    }

    // ── Detail ────────────────────────────────────────────────────

    public function show(Request $request, CorrectiveMaintenance $maintenance): JsonResponse
    {
        $this->service->authorizeAccess($request->user(), $maintenance);

        $maintenance->load([
            'locker', 'company', 'technician', 'creator', 'issue',
            'logs.changedBy',
        ]);

        return response()->json([
            'maintenance'         => $maintenance,
            'allowed_transitions' => $this->service->getAllowedTransitions($maintenance, $request->user()),
        ]);
    }

    // ── Update Fields ─────────────────────────────────────────────

    public function update(UpdateMaintenanceRequest $request, CorrectiveMaintenance $maintenance): JsonResponse
    {
        $this->service->authorizeAccess($request->user(), $maintenance);

        $data = $request->validated();
        $note = $request->input('note');

        // Handle technician assignment separately (has its own log action)
        if (array_key_exists('technician_id', $data)) {
            $this->service->assignTechnician($maintenance, $request->user(), $data['technician_id'], $note);
            unset($data['technician_id']);
        }

        if (!empty($data)) {
            $maintenance = $this->service->updateFields($maintenance, $request->user(), $data, $note);
        }

        return response()->json([
            'message'     => 'Maintenance updated.',
            'maintenance' => $maintenance->load(['locker', 'company', 'technician', 'creator']),
        ]);
    }

    // ── Status Transition ─────────────────────────────────────────

    public function transition(Request $request, CorrectiveMaintenance $maintenance): JsonResponse
    {
        $request->validate([
            'to_status'    => ['required', Rule::in(array_keys(CorrectiveMaintenance::statusOptions()))],
            'note'         => ['nullable', 'string', 'max:1000'],
            // Extra fields for 'completed' transition
            'solution'     => ['nullable', 'string'],
            'cost_actual'  => ['nullable', 'numeric', 'min:0'],
            'completed_at' => ['nullable', 'date'],
        ]);

        $this->service->authorizeAccess($request->user(), $maintenance);

        $updated = $this->service->transition(
            $maintenance,
            $request->user(),
            $request->input('to_status'),
            $request->input('note'),
            $request->only(['solution', 'cost_actual', 'completed_at', 'cancel_reason']),
        );

        return response()->json([
            'message'     => 'Status updated.',
            'maintenance' => $updated->load(['locker', 'company', 'technician']),
        ]);
    }

    // ── Assign Technician ─────────────────────────────────────────

    public function assign(Request $request, CorrectiveMaintenance $maintenance): JsonResponse
    {
        $request->validate([
            'technician_id' => ['nullable', 'integer', 'exists:users,id'],
            'note'          => ['nullable', 'string', 'max:500'],
        ]);

        if (!$request->user()->can('assign maintenance')) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $this->service->authorizeAccess($request->user(), $maintenance);

        $updated = $this->service->assignTechnician(
            $maintenance,
            $request->user(),
            $request->input('technician_id'),
            $request->input('note'),
        );

        return response()->json([
            'message'     => 'Technician assigned.',
            'maintenance' => $updated->load('technician'),
        ]);
    }

    // ── Audit Log ─────────────────────────────────────────────────

    public function logs(Request $request, CorrectiveMaintenance $maintenance): JsonResponse
    {
        $this->service->authorizeAccess($request->user(), $maintenance);

        return response()->json(
            $maintenance->logs()->with('changedBy')->get()
        );
    }
}
