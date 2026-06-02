<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\IssueAssignment;
use App\Models\IssueStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * State machine for Issue status transitions.
 *
 * ┌──────────────────────────────────────────────────────────────┐
 * │  OPEN ──────► IN_PROGRESS ──────► RESOLVED ──────► CLOSED  │
 * │    │               │                   │               │    │
 * │    └──► PENDING ◄──┘                   └──► OPEN ◄─────┘   │
 * │              │                                              │
 * │              └──► IN_PROGRESS / RESOLVED / CLOSED          │
 * └──────────────────────────────────────────────────────────────┘
 *
 * Permission rules:
 *   standard   — edit issues + (admin role OR is assignee)
 *   close      — close issues permission
 *   reopen     — any of the above
 *   reopen_closed — admin role only (super_admin | tenant_admin)
 */
class IssueStatusService
{
    // ── State machine ─────────────────────────────────────────────

    /**
     * Standard allowed transitions (ignoring permissions).
     * Key = current status; Value = all statuses reachable from it.
     */
    private const TRANSITIONS = [
        'open'        => ['in_progress', 'pending', 'resolved', 'closed'],
        'in_progress' => ['pending',     'resolved', 'open',    'closed'],
        'pending'     => ['in_progress', 'resolved', 'closed'],
        'resolved'    => ['closed',      'open'],
        'closed'      => ['open'],   // admin-only (checked separately)
    ];

    /**
     * Transitions that additionally require 'close issues' permission.
     */
    private const CLOSE_TRANSITIONS = [
        'open'        => ['closed'],
        'in_progress' => ['closed'],
        'pending'     => ['closed'],
        'resolved'    => ['closed'],
    ];

    // ── Public API ────────────────────────────────────────────────

    /**
     * Return the list of statuses this actor may transition the issue TO.
     * The result is an ordered subset of TRANSITIONS[current_status].
     */
    public function getAllowedTransitions(Issue $issue, User $actor): array
    {
        $from = $issue->status;
        $all  = self::TRANSITIONS[$from] ?? [];

        $isAdmin    = $this->isAdmin($actor);
        $isAssignee = (int) $issue->assigned_to === $actor->id;
        $canEdit    = $actor->can('edit issues') && ($isAdmin || $isAssignee);
        $canClose   = $actor->can('close issues');

        if (!$canEdit && !$canClose) {
            return [];
        }

        return array_values(array_filter($all, function (string $to) use ($from, $isAdmin, $canEdit, $canClose) {
            // Re-opening a closed issue is admin-only
            if ($from === 'closed' && $to === 'open') {
                return $isAdmin;
            }
            // Closing requires close permission
            if (in_array($to, self::CLOSE_TRANSITIONS[$from] ?? [], true)) {
                return $canClose;
            }
            return $canEdit;
        }));
    }

    /**
     * Check whether a specific transition is allowed for this actor.
     */
    public function canTransition(Issue $issue, User $actor, string $toStatus): bool
    {
        return in_array($toStatus, $this->getAllowedTransitions($issue, $actor), true);
    }

    /**
     * Validate the actor has rights to change THIS issue's status at all
     * (access-level check, not transition-level).
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function validateActor(Issue $issue, User $actor): void
    {
        if (!$actor->canAccessCompany($issue->company_id)) {
            abort(403, 'You do not have access to this issue.');
        }

        $isAdmin    = $this->isAdmin($actor);
        $isAssignee = (int) $issue->assigned_to === $actor->id;

        if (!$isAdmin && !$isAssignee && !$actor->can('edit issues')) {
            abort(403, 'Only admins or the assigned technician can change issue status.');
        }
    }

    /**
     * Execute a status transition.
     *
     * @param  string       $source  'web' | 'api' | 'command'
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException  on permission/flow violation
     */
    public function transition(
        Issue   $issue,
        User    $actor,
        string  $toStatus,
        ?string $note   = null,
        string  $source = 'web',
    ): IssueStatusHistory {

        $this->validateActor($issue, $actor);

        if (!$this->canTransition($issue, $actor, $toStatus)) {
            $from = $issue->status;
            abort(422, "Invalid transition: '{$from}' → '{$toStatus}'. " .
                       'Allowed: ' . implode(', ', $this->getAllowedTransitions($issue, $actor) ?: ['none']) . '.');
        }

        return DB::transaction(function () use ($issue, $actor, $toStatus, $note, $source) {
            $fromStatus = $issue->status;

            // ── 1. Update issue ───────────────────────────────────
            $updates = ['status' => $toStatus];

            if ($toStatus === Issue::STATUS_RESOLVED) {
                $updates['resolved_at'] = now();
            } elseif ($toStatus === Issue::STATUS_CLOSED) {
                $updates['closed_at'] = now();
            } elseif (in_array($fromStatus, [Issue::STATUS_RESOLVED, Issue::STATUS_CLOSED], true)
                   && $toStatus === Issue::STATUS_OPEN) {
                // Re-opened — clear both timestamps
                $updates['resolved_at'] = null;
                $updates['closed_at']   = null;
            }

            $issue->update($updates);

            // ── 2. Status history (dedicated log) ─────────────────
            $history = IssueStatusHistory::create([
                'issue_id'    => $issue->id,
                'changed_by'  => $actor->id,
                'from_status' => $fromStatus,
                'to_status'   => $toStatus,
                'note'        => $note,
                'metadata'    => [
                    'source'     => $source,
                    'ip'         => request()->ip(),
                    'user_agent' => substr((string) request()->userAgent(), 0, 200),
                ],
            ]);

            // ── 3. General audit trail (issue_assignments) ────────
            IssueAssignment::create([
                'issue_id'     => $issue->id,
                'type'         => $this->auditType($fromStatus, $toStatus),
                'performed_by' => $actor->id,
                'old_value'    => $fromStatus,
                'new_value'    => $toStatus,
                'note'         => $note,
            ]);

            Log::info('[IssueStatus] Transition', [
                'issue_id' => $issue->id,
                'from'     => $fromStatus,
                'to'       => $toStatus,
                'actor'    => $actor->id,
                'source'   => $source,
            ]);

            $issue->refresh();

            return $history;
        });
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function isAdmin(User $actor): bool
    {
        return $actor->isSuperAdmin() || $actor->hasRole('tenant_admin');
    }

    private function auditType(string $from, string $to): string
    {
        return match (true) {
            $to === Issue::STATUS_RESOLVED                       => IssueAssignment::TYPE_RESOLVED,
            $to === Issue::STATUS_CLOSED                         => IssueAssignment::TYPE_CLOSED,
            $to === Issue::STATUS_OPEN && $from !== 'open'       => IssueAssignment::TYPE_REOPENED,
            default                                              => IssueAssignment::TYPE_STATUS_CHANGED,
        };
    }
}
