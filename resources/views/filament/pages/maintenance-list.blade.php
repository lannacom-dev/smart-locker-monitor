<x-filament-panels::page>
@php
    use App\Models\CorrectiveMaintenance;

    $user        = auth()->user();
    $stats       = $this->getStats();
    $maintenances = $this->getMaintenances();
    $companies   = $this->getCompanies();

    $statusBadge   = CorrectiveMaintenance::statusBadgeClasses();
    $priorityBadge = CorrectiveMaintenance::priorityBadgeClasses();
    $priorityBorder = CorrectiveMaintenance::priorityBorderClasses();
@endphp

{{-- ── Stats Row ────────────────────────────────────────────────────── --}}
<div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 mb-6">

    {{-- Total --}}
    <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 shadow-sm flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Total') }}</span>
            <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-1.5">
                <x-heroicon-s-wrench-screwdriver class="w-4 h-4 text-gray-500 dark:text-gray-400"/>
            </div>
        </div>
        <p class="text-3xl font-extrabold text-gray-800 dark:text-gray-100 leading-none">{{ $stats['total'] }}</p>
    </div>

    {{-- Created --}}
    <div class="rounded-xl bg-gradient-to-br from-sky-500 to-blue-600 p-4 shadow-md text-white flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold opacity-80 uppercase tracking-wider">{{ __('Created') }}</span>
            <div class="bg-white/20 rounded-lg p-1.5">
                <x-heroicon-s-plus-circle class="w-4 h-4"/>
            </div>
        </div>
        <p class="text-3xl font-extrabold leading-none">{{ $stats['created'] }}</p>
    </div>

    {{-- In Progress --}}
    <div class="rounded-xl bg-gradient-to-br from-yellow-400 to-amber-500 p-4 shadow-md text-white flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold opacity-80 uppercase tracking-wider">{{ __('In Progress') }}</span>
            <div class="bg-white/20 rounded-lg p-1.5">
                <x-heroicon-s-arrow-path class="w-4 h-4"/>
            </div>
        </div>
        <p class="text-3xl font-extrabold leading-none">{{ $stats['in_progress'] }}</p>
    </div>

    {{-- Completed --}}
    <div class="rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 p-4 shadow-md text-white flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold opacity-80 uppercase tracking-wider">{{ __('Completed') }}</span>
            <div class="bg-white/20 rounded-lg p-1.5">
                <x-heroicon-s-check-badge class="w-4 h-4"/>
            </div>
        </div>
        <p class="text-3xl font-extrabold leading-none">{{ $stats['completed'] }}</p>
    </div>

    {{-- Cancelled --}}
    <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 shadow-sm flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Cancelled') }}</span>
            <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-1.5">
                <x-heroicon-s-x-circle class="w-4 h-4 text-gray-400"/>
            </div>
        </div>
        <p class="text-3xl font-extrabold text-gray-500 dark:text-gray-400 leading-none">{{ $stats['cancelled'] }}</p>
    </div>

    {{-- Urgent --}}
    <div class="rounded-xl bg-gradient-to-br from-red-500 to-rose-600 p-4 shadow-md text-white flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold opacity-80 uppercase tracking-wider">{{ __('Urgent') }}</span>
            <div class="bg-white/20 rounded-lg p-1.5">
                <x-heroicon-s-bolt class="w-4 h-4"/>
            </div>
        </div>
        <p class="text-3xl font-extrabold leading-none">{{ $stats['urgent'] }}</p>
    </div>

    {{-- Unassigned --}}
    <div class="rounded-xl bg-white dark:bg-gray-800 border border-orange-200 dark:border-orange-700/50 p-4 shadow-sm flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-orange-600 dark:text-orange-400 uppercase tracking-wider">{{ __('Unassigned') }}</span>
            <div class="bg-orange-50 dark:bg-orange-900/30 rounded-lg p-1.5">
                <x-heroicon-s-user-minus class="w-4 h-4 text-orange-500"/>
            </div>
        </div>
        <p class="text-3xl font-extrabold text-orange-600 dark:text-orange-400 leading-none">{{ $stats['unassigned'] }}</p>
    </div>

</div>

{{-- ── Toolbar ──────────────────────────────────────────────────────── --}}
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4 mb-4">
    <div class="flex flex-wrap gap-3 items-end">

        {{-- Search --}}
        <div class="flex-1 min-w-[200px]">
            <input type="text"
                   wire:model.live.debounce.400ms="search"
                   placeholder="{{ __('Search title, description, root cause…') }}"
                   class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                          text-sm px-3 py-2 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500" />
        </div>

        {{-- Company (super_admin) --}}
        @if($user->isSuperAdmin())
        <select wire:model.live="filterCompany"
                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                       text-sm px-3 py-2 text-gray-700 dark:text-gray-200">
            <option value="">{{ __('All Companies') }}</option>
            @foreach($companies as $c)
            <option value="{{ $c->id }}">{{ $c->name }}</option>
            @endforeach
        </select>
        @endif

        {{-- Status --}}
        <select wire:model.live="filterStatus"
                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                       text-sm px-3 py-2 text-gray-700 dark:text-gray-200">
            @foreach($this->statusOptions() as $val => $label)
            <option value="{{ $val }}">{{ $label }}</option>
            @endforeach
        </select>

        {{-- Priority --}}
        <select wire:model.live="filterPriority"
                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                       text-sm px-3 py-2 text-gray-700 dark:text-gray-200">
            @foreach($this->priorityOptions() as $val => $label)
            <option value="{{ $val }}">{{ $label }}</option>
            @endforeach
        </select>

        {{-- Technician --}}
        <select wire:model.live="filterTechnician"
                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                       text-sm px-3 py-2 text-gray-700 dark:text-gray-200">
            <option value="">{{ __('All Technicians') }}</option>
            <option value="me">{{ __('My Tasks') }}</option>
            <option value="unassigned">{{ __('Unassigned') }}</option>
        </select>

        @can('create maintenance')
        <button wire:click="openCreateModal"
                class="ml-auto inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2
                       text-sm font-medium text-white hover:bg-primary-700 transition-colors">
            <x-heroicon-o-plus class="w-4 h-4"/>
            {{ __('New Maintenance') }}
        </button>
        @endcan

    </div>
</div>

{{-- ── Table ────────────────────────────────────────────────────────── --}}
<div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">

    @if($maintenances->isEmpty())
    <div class="py-16 text-center text-gray-400 dark:text-gray-500">
        <x-heroicon-o-wrench-screwdriver class="w-12 h-12 mx-auto mb-3 opacity-50"/>
        <p class="text-sm">{{ __('No maintenance records found.') }}</p>
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-gray-750 border-b border-gray-100 dark:border-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('ID') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Title') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Priority') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Locker') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Issue') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Technician') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Scheduled') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Created') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($maintenances as $m)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors
                           {{ $priorityBorder[$m->priority] ?? '' }}">
                    <td class="px-4 py-3 font-mono text-xs text-gray-400">#{{ $m->id }}</td>
                    <td class="px-4 py-3 max-w-xs">
                        <a href="{{ route('filament.admin.pages.maintenance.{record}', ['record' => $m->id]) }}"
                           class="font-medium text-gray-900 dark:text-gray-100 hover:text-primary-600 line-clamp-2">
                            {{ $m->title }}
                        </a>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-medium
                                     ring-1 ring-inset {{ $statusBadge[$m->status] ?? '' }}">
                            @if($m->isActive())
                            <span class="h-1.5 w-1.5 rounded-full animate-pulse
                                         {{ CorrectiveMaintenance::statusDotClasses()[$m->status] ?? 'bg-gray-400' }}"></span>
                            @endif
                            {{ $m->statusLabel() }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium
                                     {{ $priorityBadge[$m->priority] ?? '' }}">
                            {{ $m->priorityLabel() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ $m->locker?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                        @if($m->issue)
                        <a href="{{ route('filament.admin.pages.issues.{record}', ['record' => $m->issue_id]) }}"
                           class="text-primary-600 hover:underline">#{{ $m->issue_id }}</a>
                        @else
                        —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                        {{ $m->technician?->name ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        {{ $m->scheduled_date ? $m->scheduled_date->format('d M Y') : '—' }}
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-400 whitespace-nowrap">
                        {{ $m->created_at->format('d M Y') }}
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('filament.admin.pages.maintenance.{record}', ['record' => $m->id]) }}"
                           class="text-xs text-primary-600 hover:underline">{{ __('View') }}</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($maintenances->hasPages())
    <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
        {{ $maintenances->links() }}
    </div>
    @endif
    @endif
</div>

{{-- ── Create Modal ─────────────────────────────────────────────────── --}}
@if($showCreateModal)
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
     wire:click.self="$set('showCreateModal', false)">
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">

        <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ __('New Corrective Maintenance') }}</h2>
            <button wire:click="$set('showCreateModal', false)" class="text-gray-400 hover:text-gray-600">
                <x-heroicon-o-x-mark class="w-5 h-5"/>
            </button>
        </div>

        <div class="p-6 space-y-4">

            {{-- Locker (required) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('Locker') }} <span class="text-red-500">*</span>
                </label>
                <select wire:model="newLockerId"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                               px-3 py-2 text-sm text-gray-700 dark:text-gray-200">
                    <option value="">{{ __('— Select locker —') }}</option>
                    @foreach($this->getLockers() as $locker)
                    <option value="{{ $locker->id }}">{{ $locker->name }}</option>
                    @endforeach
                </select>
                @error('newLockerId') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Title --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('Title') }} <span class="text-red-500">*</span>
                </label>
                <input type="text" wire:model="newTitle"
                       placeholder="{{ __('Brief description of the maintenance task') }}"
                       class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                              px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500" />
                @error('newTitle') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Description --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    {{ __('Description') }} <span class="text-red-500">*</span>
                </label>
                <textarea wire:model="newDescription" rows="3"
                          placeholder="{{ __('What needs to be done? What symptoms were observed?') }}"
                          class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-primary-500 resize-none"></textarea>
                @error('newDescription') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">

                {{-- Priority --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Priority') }}</label>
                    <select wire:model="newPriority"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                   px-3 py-2 text-sm text-gray-700 dark:text-gray-200">
                        @foreach(\App\Models\CorrectiveMaintenance::priorityOptions() as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Scheduled Date --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Scheduled Date') }}</label>
                    <input type="date" wire:model="newScheduledDate"
                           class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                  px-3 py-2 text-sm text-gray-700 dark:text-gray-200" />
                </div>

                {{-- Linked Issue --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Linked Issue') }}</label>
                    <select wire:model="newIssueId"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                   px-3 py-2 text-sm text-gray-700 dark:text-gray-200">
                        <option value="">{{ __('— None —') }}</option>
                        @foreach($this->getOpenIssues() as $issue)
                        <option value="{{ $issue->id }}">#{{ $issue->id }} {{ Str::limit($issue->title, 40) }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Technician --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Assign Technician') }}</label>
                    <select wire:model="newTechnicianId"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                   px-3 py-2 text-sm text-gray-700 dark:text-gray-200">
                        <option value="">{{ __('— Unassigned —') }}</option>
                        @foreach($this->getTechnicians() as $tech)
                        <option value="{{ $tech->id }}">{{ $tech->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Cost Estimate --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Cost Estimate (฿)') }}</label>
                    <input type="number" wire:model="newCostEstimate" step="0.01" min="0"
                           placeholder="0.00"
                           class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                  px-3 py-2 text-sm text-gray-700 dark:text-gray-200" />
                </div>

            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Notes') }}</label>
                <textarea wire:model="newNotes" rows="2"
                          placeholder="{{ __('Any additional context or notes…') }}"
                          class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 resize-none"></textarea>
            </div>

        </div>

        <div class="flex items-center justify-end gap-3 p-6 border-t border-gray-100 dark:border-gray-700">
            <button wire:click="$set('showCreateModal', false)"
                    class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 transition-colors">
                {{ __('Cancel') }}
            </button>
            <button wire:click="createMaintenance"
                    class="px-4 py-2 text-sm font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                <span wire:loading.remove wire:target="createMaintenance">{{ __('Create Maintenance') }}</span>
                <span wire:loading wire:target="createMaintenance">{{ __('Creating…') }}</span>
            </button>
        </div>
    </div>
</div>
@endif

</x-filament-panels::page>
