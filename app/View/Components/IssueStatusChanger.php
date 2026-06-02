<?php

namespace App\View\Components;

use App\Models\Issue;
use App\Services\IssueStatusService;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Blade component that renders the status-change UI panel.
 *
 * Uses Alpine.js for local UI state (selected target status, note input,
 * confirmation panel visibility). Calls the parent Livewire component's
 * `changeStatus(toStatus, note)` method via `$wire.changeStatus(...)`.
 *
 * Usage (inside a Livewire component view):
 *   <x-issue-status-changer :issue="$record" wire-method="changeStatus" />
 */
class IssueStatusChanger extends Component
{
    /** The current issue. */
    public Issue $issue;

    /**
     * Livewire method on the parent component to call when confirming.
     * Signature: changeStatus(string $toStatus, string|null $note)
     */
    public string $wireMethod;

    /** Computed: transitions allowed for the current auth user. */
    public array $allowedTransitions;

    /** Whether the current user may change the status at all. */
    public bool $canChange;

    public function __construct(Issue $issue, string $wireMethod = 'changeStatus')
    {
        $this->issue      = $issue;
        $this->wireMethod = $wireMethod;

        $user = auth()->user();
        if ($user) {
            $service                  = app(IssueStatusService::class);
            $this->allowedTransitions = $service->getAllowedTransitions($issue, $user);
            $this->canChange          = count($this->allowedTransitions) > 0;
        } else {
            $this->allowedTransitions = [];
            $this->canChange          = false;
        }
    }

    public function render(): View
    {
        return view('components.issue-status-changer', [
            'badgeClasses'  => Issue::statusBadgeClasses(),
            'buttonClasses' => Issue::statusButtonClasses(),
            'statusLabels'  => Issue::statusOptions(),
            'dotClasses'    => Issue::statusDotClasses(),
        ]);
    }
}
