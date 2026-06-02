<?php

namespace App\Http\Controllers\Admin;

use App\Models\Issue;
use App\Models\Locker;
use App\Models\User;
use App\Services\IssueService;
use App\Services\IssueStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IssueController extends Controller
{
    public function index(Request $request, IssueService $issues): View|JsonResponse
    {
        $this->authorizePermission('view issues');

        $filters = array_filter([
            'company_id'  => $request->integer('company_id') ?: null,
            'status'      => $request->input('status'),
            'severity'    => $request->input('severity'),
            'category'    => $request->input('category'),
            'assigned_to' => $request->integer('assigned_to') ?: null,
            'search'      => $request->input('search'),
        ]);

        $query = $issues->getListQuery($request->user(), $filters)->with('company');

        if ($request->wantsJson() || $request->input('_fmt') === 'json') {
            return response()->json($query->get()->map(fn ($i) => [
                'id'       => $i->id,
                'title'    => $i->title,
                'status'   => $i->status,
                'severity' => $i->severity,
                'company'  => $i->company?->name ?? '—',
            ]));
        }

        return view('admin.issues.index', [
            'issues'    => $query->paginate(25)->withQueryString(),
            'stats'     => $issues->getStats($request->user(), $filters['company_id'] ?? null),
            'companies' => $this->companiesForFilter(),
            'filters'   => $filters,
            'lastSync'  => $this->lastApiSync(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizePermission('create issues');

        $lockers = Locker::whereIn('company_id', $request->user()->accessibleCompanyIds())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.issues.create', [
            'lockers'    => $lockers,
            'categories' => Issue::categoryOptions(),
            'severities' => Issue::severityOptions(),
        ]);
    }

    public function show(Issue $issue, Request $request): View
    {
        $this->authorizePermission('view issues');
        abort_unless($request->user()->canAccessCompany($issue->company_id), 403);

        $issue->load([
            'company', 'locker', 'reporter', 'assignee',
            'comments.user',
            'statusHistories.changedBy',
            'assignments.performer',
        ]);

        // Users that can be assigned (same company or super-admin)
        $assignableUsers = User::whereIn('company_id', $request->user()->accessibleCompanyIds())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.issues.show', compact('issue', 'assignableUsers'));
    }

    public function store(Request $request, IssueService $issues): RedirectResponse
    {
        $this->authorizePermission('create issues');

        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category'    => ['required', 'string'],
            'severity'    => ['required', 'string'],
            'locker_id'   => ['nullable', 'integer', 'exists:lockers,id'],
            'due_date'    => ['nullable', 'date'],
        ]);

        $issue = $issues->create($request->user(), $validated);

        return redirect()->route('admin.issues.show', $issue)->with('success', 'สร้าง Issue แล้ว');
    }

    public function updateStatus(Request $request, Issue $issue, IssueStatusService $statuses): RedirectResponse|JsonResponse
    {
        $this->authorizePermission('edit issues');

        $validated = $request->validate([
            'status' => ['required', 'string'],
            'note'   => ['nullable', 'string'],
        ]);

        $statuses->transition($issue, $request->user(), $validated['status'], $validated['note'] ?? null);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => 'อัปเดตสถานะแล้ว']);
        }

        return back()->with('success', 'อัปเดตสถานะ Issue แล้ว');
    }

    public function assign(Request $request, Issue $issue, IssueService $issues): RedirectResponse
    {
        $this->authorizePermission('edit issues');
        abort_unless($request->user()->canAccessCompany($issue->company_id), 403);

        $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'note'        => ['nullable', 'string', 'max:500'],
        ]);

        $issues->assign(
            $issue,
            $request->user(),
            $request->input('assigned_to') ? (int) $request->input('assigned_to') : null,
            $request->input('note')
        );

        return back()->with('success', 'อัปเดต Assignee แล้ว');
    }

    public function comment(Request $request, Issue $issue, IssueService $issues): RedirectResponse
    {
        $this->authorizePermission('edit issues');
        abort_unless($request->user()->canAccessCompany($issue->company_id), 403);

        $request->validate([
            'body'        => ['required', 'string', 'max:2000'],
            'is_internal' => ['boolean'],
        ]);

        $issues->addComment(
            $issue,
            $request->user(),
            $request->input('body'),
            (bool) $request->input('is_internal', false)
        );

        return back()->with('success', 'เพิ่ม Comment แล้ว');
    }
}
