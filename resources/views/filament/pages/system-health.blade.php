<x-filament-panels::page>
@php
    use App\Models\SystemHealthCheck;
    use App\Models\SystemAlert;

    $user          = auth()->user();
    $overallStatus = $this->getOverallStatus();
    $healthChecks  = $this->getHealthChecks();
    $issueSummary  = $this->getIssueSummary();
    $alerts        = $this->getAlerts();
    $companies     = $this->getCompanies();
    $lastChecked   = $this->getLastCheckedAt();

    // Organize checks by type (keep latest per type)
    $deviceChecks     = $healthChecks->where('check_type', SystemHealthCheck::TYPE_DEVICE);
    $connectionChecks = $healthChecks->where('check_type', SystemHealthCheck::TYPE_CONNECTION);
    $apiChecks        = $healthChecks->where('check_type', SystemHealthCheck::TYPE_API);

    // Status display config
    $overallConfig = match($overallStatus) {
        'critical' => ['bg' => 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800',
                       'dot' => 'bg-red-500', 'text' => 'text-red-700 dark:text-red-400',
                       'label' => 'CRITICAL', 'icon' => '🔴'],
        'warning'  => ['bg' => 'bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:border-amber-800',
                       'dot' => 'bg-amber-400', 'text' => 'text-amber-700 dark:text-amber-400',
                       'label' => 'WARNING', 'icon' => '⚠️'],
        'healthy'  => ['bg' => 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800',
                       'dot' => 'bg-green-500', 'text' => 'text-green-700 dark:text-green-400',
                       'label' => 'HEALTHY', 'icon' => '✅'],
        default    => ['bg' => 'bg-gray-50 border-gray-200 dark:bg-gray-800 dark:border-gray-700',
                       'dot' => 'bg-gray-400', 'text' => 'text-gray-500',
                       'label' => 'UNKNOWN', 'icon' => '❓'],
    };

    $checkStatusCfg = [
        'healthy'  => ['border' => 'border-green-200 dark:border-green-800',
                       'badge'  => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-400',
                       'label'  => 'HEALTHY',  'icon' => '✓'],
        'warning'  => ['border' => 'border-amber-200 dark:border-amber-800',
                       'badge'  => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-400',
                       'label'  => 'WARNING',  'icon' => '⚠'],
        'critical' => ['border' => 'border-red-200 dark:border-red-800',
                       'badge'  => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-400',
                       'label'  => 'CRITICAL', 'icon' => '✕'],
        'unknown'  => ['border' => 'border-gray-200 dark:border-gray-700',
                       'badge'  => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                       'label'  => 'NO DATA',  'icon' => '?'],
    ];

    $severityConfig = [
        'critical' => ['bg' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-400',
                       'dot' => 'bg-red-500', 'label' => 'Critical', 'icon' => '🔴'],
        'warning'  => ['bg' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-400',
                       'dot' => 'bg-amber-400', 'label' => 'Warning', 'icon' => '⚠️'],
        'info'     => ['bg' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-400',
                       'dot' => 'bg-blue-400', 'label' => 'Info', 'icon' => 'ℹ️'],
    ];

    $statusBadgeCfg = [
        'open'         => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        'acknowledged' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        'resolved'     => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    ];
@endphp

{{-- ═══════════════════════════════════════════════════════════
     Top: Company filter (super-admin only) + Last-checked
═══════════════════════════════════════════════════════════ --}}
<div class="flex flex-wrap items-end justify-between gap-3 mb-6">

    <div class="flex flex-wrap items-end gap-3">
        @if($user->isSuperAdmin())
        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Company / Tenant</label>
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="filterCompany">
                    <option value="">All Companies</option>
                    @foreach($companies as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
        @endif
    </div>

    <div class="flex items-center gap-2 text-xs text-gray-400">
        @if($lastChecked)
            <x-heroicon-o-clock class="w-4 h-4" />
            Last check: {{ $lastChecked }}
        @else
            <span class="italic">No checks run yet — schedule health:check</span>
        @endif

        {{-- Manual trigger button --}}
        <x-filament::button
            size="xs"
            color="gray"
            wire:click="$dispatch('refresh')"
            title="Refresh data"
        >
            <x-heroicon-o-arrow-path class="w-3 h-3" />
        </x-filament::button>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     Overall Status Banner
═══════════════════════════════════════════════════════════ --}}
<div class="rounded-xl border {{ $overallConfig['bg'] }} p-5 mb-6 flex items-center gap-4">
    <div class="relative flex h-10 w-10 shrink-0 items-center justify-center">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $overallConfig['dot'] }} opacity-30"></span>
        <span class="relative inline-flex h-6 w-6 rounded-full {{ $overallConfig['dot'] }}"></span>
    </div>
    <div>
        <p class="text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">
            Overall System Status
        </p>
        <p class="text-2xl font-bold {{ $overallConfig['text'] }}">
            {{ $overallConfig['icon'] }} {{ $overallConfig['label'] }}
        </p>
    </div>

    {{-- Issue count badges --}}
    <div class="ml-auto flex items-center gap-3 flex-wrap">
        @if($issueSummary['critical'] > 0)
        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-400">
            🔴 {{ $issueSummary['critical'] }} Critical
        </span>
        @endif
        @if($issueSummary['warning'] > 0)
        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-semibold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-400">
            ⚠️ {{ $issueSummary['warning'] }} Warning
        </span>
        @endif
        @if($issueSummary['info'] > 0)
        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-semibold bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-400">
            ℹ️ {{ $issueSummary['info'] }} Info
        </span>
        @endif
        @if($issueSummary['total'] === 0)
        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400">
            ✓ No open issues
        </span>
        @endif
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     Health Check Cards (3 columns)
═══════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

    @php
    $checkCards = [
        [
            'type'    => SystemHealthCheck::TYPE_DEVICE,
            'label'   => 'Device Health',
            'icon'    => 'heroicon-o-server-stack',
            'checks'  => $deviceChecks,
            'emptyMsg'=> 'No device health data',
            'metrics' => fn($d) => [
                ['label' => 'Total Lockers',  'value' => $d['total']       ?? '—'],
                ['label' => 'Available',       'value' => $d['available']   ?? '—'],
                ['label' => 'In Use',          'value' => $d['inUse']       ?? '—'],
                ['label' => 'Fault',           'value' => ($d['fault'] ?? 0) . ' (' . ($d['faultRate'] ?? 0) . '%)'],
                ['label' => 'Healthy Rate',    'value' => ($d['healthyRate'] ?? '—') . '%'],
            ],
        ],
        [
            'type'    => SystemHealthCheck::TYPE_CONNECTION,
            'label'   => 'Connection Health',
            'icon'    => 'heroicon-o-wifi',
            'checks'  => $connectionChecks,
            'emptyMsg'=> 'No connection health data',
            'metrics' => fn($d) => [
                ['label' => 'Total',    'value' => $d['total']   ?? '—'],
                ['label' => 'Online',   'value' => ($d['online'] ?? 0) . ' (' . ($d['onlineRate'] ?? 0) . '%)'],
                ['label' => 'Warning',  'value' => $d['warning'] ?? '—'],
                ['label' => 'Offline',  'value' => ($d['offline'] ?? 0) . ' (' . ($d['offlineRate'] ?? 0) . '%)'],
                ['label' => 'Online Rate', 'value' => ($d['onlineRate'] ?? '—') . '%'],
            ],
        ],
        [
            'type'    => SystemHealthCheck::TYPE_API,
            'label'   => 'API Health',
            'icon'    => 'heroicon-o-cloud',
            'checks'  => $apiChecks,
            'emptyMsg'=> 'No API health data',
            'metrics' => fn($d) => [
                ['label' => 'Reachable',     'value' => ($d['reachable'] ?? false) ? '✓ Yes' : '✕ No'],
                ['label' => 'Response Time', 'value' => isset($d['response_time_ms']) ? $d['response_time_ms'] . ' ms' : '—'],
                ['label' => 'Endpoint',      'value' => $d['endpoint']   ?? '—'],
                ['label' => 'Error',         'value' => $d['error']      ?? '—'],
            ],
        ],
    ];
    @endphp

    @foreach($checkCards as $card)
    <div class="rounded-xl border bg-white dark:bg-gray-800 shadow-sm overflow-hidden">

        {{-- Card header --}}
        <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center gap-2">
            <x-dynamic-component :component="$card['icon']" class="w-5 h-5 text-gray-400" />
            <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $card['label'] }}</span>
        </div>

        @if($card['checks']->isEmpty())
        <div class="px-4 py-6 text-center text-gray-400 text-sm">
            <x-heroicon-o-clock class="w-8 h-8 mx-auto mb-2 opacity-40" />
            {{ $card['emptyMsg'] }}<br>
            <span class="text-xs">Run <code>php artisan health:check</code></span>
        </div>
        @else
            {{-- If multiple companies visible, show sub-cards per company --}}
            @foreach($card['checks'] as $check)
            @php
                $cfg     = $checkStatusCfg[$check->status] ?? $checkStatusCfg['unknown'];
                $details = $check->details ?? [];
                $metrics = ($card['metrics'])($details);
            @endphp
            <div class="px-4 py-3 {{ ! $loop->last ? 'border-b border-gray-50 dark:border-gray-700/50' : '' }}">

                {{-- Status row --}}
                <div class="flex items-center justify-between mb-2">
                    @if($check->company_id)
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $check->company?->name ?? 'Company #' . $check->company_id }}
                        </span>
                    @else
                        <span class="text-xs text-gray-500 dark:text-gray-400">Global</span>
                    @endif

                    <div class="flex items-center gap-2">
                        {{-- Score bar --}}
                        <div class="w-16 h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ $check->status === 'healthy' ? 'bg-green-500' : ($check->status === 'warning' ? 'bg-amber-400' : 'bg-red-500') }}"
                                 style="width: {{ $check->score }}%">
                            </div>
                        </div>
                        <span class="text-xs font-semibold {{ $cfg['badge'] }} px-2 py-0.5 rounded-full">
                            {{ $cfg['icon'] }} {{ $cfg['label'] }}
                        </span>
                    </div>
                </div>

                {{-- Metrics grid --}}
                <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                    @foreach($metrics as $metric)
                    <div>
                        <span class="text-xs text-gray-400">{{ $metric['label'] }}</span>
                        <div class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate">
                            {{ $metric['value'] }}
                        </div>
                    </div>
                    @endforeach
                </div>

                <div class="mt-1.5 text-right">
                    <span class="text-xs text-gray-400">{{ $check->checked_at->diffForHumans() }}</span>
                </div>
            </div>
            @endforeach
        @endif
    </div>
    @endforeach

</div>

{{-- ═══════════════════════════════════════════════════════════
     Issue Summary Row
═══════════════════════════════════════════════════════════ --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">

    @foreach([
        ['key' => 'critical', 'label' => 'Critical Issues', 'color' => 'text-red-600',   'bg' => 'border-red-100 dark:border-red-900/30'],
        ['key' => 'warning',  'label' => 'Warnings',        'color' => 'text-amber-500', 'bg' => 'border-amber-100 dark:border-amber-900/30'],
        ['key' => 'info',     'label' => 'Info',            'color' => 'text-blue-500',  'bg' => 'border-blue-100 dark:border-blue-900/30'],
        ['key' => 'total',    'label' => 'Total Open',      'color' => 'text-gray-700 dark:text-gray-200', 'bg' => 'border-gray-200 dark:border-gray-700'],
    ] as $stat)
    <div class="rounded-xl border {{ $stat['bg'] }} bg-white dark:bg-gray-800 p-4 shadow-sm text-center">
        <p class="text-3xl font-bold {{ $stat['color'] }}">{{ $issueSummary[$stat['key']] }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $stat['label'] }}</p>
    </div>
    @endforeach

</div>

{{-- ═══════════════════════════════════════════════════════════
     Alert List
═══════════════════════════════════════════════════════════ --}}
<x-filament::section>
    <x-slot name="heading">System Alerts</x-slot>
    <x-slot name="description">Recent alerts — click Acknowledge to confirm you've seen the issue</x-slot>
    <x-slot name="headerEnd">
        {{-- Filters --}}
        <div class="flex flex-wrap items-center gap-2">
            {{-- Status filter --}}
            <x-filament::input.wrapper class="text-xs">
                <x-filament::input.select wire:model.live="filterAlertStatus" class="text-xs">
                    <option value="">All Statuses</option>
                    <option value="open">Open</option>
                    <option value="acknowledged">Acknowledged</option>
                    <option value="resolved">Resolved</option>
                </x-filament::input.select>
            </x-filament::input.wrapper>

            {{-- Severity filter --}}
            <x-filament::input.wrapper class="text-xs">
                <x-filament::input.select wire:model.live="filterAlertSeverity" class="text-xs">
                    <option value="">All Severities</option>
                    <option value="critical">Critical</option>
                    <option value="warning">Warning</option>
                    <option value="info">Info</option>
                </x-filament::input.select>
            </x-filament::input.wrapper>

            <span class="text-xs text-gray-400">{{ $alerts->count() }} results</span>
        </div>
    </x-slot>

    @if($alerts->isEmpty())
    <div class="text-center py-12 text-gray-400">
        <x-heroicon-o-check-badge class="w-12 h-12 mx-auto mb-3 opacity-30" />
        <p class="font-medium">No alerts found</p>
        <p class="text-xs mt-1">
            @if($filterAlertStatus === 'open')
                System is running clean — no open issues detected.
            @else
                No alerts match the selected filters.
            @endif
        </p>
    </div>
    @else
    <div class="overflow-x-auto -mx-6 -mb-6">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 dark:bg-gray-700/50 text-gray-500 dark:text-gray-400 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 w-24">Severity</th>
                    <th class="px-4 py-3 w-36">Type</th>
                    <th class="px-4 py-3">Title / Message</th>
                    @if($user->isSuperAdmin())
                    <th class="px-4 py-3 w-32">Company</th>
                    @endif
                    <th class="px-4 py-3 w-24">Status</th>
                    <th class="px-4 py-3 w-36">Time</th>
                    <th class="px-4 py-3 w-36 text-center">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($alerts as $alert)
                @php
                    $sevCfg    = $severityConfig[$alert->severity] ?? $severityConfig['info'];
                    $statColor = $statusBadgeCfg[$alert->status]  ?? $statusBadgeCfg['open'];
                    $typeLabel = \App\Models\SystemAlert::typeLabels()[$alert->alert_type] ?? $alert->alert_type;
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors
                           {{ $alert->status === 'open' && $alert->severity === 'critical' ? 'border-l-2 border-l-red-400' : '' }}
                           {{ $alert->status === 'open' && $alert->severity === 'warning'  ? 'border-l-2 border-l-amber-400' : '' }}">

                    {{-- Severity --}}
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-semibold {{ $sevCfg['bg'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $sevCfg['dot'] }}"></span>
                            {{ $sevCfg['label'] }}
                        </span>
                    </td>

                    {{-- Type --}}
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300 text-xs font-medium">
                        {{ $typeLabel }}
                    </td>

                    {{-- Title / Message --}}
                    <td class="px-4 py-3">
                        <p class="font-medium text-gray-900 dark:text-white">{{ $alert->title }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 line-clamp-2">{{ $alert->message }}</p>

                        @if($alert->isAcknowledged())
                        <div class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                            <span class="font-medium">Acknowledged</span> by {{ $alert->acknowledgedBy?->name ?? '—' }}
                            @if($alert->acknowledge_note)
                                — "{{ Str::limit($alert->acknowledge_note, 80) }}"
                            @endif
                        </div>
                        @endif
                    </td>

                    {{-- Company (super-admin only) --}}
                    @if($user->isSuperAdmin())
                    <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                        {{ $alert->company?->name ?? '— Global —' }}
                    </td>
                    @endif

                    {{-- Status badge --}}
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statColor }}">
                            {{ ucfirst($alert->status) }}
                        </span>
                    </td>

                    {{-- Time --}}
                    <td class="px-4 py-3 text-xs text-gray-400 whitespace-nowrap">
                        <div>{{ $alert->created_at->format('d M H:i') }}</div>
                        <div class="text-gray-300 dark:text-gray-600">{{ $alert->created_at->diffForHumans() }}</div>
                    </td>

                    {{-- Action --}}
                    <td class="px-4 py-3 text-center">
                        @if($alert->isOpen())
                            @can('acknowledge alerts')
                            <button
                                wire:click="openAcknowledgeModal({{ $alert->id }})"
                                class="inline-flex items-center gap-1 text-xs bg-amber-50 hover:bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:hover:bg-amber-900/50 dark:text-amber-400 border border-amber-200 dark:border-amber-800 px-3 py-1 rounded-lg transition-colors font-medium"
                            >
                                <x-heroicon-o-check class="w-3.5 h-3.5" />
                                Acknowledge
                            </button>
                            @endcan
                        @elseif($alert->isAcknowledged())
                            <span class="text-xs text-amber-500">
                                ✓ ACK'd {{ $alert->acknowledged_at?->diffForHumans() }}
                            </span>
                        @elseif($alert->isResolved())
                            <span class="text-xs text-green-500">
                                ✓ Resolved {{ $alert->resolved_at?->diffForHumans() }}
                            </span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</x-filament::section>

{{-- ═══════════════════════════════════════════════════════════
     Acknowledge Modal
═══════════════════════════════════════════════════════════ --}}
@can('acknowledge alerts')
<x-filament::modal id="acknowledge-modal" width="md">
    <x-slot name="heading">Acknowledge Alert</x-slot>
    <x-slot name="description">
        Confirming that you have seen this alert and are taking action.
        This action will be recorded in the audit log.
    </x-slot>

    <div class="space-y-4">
        @if($acknowledgeAlertId)
        @php $ackAlert = $alerts->find($acknowledgeAlertId); @endphp
        @if($ackAlert)
        <div class="rounded-lg bg-gray-50 dark:bg-gray-700 p-3 text-sm">
            <div class="font-semibold text-gray-800 dark:text-gray-200">{{ $ackAlert->title }}</div>
            <div class="text-gray-500 dark:text-gray-400 mt-1 text-xs">{{ $ackAlert->message }}</div>
        </div>
        @endif
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Note (optional)
            </label>
            <textarea
                wire:model="acknowledgeNote"
                rows="3"
                maxlength="500"
                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:ring-primary-500 focus:border-primary-500"
                placeholder="What action is being taken? (e.g. Technician dispatched, monitoring closely...)"
            ></textarea>
        </div>
    </div>

    <x-slot name="footer">
        <div class="flex justify-end gap-3">
            <x-filament::button
                color="gray"
                x-on:click="$dispatch('close-modal', { id: 'acknowledge-modal' })"
            >
                Cancel
            </x-filament::button>
            <x-filament::button
                wire:click="submitAcknowledge"
                wire:loading.attr="disabled"
                color="warning"
            >
                <span wire:loading.remove>✓ Acknowledge Alert</span>
                <span wire:loading>Saving…</span>
            </x-filament::button>
        </div>
    </x-slot>
</x-filament::modal>
@endcan

</x-filament-panels::page>
