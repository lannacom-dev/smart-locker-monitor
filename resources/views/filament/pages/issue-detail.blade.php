<x-filament-panels::page>
@php
    use App\Models\Issue;
    use App\Models\IssueAssignment;

    $user  = auth()->user();
    $issue = $this->record;

    $canEdit   = $user->can('edit issues');
    $canAssign = $user->can('assign issues');

    $badgeClasses  = Issue::statusBadgeClasses();
    $severityBg    = [
        'low'      => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        'medium'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        'high'     => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
        'critical' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    ];
    $severityBorder = [
        'low'      => 'border-l-4 border-gray-300',
        'medium'   => 'border-l-4 border-blue-400',
        'high'     => 'border-l-4 border-orange-400',
        'critical' => 'border-l-4 border-red-500',
    ];
    $auditTypeColors = [
        IssueAssignment::TYPE_CREATED        => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        IssueAssignment::TYPE_ASSIGNED       => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
        IssueAssignment::TYPE_UNASSIGNED     => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        IssueAssignment::TYPE_STATUS_CHANGED => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
        IssueAssignment::TYPE_RESOLVED       => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
        IssueAssignment::TYPE_CLOSED         => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        IssueAssignment::TYPE_REOPENED       => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
    ];
    $assignableUsers = $this->getAssignableUsers();

    $isOverdue = $issue->due_date && $issue->due_date->isPast() && $issue->isActive();

    $severityGradient = [
        'critical' => 'from-red-600 to-rose-700',
        'high'     => 'from-orange-500 to-red-600',
        'medium'   => 'from-blue-500 to-indigo-700',
        'low'      => 'from-slate-500 to-gray-700',
    ];
@endphp

{{-- ── Gradient Header Banner ───────────────────────────────────────── --}}
<div class="rounded-2xl bg-gradient-to-r {{ $severityGradient[$issue->severity] ?? 'from-slate-500 to-gray-700' }} p-6 mb-6 shadow-lg text-white">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-start gap-4">
            <a href="{{ route('filament.admin.pages.issues') }}"
               class="mt-0.5 inline-flex items-center gap-1 text-sm text-white/70 hover:text-white transition-colors">
                <x-heroicon-o-arrow-left class="w-4 h-4"/>
                {{ __('Back to Issues') }}
            </a>
            <div>
                <div class="flex items-center gap-2 flex-wrap mb-1">
                    <span class="font-mono text-xs text-white/60">#{{ $issue->id }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-white/20 text-white border border-white/30">
                        {{ $issue->severityLabel() }}
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-white/15 text-white border border-white/25">
                        {{ $issue->categoryLabel() }}
                    </span>
                    @if($isOverdue)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-white/20 text-white border border-white/30">
                        ⚠ {{ __('Overdue') }}
                    </span>
                    @endif
                </div>
                <h1 class="text-xl font-bold text-white line-clamp-2">{{ $issue->title }}</h1>
                @if($issue->locker)
                <p class="text-sm text-white/70 mt-0.5">{{ $issue->locker->name }}</p>
                @endif
            </div>
        </div>
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-white/15 text-white border border-white/25">
            @if($issue->isActive())
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute h-full w-full rounded-full bg-white opacity-60"></span>
                    <span class="relative h-2 w-2 rounded-full bg-white"></span>
                </span>
            @else
                <span class="w-2 h-2 rounded-full bg-white/70"></span>
            @endif
            {{ $issue->statusLabel() }}
        </span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ───────────────────────────────────────────────────────────────
         LEFT COLUMN: Issue content + Activity timeline
    ─────────────────────────────────────────────────────────────────── --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- ── Issue Card ───────────────────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700
                    {{ $severityBorder[$issue->severity] ?? '' }}">
            <div class="p-6">

                {{-- Badges row --}}
                <div class="flex flex-wrap items-center gap-2 mb-4">
                    <span class="font-mono text-xs text-gray-400 dark:text-gray-500">#{{ $issue->id }}</span>

                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold
                                 ring-1 ring-inset {{ $badgeClasses[$issue->status] ?? '' }}">
                        @if($issue->isActive())
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute h-full w-full rounded-full opacity-60
                                         {{ \App\Models\Issue::statusDotClasses()[$issue->status] ?? 'bg-gray-400' }}"></span>
                            <span class="relative h-2 w-2 rounded-full
                                         {{ \App\Models\Issue::statusDotClasses()[$issue->status] ?? 'bg-gray-400' }}"></span>
                        </span>
                        @endif
                        {{ $issue->statusLabel() }}
                    </span>

                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium
                                 {{ $severityBg[$issue->severity] ?? '' }}">
                        {{ $issue->severityLabel() }}
                    </span>
                    <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium
                                 bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        {{ $issue->categoryLabel() }}
                    </span>
                    @if($isOverdue)
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium
                                 bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300">
                        ⚠ {{ __('Overdue') }}
                    </span>
                    @endif
                </div>

                <h1 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-3">
                    {{ $issue->title }}
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">
                    {{ $issue->description }}
                </p>
            </div>

            {{-- Meta strip --}}
            <div class="border-t border-gray-100 dark:border-gray-700
                        grid grid-cols-2 sm:grid-cols-3 divide-x divide-y
                        divide-gray-100 dark:divide-gray-700">
                @php
                    $metas = [
                        [__('Reported By'), $issue->reporter?->name ?? '—'],
                        [__('Company'),     $issue->company?->name ?? '—'],
                        [__('Locker'),      $issue->locker?->name ?? '—'],
                        [__('Assigned To'), $issue->assignee?->name ?? '—'],
                        [__('Created'),     $issue->created_at->format('d M Y H:i')],
                        [__('Due Date'),    $issue->due_date ? $issue->due_date->format('d M Y') : '—'],
                    ];
                    if ($issue->resolved_at) {
                        $metas[] = [__('Resolved At'), $issue->resolved_at->format('d M Y H:i')];
                    }
                    if ($issue->closed_at) {
                        $metas[] = [__('Closed At'), $issue->closed_at->format('d M Y H:i')];
                    }
                @endphp
                @foreach($metas as [$label, $value])
                <div class="px-4 py-3">
                    <div class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">{{ $label }}</div>
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-200
                                {{ $label === 'Due Date' && $isOverdue ? 'text-red-600' : '' }}">
                        {{ $value }}
                        @if($label === 'Due Date' && $isOverdue) ⚠ @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ── Status History Timeline ───────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                <x-heroicon-o-clock class="w-5 h-5 text-gray-400"/>
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Status History') }}</h2>
                <span class="ml-auto text-xs text-gray-400 dark:text-gray-500">
                    {{ $issue->statusHistories->count() }} {{ $issue->statusHistories->count() !== 1 ? __('transitions') : __('transition') }}
                </span>
            </div>
            <div class="p-6">
                <x-issue-status-timeline :histories="$issue->statusHistories" />
            </div>
        </div>

        {{-- ── Activity Timeline (comments + general audit) ────────────── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                <x-heroicon-o-chat-bubble-left-right class="w-5 h-5 text-gray-400"/>
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Activity') }}</h2>
            </div>

            @php
                $timelineItems = collect();
                foreach($issue->assignments as $e) {
                    $timelineItems->push(['type' => 'audit',   'at' => $e->created_at, 'data' => $e]);
                }
                foreach($issue->comments as $c) {
                    $timelineItems->push(['type' => 'comment', 'at' => $c->created_at, 'data' => $c]);
                }
                $timelineItems = $timelineItems->sortBy('at');
            @endphp

            <div class="p-6 space-y-4">
                @forelse($timelineItems as $item)

                    @if($item['type'] === 'audit')
                    @php $entry = $item['data']; @endphp
                    <div class="flex gap-3">
                        <div class="mt-0.5 flex-shrink-0">
                            <span class="text-base">{{ $entry->typeIcon() }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium
                                             {{ $auditTypeColors[$entry->type] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $entry->typeLabel() }}
                                </span>
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                    {{ $entry->performer?->name ?? __('System') }}
                                </span>
                                <span class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ $entry->created_at->diffForHumans() }}
                                </span>
                            </div>
                            @if($entry->old_value || $entry->new_value)
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                @if($entry->old_value)<span class="line-through text-red-400">{{ $entry->old_value }}</span>@endif
                                @if($entry->old_value && $entry->new_value) → @endif
                                @if($entry->new_value)<span class="text-green-600 dark:text-green-400 font-medium">{{ $entry->new_value }}</span>@endif
                            </div>
                            @endif
                            @if($entry->note)<p class="mt-1 text-xs italic text-gray-500 dark:text-gray-400">{{ $entry->note }}</p>@endif
                        </div>
                    </div>

                    @else
                    @php $comment = $item['data']; @endphp
                    <div class="flex gap-3 {{ $comment->is_internal ? '' : '' }}">
                        <div class="mt-0.5 flex-shrink-0 w-8 h-8 rounded-full
                                    {{ $comment->is_internal ? 'bg-amber-100 dark:bg-amber-900/30' : 'bg-primary-100 dark:bg-primary-900/30' }}
                                    flex items-center justify-center">
                            <span class="text-xs font-bold
                                         {{ $comment->is_internal ? 'text-amber-700 dark:text-amber-300' : 'text-primary-700 dark:text-primary-300' }}">
                                {{ strtoupper(substr($comment->user?->name ?? '?', 0, 1)) }}
                            </span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="{{ $comment->is_internal
                                    ? 'bg-amber-50 dark:bg-amber-900/10 border border-dashed border-amber-300 dark:border-amber-600'
                                    : 'bg-gray-50 dark:bg-gray-750' }} rounded-lg px-4 py-3">
                                <div class="flex items-center gap-2 mb-1.5">
                                    <span class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                        {{ $comment->user?->name ?? '—' }}
                                    </span>
                                    @if($comment->is_internal)
                                    <span class="text-xs px-1.5 py-0.5 rounded bg-amber-100 text-amber-700
                                                 dark:bg-amber-900/30 dark:text-amber-300 font-medium">
                                        {{ __('Internal Note') }}
                                    </span>
                                    @endif
                                    <span class="text-xs text-gray-400 dark:text-gray-500 ml-auto">
                                        {{ $comment->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-wrap">
                                    {{ $comment->body }}
                                </p>
                            </div>
                        </div>
                    </div>
                    @endif

                @empty
                <p class="text-center text-sm text-gray-400 dark:text-gray-500 py-4">{{ __('No activity yet.') }}</p>
                @endforelse
            </div>

            {{-- Add Comment --}}
            <div class="px-6 pb-6 border-t border-gray-100 dark:border-gray-700 pt-4">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">{{ __('Add Comment') }}</h3>
                <textarea wire:model="commentBody" rows="3"
                          placeholder="{{ __('Write a comment…') }}"
                          class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                 px-3 py-2 text-sm text-gray-900 dark:text-gray-100
                                 focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none mb-3"></textarea>
                @error('commentBody') <p class="text-red-500 text-xs mb-2">{{ $message }}</p> @enderror

                <div class="flex items-center justify-between">
                    @if($canEdit)
                    <label class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400 cursor-pointer">
                        <input type="checkbox" wire:model="commentInternal"
                               class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                        {{ __('Internal note (staff only)') }}
                    </label>
                    @else
                    <span></span>
                    @endif

                    <button wire:click="submitComment"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary-600 text-white
                                   text-sm font-medium hover:bg-primary-700 transition-colors">
                        <span wire:loading.remove wire:target="submitComment">{{ __('Post Comment') }}</span>
                        <span wire:loading wire:target="submitComment">{{ __('Posting…') }}</span>
                    </button>
                </div>
            </div>
        </div>

    </div>

    {{-- ───────────────────────────────────────────────────────────────
         RIGHT COLUMN: Status changer + Assign + Info
    ─────────────────────────────────────────────────────────────────── --}}
    <div class="space-y-4">

        {{-- ── Status Changer Component ─────────────────────────────── --}}
        <x-issue-status-changer
            :issue="$issue"
            wire-method="changeStatus"
        />

        {{-- ── Assign Panel ─────────────────────────────────────────── --}}
        @if($canAssign)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                @if($issue->assignee)
                    {{ __('Reassign') }}
                    <span class="font-normal text-gray-400">({{ $issue->assignee->name }})</span>
                @else
                    {{ __('Assign Issue') }}
                @endif
            </h3>
            <select wire:model="assignUserId"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                           px-3 py-2 text-sm text-gray-700 dark:text-gray-200 mb-3">
                <option value="">{{ __('— Unassign —') }}</option>
                @foreach($assignableUsers as $u)
                <option value="{{ $u->id }}" @selected((int)$assignUserId === $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
            <input type="text" wire:model="assignNote" placeholder="{{ __('Optional note…') }}"
                   class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                          px-3 py-2 text-sm text-gray-900 dark:text-gray-100
                          focus:ring-2 focus:ring-primary-500 focus:border-transparent mb-3" />
            <button wire:click="submitAssign"
                    class="w-full px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium
                           hover:bg-indigo-700 transition-colors">
                <span wire:loading.remove wire:target="submitAssign">{{ __('Save Assignment') }}</span>
                <span wire:loading wire:target="submitAssign">{{ __('Saving…') }}</span>
            </button>
        </div>
        @endif

        {{-- ── Issue Metadata ───────────────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">{{ __('Details') }}</h3>
            <dl class="space-y-2 text-sm">
                @php
                    $infoRows = [
                        [__('ID'),         '#' . $issue->id],
                        [__('Reporter'),   $issue->reporter?->name ?? '—'],
                        [__('Assignee'),   $issue->assignee?->name ?? __('Unassigned')],
                        [__('Category'),   $issue->categoryLabel()],
                        [__('Company'),    $issue->company?->name ?? '—'],
                    ];
                    if ($issue->locker) {
                        $infoRows[] = [__('Locker'), $issue->locker->name];
                    }
                    if ($issue->due_date) {
                        $infoRows[] = [__('Due'), $issue->due_date->format('d M Y') . ($isOverdue ? ' ⚠' : '')];
                    }
                    $infoRows[] = [__('Created'),   $issue->created_at->format('d M Y')];
                    $infoRows[] = [__('Comments'),  $issue->comments->count()];
                    $infoRows[] = [__('Transitions'), $issue->statusHistories->count()];
                @endphp
                @foreach($infoRows as [$k, $v])
                <div class="flex justify-between">
                    <dt class="text-gray-400 dark:text-gray-500">{{ $k }}</dt>
                    <dd class="font-medium text-gray-600 dark:text-gray-300 text-right">{{ $v }}</dd>
                </div>
                @endforeach
            </dl>
        </div>

    </div>
</div>

</x-filament-panels::page>
