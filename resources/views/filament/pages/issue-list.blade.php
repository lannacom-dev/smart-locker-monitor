<x-filament-panels::page>
@php
    use App\Models\Issue;

    $user      = auth()->user();
    $stats     = $this->getStats();
    $issues    = $this->getIssues();
    $companies = $this->getCompanies();
    $lockers   = $this->getLockers();

    $statusColors = [
        'open'        => 'blue',
        'in_progress' => 'yellow',
        'resolved'    => 'green',
        'closed'      => 'gray',
    ];
    $statusBg = \App\Models\Issue::statusBadgeClasses();
    $severityBg = [
        'low'      => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
        'medium'   => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
        'high'     => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
        'critical' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    ];
    $severityLeft   = \App\Models\Issue::statusBorderClasses();  // reuse per-status colours on rows
@endphp

{{-- ── Stats Row ────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 mb-6">

    {{-- Total --}}
    <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 shadow-sm flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Total') }}</span>
            <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-1.5">
                <x-heroicon-s-clipboard-document-list class="w-4 h-4 text-gray-500 dark:text-gray-400"/>
            </div>
        </div>
        <p class="text-3xl font-extrabold text-gray-800 dark:text-gray-100 leading-none">{{ $stats['total'] }}</p>
    </div>

    {{-- Open --}}
    <div class="rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 p-4 shadow-md text-white flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold opacity-80 uppercase tracking-wider">{{ __('Open') }}</span>
            <div class="bg-white/20 rounded-lg p-1.5">
                <x-heroicon-s-folder-open class="w-4 h-4"/>
            </div>
        </div>
        <p class="text-3xl font-extrabold leading-none">{{ $stats['open'] }}</p>
    </div>

    {{-- In Progress --}}
    <div class="rounded-xl bg-gradient-to-br from-yellow-400 to-amber-500 p-4 shadow-md text-white flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold opacity-80 uppercase tracking-wider">{{ __('In Progress') }}</span>
            <div class="bg-white/20 rounded-lg p-1.5">
                <x-heroicon-s-arrow-path class="w-4 h-4"/>
            </div>
        </div>
        <p class="text-3xl font-extrabold leading-none">{{ $stats['inProgress'] }}</p>
    </div>

    {{-- Pending --}}
    <div class="rounded-xl bg-white dark:bg-gray-800 border border-amber-200 dark:border-amber-700/50 p-4 shadow-sm flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-amber-600 dark:text-amber-400 uppercase tracking-wider">{{ __('Pending') }}</span>
            <div class="bg-amber-50 dark:bg-amber-900/30 rounded-lg p-1.5">
                <x-heroicon-s-clock class="w-4 h-4 text-amber-500"/>
            </div>
        </div>
        <p class="text-3xl font-extrabold text-amber-600 dark:text-amber-400 leading-none">{{ $stats['pending'] }}</p>
    </div>

    {{-- Resolved --}}
    <div class="rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 p-4 shadow-md text-white flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold opacity-80 uppercase tracking-wider">{{ __('Resolved') }}</span>
            <div class="bg-white/20 rounded-lg p-1.5">
                <x-heroicon-s-check-badge class="w-4 h-4"/>
            </div>
        </div>
        <p class="text-3xl font-extrabold leading-none">{{ $stats['resolved'] }}</p>
    </div>

    {{-- Critical --}}
    <div class="rounded-xl bg-gradient-to-br from-red-500 to-rose-600 p-4 shadow-md text-white flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold opacity-80 uppercase tracking-wider">{{ __('Critical') }}</span>
            <div class="bg-white/20 rounded-lg p-1.5">
                <x-heroicon-s-fire class="w-4 h-4"/>
            </div>
        </div>
        <p class="text-3xl font-extrabold leading-none">{{ $stats['critical'] }}</p>
    </div>

    {{-- Overdue --}}
    <div class="rounded-xl bg-white dark:bg-gray-800 border border-orange-200 dark:border-orange-700/50 p-4 shadow-sm flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-orange-600 dark:text-orange-400 uppercase tracking-wider">{{ __('Overdue') }}</span>
            <div class="bg-orange-50 dark:bg-orange-900/30 rounded-lg p-1.5">
                <x-heroicon-s-exclamation-circle class="w-4 h-4 text-orange-500"/>
            </div>
        </div>
        <p class="text-3xl font-extrabold text-orange-600 dark:text-orange-400 leading-none">{{ $stats['overdue'] }}</p>
    </div>

</div>

{{-- ── Toolbar ──────────────────────────────────────────────────────── --}}
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 mb-4">
    <div class="flex flex-wrap gap-3 items-end">

        {{-- Search --}}
        <div class="flex-1 min-w-[200px]">
            <input type="text"
                   wire:model.live.debounce.400ms="search"
                   placeholder="{{ __('Search title or description…') }}"
                   class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-transparent" />
        </div>

        {{-- Company filter (super_admin only) --}}
        @if($user->isSuperAdmin())
        <div>
            <select wire:model.live="filterCompany"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 text-gray-700 dark:text-gray-200">
                <option value="">{{ __('All Companies') }}</option>
                @foreach($companies as $c)
                <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- Status --}}
        <div>
            <select wire:model.live="filterStatus"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 text-gray-700 dark:text-gray-200">
                @foreach($this->statusOptions() as $val => $label)
                <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Severity --}}
        <div>
            <select wire:model.live="filterSeverity"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 text-gray-700 dark:text-gray-200">
                @foreach($this->severityOptions() as $val => $label)
                <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Category --}}
        <div>
            <select wire:model.live="filterCategory"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 text-gray-700 dark:text-gray-200">
                @foreach($this->categoryOptions() as $val => $label)
                <option value="{{ $val }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Assigned to --}}
        <div>
            <select wire:model.live="filterAssignee"
                    class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm px-3 py-2 text-gray-700 dark:text-gray-200">
                <option value="">{{ __('All Assignees') }}</option>
                <option value="me">{{ __('Assigned to Me') }}</option>
                <option value="unassigned">{{ __('Unassigned') }}</option>
            </select>
        </div>

        {{-- Create button --}}
        @can('create issues')
        <button wire:click="openCreateModal"
                class="ml-auto inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700 transition-colors">
            <x-heroicon-o-plus class="w-4 h-4"/>
            {{ __('New Issue') }}
        </button>
        @endcan

    </div>
</div>

{{-- ── Issues Table ─────────────────────────────────────────────────── --}}
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">

    @if($issues->isEmpty())
    <div class="py-16 text-center text-gray-400 dark:text-gray-500">
        <x-heroicon-o-clipboard-document-list class="w-12 h-12 mx-auto mb-3 opacity-50"/>
        <p class="text-sm">{{ __('No issues found matching your filters.') }}</p>
    </div>
    @else

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-750 border-b border-gray-100 dark:border-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('ID') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Title') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Severity') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Category') }}</th>
                    @if($user->isSuperAdmin())<th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Company') }}</th>@endif
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Locker') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Assignee') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Due') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Created') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($issues as $issue)
                @php
                    $isOverdue = $issue->due_date && $issue->due_date->isPast()
                                 && !in_array($issue->status, ['resolved','closed']);
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors {{ $severityLeft[$issue->severity] ?? '' }}">
                    <td class="px-4 py-3 text-gray-400 dark:text-gray-500 font-mono text-xs">#{{ $issue->id }}</td>
                    <td class="px-4 py-3 max-w-xs">
                        <a href="{{ route('filament.admin.pages.issues.{record}', ['record' => $issue->id]) }}"
                           class="font-medium text-gray-900 dark:text-gray-100 hover:text-primary-600 dark:hover:text-primary-400 line-clamp-2">
                            {{ $issue->title }}
                        </a>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $statusBg[$issue->status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $issue->statusLabel() }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $severityBg[$issue->severity] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ $issue->severityLabel() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ $issue->categoryLabel() }}</td>
                    @if($user->isSuperAdmin())
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ $issue->company?->name ?? '—' }}</td>
                    @endif
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ $issue->locker?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ $issue->assignee?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs {{ $isOverdue ? 'text-red-600 font-semibold' : 'text-gray-500 dark:text-gray-400' }}">
                        {{ $issue->due_date ? $issue->due_date->format('d M Y') : '—' }}
                        @if($isOverdue)<span class="ml-1 text-red-500">⚠</span>@endif
                    </td>
                    <td class="px-4 py-3 text-gray-400 dark:text-gray-500 text-xs whitespace-nowrap">
                        {{ $issue->created_at->format('d M Y') }}
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('filament.admin.pages.issues.{record}', ['record' => $issue->id]) }}"
                           class="text-xs text-primary-600 dark:text-primary-400 hover:underline">
                            {{ __('View') }}
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($issues->hasPages())
    <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
        {{ $issues->links() }}
    </div>
    @endif

    @endif
</div>

{{-- ── Create Issue Modal ───────────────────────────────────────────── --}}
@if($showCreateModal)
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
     wire:click.self="$set('showCreateModal', false)">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">

        <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('New Issue') }}</h2>
            <button wire:click="$set('showCreateModal', false)"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                <x-heroicon-o-x-mark class="w-5 h-5"/>
            </button>
        </div>

        <div class="p-6 space-y-4">

            {{-- Title --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Title') }} <span class="text-red-500">*</span></label>
                <input type="text" wire:model="newTitle"
                       placeholder="{{ __('Brief description of the issue') }}"
                       class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-transparent" />
                @error('newTitle') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Description') }} <span class="text-red-500">*</span></label>
                <textarea wire:model="newDescription" rows="4"
                          placeholder="{{ __('Detailed description, steps to reproduce, impact…') }}"
                          class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none"></textarea>
                @error('newDescription') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">

                {{-- Category --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Category') }}</label>
                    <select wire:model="newCategory"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-700 dark:text-gray-200">
                        @foreach(\App\Models\Issue::categoryOptions() as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Severity --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Severity') }}</label>
                    <select wire:model="newSeverity"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-700 dark:text-gray-200">
                        @foreach(\App\Models\Issue::severityOptions() as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Locker --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Related Locker') }}</label>
                    <select wire:model="newLockerId"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-700 dark:text-gray-200">
                        <option value="">{{ __('— None —') }}</option>
                        @foreach($lockers as $locker)
                        <option value="{{ $locker->id }}">{{ $locker->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Due Date --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Due Date') }}</label>
                    <input type="date" wire:model="newDueDate"
                           class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-700 dark:text-gray-200" />
                </div>

            </div>

        </div>

        <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-100 dark:border-gray-700">
            <button wire:click="$set('showCreateModal', false)"
                    class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition-colors">
                {{ __('Cancel') }}
            </button>
            <button wire:click="createIssue"
                    class="px-4 py-2 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                <span wire:loading.remove wire:target="createIssue">{{ __('Create Issue') }}</span>
                <span wire:loading wire:target="createIssue">{{ __('Creating…') }}</span>
            </button>
        </div>

    </div>
</div>
@endif

</x-filament-panels::page>
