<x-filament-panels::page>
    @php
        $lockers      = $this->getLockers();
        $locations    = $this->getLocations();
        $companies    = $this->getCompanies();
        $counts       = $this->getStatusCounts();
        $recentLogs   = $this->getRecentLogs();
        $statusOptions = \App\Models\Locker::statusOptions();
        $statusColors  = \App\Models\Locker::statusColors();
        $colorMap = [
            'success' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            'info'    => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
            'danger'  => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            'gray'    => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
            'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        ];
        $user = auth()->user();
    @endphp

    {{-- ── Stat Cards ───────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-5 mb-6">

        {{-- Available --}}
        <div class="rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 p-4 shadow-md text-white">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-80">{{ __('Available') }}</span>
                <div class="bg-white/20 rounded-lg p-1.5">
                    <x-heroicon-s-check-circle class="w-5 h-5"/>
                </div>
            </div>
            <p class="text-4xl font-extrabold leading-none">{{ $counts['available'] }}</p>
        </div>

        {{-- In Use --}}
        <div class="rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 p-4 shadow-md text-white">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-80">{{ __('In Use') }}</span>
                <div class="bg-white/20 rounded-lg p-1.5">
                    <x-heroicon-s-lock-open class="w-5 h-5"/>
                </div>
            </div>
            <p class="text-4xl font-extrabold leading-none">{{ $counts['in_use'] }}</p>
        </div>

        {{-- Fault --}}
        <div class="rounded-xl bg-gradient-to-br from-red-500 to-rose-600 p-4 shadow-md text-white">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-80">{{ __('Fault') }}</span>
                <div class="bg-white/20 rounded-lg p-1.5">
                    <x-heroicon-s-exclamation-triangle class="w-5 h-5"/>
                </div>
            </div>
            <p class="text-4xl font-extrabold leading-none">{{ $counts['fault'] }}</p>
        </div>

        {{-- Offline --}}
        <div class="rounded-xl bg-gradient-to-br from-slate-500 to-gray-600 p-4 shadow-md text-white">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-80">{{ __('Offline') }}</span>
                <div class="bg-white/20 rounded-lg p-1.5">
                    <x-heroicon-s-signal-slash class="w-5 h-5"/>
                </div>
            </div>
            <p class="text-4xl font-extrabold leading-none">{{ $counts['offline'] }}</p>
        </div>

        {{-- Disabled --}}
        <div class="rounded-xl bg-gradient-to-br from-amber-500 to-yellow-500 p-4 shadow-md text-white">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider opacity-80">{{ __('Disabled') }}</span>
                <div class="bg-white/20 rounded-lg p-1.5">
                    <x-heroicon-s-no-symbol class="w-5 h-5"/>
                </div>
            </div>
            <p class="text-4xl font-extrabold leading-none">{{ $counts['disabled'] }}</p>
        </div>

    </div>

    {{-- ── Filter Bar ───────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-end gap-3 px-4 py-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm mb-5">
        <div class="flex items-center gap-1.5 text-gray-400 dark:text-gray-500 shrink-0">
            <x-heroicon-o-funnel class="w-4 h-4"/>
            <span class="text-xs font-medium uppercase tracking-wider">{{ __('Filter') }}</span>
        </div>

        <div class="flex flex-col gap-1">
            <label class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ __('Status') }}</label>
            <select wire:model.live="filterStatus" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm bg-white dark:bg-gray-700 dark:text-white shadow-sm min-w-[130px]">
                <option value="">{{ __('All Statuses') }}</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex flex-col gap-1">
            <label class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ __('Branch / Location') }}</label>
            <select wire:model.live="filterLocation" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm bg-white dark:bg-gray-700 dark:text-white shadow-sm min-w-[150px]">
                <option value="">{{ __('All Locations') }}</option>
                @foreach($locations as $loc)
                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                @endforeach
            </select>
        </div>

        @if($user->isSuperAdmin())
        <div class="flex flex-col gap-1">
            <label class="text-xs text-gray-500 dark:text-gray-400 font-medium">{{ __('Company') }}</label>
            <select wire:model.live="filterCompany" class="rounded-lg border-gray-300 dark:border-gray-600 text-sm bg-white dark:bg-gray-700 dark:text-white shadow-sm min-w-[150px]">
                <option value="">{{ __('All Companies') }}</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
    </div>

    {{-- ── Locker Table ─────────────────────────────────────────────────── --}}
    <div
        x-data="lockerMonitor({{ $user->company_id ?? 'null' }}, {{ $user->isSuperAdmin() ? 'true' : 'false' }})"
        class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden"
    >
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 dark:bg-gray-700/60 border-b border-gray-200 dark:border-gray-700">
                    <tr>
                        <th class="px-5 py-3.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Locker') }}</th>
                        <th class="px-5 py-3.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Location') }}</th>
                        @if($user->isSuperAdmin())
                        <th class="px-5 py-3.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Company') }}</th>
                        @endif
                        <th class="px-5 py-3.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Status') }}</th>
                        <th class="px-5 py-3.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Last Seen') }}</th>
                        <th class="px-5 py-3.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Changed By') }}</th>
                        @can('edit lockers')
                        <th class="px-5 py-3.5 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-center">{{ __('Actions') }}</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                    @forelse($lockers as $locker)
                        @php
                            $rowAccent = match($locker->status) {
                                'available' => 'border-l-4 border-l-emerald-400',
                                'in_use'    => 'border-l-4 border-l-blue-400',
                                'fault'     => 'border-l-4 border-l-red-400',
                                'offline'   => 'border-l-4 border-l-slate-400',
                                'disabled'  => 'border-l-4 border-l-amber-400',
                                default     => 'border-l-4 border-l-transparent',
                            };
                        @endphp
                        <tr
                            x-bind:data-locker-id="{{ $locker->id }}"
                            class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition-colors {{ $rowAccent }}"
                        >
                            <td class="px-5 py-3.5">
                                <div class="font-semibold text-gray-900 dark:text-white">{{ $locker->name }}</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 font-mono">{{ $locker->serial_number }}</div>
                            </td>
                            <td class="px-5 py-3.5 text-gray-600 dark:text-gray-300">
                                {{ $locker->location?->name ?? '—' }}
                            </td>
                            @if($user->isSuperAdmin())
                            <td class="px-5 py-3.5 text-gray-600 dark:text-gray-300">
                                {{ $locker->company?->name ?? '—' }}
                            </td>
                            @endif
                            <td class="px-5 py-3.5">
                                <span
                                    x-bind:class="statusBadgeClass({{ $locker->id }})"
                                    x-bind:data-status="{{ $locker->status }}"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-current opacity-70"></span>
                                    <span x-text="statusLabel({{ $locker->id }}, '{{ $locker->status }}')">
                                        {{ $statusOptions[$locker->status] ?? $locker->status }}
                                    </span>
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 text-xs">
                                {{ $locker->last_seen_at?->diffForHumans() ?? __('Never connected') }}
                            </td>
                            <td class="px-5 py-3.5 text-gray-500 dark:text-gray-400 text-xs">
                                @if($locker->statusLogs->isNotEmpty())
                                    <div class="font-medium text-gray-700 dark:text-gray-300">{{ $locker->statusLogs->first()->changedBy?->name ?? __('System') }}</div>
                                    <div class="text-gray-400 mt-0.5">{{ $locker->statusLogs->first()->created_at->diffForHumans() }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            @can('edit lockers')
                            <td class="px-5 py-3.5 text-center">
                                <button
                                    wire:click="openUpdateModal({{ $locker->id }})"
                                    class="inline-flex items-center gap-1.5 text-xs bg-primary-600 hover:bg-primary-700 text-white px-3 py-1.5 rounded-lg transition-colors font-medium shadow-sm"
                                >
                                    <x-heroicon-o-pencil-square class="w-3.5 h-3.5"/>
                                    {{ __('Change Status') }}
                                </button>
                            </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center">
                                <x-heroicon-o-archive-box class="w-10 h-10 mx-auto mb-2 text-gray-300"/>
                                <p class="text-gray-400 text-sm">{{ __('No lockers found matching the criteria') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Audit Log ────────────────────────────────────────────────────── --}}
    <div class="mt-8">
        <div class="flex items-center gap-2 mb-3">
            <x-heroicon-o-clock class="w-5 h-5 text-gray-400"/>
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">{{ __('Recent Status Change History') }}</h3>
        </div>
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700/60 border-b border-gray-200 dark:border-gray-700">
                        <tr>
                            <th class="px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Locker') }}</th>
                            <th class="px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Old Status') }}</th>
                            <th class="px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('New Status') }}</th>
                            <th class="px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Reason') }}</th>
                            <th class="px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Changed By') }}</th>
                            <th class="px-5 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Time') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/70">
                        @forelse($recentLogs as $log)
                            <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition-colors">
                                <td class="px-5 py-3 font-medium text-gray-900 dark:text-white">{{ $log->locker?->name ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    @if($log->old_status)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $colorMap[$statusColors[$log->old_status] ?? 'gray'] ?? '' }}">
                                        {{ $statusOptions[$log->old_status] ?? $log->old_status }}
                                    </span>
                                    @else <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $colorMap[$statusColors[$log->new_status] ?? 'gray'] ?? '' }}">
                                        {{ $statusOptions[$log->new_status] ?? $log->new_status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-gray-500 dark:text-gray-400 text-xs max-w-xs truncate">{{ $log->reason ?? '—' }}</td>
                                <td class="px-5 py-3 text-gray-700 dark:text-gray-300 text-sm">{{ $log->changedBy?->name ?? __('System') }}</td>
                                <td class="px-5 py-3 text-gray-400 dark:text-gray-500 text-xs whitespace-nowrap">{{ $log->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center">
                                    <x-heroicon-o-clipboard-document-list class="w-8 h-8 mx-auto mb-2 text-gray-300"/>
                                    <p class="text-gray-400 text-sm">{{ __('No history yet') }}</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Update Status Modal ──────────────────────────────────────────── --}}
    @can('edit lockers')
    <x-filament::modal id="update-status-modal" width="md">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-pencil-square class="w-5 h-5 text-primary-600"/>
                {{ __('Change Locker Status') }}
            </div>
        </x-slot>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('New Status') }} <span class="text-red-500">*</span></label>
                <select wire:model="updateNewStatus" class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:bg-gray-800 dark:border-gray-600 dark:text-white">
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">{{ __('Reason (optional)') }}</label>
                <textarea
                    wire:model="updateReason"
                    rows="3"
                    maxlength="500"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:bg-gray-800 dark:border-gray-600 dark:text-white resize-none"
                    placeholder="{{ __('Specify the reason for changing status...') }}"
                ></textarea>
            </div>
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-3">
                <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'update-status-modal' })">
                    {{ __('Cancel') }}
                </x-filament::button>
                <x-filament::button wire:click="submitStatusUpdate" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submitStatusUpdate">{{ __('Save Changes') }}</span>
                    <span wire:loading wire:target="submitStatusUpdate" class="flex items-center gap-2">
                        <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        {{ __('Saving...') }}
                    </span>
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>
    @endcan

    @script
    <script>
        function lockerMonitor(companyId, isSuperAdmin) {
            const statusLabels = @js(\App\Models\Locker::statusOptions());
            const statusClasses = {
                available: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
                in_use:    'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
                fault:     'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                offline:   'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
                disabled:  'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
            };

            const currentStatuses = {};
            document.querySelectorAll('[data-locker-id]').forEach(row => {
                const id     = row.getAttribute('data-locker-id');
                const badge  = row.querySelector('[data-status]');
                if (badge) currentStatuses[id] = badge.getAttribute('data-status');
            });

            return {
                statuses: currentStatuses,

                statusLabel(lockerId, fallback) {
                    const s = this.statuses[String(lockerId)] ?? fallback;
                    return statusLabels[s] ?? s;
                },

                statusBadgeClass(lockerId) {
                    const s = this.statuses[String(lockerId)] ?? '';
                    return 'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ' + (statusClasses[s] ?? 'bg-gray-100 text-gray-600');
                },

                init() {
                    if (typeof window.Echo === 'undefined') return;

                    const channelId = isSuperAdmin ? null : companyId;
                    if (!channelId) return;

                    window.Echo.private(`company.${channelId}`)
                        .listen('.locker.status.updated', (e) => {
                            this.statuses[String(e.locker_id)] = e.new_status;
                        });
                }
            };
        }
    </script>
    @endscript
</x-filament-panels::page>
