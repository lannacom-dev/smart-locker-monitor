<?php

namespace App\Http\Controllers\Admin;

use App\Models\CorrectiveMaintenance;
use App\Models\MaintenanceAttachment;
use App\Models\Locker;
use App\Models\User;
use App\Services\CorrectiveMaintenanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    public function index(Request $request, CorrectiveMaintenanceService $service): View|JsonResponse
    {
        $this->authorizePermission('view maintenance');

        $filters = array_filter([
            'company_id' => $request->integer('company_id') ?: null,
            'status'     => $request->input('status'),
            'priority'   => $request->input('priority'),
            'search'     => $request->input('search'),
        ]);

        $query = $service->getListQuery($request->user(), $filters)->with('company');

        if ($request->wantsJson() || $request->input('_fmt') === 'json') {
            return response()->json($query->get()->map(fn ($m) => [
                'id'       => $m->id,
                'title'    => $m->title,
                'status'   => $m->status,
                'priority' => $m->priority,
                'company'  => $m->company?->name ?? '—',
            ]));
        }

        return view('admin.maintenance.index', [
            'items'     => $query->paginate(25)->withQueryString(),
            'stats'     => $service->getStats($request->user(), $filters['company_id'] ?? null),
            'companies' => $this->companiesForFilter(),
            'filters'   => $filters,
            'lastSync'  => $this->lastApiSync(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizePermission('create maintenance');

        $lockers = Locker::whereIn('company_id', $request->user()->accessibleCompanyIds())
            ->orderBy('name')
            ->get(['id', 'name']);

        $technicians = User::whereIn('company_id', $request->user()->accessibleCompanyIds())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.maintenance.create', [
            'lockers'     => $lockers,
            'technicians' => $technicians,
            'types'       => CorrectiveMaintenance::typeOptions(),
            'priorities'  => CorrectiveMaintenance::priorityOptions(),
        ]);
    }

    public function store(Request $request, CorrectiveMaintenanceService $service): RedirectResponse
    {
        $this->authorizePermission('create maintenance');

        $validated = $request->validate([
            'locker_id'      => ['required', 'integer', 'exists:lockers,id'],
            'type'           => ['required', 'string', 'in:preventive,corrective,emergency'],
            'title'          => ['required', 'string', 'max:255'],
            'description'    => ['required', 'string'],
            'root_cause'     => ['nullable', 'string'],
            'priority'       => ['required', 'string', 'in:low,medium,high,urgent'],
            'technician_id'  => ['nullable', 'integer', 'exists:users,id'],
            'scheduled_date' => ['nullable', 'date'],
            'cost_estimate'  => ['nullable', 'numeric', 'min:0'],
            'notes'          => ['nullable', 'string', 'max:2000'],
            'images.*'       => ['nullable', 'file', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
        ]);

        $maintenance = $service->create($request->user(), $validated);

        // Handle before-images attached at creation time
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $this->storeAttachment($maintenance, $request->user(), $file, MaintenanceAttachment::PHASE_BEFORE);
            }
        }

        return redirect()
            ->route('admin.maintenance.show', $maintenance)
            ->with('success', 'สร้างรายการ Maintenance แล้ว');
    }

    public function show(CorrectiveMaintenance $maintenance, Request $request): View
    {
        $this->authorizePermission('view maintenance');
        abort_unless($request->user()->canAccessCompany($maintenance->company_id), 403);

        $maintenance->load([
            'company', 'locker', 'creator', 'technician',
            'logs.changedBy', 'attachments.uploader', 'issue',
        ]);

        $assignableUsers = User::whereIn('company_id', $request->user()->accessibleCompanyIds())
            ->orderBy('name')
            ->get(['id', 'name']);

        $allowedTransitions = app(CorrectiveMaintenanceService::class)
            ->getAllowedTransitions($maintenance, $request->user());

        return view('admin.maintenance.show', compact(
            'maintenance', 'assignableUsers', 'allowedTransitions'
        ));
    }

    public function assign(Request $request, CorrectiveMaintenance $maintenance, CorrectiveMaintenanceService $service): RedirectResponse
    {
        $this->authorizePermission('assign maintenance');
        abort_unless($request->user()->canAccessCompany($maintenance->company_id), 403);

        $request->validate([
            'technician_id' => ['nullable', 'integer', 'exists:users,id'],
            'note'          => ['nullable', 'string', 'max:500'],
        ]);

        $service->assignTechnician(
            $maintenance,
            $request->user(),
            $request->input('technician_id') ? (int) $request->input('technician_id') : null,
            $request->input('note')
        );

        return back()->with('success', 'อัปเดต Technician แล้ว');
    }

    public function updateFields(Request $request, CorrectiveMaintenance $maintenance, CorrectiveMaintenanceService $service): RedirectResponse
    {
        $this->authorizePermission('edit maintenance');
        abort_unless($request->user()->canAccessCompany($maintenance->company_id), 403);

        $validated = $request->validate([
            'root_cause'     => ['nullable', 'string'],
            'solution'       => ['nullable', 'string'],
            'notes'          => ['nullable', 'string', 'max:2000'],
            'cost_estimate'  => ['nullable', 'numeric', 'min:0'],
            'cost_actual'    => ['nullable', 'numeric', 'min:0'],
            'scheduled_date' => ['nullable', 'date'],
            'note'           => ['nullable', 'string', 'max:500'],
        ]);

        $note = $validated['note'] ?? null;
        unset($validated['note']);

        $service->updateFields($maintenance, $request->user(), $validated, $note);

        return back()->with('success', 'บันทึกข้อมูลแล้ว');
    }

    public function addNote(Request $request, CorrectiveMaintenance $maintenance, CorrectiveMaintenanceService $service): RedirectResponse
    {
        $this->authorizePermission('edit maintenance');
        abort_unless($request->user()->canAccessCompany($maintenance->company_id), 403);

        $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);

        $service->addNote($maintenance, $request->user(), $request->input('note'));

        return back()->with('success', 'เพิ่มหมายเหตุแล้ว');
    }

    public function uploadAttachment(Request $request, CorrectiveMaintenance $maintenance): RedirectResponse
    {
        $this->authorizePermission('edit maintenance');
        abort_unless($request->user()->canAccessCompany($maintenance->company_id), 403);

        $request->validate([
            'phase'    => ['required', 'string', 'in:before,during,after'],
            'images'   => ['required', 'array', 'min:1'],
            'images.*' => ['file', 'mimes:jpeg,jpg,png,gif,webp', 'max:5120'],
        ]);

        foreach ($request->file('images') as $file) {
            $attachment = $this->storeAttachment($maintenance, $request->user(), $file, $request->input('phase'));

            // Audit log
            app(CorrectiveMaintenanceService::class)->addNote(
                $maintenance,
                $request->user(),
                null
            );
            // Log attachment action directly
            \App\Models\CorrectiveMaintenanceLog::create([
                'maintenance_id' => $maintenance->id,
                'changed_by'     => $request->user()->id,
                'action'         => \App\Models\CorrectiveMaintenanceLog::ACTION_ATTACHMENT_ADDED,
                'field_name'     => 'phase',
                'old_value'      => null,
                'new_value'      => $attachment->original_name,
                'note'           => $request->input('phase') . ' phase',
            ]);
        }

        return back()->with('success', 'อัปโหลดรูปภาพแล้ว');
    }

    public function transition(Request $request, CorrectiveMaintenance $maintenance, CorrectiveMaintenanceService $service): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('edit maintenance');

        $validated = $request->validate([
            'status'       => ['required', 'string'],
            'note'         => ['nullable', 'string'],
            'solution'     => ['nullable', 'string'],
            'cost_actual'  => ['nullable', 'numeric', 'min:0'],
            'completed_at' => ['nullable', 'date'],
        ]);

        $extra = array_filter([
            'solution'     => $validated['solution']     ?? null,
            'cost_actual'  => $validated['cost_actual']  ?? null,
            'completed_at' => $validated['completed_at'] ?? null,
        ], fn($v) => $v !== null);

        $service->transition($maintenance, $request->user(), $validated['status'], $validated['note'] ?? null, $extra);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => 'อัปเดตสถานะแล้ว']);
        }

        return back()->with('success', 'อัปเดตสถานะแล้ว');
    }

    // ── Private helpers ───────────────────────────────────────────

    private function storeAttachment(
        CorrectiveMaintenance $maintenance,
        User                  $uploader,
        \Illuminate\Http\UploadedFile $file,
        string                $phase,
    ): MaintenanceAttachment {
        $ext      = $file->getClientOriginalExtension();
        $filename = $phase . '_' . Str::uuid() . '.' . $ext;
        $dir      = 'maintenance/' . $maintenance->id;
        $path     = $file->storeAs($dir, $filename, 'public');

        return MaintenanceAttachment::create([
            'maintenance_id' => $maintenance->id,
            'uploaded_by'    => $uploader->id,
            'phase'          => $phase,
            'file_path'      => $path,
            'original_name'  => $file->getClientOriginalName(),
            'mime_type'      => $file->getMimeType(),
            'size'           => $file->getSize(),
        ]);
    }
}
