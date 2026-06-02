<x-filament-panels::page>

    {{-- ════════════════════════════════════════════════════════════
         Filter Bar
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="flex flex-wrap items-end gap-3 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm mb-6">
        <div class="flex items-center gap-1.5 text-gray-400 dark:text-gray-500 shrink-0 mr-1">
            <x-heroicon-o-funnel class="w-4 h-4"/>
            <span class="text-xs font-medium uppercase tracking-wider">{{ __('Filter') }}</span>
        </div>

        @if(auth()->user()->isSuperAdmin())
        <div class="flex flex-col gap-1 min-w-40">
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Company / Tenant') }}</label>
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="filterCompanyId">
                    <option value="">{{ __('All Companies') }}</option>
                    @foreach($this->getCompanies() as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
        @endif

        <div class="flex flex-col gap-1 min-w-40">
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Location') }}</label>
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="filterLocationId">
                    <option value="">{{ __('All Locations') }}</option>
                    @foreach($this->getLocations() as $l)
                        <option value="{{ $l->id }}">{{ $l->name }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>

        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('From') }}</label>
            <x-filament::input.wrapper>
                <x-filament::input type="date" wire:model.live="filterDateFrom" />
            </x-filament::input.wrapper>
        </div>

        <div class="flex flex-col gap-1">
            <label class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('To') }}</label>
            <x-filament::input.wrapper>
                <x-filament::input type="date" wire:model.live="filterDateTo" />
            </x-filament::input.wrapper>
        </div>

        {{-- Quick range shortcuts --}}
        <div class="flex gap-2 flex-wrap">
            @foreach([
                ['label' => __('Today'),      'from' => now()->format('Y-m-d'), 'to' => now()->format('Y-m-d')],
                ['label' => __('7 days'),     'from' => now()->subDays(6)->format('Y-m-d'), 'to' => now()->format('Y-m-d')],
                ['label' => __('30 days'),    'from' => now()->subDays(29)->format('Y-m-d'), 'to' => now()->format('Y-m-d')],
                ['label' => __('This month'), 'from' => now()->startOfMonth()->format('Y-m-d'), 'to' => now()->format('Y-m-d')],
            ] as $r)
            <x-filament::button
                color="gray"
                size="xs"
                wire:click="$set('filterDateFrom','{{ $r['from'] }}'); $set('filterDateTo','{{ $r['to'] }}')"
            >{{ $r['label'] }}</x-filament::button>
            @endforeach
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════
         Stat Widgets (3 rows of cards)
    ═══════════════════════════════════════════════════════════════ --}}
    @php
        $widgetData = $this->getWidgetsData();
    @endphp

    <div class="space-y-4">
        {{-- Row 1: Locker overview --}}
        @livewire(\App\Filament\Widgets\LockerOverviewStats::class,
            $widgetData,
            key('locker-stats-' . md5(json_encode($this->getFilters())))
        )

        {{-- Row 2: Box overview --}}
        @livewire(\App\Filament\Widgets\BoxOverviewStats::class,
            $widgetData,
            key('box-stats-' . md5(json_encode($this->getFilters())))
        )

        {{-- Row 3: Transaction overview --}}
        @livewire(\App\Filament\Widgets\TransactionOverviewStats::class,
            $widgetData,
            key('tx-stats-' . md5(json_encode($this->getFilters())))
        )
    </div>

    {{-- ════════════════════════════════════════════════════════════
         Charts Row
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4 mt-6">

        {{-- Usage Trend — takes 2/3 width --}}
        <div class="xl:col-span-2">
            @livewire(\App\Filament\Widgets\UsageTrendChart::class,
                $widgetData,
                key('trend-chart-' . md5(json_encode($this->getFilters())))
            )
        </div>

        {{-- Top Lockers — takes 1/3 width --}}
        <div>
            @livewire(\App\Filament\Widgets\TopLockersChart::class,
                $widgetData,
                key('top-lockers-' . md5(json_encode($this->getFilters())))
            )
        </div>
    </div>

    {{-- ════════════════════════════════════════════════════════════
         Detail Table — Recent Events
    ═══════════════════════════════════════════════════════════════ --}}
    @php
        $stats = $this->getSummaryStats();
        $user  = auth()->user();

        $recentEvents = \App\Models\LockerEvent::with(['locker','lockerBox'])
            ->when(! $user->isSuperAdmin(), fn($q) => $q->where('company_id', $user->company_id))
            ->when($this->filterCompanyId !== '' && $user->isSuperAdmin(),
                fn($q) => $q->where('company_id', $this->filterCompanyId))
            ->when($this->filterLocationId !== '',
                fn($q) => $q->whereHas('locker', fn($lq) => $lq->where('location_id', $this->filterLocationId)))
            ->when($this->filterDateFrom,
                fn($q) => $q->where('created_at', '>=', \Carbon\Carbon::parse($this->filterDateFrom)->startOfDay()))
            ->when($this->filterDateTo,
                fn($q) => $q->where('created_at', '<=', \Carbon\Carbon::parse($this->filterDateTo)->endOfDay()))
            ->latest()
            ->limit(50)
            ->get();
    @endphp

    <x-filament::section :heading="__('Recent Events')" class="mt-6">
        <x-slot name="headerEnd">
            <span class="text-xs text-gray-400">{{ __('Showing last 50 events in selected range') }}</span>
        </x-slot>

        @if($recentEvents->isEmpty())
        <div class="text-center py-8 text-gray-400">
            <x-heroicon-o-inbox class="w-12 h-12 mx-auto mb-2 opacity-30" />
            <p>{{ __('No events found for the selected filters.') }}</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-700 text-left">
                        <th class="pb-2 pr-4 font-medium text-gray-500">{{ __('Time') }}</th>
                        <th class="pb-2 pr-4 font-medium text-gray-500">{{ __('Locker') }}</th>
                        <th class="pb-2 pr-4 font-medium text-gray-500">{{ __('Box') }}</th>
                        <th class="pb-2 pr-4 font-medium text-gray-500">{{ __('Event') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700/50">
                    @foreach($recentEvents as $event)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="py-2 pr-4 text-gray-400 whitespace-nowrap text-xs">
                            {{ $event->created_at->format('d M H:i') }}
                        </td>
                        <td class="py-2 pr-4 font-medium text-gray-700 dark:text-gray-300">
                            {{ $event->locker?->name ?? '—' }}
                        </td>
                        <td class="py-2 pr-4 text-gray-500">
                            {{ $event->lockerBox ? '#' . $event->lockerBox->box_number : '—' }}
                        </td>
                        <td class="py-2">
                            @php
                                $eventBadge = [
                                    'open'      => ['color' => 'success', 'icon' => '🔓'],
                                    'unlock'    => ['color' => 'info',    'icon' => '🔑'],
                                    'close'     => ['color' => 'gray',    'icon' => '🔒'],
                                    'heartbeat' => ['color' => 'gray',    'icon' => '💓'],
                                    'error'     => ['color' => 'danger',  'icon' => '⚠️'],
                                    'sync'      => ['color' => 'gray',    'icon' => '🔄'],
                                ][$event->event_type] ?? ['color' => 'gray', 'icon' => '•'];
                            @endphp
                            <x-filament::badge :color="$eventBadge['color']" size="sm">
                                {{ $eventBadge['icon'] }} {{ ucfirst($event->event_type) }}
                            </x-filament::badge>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </x-filament::section>

    {{-- ════════════════════════════════════════════════════════════
         Summary Breakdown Panel
    ═══════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">

        {{-- Locker Status Breakdown --}}
        <x-filament::section :heading="__('Locker Status')">
            @php $lockers = $stats['lockers']; @endphp
            <div class="space-y-2">
                @foreach([
                    ['label' => __('Available'), 'value' => $lockers['available'], 'color' => 'bg-green-500'],
                    ['label' => __('In Use'),    'value' => $lockers['in_use'],    'color' => 'bg-blue-500'],
                    ['label' => __('Fault'),     'value' => $lockers['fault'],     'color' => 'bg-red-500'],
                    ['label' => __('Offline'),   'value' => $lockers['offline'],   'color' => 'bg-gray-400'],
                    ['label' => __('Disabled'),  'value' => $lockers['disabled'],  'color' => 'bg-yellow-400'],
                ] as $row)
                @if($lockers['total'] > 0)
                <div>
                    <div class="flex justify-between text-xs mb-0.5">
                        <span class="text-gray-600 dark:text-gray-400">{{ $row['label'] }}</span>
                        <span class="font-medium">{{ $row['value'] }}</span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                        <div class="{{ $row['color'] }} h-1.5 rounded-full"
                             style="width: {{ $lockers['total'] > 0 ? round($row['value']/$lockers['total']*100) : 0 }}%">
                        </div>
                    </div>
                </div>
                @endif
                @endforeach
            </div>
        </x-filament::section>

        {{-- Box Status Breakdown --}}
        <x-filament::section :heading="__('Box Status')">
            @php $boxes = $stats['boxes']; @endphp
            <div class="space-y-2">
                @foreach([
                    ['label' => __('Available'), 'value' => $boxes['available'], 'color' => 'bg-green-500'],
                    ['label' => __('Occupied'),  'value' => $boxes['occupied'],  'color' => 'bg-blue-500'],
                    ['label' => __('Open'),      'value' => $boxes['open'],      'color' => 'bg-amber-400'],
                    ['label' => __('Error'),     'value' => $boxes['error'],     'color' => 'bg-red-500'],
                    ['label' => __('Disabled'),  'value' => $boxes['disabled'],  'color' => 'bg-gray-400'],
                ] as $row)
                @if($boxes['total'] > 0)
                <div>
                    <div class="flex justify-between text-xs mb-0.5">
                        <span class="text-gray-600 dark:text-gray-400">{{ $row['label'] }}</span>
                        <span class="font-medium">{{ $row['value'] }}</span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                        <div class="{{ $row['color'] }} h-1.5 rounded-full"
                             style="width: {{ $boxes['total'] > 0 ? round($row['value']/$boxes['total']*100) : 0 }}%">
                        </div>
                    </div>
                </div>
                @endif
                @endforeach
            </div>
        </x-filament::section>

        {{-- Connection Status --}}
        <x-filament::section :heading="__('Connection Health')">
            @php
                $conn  = $stats['connection'];
                $total = $conn['online'] + $conn['warning'] + $conn['offline'];
            @endphp
            <div class="space-y-3">
                @foreach([
                    ['label' => __('Online'),  'value' => $conn['online'],  'color' => 'bg-green-500',  'hex' => '#22c55e'],
                    ['label' => __('Warning'), 'value' => $conn['warning'], 'color' => 'bg-amber-400',  'hex' => '#f59e0b'],
                    ['label' => __('Offline'), 'value' => $conn['offline'], 'color' => 'bg-red-500',    'hex' => '#ef4444'],
                ] as $row)
                <div>
                    <div class="flex justify-between text-xs mb-0.5">
                        <span class="flex items-center gap-1.5 text-gray-600 dark:text-gray-400">
                            <span class="w-2 h-2 rounded-full {{ $row['color'] }}"></span>
                            {{ $row['label'] }}
                        </span>
                        <span class="font-medium">{{ $row['value'] }}</span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5">
                        <div class="{{ $row['color'] }} h-1.5 rounded-full"
                             style="width: {{ $total > 0 ? round($row['value']/$total*100) : 0 }}%">
                        </div>
                    </div>
                </div>
                @endforeach

                <div class="pt-2 border-t border-gray-100 dark:border-gray-700 text-xs text-gray-400">
                    @php $pct = $total > 0 ? round($conn['online'] / $total * 100) : 0; @endphp
                    <span class="font-semibold text-lg {{ $pct >= 80 ? 'text-green-500' : ($pct >= 50 ? 'text-amber-500' : 'text-red-500') }}">
                        {{ $pct }}%
                    </span>
                    {{ __('connectivity rate') }}
                </div>
            </div>
        </x-filament::section>
    </div>

</x-filament-panels::page>
