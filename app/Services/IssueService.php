<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\IssueAssignment;
use App\Models\IssueComment;
use App\Models\IssueStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IssueService
{
    // ── Create ────────────────────────────────────────────────────

    public function create(User $reporter, array $data): Issue
    {
        return DB::transaction(function () use ($reporter, $data) {
            /** @var Issue $issue */
            $issue = Issue::create([
                'company_id'  => $reporter->company_id,
                'locker_id'   => $data['locker_id'] ?? null,
                'reported_by' => $reporter->id,
                'assigned_to' => null,
                'title'       => $data['title'],
                'description' => $data['description'],
                'category'    => $data['category'],
                'severity'    => $data['severity'] ?? Issue::SEV_MEDIUM,
                'status'      => Issue::STATUS_OPEN,
                'due_date'    => $data['due_date'] ?? null,
            ]);

            // Audit: created
            IssueAssignment::create([
                'issue_id'     => $issue->id,
                'type'         => IssueAssignment::TYPE_CREATED,
                'performed_by' => $reporter->id,
                'new_value'    => $issue->severity,
                'note'         => 'Issue created',
            ]);

            Log::info('Issue created', ['issue_id' => $issue->id, 'reporter' => $reporter->id]);
            return $issue;
        });
    }

    // ── Assign ────────────────────────────────────────────────────

    public function assign(Issue $issue, User $actor, ?int $assigneeId, ?string $note = null): Issue
    {
        return DB::transaction(function () use ($issue, $actor, $assigneeId, $note) {
            $previousAssignee = $issue->assignee;
            $oldValue = $previousAssignee?->name ?? 'unassigned';

            if ($assigneeId === null) {
                // Unassign
                $issue->update(['assigned_to' => null]);
                IssueAssignment::create([
                    'issue_id'     => $issue->id,
                    'type'         => IssueAssignment::TYPE_UNASSIGNED,
                    'performed_by' => $actor->id,
                    'assigned_to'  => null,
                    'old_value'    => $oldValue,
                    'new_value'    => null,
                    'note'         => $note,
                ]);
            } else {
                $newAssignee = User::findOrFail($assigneeId);
                $issue->update([
                    'assigned_to' => $assigneeId,
                    'status'      => $issue->status === Issue::STATUS_OPEN
                        ? Issue::STATUS_IN_PROGRESS
                        : $issue->status,
                ]);
                IssueAssignment::create([
                    'issue_id'     => $issue->id,
                    'type'         => IssueAssignment::TYPE_ASSIGNED,
                    'performed_by' => $actor->id,
                    'assigned_to'  => $assigneeId,
                    'old_value'    => $oldValue,
                    'new_value'    => $newAssignee->name,
                    'note'         => $note,
                ]);
            }

            $issue->refresh();
            Log::info('Issue assigned', ['issue_id' => $issue->id, 'assignee' => $assigneeId, 'by' => $actor->id]);
            return $issue;
        });
    }

    // ── Status Change ─────────────────────────────────────────────
    // NOTE: For web/API requests use IssueStatusService::transition() which
    //       enforces the state machine and permission rules.
    //       This method is kept for internal/programmatic use and also writes
    //       an IssueStatusHistory record for a complete audit trail.

    public function updateStatus(Issue $issue, User $actor, string $newStatus, ?string $note = null): Issue
    {
        return DB::transaction(function () use ($issue, $actor, $newStatus, $note) {
            $oldStatus = $issue->status;
            if ($oldStatus === $newStatus) {
                return $issue;
            }

            $updates = ['status' => $newStatus];

            if ($newStatus === Issue::STATUS_RESOLVED) {
                $updates['resolved_at'] = now();
                $type = IssueAssignment::TYPE_RESOLVED;
            } elseif ($newStatus === Issue::STATUS_CLOSED) {
                $updates['closed_at'] = now();
                $type = IssueAssignment::TYPE_CLOSED;
            } elseif (in_array($oldStatus, [Issue::STATUS_RESOLVED, Issue::STATUS_CLOSED], true)) {
                $updates['resolved_at'] = null;
                $updates['closed_at']   = null;
                $type = IssueAssignment::TYPE_REOPENED;
            } else {
                $type = IssueAssignment::TYPE_STATUS_CHANGED;
            }

            $issue->update($updates);

            // Dedicated status history
            IssueStatusHistory::create([
                'issue_id'    => $issue->id,
                'changed_by'  => $actor->id,
                'from_status' => $oldStatus,
                'to_status'   => $newStatus,
                'note'        => $note,
                'metadata'    => ['source' => 'internal'],
            ]);

            // General audit trail
            IssueAssignment::create([
                'issue_id'     => $issue->id,
                'type'         => $type,
                'performed_by' => $actor->id,
                'old_value'    => $oldStatus,
                'new_value'    => $newStatus,
                'note'         => $note,
            ]);

            $issue->refresh();
            Log::info('Issue status changed (IssueService)', [
                'issue_id' => $issue->id,
                'from'     => $oldStatus,
                'to'       => $newStatus,
                'by'       => $actor->id,
            ]);
            return $issue;
        });
    }

    // ── Comment ───────────────────────────────────────────────────

    public function addComment(Issue $issue, User $author, string $body, bool $isInternal = false): IssueComment
    {
        $comment = IssueComment::create([
            'issue_id'    => $issue->id,
            'user_id'     => $author->id,
            'body'        => $body,
            'is_internal' => $isInternal,
        ]);

        Log::info('Issue comment added', ['issue_id' => $issue->id, 'comment_id' => $comment->id, 'internal' => $isInternal]);
        return $comment;
    }

    // ── Queries ───────────────────────────────────────────────────

    public function getListQuery(User $user, array $filters = []): Builder
    {
        $query = Issue::with(['company', 'locker', 'reporter', 'assignee'])
            ->orderByDesc('created_at');

        // Tenant scoping
        $companyIds = $user->accessibleCompanyIds();
        if (!empty($filters['company_id'])) {
            // Filter to specific company (only if accessible)
            $requestedId = (int) $filters['company_id'];
            if (in_array($requestedId, $companyIds, true)) {
                $query->where('company_id', $requestedId);
            } else {
                $query->whereRaw('1=0'); // Unauthorized
            }
        } else {
            count($companyIds) === 1
                ? $query->where('company_id', $companyIds[0])
                : $query->whereIn('company_id', $companyIds);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['assigned_to'])) {
            if ($filters['assigned_to'] === 'me') {
                $query->where('assigned_to', $user->id);
            } elseif ($filters['assigned_to'] === 'unassigned') {
                $query->whereNull('assigned_to');
            } elseif (is_numeric($filters['assigned_to'])) {
                $query->where('assigned_to', (int) $filters['assigned_to']);
            }
        }

        if (!empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', $term)
                  ->orWhere('description', 'like', $term);
            });
        }

        return $query;
    }

    public function getStats(User $user, ?int $companyId = null): array
    {
        // Single round-trip: CASE/SUM replaces 8 separate COUNT clones
        $base = Issue::query();
        $ids  = $user->accessibleCompanyIds();

        if ($companyId && in_array($companyId, $ids, true)) {
            $base->where('company_id', $companyId);
        } elseif (!$user->isSuperAdmin()) {
            count($ids) === 1
                ? $base->where('company_id', $ids[0])
                : $base->whereIn('company_id', $ids);
        }

        $now = now();

        $row = $base->selectRaw("
            COUNT(*) AS total,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS status_open,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS status_in_progress,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS status_pending,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS status_resolved,
            SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS status_closed,
            SUM(CASE WHEN severity = ? AND status IN (?, ?, ?) THEN 1 ELSE 0 END) AS critical_cnt,
            SUM(CASE WHEN due_date IS NOT NULL AND due_date < ? AND status NOT IN (?, ?) THEN 1 ELSE 0 END) AS overdue_cnt
        ", [
            Issue::STATUS_OPEN,
            Issue::STATUS_IN_PROGRESS,
            Issue::STATUS_PENDING,
            Issue::STATUS_RESOLVED,
            Issue::STATUS_CLOSED,
            Issue::SEV_CRITICAL,
            Issue::STATUS_OPEN, Issue::STATUS_IN_PROGRESS, Issue::STATUS_PENDING,
            $now,
            Issue::STATUS_RESOLVED, Issue::STATUS_CLOSED,
        ])->first();

        $total      = (int) $row->total;
        $open       = (int) $row->status_open;
        $inProgress = (int) $row->status_in_progress;
        $pending    = (int) $row->status_pending;
        $resolved   = (int) $row->status_resolved;
        $closed     = (int) $row->status_closed;
        $critical   = (int) $row->critical_cnt;
        $overdue    = (int) $row->overdue_cnt;

        return compact('total', 'open', 'inProgress', 'pending', 'resolved', 'closed', 'critical', 'overdue');
    }

    // ── Authorization Guard ───────────────────────────────────────

    public function authorizeAccess(User $user, Issue $issue): void
    {
        if (!$user->canAccessCompany($issue->company_id)) {
            abort(403, 'You do not have access to this issue.');
        }
    }
}
