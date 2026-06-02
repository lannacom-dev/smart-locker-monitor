<x-filament-panels::page>
@php
    use App\Models\CorrectiveMaintenance;
    use App\Models\CorrectiveMaintenanceLog;

    $user  = auth()->user();
    $m     = $this->record;

    $canEdit     = $user->can('edit maintenance');
    $canAssign   = $user->can('assign maintenance');
    $canComplete = $user->can('complete maintenance');
    $canCancel   = $user->can('cancel maintenance');

    $isTech      = (int)$m->technician_id === $user->id;
    $canEditContent = $canEdit && ($user->isSuperAdmin() || $user->hasRole('tenant_admin') || $isTech);

    $statusBadge   = CorrectiveMaintenance::statusBadgeClasses();
    $statusBtn     = CorrectiveMaintenance::statusButtonClasses();
    $statusDot     = CorrectiveMaintenance::statusDotClasses();
    $statusLabels  = CorrectiveMaintenance::statusOptions();
    $priorityBadge = CorrectiveMaintenance::priorityBadgeClasses();
    $priorityBorder = CorrectiveMaintenance::priorityBorderClasses();

    $allowedTransitions = $this->getAllowedTransitions();
    $technicians        = $this->getTechnicians();

    $priorityGradient = [
        'urgent' => 'from-red-600 to-rose-700',
        'high'   => 'from-orange-500 to-red-600',
        'medium' => 'from-blue-500 to-indigo-700',
        'low'    => 'from-slate-500 to-gray-700',
    ];
@endphp

{{-- ── Gradient Header Banner ──────────────────────────────────────── --}}
<div class="rounded-2xl bg-gradient-to-r {{ $priorityGradient[$m->priority] ?? 'from-slate-500 to-gray-700' }} p-6 mb-6 shadow-lg text-white">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-start gap-4">
            <a href="{{ route('filament.admin.pages.maintenance') }}"
               class="mt-0.5 inline-flex items-center gap-1 text-sm text-white/70 hover:text-white transition-colors">
                <x-heroicon-o-arrow-left class="w-4 h-4"/>
                {{ __('Back to Maintenance') }}
            </a>
            <div>
                <div class="flex items-center gap-2 flex-wrap mb-1">
                    <span class="font-mono text-xs text-white/60">CM #{{ $m->id }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-white/20 text-white border border-white/30">
                        {{ $m->priorityLabel() }}
                    </span>
                    @if($m->formattedDuration() !== '—')
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-white/15 text-white border border-white/25">
                        <x-heroicon-o-clock class="w-3 h-3"/>
                        {{ $m->formattedDuration() }}
                    </span>
                    @endif
                </div>
                <h1 class="text-xl font-bold text-white line-clamp-2">{{ $m->title }}</h1>
                @if($m->locker)
                <p class="text-sm text-white/70 mt-0.5">{{ $m->locker->name }}</p>
                @endif
            </div>
        </div>
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-white/15 text-white border border-white/25">
            @if($m->isActive())
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute h-full w-full rounded-full bg-white opacity-60"></span>
                    <span class="relative h-2 w-2 rounded-full bg-white"></span>
                </span>
            @else
                <span class="w-2 h-2 rounded-full bg-white/70"></span>
            @endif
            {{ $m->statusLabel() }}
        </span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- ── LEFT: Main content ──────────────────────────────────────── --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Header card --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="p-6">
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-2">{{ $m->title }}</h2>
                @if($m->description)
                <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{ $m->description }}</p>
                @endif
            </div>

            {{-- Meta strip --}}
            <div class="border-t border-gray-100 dark:border-gray-700 grid grid-cols-2 sm:grid-cols-3
                        divide-x divide-y divide-gray-100 dark:divide-gray-700">
                @php
                    $metas = [
                        [__('Locker'),      $m->locker?->name ?? '—'],
                        [__('Company'),     $m->company?->name ?? '—'],
                        [__('Created By'),  $m->creator?->name ?? '—'],
                        [__('Technician'),  $m->technician?->name ?? __('Unassigned')],
                        [__('Scheduled'),   $m->scheduled_date ? $m->scheduled_date->format('d M Y') : '—'],
                        [__('Created'),     $m->created_at->format('d M Y H:i')],
                    ];
                    if($m->started_at)   $metas[] = [__('Started'),   $m->started_at->format('d M Y H:i')];
                    if($m->completed_at) $metas[] = [__('Completed'), $m->completed_at->format('d M Y H:i')];
                    if($m->cancelled_at) $metas[] = [__('Cancelled'), $m->cancelled_at->format('d M Y H:i')];
                    if($m->issue)        $metas[] = [__('Issue'),     '#' . $m->issue_id . ': ' . Str::limit($m->issue->title, 40)];
                @endphp
                @foreach($metas as [$k, $v])
                <div class="px-4 py-3">
                    <div class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">{{ $k }}</div>
                    <div class="text-sm font-medium text-gray-700 dark:text-gray-200">{{ $v }}</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ── Root Cause ─────────────────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5"
             x-data="{ editing: @entangle('editingRoot') }">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <span class="text-base">🔍</span>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Root Cause') }}</h3>
                </div>
                @if($canEditContent)
                <button @click="editing = !editing"
                        class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400">
                    <span x-show="!editing">{{ __('Edit') }}</span>
                    <span x-show="editing" x-cloak>{{ __('Cancel') }}</span>
                </button>
                @endif
            </div>

            <div x-show="!editing">
                @if($m->root_cause)
                <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{ $m->root_cause }}</p>
                @else
                <p class="text-sm text-gray-400 dark:text-gray-500 italic">{{ __('Not yet documented.') }}</p>
                @endif
            </div>

            <div x-show="editing" x-cloak>
                <textarea wire:model="editRootCause" rows="4"
                          placeholder="{{ __('Describe the root cause of the problem…') }}"
                          class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 resize-none mb-3 focus:ring-2 focus:ring-primary-500"></textarea>
                <button wire:click="saveRootCause"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                    <span wire:loading.remove wire:target="saveRootCause">{{ __('Save Root Cause') }}</span>
                    <span wire:loading wire:target="saveRootCause">{{ __('Saving…') }}</span>
                </button>
            </div>
        </div>

        {{-- ── Solution ────────────────────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5"
             x-data="{ editing: @entangle('editingSolution') }">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <span class="text-base">🔧</span>
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Solution') }}</h3>
                </div>
                @if($canEditContent)
                <button @click="editing = !editing"
                        class="text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400">
                    <span x-show="!editing">{{ __('Edit') }}</span>
                    <span x-show="editing" x-cloak>{{ __('Cancel') }}</span>
                </button>
                @endif
            </div>

            <div x-show="!editing">
                @if($m->solution)
                <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">{{ $m->solution }}</p>
                @else
                <p class="text-sm text-gray-400 dark:text-gray-500 italic">{{ __('Not yet documented.') }}</p>
                @endif
            </div>

            <div x-show="editing" x-cloak>
                <textarea wire:model="editSolution" rows="4"
                          placeholder="{{ __('What was done to fix the issue?') }}"
                          class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 resize-none mb-3 focus:ring-2 focus:ring-primary-500"></textarea>
                <button wire:click="saveSolution"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                    <span wire:loading.remove wire:target="saveSolution">{{ __('Save Solution') }}</span>
                    <span wire:loading wire:target="saveSolution">{{ __('Saving…') }}</span>
                </button>
            </div>
        </div>

        {{-- ── Notes ──────────────────────────────────────────────── --}}
        @if($canEditContent || $m->notes)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5"
             x-data="{ editing: @entangle('editingNotes') }">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Notes') }}</h3>
                @if($canEditContent)
                <button @click="editing = !editing" class="text-xs text-primary-600 hover:text-primary-700">
                    <span x-show="!editing">{{ __('Edit') }}</span>
                    <span x-show="editing" x-cloak>{{ __('Cancel') }}</span>
                </button>
                @endif
            </div>
            <div x-show="!editing">
                @if($m->notes)
                <p class="text-sm text-gray-600 dark:text-gray-300 whitespace-pre-wrap">{{ $m->notes }}</p>
                @else
                <p class="text-sm text-gray-400 italic">{{ __('No notes.') }}</p>
                @endif
            </div>
            <div x-show="editing" x-cloak>
                <textarea wire:model="editNotes" rows="3"
                          class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                 px-3 py-2 text-sm resize-none mb-3 focus:ring-2 focus:ring-primary-500"></textarea>
                <button wire:click="saveNotes"
                        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-primary-600 text-white hover:bg-primary-700 transition-colors">
                    {{ __('Save Notes') }}
                </button>
            </div>
        </div>
        @endif

        {{-- ── Audit Log ───────────────────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
                <x-heroicon-o-clock class="w-5 h-5 text-gray-400"/>
                <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">{{ __('Audit Log') }}</h2>
                <span class="ml-auto text-xs text-gray-400">{{ $m->logs->count() }} {{ __('entries') }}</span>
            </div>
            <div class="p-6">
                @if($m->logs->isEmpty())
                <p class="text-center text-sm text-gray-400 py-4">{{ __('No log entries yet.') }}</p>
                @else
                <ol class="relative space-y-4">
                    @foreach($m->logs as $index => $log)
                    @php
                        $isLast = $index === $m->logs->count() - 1;
                        $actionColors = [
                            CorrectiveMaintenanceLog::ACTION_CREATED             => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
                            CorrectiveMaintenanceLog::ACTION_STATUS_CHANGED      => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                            CorrectiveMaintenanceLog::ACTION_TECHNICIAN_ASSIGNED => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
                            CorrectiveMaintenanceLog::ACTION_ROOT_CAUSE_UPDATED  => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                            CorrectiveMaintenanceLog::ACTION_SOLUTION_UPDATED    => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
                            CorrectiveMaintenanceLog::ACTION_COMPLETED           => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                            CorrectiveMaintenanceLog::ACTION_CANCELLED           => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                            CorrectiveMaintenanceLog::ACTION_REOPENED            => 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
                            CorrectiveMaintenanceLog::ACTION_REACTIVATED         => 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300',
                            CorrectiveMaintenanceLog::ACTION_FIELD_UPDATED       => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                            CorrectiveMaintenanceLog::ACTION_COST_UPDATED        => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
                        ];
                        $badgeClass = $actionColors[$log->action] ?? 'bg-gray-100 text-gray-600';
                    @endphp
                    <li class="flex gap-4">
                        {{-- Dot + connector --}}
                        <div class="flex flex-col items-center flex-shrink-0">
                            <span class="relative z-10 h-3 w-3 rounded-full ring-2 ring-white dark:ring-gray-800
                                         {{ $isLast ? 'bg-primary-500' : 'bg-gray-300 dark:bg-gray-600' }}">
                                @if($isLast)
                                <span class="absolute h-3 w-3 rounded-full animate-ping opacity-50 bg-primary-500"></span>
                                @endif
                            </span>
                            @if(!$isLast)
                            <div class="w-px flex-1 mt-1 bg-gray-200 dark:bg-gray-600 min-h-[16px]"></div>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0 pb-4">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium {{ $badgeClass }}">
                                    {{ $log->actionIcon() }} {{ $log->actionLabel() }}
                                </span>
                                @if($log->field_name)
                                <span class="text-xs text-gray-400">· {{ $log->fieldLabel() }}</span>
                                @endif
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                                    {{ $log->changedBy?->name ?? __('System') }}
                                </span>
                                <time class="text-xs text-gray-400 ml-auto"
                                      title="{{ $log->created_at->format('d M Y H:i:s') }}">
                                    {{ $log->created_at->diffForHumans() }}
                                </time>
                            </div>

                            @if($log->old_value || $log->new_value)
                            <div class="text-xs text-gray-500 mt-0.5">
                                @if($log->old_value)
                                <span class="line-through text-red-400">{{ Str::limit($log->old_value, 80) }}</span>
                                @if($log->new_value) <span class="mx-1 text-gray-300">→</span> @endif
                                @endif
                                @if($log->new_value)
                                <span class="text-green-600 dark:text-green-400 font-medium">{{ Str::limit($log->new_value, 80) }}</span>
                                @endif
                            </div>
                            @endif

                            @if($log->note)
                            <p class="mt-1 text-xs text-gray-500 italic border-l-2 border-gray-200 dark:border-gray-600 pl-2">
                                {{ $log->note }}
                            </p>
                            @endif
                        </div>
                    </li>
                    @endforeach
                </ol>
                @endif
            </div>
        </div>

    </div>{{-- end left column --}}

    {{-- ── RIGHT: Actions ──────────────────────────────────────────── --}}
    <div class="space-y-4">

        {{-- ── Status Changer (Alpine.js) ───────────────────────────── --}}
        <div
            x-data="{
                selected: null,
                note: '',
                loading: false,
                open(s) { this.selected = s; this.note = ''; },
                cancel() { this.selected = null; },
                async confirm() {
                    this.loading = true;
                    await $wire.changeStatus(this.selected, this.note || null);
                    this.selected = null;
                    this.note = '';
                    this.loading = false;
                },
            }"
            class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"
        >
            <div class="px-5 pt-5 pb-3">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Status') }}</h3>
                </div>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-semibold
                             ring-1 ring-inset {{ $statusBadge[$m->status] ?? '' }}">
                    @if($m->isActive())
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute h-full w-full rounded-full opacity-75
                                     {{ $statusDot[$m->status] ?? 'bg-gray-400' }}"></span>
                        <span class="relative h-2 w-2 rounded-full {{ $statusDot[$m->status] ?? 'bg-gray-400' }}"></span>
                    </span>
                    @endif
                    {{ $m->statusLabel() }}
                </span>
            </div>

            @if(count($allowedTransitions) > 0 && !in_array($m->status, [CorrectiveMaintenance::STATUS_COMPLETED, CorrectiveMaintenance::STATUS_CANCELLED]))
            {{-- Simple transitions (not complete, not cancel) --}}
            @php
                $simpleTransitions = array_filter($allowedTransitions, fn($t) =>
                    !in_array($t, [CorrectiveMaintenance::STATUS_COMPLETED, CorrectiveMaintenance::STATUS_CANCELLED])
                );
            @endphp
            @if(count($simpleTransitions) > 0)
            <div class="px-5 pb-3">
                <p class="text-xs text-gray-400 mb-2">{{ __('Change to:') }}</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($simpleTransitions as $toStatus)
                    <button @click="open('{{ $toStatus }}')"
                            :class="selected === '{{ $toStatus }}' ? 'ring-2 ring-offset-1 ring-primary-400' : ''"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium transition-all
                                   {{ $statusBtn[$toStatus] ?? '' }}">
                        {{ $statusLabels[$toStatus] ?? $toStatus }}
                    </button>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Confirmation panel (simple) --}}
            <div x-show="selected !== null && selected !== 'completed' && selected !== 'cancelled'"
                 x-transition
                 class="border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-750 px-5 py-4"
                 x-cloak>
                <p class="text-xs font-medium text-gray-600 mb-2">
                    {{ __('Confirm status change') }} →
                    <span x-text="selected ? (@js($statusLabels))[selected] : ''" class="font-semibold text-gray-800 dark:text-gray-100 ml-1"></span>
                </p>
                <textarea x-model="note" rows="2" placeholder="{{ __('Optional note…') }}"
                          class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                 px-3 py-2 text-xs text-gray-900 dark:text-gray-100 resize-none mb-2 focus:ring-2 focus:ring-primary-500"></textarea>
                <div class="flex gap-2">
                    <button @click="confirm()" :disabled="loading"
                            class="flex-1 py-2 rounded-lg bg-primary-600 text-white text-xs font-medium
                                   hover:bg-primary-700 disabled:opacity-50 transition-colors">
                        <span x-text="loading ? 'Saving…' : 'Confirm'"></span>
                    </button>
                    <button @click="cancel()" class="px-3 py-2 rounded-lg border border-gray-300 text-xs text-gray-600
                                                     hover:bg-gray-50 transition-colors">
                        {{ __('Cancel') }}
                    </button>
                </div>
            </div>
            @endif
        </div>

        {{-- ── Complete Form ─────────────────────────────────────────── --}}
        @if(in_array(CorrectiveMaintenance::STATUS_COMPLETED, $allowedTransitions) && $canComplete)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-green-100 dark:border-green-800/50 p-5">
            <h3 class="text-sm font-semibold text-green-700 dark:text-green-400 mb-3 flex items-center gap-2">
                ✅ {{ __('Mark as Completed') }}
            </h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Solution (optional)') }}</label>
                    <textarea wire:model="completeSolution" rows="2"
                              placeholder="{{ __('What was done to fix it?') }}"
                              class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                     px-3 py-2 text-xs text-gray-900 dark:text-gray-100 resize-none focus:ring-2 focus:ring-green-500"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Actual Cost (฿)') }}</label>
                    <input type="number" wire:model="completeCostActual" step="0.01" min="0"
                           placeholder="0.00"
                           class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                  px-3 py-2 text-xs text-gray-700 dark:text-gray-200" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Note') }}</label>
                    <input type="text" wire:model="completeNote"
                           placeholder="{{ __('Completion note…') }}"
                           class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                  px-3 py-2 text-xs text-gray-700 dark:text-gray-200" />
                </div>
            </div>
            <button wire:click="submitComplete"
                    class="mt-3 w-full px-4 py-2 rounded-lg bg-green-600 text-white text-sm font-medium
                           hover:bg-green-700 transition-colors">
                <span wire:loading.remove wire:target="submitComplete">{{ __('Complete Maintenance') }}</span>
                <span wire:loading wire:target="submitComplete">{{ __('Saving…') }}</span>
            </button>
        </div>
        @endif

        {{-- ── Cancel Form ──────────────────────────────────────────── --}}
        @if(in_array(CorrectiveMaintenance::STATUS_CANCELLED, $allowedTransitions) && $canCancel)
        <div x-data="{ open: false }"
             class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
            <button @click="open = !open"
                    class="flex items-center justify-between w-full text-sm font-medium text-gray-600 dark:text-gray-400
                           hover:text-red-600 transition-colors">
                <span>❌ {{ __('Cancel Maintenance') }}</span>
                <x-heroicon-o-chevron-down class="w-4 h-4 transition-transform" :class="open && 'rotate-180'"/>
            </button>
            <div x-show="open" x-transition x-cloak class="mt-3">
                <textarea wire:model="cancelReason" rows="2"
                          placeholder="{{ __('Reason for cancellation…') }}"
                          class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                 px-3 py-2 text-xs text-gray-900 dark:text-gray-100 resize-none mb-2 focus:ring-2 focus:ring-red-500"></textarea>
                <button wire:click="changeStatus('cancelled', $wire.cancelReason || null)"
                        class="w-full py-2 rounded-lg bg-red-600 text-white text-xs font-medium
                               hover:bg-red-700 transition-colors">
                    <span wire:loading.remove wire:target="changeStatus">{{ __('Confirm Cancellation') }}</span>
                    <span wire:loading wire:target="changeStatus">{{ __('Cancelling…') }}</span>
                </button>
            </div>
        </div>
        @endif

        {{-- ── Reactivate (from cancelled) ──────────────────────────── --}}
        @if(in_array(CorrectiveMaintenance::STATUS_CREATED, $allowedTransitions))
        <button wire:click="changeStatus('created', 'Reactivated')"
                class="w-full px-4 py-2 rounded-lg bg-sky-600 text-white text-sm font-medium
                       hover:bg-sky-700 transition-colors">
            ♻️ {{ __('Reactivate') }}
        </button>
        @endif

        {{-- ── Assign Technician ────────────────────────────────────── --}}
        @if($canAssign)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                @if($m->technician) {{ __('Reassign Technician') }} @else {{ __('Assign Technician') }} @endif
            </h3>
            <select wire:model="assignTechId"
                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                           px-3 py-2 text-sm text-gray-700 dark:text-gray-200 mb-3">
                <option value="">{{ __('— Unassign —') }}</option>
                @foreach($technicians as $tech)
                <option value="{{ $tech->id }}" @selected((int)$assignTechId === $tech->id)>{{ $tech->name }}</option>
                @endforeach
            </select>
            <input type="text" wire:model="assignNote" placeholder="{{ __('Optional note…') }}"
                   class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                          px-3 py-2 text-sm text-gray-700 dark:text-gray-200 mb-3" />
            <button wire:click="submitAssign"
                    class="w-full px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium
                           hover:bg-indigo-700 transition-colors">
                <span wire:loading.remove wire:target="submitAssign">{{ __('Save Assignment') }}</span>
                <span wire:loading wire:target="submitAssign">{{ __('Saving…') }}</span>
            </button>
        </div>
        @endif

        {{-- ── Cost Summary ─────────────────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">💰 {{ __('Cost Tracking') }}</h3>
            <div class="space-y-2">
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('Estimate (฿)') }}</label>
                    <input type="number" wire:model="costEstimate" step="0.01" min="0"
                           placeholder="0.00" @if(!$canEditContent) readonly @endif
                           class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                  px-3 py-2 text-sm text-gray-700 dark:text-gray-200" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ __('Actual (฿)') }}</label>
                    <input type="number" wire:model="costActual" step="0.01" min="0"
                           placeholder="0.00" @if(!$canEditContent) readonly @endif
                           class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                                  px-3 py-2 text-sm text-gray-700 dark:text-gray-200" />
                </div>
                @if($m->cost_estimate && $m->cost_actual)
                @php $diff = $m->cost_actual - $m->cost_estimate; @endphp
                <div class="flex items-center justify-between pt-1 border-t border-gray-100 dark:border-gray-700 text-xs">
                    <span class="text-gray-500">{{ __('Variance') }}</span>
                    <span class="{{ $diff > 0 ? 'text-red-600 font-medium' : ($diff < 0 ? 'text-green-600 font-medium' : 'text-gray-500') }}">
                        {{ $diff >= 0 ? '+' : '' }}{{ number_format($diff, 2) }} ฿
                    </span>
                </div>
                @endif
                @if($canEditContent)
                <button wire:click="saveCost"
                        class="w-full px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 dark:border-gray-600
                               text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                    <span wire:loading.remove wire:target="saveCost">{{ __('Update Cost') }}</span>
                    <span wire:loading wire:target="saveCost">{{ __('Saving…') }}</span>
                </button>
                @endif
            </div>
        </div>

        {{-- ── Details panel ────────────────────────────────────────── --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">{{ __('Details') }}</h3>
            <dl class="space-y-2 text-sm">
                @php
                    $details = [
                        [__('ID'),        '#' . $m->id],
                        [__('Status'),    $m->statusLabel()],
                        [__('Priority'),  $m->priorityLabel()],
                        [__('Locker'),    $m->locker?->name ?? '—'],
                        [__('Duration'),  $m->formattedDuration()],
                        [__('Log Entries'), $m->logs->count()],
                    ];
                    if($m->cancel_reason) {
                        $details[] = [__('Cancel Reason'), Str::limit($m->cancel_reason, 50)];
                    }
                @endphp
                @foreach($details as [$k, $v])
                <div class="flex justify-between">
                    <dt class="text-gray-400 dark:text-gray-500">{{ $k }}</dt>
                    <dd class="text-gray-600 dark:text-gray-300 font-medium text-right">{{ $v }}</dd>
                </div>
                @endforeach
            </dl>
        </div>

    </div>{{-- end right column --}}
</div>

</x-filament-panels::page>
