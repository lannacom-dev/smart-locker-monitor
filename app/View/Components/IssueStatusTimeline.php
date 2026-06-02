<?php

namespace App\View\Components;

use App\Models\Issue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Blade component that renders the status history timeline.
 *
 * Usage:
 *   <x-issue-status-timeline :histories="$record->statusHistories()->with('changedBy')->get()" />
 */
class IssueStatusTimeline extends Component
{
    public function __construct(
        /** @var Collection<IssueStatusHistory> */
        public Collection|array $histories,
        public bool $compact = false,
    ) {}

    public function render(): View
    {
        return view('components.issue-status-timeline', [
            'badgeClasses' => Issue::statusBadgeClasses(),
            'dotClasses'   => Issue::statusDotClasses(),
            'statusLabels' => Issue::statusOptions(),
        ]);
    }
}
