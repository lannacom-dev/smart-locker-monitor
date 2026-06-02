<x-filament-panels::page>

    {{-- ── Filter Bar ─────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-end gap-3 px-4 py-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm mb-4">
        <div class="flex items-center gap-1.5 text-gray-400 dark:text-gray-500 shrink-0 mr-1">
            <x-heroicon-o-funnel class="w-4 h-4"/>
            <span class="text-xs font-medium uppercase tracking-wider">{{ __('Filter') }}</span>
        </div>

        @if(auth()->user()->isSuperAdmin())
        <div class="flex-1 min-w-36">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="filterCompanyId">
                    <option value="">{{ __('All Companies') }}</option>
                    @foreach($this->getCompanies() as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
        @endif

        <div class="flex-1 min-w-36">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="filterBuilding">
                    <option value="">{{ __('All Buildings') }}</option>
                    @foreach($this->getUniqueBuildings() as $b)
                        <option value="{{ $b }}">{{ $b }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>

        <div class="flex-1 min-w-28">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="filterFloor">
                    <option value="">{{ __('All Floors') }}</option>
                    @foreach($this->getUniqueFloors() as $f)
                        <option value="{{ $f }}">{{ __('Floor') }} {{ $f }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>

        <div class="flex-1 min-w-36">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="filterConnStatus">
                    <option value="">{{ __('All Status') }}</option>
                    <option value="online">🟢 {{ __('Online') }}</option>
                    <option value="warning">🟡 {{ __('Warning') }}</option>
                    <option value="offline">🔴 {{ __('Offline') }}</option>
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </div>
    </div>

    {{-- ── Floor Plan Tabs ─────────────────────────────────────────── --}}
    @php
        $floorPlans = $this->getFloorPlans();
        $selectedFP = $this->getSelectedFloorPlan();
        $placedLockers = $this->getPlacedLockers();
        $summary = $this->getConnectionSummary();
        $canEdit = auth()->user()->can('edit lockers');
    @endphp

    @if($floorPlans->isEmpty())
        <x-filament::section>
            <div class="text-center py-12 text-gray-400 dark:text-gray-500">
                <x-heroicon-o-map class="w-16 h-16 mx-auto mb-3 opacity-40" />
                <p class="text-lg font-medium">{{ __('No floor plans found.') }}</p>
                <p class="text-sm mt-1">
                    <a href="{{ route('filament.admin.resources.floor-plans.create') }}"
                       class="text-primary-500 underline">{{ __('Create a floor plan') }}</a>
                    {{ __('to start placing lockers.') }}
                </p>
            </div>
        </x-filament::section>
    @else

    {{-- Floor plan selector tabs --}}
    <div class="flex gap-2 flex-wrap mb-3">
        @foreach($floorPlans as $fp)
        <button
            wire:click="$set('selectedFloorPlanId', {{ $fp->id }})"
            @class([
                'px-3 py-1.5 rounded-lg text-sm font-medium border transition-colors',
                'bg-primary-500 text-white border-primary-500' => $selectedFloorPlanId == $fp->id,
                'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 hover:border-primary-400' => $selectedFloorPlanId != $fp->id,
            ])
        >
            @if($fp->building) <span class="opacity-70">{{ $fp->building }}</span> · @endif
            @if($fp->floor) {{ __('Floor') }} {{ $fp->floor }} · @endif
            {{ $fp->name }}
        </button>
        @endforeach
    </div>

    {{-- ── Status Summary Cards ────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">

        {{-- Total --}}
        <div class="rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 shadow-sm flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Total') }}</span>
                <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-1.5">
                    <x-heroicon-s-server-stack class="w-4 h-4 text-gray-500 dark:text-gray-400"/>
                </div>
            </div>
            <p class="text-3xl font-extrabold text-gray-800 dark:text-gray-100 leading-none">{{ $summary['total'] }}</p>
        </div>

        {{-- Online --}}
        <div class="rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 p-4 shadow-md text-white flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold opacity-80 uppercase tracking-wider">{{ __('Online') }}</span>
                <div class="bg-white/20 rounded-lg p-1.5">
                    <x-heroicon-s-signal class="w-4 h-4"/>
                </div>
            </div>
            <p class="text-3xl font-extrabold leading-none">{{ $summary['online'] }}</p>
        </div>

        {{-- Warning --}}
        <div class="rounded-xl bg-gradient-to-br from-amber-400 to-orange-500 p-4 shadow-md text-white flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold opacity-80 uppercase tracking-wider">{{ __('Warning') }}</span>
                <div class="bg-white/20 rounded-lg p-1.5">
                    <x-heroicon-s-exclamation-triangle class="w-4 h-4"/>
                </div>
            </div>
            <p class="text-3xl font-extrabold leading-none">{{ $summary['warning'] }}</p>
        </div>

        {{-- Offline --}}
        <div class="rounded-xl bg-gradient-to-br from-slate-500 to-gray-600 p-4 shadow-md text-white flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold opacity-80 uppercase tracking-wider">{{ __('Offline') }}</span>
                <div class="bg-white/20 rounded-lg p-1.5">
                    <x-heroicon-s-signal-slash class="w-4 h-4"/>
                </div>
            </div>
            <p class="text-3xl font-extrabold leading-none">{{ $summary['offline'] }}</p>
        </div>

    </div>

    {{-- ── Main Content: Floor Plan + Detail Panel ─────────────────── --}}
    <div class="flex gap-4">

        {{-- Floor Plan Canvas --}}
        <div class="flex-1 min-w-0">
            <x-filament::section
                :heading="$selectedFP?->label ?? __('Select a floor plan above')"
                :description="$canEdit ? __('Click an empty area to place a locker. Click a marker to select it.') : null"
            >
                @if($selectedFP && ($selectedFP->display_image_url || true))
                <div
                    class="relative w-full overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700 select-none"
                    style="padding-bottom: 60%; background: #f1f5f9;"
                    x-data="floorPlanCanvas({
                        canEdit: {{ $canEdit ? 'true' : 'false' }},
                        lockers: {{ Js::from($placedLockers->values()) }}
                    })"
                    @click="handleCanvasClick($event, $el)"
                >
                    {{-- Floor plan image --}}
                    @if($selectedFP->display_image_url)
                    <img
                        src="{{ $selectedFP->display_image_url }}"
                        class="absolute inset-0 w-full h-full object-contain pointer-events-none"
                        draggable="false"
                        alt="{{ $selectedFP->name }}"
                    />
                    @else
                    <div class="absolute inset-0 flex items-center justify-center text-gray-300">
                        <div class="text-center">
                            <x-heroicon-o-map class="w-20 h-20 mx-auto mb-2 opacity-30" />
                            <p class="text-sm">{{ __('No image uploaded. Locker positions are shown on a blank canvas.') }}</p>
                        </div>
                    </div>
                    @endif

                    {{-- Locker markers --}}
                    @foreach($placedLockers as $locker)
                    <div
                        class="absolute cursor-pointer group"
                        style="left: {{ $locker->pos_x }}%; top: {{ $locker->pos_y }}%; transform: translate(-50%, -50%);"
                        wire:click.stop="selectLocker({{ $locker->id }})"
                        x-on:mousedown.stop="startDrag({{ $locker->locker_location_id }}, $event, $el)"
                        title="{{ $locker->name }}"
                    >
                        {{-- Outer ring: connection status --}}
                        <div
                            @class([
                                'w-10 h-10 rounded-full flex items-center justify-center ring-4 ring-white/60 shadow-lg transition-transform group-hover:scale-125',
                                'bg-green-500'  => $locker->connection_status === 'online',
                                'bg-amber-400'  => $locker->connection_status === 'warning',
                                'bg-red-500'    => $locker->connection_status === 'offline',
                                'bg-gray-400'   => !in_array($locker->connection_status, ['online','warning','offline']),
                            ])
                            style="box-shadow: 0 0 0 3px {{ $locker->conn_hex }}44;"
                        >
                            {{-- Inner dot: selected indicator --}}
                            <div @class([
                                'w-4 h-4 rounded-full bg-white/80',
                                'ring-2 ring-yellow-300' => $detailLockerId == $locker->id,
                            ])></div>
                        </div>

                        {{-- Label below marker --}}
                        <div class="absolute top-full mt-1 left-1/2 -translate-x-1/2 whitespace-nowrap
                                    bg-black/70 text-white text-[10px] px-1.5 py-0.5 rounded pointer-events-none
                                    opacity-0 group-hover:opacity-100 transition-opacity z-10">
                            {{ $locker->name }}
                            @if($locker->floor_zone) · {{ $locker->floor_zone }} @endif
                        </div>
                    </div>
                    @endforeach

                    {{-- Drag ghost (Alpine.js managed) --}}
                    <div
                        x-show="dragging"
                        x-bind:style="`left:${dragX}%;top:${dragY}%;`"
                        class="absolute w-10 h-10 rounded-full bg-blue-500/50 border-2 border-blue-400 pointer-events-none"
                        style="transform:translate(-50%,-50%);"
                    ></div>
                </div>

                @if($canEdit)
                <p class="text-xs text-gray-400 mt-2">
                    🖱 {{ __('Drag markers to reposition · Click empty area to place a new locker') }}
                </p>
                @endif

                @else
                <div class="text-center py-10 text-gray-400">
                    <x-heroicon-o-arrow-up class="w-8 h-8 mx-auto mb-2" />
                    <p>{{ __('Select a floor plan tab above.') }}</p>
                </div>
                @endif
            </x-filament::section>
        </div>

        {{-- ── Detail Panel ─────────────────────────────────────────── --}}
        @if($this->getDetailLocker())
        @php $detail = $this->getDetailLocker(); @endphp
        <div class="w-80 shrink-0">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between">
                        <span>{{ $detail->name }}</span>
                        <button wire:click="$set('detailLockerId', '')" class="text-gray-400 hover:text-gray-600">
                            <x-heroicon-o-x-mark class="w-4 h-4" />
                        </button>
                    </div>
                </x-slot>

                {{-- Connection status badge --}}
                <div class="flex items-center gap-2 mb-3">
                    @php
                        $connColors = [
                            'online'  => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                            'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
                            'offline' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                        ];
                        $connDots = ['online'=>'bg-green-500','warning'=>'bg-amber-400','offline'=>'bg-red-500'];
                    @endphp
                    <span @class([
                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold',
                        $connColors[$detail->connection_status] ?? 'bg-gray-100 text-gray-600',
                    ])>
                        <span @class(['w-2 h-2 rounded-full', $connDots[$detail->connection_status] ?? 'bg-gray-400'])></span>
                        {{ strtoupper($detail->connection_status) }}
                    </span>

                    @php
                        $opColors = \App\Models\Locker::statusColors();
                        $opLabels = \App\Models\Locker::statusOptions();
                    @endphp
                    <x-filament::badge :color="$opColors[$detail->status] ?? 'gray'">
                        {{ $opLabels[$detail->status] ?? $detail->status }}
                    </x-filament::badge>
                </div>

                {{-- Info rows --}}
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('Serial') }}</dt>
                        <dd class="font-mono text-xs">{{ $detail->serial_number }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('IP') }}</dt>
                        <dd class="font-mono text-xs">{{ $detail->ip_address ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('Firmware') }}</dt>
                        <dd>{{ $detail->firmware_version ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('Last seen') }}</dt>
                        <dd>{{ $detail->last_seen_at ? $detail->last_seen_at->diffForHumans() : __('Never') }}</dd>
                    </div>
                    @if($detail->company_name)
                    <div class="flex justify-between">
                        <dt class="text-gray-500">{{ __('Company') }}</dt>
                        <dd>{{ $detail->company_name }}</dd>
                    </div>
                    @endif
                </dl>

                @if($canEdit)
                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <x-filament::button
                        wire:click="removeFromFloorPlan({{ $detail->id }})"
                        color="danger"
                        size="sm"
                        icon="heroicon-o-map-pin"
                        wire:confirm="{{ __('Remove') }} {{ $detail->name }} {{ __('from this floor plan?') }}"
                        class="w-full"
                    >
                        {{ __('Remove from Floor Plan') }}
                    </x-filament::button>
                </div>
                @endif

                {{-- Connection history --}}
                @if($detail->history->isNotEmpty())
                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ __('Connection Log') }}</p>
                    <div class="space-y-1.5 max-h-48 overflow-y-auto">
                        @foreach($detail->history as $log)
                        <div class="flex items-start gap-2 text-xs">
                            <span @class([
                                'mt-0.5 w-2 h-2 rounded-full shrink-0',
                                'bg-green-500' => $log->new_status === 'online',
                                'bg-amber-400' => $log->new_status === 'warning',
                                'bg-red-500'   => $log->new_status === 'offline',
                                'bg-gray-400'  => true,
                            ])></span>
                            <div>
                                <span class="text-gray-500">{{ $log->old_status ?? '·' }} → </span>
                                <span class="font-medium">{{ strtoupper($log->new_status) }}</span>
                                <span class="text-gray-400 ml-1">({{ $log->source }})</span>
                                <div class="text-gray-400">{{ $log->created_at?->diffForHumans() }}</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </x-filament::section>
        </div>
        @endif

    </div>
    @endif

    {{-- ── Place Locker Modal ──────────────────────────────────────── --}}
    @if($showPlaceModal && $canEdit)
    <x-filament::modal
        :heading="__('Place Locker at') . ' (' . $clickedX . '%, ' . $clickedY . '%)'"
        wire:model="showPlaceModal"
        :close-button="true"
    >
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Locker') }}</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model="placeLockerId">
                        <option value="">{{ __('— Select locker —') }}</option>
                        @foreach($this->getUnplacedLockers() as $ul)
                        <option value="{{ $ul->id }}">{{ $ul->name }} ({{ $ul->serial_number }})</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Zone (optional)') }}</label>
                <x-filament::input.wrapper>
                    <x-filament::input wire:model="placeZone" placeholder="{{ __('e.g. Server Room, Zone B') }}" />
                </x-filament::input.wrapper>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Note (optional)') }}</label>
                <x-filament::input.wrapper>
                    <textarea wire:model="placeNote" rows="2"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm px-3 py-2"
                        placeholder="{{ __('Additional info...') }}"></textarea>
                </x-filament::input.wrapper>
            </div>
        </div>

        <x-slot name="footerActions">
            <x-filament::button wire:click="saveLockerPosition" color="success">
                {{ __('Place Locker') }}
            </x-filament::button>
            <x-filament::button wire:click="closePlaceModal" color="gray">
                {{ __('Cancel') }}
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
    @endif

    {{-- ── Legend ──────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-5 mt-2 text-xs text-gray-500">
        <span class="font-semibold">{{ __('Connection:') }}</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span>{{ __('Online') }}</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-amber-400 inline-block"></span>{{ __('Warning') }}</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span>{{ __('Offline') }}</span>
        <span class="ml-4 font-semibold">{{ __('Operational:') }}</span>
        <span><x-filament::badge color="success" size="sm">{{ __('Available') }}</x-filament::badge></span>
        <span><x-filament::badge color="info" size="sm">{{ __('In Use') }}</x-filament::badge></span>
        <span><x-filament::badge color="danger" size="sm">{{ __('Fault') }}</x-filament::badge></span>
        <span><x-filament::badge color="gray" size="sm">{{ __('Offline') }}</x-filament::badge></span>
    </div>

    {{-- ── Alpine.js Floor Plan Controller ────────────────────────── --}}
    @script
    <script>
    Alpine.data('floorPlanCanvas', ({ canEdit, lockers }) => ({
        dragging: false,
        dragLockerLocationId: null,
        dragX: 0,
        dragY: 0,
        _startX: 0,
        _startY: 0,
        _containerRect: null,

        startDrag(lockerLocationId, event, el) {
            if (!canEdit) return;

            this.dragging = true;
            this.dragLockerLocationId = lockerLocationId;
            this._containerRect = el.closest('[x-data]').getBoundingClientRect();
            this._startX = event.clientX;
            this._startY = event.clientY;

            const onMove = (e) => {
                const rect = this._containerRect;
                this.dragX = Math.min(100, Math.max(0, ((e.clientX - rect.left) / rect.width) * 100));
                this.dragY = Math.min(100, Math.max(0, ((e.clientY - rect.top) / rect.height) * 100));
            };

            const onUp = (e) => {
                document.removeEventListener('mousemove', onMove);
                document.removeEventListener('mouseup', onUp);

                if (this.dragging && this.dragLockerLocationId !== null) {
                    const rect = this._containerRect;
                    const finalX = Math.min(100, Math.max(0, ((e.clientX - rect.left) / rect.width) * 100));
                    const finalY = Math.min(100, Math.max(0, ((e.clientY - rect.top) / rect.height) * 100));

                    @this.moveLocker(this.dragLockerLocationId, finalX, finalY);
                }

                this.dragging = false;
                this.dragLockerLocationId = null;
            };

            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        },

        handleCanvasClick(event, el) {
            if (!canEdit) return;
            if (this.dragging) return;

            // Only trigger if clicking the canvas background (not a marker)
            if (event.target !== el && !event.target.classList.contains('absolute') &&
                event.target.tagName !== 'IMG') {
                return;
            }

            const rect = el.getBoundingClientRect();
            const x = ((event.clientX - rect.left) / rect.width) * 100;
            const y = ((event.clientY - rect.top) / rect.height) * 100;

            @this.openPlaceModal(x.toFixed(2), y.toFixed(2));
        },
    }));
    </script>
    @endscript

</x-filament-panels::page>
