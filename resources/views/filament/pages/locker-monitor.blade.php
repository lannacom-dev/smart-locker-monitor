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
            'success' => 'bg-green-100 text-green-800',
            'info'    => 'bg-blue-100 text-blue-800',
            'danger'  => 'bg-red-100 text-red-800',
            'gray'    => 'bg-gray-100 text-gray-600',
            'warning' => 'bg-yellow-100 text-yellow-800',
        ];
        $user = auth()->user();
    @endphp

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-5 mb-6">
        @foreach([
            ['label' => 'Available', 'key' => 'available', 'color' => 'text-green-600'],
            ['label' => 'In Use',    'key' => 'in_use',    'color' => 'text-blue-600'],
            ['label' => 'Fault',     'key' => 'fault',     'color' => 'text-red-600'],
            ['label' => 'Offline',   'key' => 'offline',   'color' => 'text-gray-500'],
            ['label' => 'Disabled',  'key' => 'disabled',  'color' => 'text-yellow-600'],
        ] as $stat)
            <div class="rounded-xl border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 p-4 shadow-sm">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                <p class="text-3xl font-bold {{ $stat['color'] }}">{{ $counts[$stat['key']] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap gap-3 mb-4">
        <div>
            <label class="block text-xs text-gray-500 mb-1">สถานะ</label>
            <select wire:model.live="filterStatus" class="rounded-lg border-gray-300 text-sm shadow-sm dark:bg-gray-800 dark:border-gray-600 dark:text-white">
                <option value="">ทั้งหมด</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs text-gray-500 mb-1">สาขา / Location</label>
            <select wire:model.live="filterLocation" class="rounded-lg border-gray-300 text-sm shadow-sm dark:bg-gray-800 dark:border-gray-600 dark:text-white">
                <option value="">ทั้งหมด</option>
                @foreach($locations as $loc)
                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                @endforeach
            </select>
        </div>

        @if($user->isSuperAdmin())
        <div>
            <label class="block text-xs text-gray-500 mb-1">บริษัท</label>
            <select wire:model.live="filterCompany" class="rounded-lg border-gray-300 text-sm shadow-sm dark:bg-gray-800 dark:border-gray-600 dark:text-white">
                <option value="">ทั้งหมด</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
    </div>

    {{-- Locker Table --}}
    <div
        x-data="lockerMonitor({{ $user->company_id ?? 'null' }}, {{ $user->isSuperAdmin() ? 'true' : 'false' }})"
        class="rounded-xl border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm overflow-hidden"
    >
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3">ชื่อตู้</th>
                        <th class="px-4 py-3">Location</th>
                        @if($user->isSuperAdmin())
                        <th class="px-4 py-3">บริษัท</th>
                        @endif
                        <th class="px-4 py-3">สถานะ</th>
                        <th class="px-4 py-3">Last Seen</th>
                        <th class="px-4 py-3">เปลี่ยนโดย</th>
                        @can('edit lockers')
                        <th class="px-4 py-3 text-center">จัดการ</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($lockers as $locker)
                        <tr
                            x-bind:data-locker-id="{{ $locker->id }}"
                            class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors"
                        >
                            <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                {{ $locker->name }}
                                <div class="text-xs text-gray-400">{{ $locker->serial_number }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                {{ $locker->location?->name ?? '—' }}
                            </td>
                            @if($user->isSuperAdmin())
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                {{ $locker->company?->name ?? '—' }}
                            </td>
                            @endif
                            <td class="px-4 py-3">
                                <span
                                    x-bind:class="statusBadgeClass({{ $locker->id }})"
                                    x-bind:data-status="{{ $locker->status }}"
                                    class="px-2 py-1 rounded-full text-xs font-semibold"
                                >
                                    <span x-text="statusLabel({{ $locker->id }}, '{{ $locker->status }}')">
                                        {{ $statusOptions[$locker->status] ?? $locker->status }}
                                    </span>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">
                                {{ $locker->last_seen_at?->diffForHumans() ?? 'ไม่เคยเชื่อมต่อ' }}
                            </td>
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">
                                @if($locker->statusLogs->isNotEmpty())
                                    {{ $locker->statusLogs->first()->changedBy?->name ?? 'System' }}
                                    <div class="text-gray-400">{{ $locker->statusLogs->first()->created_at->diffForHumans() }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            @can('edit lockers')
                            <td class="px-4 py-3 text-center">
                                <button
                                    wire:click="openUpdateModal({{ $locker->id }})"
                                    class="text-xs bg-primary-600 hover:bg-primary-700 text-white px-3 py-1 rounded-lg transition-colors"
                                >
                                    เปลี่ยนสถานะ
                                </button>
                            </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-400">ไม่พบตู้ที่ตรงกับเงื่อนไข</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Audit Log --}}
    <div class="mt-8">
        <h3 class="text-base font-semibold text-gray-700 dark:text-gray-300 mb-3">ประวัติการเปลี่ยนสถานะล่าสุด</h3>
        <div class="rounded-xl border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-gray-50 dark:bg-gray-700 text-gray-600 dark:text-gray-300 uppercase text-xs">
                        <tr>
                            <th class="px-4 py-3">ตู้</th>
                            <th class="px-4 py-3">สถานะเดิม</th>
                            <th class="px-4 py-3">สถานะใหม่</th>
                            <th class="px-4 py-3">เหตุผล</th>
                            <th class="px-4 py-3">เปลี่ยนโดย</th>
                            <th class="px-4 py-3">เวลา</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($recentLogs as $log)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">{{ $log->locker?->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @if($log->old_status)
                                    <span class="px-2 py-0.5 rounded-full text-xs {{ $colorMap[$statusColors[$log->old_status] ?? 'gray'] ?? '' }}">
                                        {{ $statusOptions[$log->old_status] ?? $log->old_status }}
                                    </span>
                                    @else —
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs {{ $colorMap[$statusColors[$log->new_status] ?? 'gray'] ?? '' }}">
                                        {{ $statusOptions[$log->new_status] ?? $log->new_status }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $log->reason ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $log->changedBy?->name ?? 'System' }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">{{ $log->created_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-6 text-center text-gray-400">ยังไม่มีประวัติ</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Update Status Modal --}}
    @can('edit lockers')
    <x-filament::modal id="update-status-modal" width="md">
        <x-slot name="heading">เปลี่ยนสถานะตู้</x-slot>

        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">สถานะใหม่ <span class="text-red-500">*</span></label>
                <select wire:model="updateNewStatus" class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:bg-gray-800 dark:border-gray-600 dark:text-white">
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">เหตุผล (ไม่บังคับ)</label>
                <textarea
                    wire:model="updateReason"
                    rows="3"
                    maxlength="500"
                    class="w-full rounded-lg border-gray-300 text-sm shadow-sm dark:bg-gray-800 dark:border-gray-600 dark:text-white"
                    placeholder="ระบุเหตุผลในการเปลี่ยนสถานะ..."
                ></textarea>
            </div>
        </div>

        <x-slot name="footer">
            <div class="flex justify-end gap-3">
                <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'update-status-modal' })">
                    ยกเลิก
                </x-filament::button>
                <x-filament::button wire:click="submitStatusUpdate" wire:loading.attr="disabled">
                    <span wire:loading.remove>บันทึก</span>
                    <span wire:loading>กำลังบันทึก...</span>
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
                available: 'bg-green-100 text-green-800',
                in_use:    'bg-blue-100 text-blue-800',
                fault:     'bg-red-100 text-red-800',
                offline:   'bg-gray-100 text-gray-600',
                disabled:  'bg-yellow-100 text-yellow-800',
            };

            // Track current statuses keyed by locker id
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
                    return 'px-2 py-1 rounded-full text-xs font-semibold ' + (statusClasses[s] ?? 'bg-gray-100 text-gray-600');
                },

                init() {
                    if (typeof window.Echo === 'undefined') return;

                    const channelId = isSuperAdmin ? null : companyId;
                    if (!channelId) {
                        // Super admin listens to all — could subscribe per visible company
                        // For simplicity, Livewire re-renders after update
                        return;
                    }

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
