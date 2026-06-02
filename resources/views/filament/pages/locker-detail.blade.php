<x-filament-panels::page>
    @php
        $statusColors = [
            'available' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
            'in_use'    => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300',
            'fault'     => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
            'offline'   => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
            'disabled'  => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        ];
        $statusGradient = [
            'available' => 'from-emerald-600 to-green-700',
            'in_use'    => 'from-blue-600 to-indigo-700',
            'fault'     => 'from-red-600 to-rose-700',
            'offline'   => 'from-slate-600 to-gray-700',
            'disabled'  => 'from-amber-500 to-yellow-600',
        ];
        $statusLabels = \App\Models\Locker::statusOptions();
        $s    = $this->record->status;
        $conn = $this->record->connection_status ?? 'offline';
        $connColors = [
            'online'  => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
            'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            'offline' => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
        ];
    @endphp

    {{-- ══════════════════════════════════════════════════════════════
         HEADER BANNER
    ══════════════════════════════════════════════════════════════ --}}
    <div class="rounded-2xl bg-gradient-to-r {{ $statusGradient[$s] ?? 'from-slate-600 to-gray-700' }} p-6 mb-6 shadow-lg text-white">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-4">
                {{-- Back --}}
                <a href="{{ url()->previous() }}"
                   class="mt-0.5 inline-flex items-center gap-1 text-sm text-white/70 hover:text-white transition-colors">
                    <x-heroicon-o-arrow-left class="w-4 h-4"/>
                    {{ __('Back') }}
                </a>

                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-2xl font-bold text-white">{{ $this->record->name }}</h1>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-white/20 text-white border border-white/30">
                            <span class="w-1.5 h-1.5 rounded-full bg-current opacity-90"></span>
                            {{ $statusLabels[$s] ?? ucfirst($s) }}
                        </span>
                        @unless($this->record->is_active)
                            <span class="px-2 py-0.5 bg-white/20 text-white text-xs rounded-full font-medium border border-white/30">{{ __('Inactive') }}</span>
                        @endunless
                    </div>
                    <p class="text-sm text-white/70 mt-1">
                        @if($this->record->code)
                            <span class="font-mono">{{ $this->record->code }}</span> ·
                        @endif
                        {{ $this->record->company->name ?? '—' }}
                        @if($this->record->location)
                            · {{ $this->record->location->name }}
                        @endif
                    </p>
                </div>
            </div>

            {{-- Connection badge --}}
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-medium bg-white/15 text-white border border-white/25">
                @if($conn === 'online')
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                    </span>
                @else
                    <span class="w-2 h-2 rounded-full bg-current opacity-70"></span>
                @endif
                {{ ucfirst($conn) }}
                @if($this->record->last_seen_at)
                    <span class="opacity-70">· {{ $this->record->last_seen_at->diffForHumans() }}</span>
                @endif
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ══════════════════════════════════════════════════════════
             LEFT COLUMN (2/3) — Editable sections
        ══════════════════════════════════════════════════════════ --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- ── Basic Info ──────────────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-information-circle class="w-5 h-5 text-gray-400"/>
                        {{ __('Basic Information') }}
                    </h2>
                    @can('edit lockers')
                    <div x-data="{ editing: @entangle('editingBasic') }">
                        <button x-show="!editing" @click="editing = true"
                            class="inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium">
                            <x-heroicon-o-pencil-square class="w-4 h-4"/>
                            {{ __('Edit') }}
                        </button>
                    </div>
                    @endcan
                </div>

                <div x-data="{ editing: @entangle('editingBasic') }" class="p-6">

                    {{-- ─── View Mode ─────────────────────────────── --}}
                    <div x-show="!editing" class="grid grid-cols-2 gap-x-8 gap-y-4 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('Name') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $this->record->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('Code') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-white font-mono">{{ $this->record->code ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('Zone') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $this->record->zone ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('Floor') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $this->record->floor ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('Location') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $this->record->location->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('Tenant') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $this->record->tenant->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('Status') }}</dt>
                            <dd>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold {{ $statusColors[$this->record->status] ?? '' }}">
                                    {{ $statusLabels[$this->record->status] ?? ucfirst($this->record->status) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('Active') }}</dt>
                            <dd>
                                @if($this->record->is_active)
                                    <span class="text-green-600 dark:text-green-400 font-medium">{{ __('Yes') }}</span>
                                @else
                                    <span class="text-red-500 dark:text-red-400 font-medium">{{ __('No') }}</span>
                                @endif
                            </dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('Description') }}</dt>
                            <dd class="text-gray-700 dark:text-gray-300">{{ $this->record->description ?: '—' }}</dd>
                        </div>
                    </div>

                    {{-- ─── Edit Mode ──────────────────────────────── --}}
                    <div x-show="editing" x-cloak>
                        <div class="grid grid-cols-2 gap-4">
                            {{-- Name --}}
                            <div class="col-span-2 sm:col-span-1">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Name') }} <span class="text-red-500">*</span></label>
                                <input wire:model="editName" type="text" maxlength="255"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                                @error('editName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>

                            {{-- Code --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Code') }}</label>
                                <input wire:model="editCode" type="text" maxlength="50"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                                    placeholder="e.g. LKR-001">
                                @error('editCode') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>

                            {{-- Zone --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Zone') }}</label>
                                <input wire:model="editZone" type="text" maxlength="100"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                                    placeholder="e.g. Zone A">
                                @error('editZone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>

                            {{-- Floor --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Floor') }}</label>
                                <input wire:model="editFloor" type="text" maxlength="50"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                                    placeholder="e.g. G, 1, 2">
                                @error('editFloor') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>

                            {{-- Location --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Location') }}</label>
                                <select wire:model="editLocationId"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                                    <option value="">{{ __('— None —') }}</option>
                                    @foreach($this->getLocations() as $loc)
                                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                                @error('editLocationId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>

                            {{-- Tenant --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Tenant') }}</label>
                                <select wire:model="editTenantId"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                                    <option value="">{{ __('— Unassigned —') }}</option>
                                    @foreach($this->getTenants() as $company)
                                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                                    @endforeach
                                </select>
                                @error('editTenantId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>

                            {{-- Status --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Status') }} <span class="text-red-500">*</span></label>
                                <select wire:model="editStatus"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                                    @foreach(\App\Models\Locker::statusOptions() as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('editStatus') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>

                            {{-- Is Active toggle --}}
                            <div class="flex items-center gap-3 mt-1">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" wire:model="editIsActive" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-primary-500 rounded-full peer dark:bg-gray-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Active') }}</span>
                                </label>
                            </div>

                            {{-- Description --}}
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Description') }}</label>
                                <textarea wire:model="editDescription" rows="3" maxlength="2000"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"
                                    placeholder="Optional description..."></textarea>
                                @error('editDescription') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>

                            {{-- Audit note --}}
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Reason / Note') }} <span class="text-gray-400">{{ __('(optional, saved to history)') }}</span></label>
                                <input wire:model="editNoteBasic" type="text" maxlength="500"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                                    placeholder="e.g. Updated zone after relocation">
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex justify-end gap-3 mt-5 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="editing = false; $wire.editingBasic = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">
                                {{ __('Cancel') }}
                            </button>
                            <button wire:click="saveBasic" wire:loading.attr="disabled"
                                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg disabled:opacity-60 flex items-center gap-2">
                                <x-heroicon-o-check class="w-4 h-4"/>
                                <span wire:loading.remove wire:target="saveBasic">{{ __('Save Changes') }}</span>
                                <span wire:loading wire:target="saveBasic">{{ __('Saving…') }}</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ── Technical Info ──────────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-cpu-chip class="w-5 h-5 text-gray-400"/>
                        {{ __('Technical Details') }}
                    </h2>
                    @can('edit lockers')
                    <div x-data="{ editing: @entangle('editingTechnical') }">
                        <button x-show="!editing" @click="editing = true"
                            class="inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium">
                            <x-heroicon-o-pencil-square class="w-4 h-4"/>
                            {{ __('Edit') }}
                        </button>
                    </div>
                    @endcan
                </div>

                <div x-data="{ editing: @entangle('editingTechnical') }" class="p-6">

                    {{-- View Mode --}}
                    <div x-show="!editing" class="grid grid-cols-2 gap-x-8 gap-y-4 text-sm">
                        @if(auth()->user()->isSuperAdmin())
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('Serial Number') }}</dt>
                            <dd class="font-mono font-medium text-gray-900 dark:text-white">{{ $this->record->serial_number ?: '—' }}</dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('IP Address') }}</dt>
                            <dd class="font-mono font-medium text-gray-900 dark:text-white">{{ $this->record->ip_address ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('Firmware Version') }}</dt>
                            <dd class="font-mono font-medium text-gray-900 dark:text-white">{{ $this->record->firmware_version ?: '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('Heartbeat Interval') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">
                                {{ $this->record->heartbeat_interval ? $this->record->heartbeat_interval . 's' : '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('Mark Offline After') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">
                                {{ $this->record->offline_after ? $this->record->offline_after . 's' : '—' }}
                            </dd>
                        </div>
                        @if($this->record->external_locker_id || $this->record->external_unit_id)
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('External Locker ID') }}</dt>
                            <dd class="font-mono font-medium text-gray-900 dark:text-white">{{ $this->record->external_locker_id ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('External Unit ID') }}</dt>
                            <dd class="font-mono font-medium text-gray-900 dark:text-white">{{ $this->record->external_unit_id ?? '—' }}</dd>
                        </div>
                        @endif
                    </div>

                    {{-- Edit Mode --}}
                    <div x-show="editing" x-cloak>
                        @if(! auth()->user()->isSuperAdmin())
                        <div class="mb-4 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-sm text-amber-700 dark:text-amber-300 flex items-center gap-2">
                            <x-heroicon-o-lock-closed class="w-4 h-4 shrink-0"/>
                            {{ __('Serial number and firmware version can only be edited by Super Admin.') }}
                        </div>
                        @endif

                        <div class="grid grid-cols-2 gap-4">
                            {{-- Serial Number (super_admin only) --}}
                            @if(auth()->user()->isSuperAdmin())
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                    {{ __('Serial Number') }}
                                    <span class="ml-1 px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded text-[10px]">{{ __('Super Admin') }}</span>
                                </label>
                                <input wire:model="editSerialNumber" type="text" maxlength="100"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-primary-500">
                                @error('editSerialNumber') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            @endif

                            {{-- IP Address --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('IP Address') }}</label>
                                <input wire:model="editIpAddress" type="text"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-primary-500"
                                    placeholder="192.168.1.100">
                                @error('editIpAddress') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>

                            {{-- Firmware Version (super_admin only) --}}
                            @if(auth()->user()->isSuperAdmin())
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                    {{ __('Firmware Version') }}
                                    <span class="ml-1 px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded text-[10px]">{{ __('Super Admin') }}</span>
                                </label>
                                <input wire:model="editFirmwareVersion" type="text" maxlength="50"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-primary-500"
                                    placeholder="v1.2.3">
                                @error('editFirmwareVersion') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>

                            {{-- Heartbeat Interval --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                    {{ __('Heartbeat Interval (s)') }}
                                    <span class="ml-1 px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded text-[10px]">{{ __('Super Admin') }}</span>
                                </label>
                                <input wire:model="editHeartbeatInterval" type="number" min="10" max="3600"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                                    placeholder="30">
                                @error('editHeartbeatInterval') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>

                            {{-- Offline After --}}
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                    {{ __('Mark Offline After (s)') }}
                                    <span class="ml-1 px-1.5 py-0.5 bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded text-[10px]">{{ __('Super Admin') }}</span>
                                </label>
                                <input wire:model="editOfflineAfter" type="number" min="30" max="86400"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                                    placeholder="300">
                                @error('editOfflineAfter') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                            </div>
                            @endif

                            {{-- Audit note --}}
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Reason / Note') }} <span class="text-gray-400">{{ __('(optional)') }}</span></label>
                                <input wire:model="editNoteTechnical" type="text" maxlength="500"
                                    class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                                    placeholder="e.g. Firmware upgrade to v2.0">
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 mt-5 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="editing = false; $wire.editingTechnical = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">
                                {{ __('Cancel') }}
                            </button>
                            <button wire:click="saveTechnical" wire:loading.attr="disabled"
                                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg disabled:opacity-60 flex items-center gap-2">
                                <x-heroicon-o-check class="w-4 h-4"/>
                                <span wire:loading.remove wire:target="saveTechnical">{{ __('Save Changes') }}</span>
                                <span wire:loading wire:target="saveTechnical">{{ __('Saving…') }}</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            {{-- ── Edit History ────────────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-clock class="w-5 h-5 text-gray-400"/>
                        {{ __('Edit History') }}
                        <span class="ml-1 px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 text-xs rounded-full">
                            {{ $this->record->editLogs->count() }}
                        </span>
                    </h2>
                </div>

                @if($this->record->editLogs->isEmpty())
                    <div class="px-6 py-10 text-center text-gray-400 dark:text-gray-500 text-sm">
                        <x-heroicon-o-document-text class="w-8 h-8 mx-auto mb-2 opacity-40"/>
                        {{ __('No edit history yet') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-700/50 text-left">
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Field') }}</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Before') }}</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('After') }}</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Changed By') }}</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('When') }}</th>
                                    <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Note') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($this->record->editLogs as $log)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1.5 font-medium text-gray-800 dark:text-gray-200">
                                            <x-dynamic-component :component="$log->fieldIcon()" class="w-3.5 h-3.5 text-gray-400 shrink-0"/>
                                            {{ $log->fieldLabel() }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 max-w-[160px]">
                                        <span class="text-red-600 dark:text-red-400 font-mono text-xs break-all line-through opacity-80">
                                            {{ $log->old_value !== null ? Str::limit($log->old_value, 40) : '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 max-w-[160px]">
                                        <span class="text-green-600 dark:text-green-400 font-mono text-xs break-all font-medium">
                                            {{ $log->new_value !== null ? Str::limit($log->new_value, 40) : '—' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                                        {{ $log->changedBy->name ?? __('System') }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap" title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
                                        {{ $log->created_at->diffForHumans() }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400 max-w-[160px]">
                                        {{ $log->note ? Str::limit($log->note, 50) : '—' }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </div>{{-- /left column --}}

        {{-- ══════════════════════════════════════════════════════════
             RIGHT COLUMN (1/3) — Stats sidebar
        ══════════════════════════════════════════════════════════ --}}
        <div class="space-y-6">

            {{-- Locker Stats --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 p-5">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">{{ __('Locker Info') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Company (Owner)') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-white text-right">{{ $this->record->company->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Tenant') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-white text-right">{{ $this->record->tenant->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Location') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-white text-right">{{ $this->record->location->name ?? '—' }}</dd>
                    </div>
                    @if($this->record->zone || $this->record->floor)
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Zone / Floor') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-white text-right">
                            {{ implode(' · ', array_filter([$this->record->zone, $this->record->floor])) }}
                        </dd>
                    </div>
                    @endif
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-3">
                        <div class="flex justify-between mb-2">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Last Seen') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-white text-right">
                                {{ $this->record->last_seen_at ? $this->record->last_seen_at->diffForHumans() : __('Never') }}
                            </dd>
                        </div>
                        <div class="flex justify-between mb-2">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Added') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-white text-right">
                                {{ $this->record->created_at->format('d M Y') }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Last Updated') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-white text-right">
                                {{ $this->record->updated_at->diffForHumans() }}
                            </dd>
                        </div>
                    </div>
                </dl>
            </div>

            {{-- Related Issues --}}
            @php
                $openIssues = \App\Models\Issue::where('locker_id', $this->record->id)
                    ->whereIn('status', ['open','in_progress','pending'])
                    ->latest()->limit(5)->get();
            @endphp
            @if($openIssues->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-red-200 dark:ring-red-800/50 p-5">
                <h3 class="text-sm font-semibold text-red-700 dark:text-red-400 mb-3 flex items-center gap-2">
                    <x-heroicon-o-exclamation-triangle class="w-4 h-4"/>
                    {{ __('Open Issues') }} ({{ $openIssues->count() }})
                </h3>
                <div class="space-y-2">
                    @foreach($openIssues as $issue)
                    <a href="{{ route('filament.admin.pages.issues.{record}', ['record' => $issue->id]) }}"
                       class="block text-sm text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 truncate">
                        #{{ $issue->id }} {{ $issue->title }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Related Maintenance --}}
            @php
                $activeMaintenance = \App\Models\CorrectiveMaintenance::where('locker_id', $this->record->id)
                    ->whereIn('status', ['created','in_progress'])
                    ->latest()->limit(3)->get();
            @endphp
            @if($activeMaintenance->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-amber-200 dark:ring-amber-800/50 p-5">
                <h3 class="text-sm font-semibold text-amber-700 dark:text-amber-400 mb-3 flex items-center gap-2">
                    <x-heroicon-o-wrench class="w-4 h-4"/>
                    {{ __('Active Maintenance') }} ({{ $activeMaintenance->count() }})
                </h3>
                <div class="space-y-2">
                    @foreach($activeMaintenance as $m)
                    <a href="{{ route('filament.admin.pages.maintenance.{record}', ['record' => $m->id]) }}"
                       class="block text-sm text-gray-700 dark:text-gray-300 hover:text-primary-600 dark:hover:text-primary-400 truncate">
                        CM #{{ $m->id }} {{ $m->title }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

        </div>{{-- /right column --}}
    </div>
</x-filament-panels::page>
