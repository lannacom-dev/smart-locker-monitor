<x-filament-panels::page>
    @php
        $record       = $this->record;
        $badgeClasses = $this->getBadgeClasses();
        $userTypes    = $this->getUserTypes();
        $canEdit      = auth()->user()->can('edit locker users');
        $canDisable   = auth()->user()->can('disable locker users');
    @endphp

    {{-- ── Gradient Header Banner ──────────────────────────────────── --}}
    <div class="rounded-2xl bg-gradient-to-r from-violet-600 to-purple-700 p-6 mb-6 shadow-lg text-white">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('filament.admin.pages.locker-users') }}"
                   class="inline-flex items-center gap-1 text-sm text-white/70 hover:text-white transition-colors self-start mt-1">
                    <x-heroicon-o-arrow-left class="w-4 h-4"/> {{ __('Back to list') }}
                </a>
                <div class="w-14 h-14 rounded-full bg-white/20 border-2 border-white/30 flex items-center justify-center shrink-0">
                    <span class="text-xl font-bold text-white">{{ strtoupper(substr($record->full_name, 0, 1)) }}</span>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">{{ $record->full_name }}</h1>
                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                        @if($record->userType)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-white/20 text-white border border-white/30">
                            {{ $record->userType->name }}
                        </span>
                        @endif
                        @if($record->is_active)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-400/30 text-white border border-green-300/40">
                            <span class="w-1.5 h-1.5 rounded-full bg-white"></span> {{ __('Active') }}
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-400/30 text-white border border-red-300/40">
                            <span class="w-1.5 h-1.5 rounded-full bg-white"></span> {{ __('Disabled') }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-6">
        {{-- ── Left: Profile + Audit ─────────────────────────────── --}}
        <div class="col-span-2 space-y-6">

            {{-- Profile card --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-user class="w-5 h-5 text-gray-400"/> {{ __('Profile') }}
                    </h2>
                    @if($canEdit && ! $this->editingProfile)
                    <button wire:click="$set('editingProfile', true)"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">
                        <x-heroicon-o-pencil-square class="w-3.5 h-3.5"/> {{ __('Edit') }}
                    </button>
                    @endif
                </div>

                @if($this->editingProfile)
                {{-- Edit mode --}}
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Full Name') }} <span class="text-red-500">*</span></label>
                            <input wire:model="editFullName" type="text"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                            @error('editFullName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('User Type') }} <span class="text-red-500">*</span></label>
                            <select wire:model="editUserTypeId"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                                @foreach($userTypes as $ut)
                                <option value="{{ $ut->id }}">{{ $ut->name }}</option>
                                @endforeach
                            </select>
                            @error('editUserTypeId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Email') }}</label>
                            <input wire:model="editEmail" type="email"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                            @error('editEmail') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Phone') }}</label>
                            <input wire:model="editPhone" type="text"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Employee ID') }}</label>
                            <input wire:model="editEmployeeId" type="text" placeholder="EMP-0001"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                            @error('editEmployeeId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Organization') }}</label>
                            <input wire:model="editOrganization" type="text"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                            @error('editOrganization') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Access Start Date') }}</label>
                            <input wire:model="editAccessStart" type="date"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                            @error('editAccessStart') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Access End Date') }}</label>
                            <input wire:model="editAccessEnd" type="date"
                                class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                            @error('editAccessEnd') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
                        <button wire:click="$set('editingProfile', false)"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">
                            {{ __('Cancel') }}
                        </button>
                        <button wire:click="saveProfile" wire:loading.attr="disabled" wire:target="saveProfile"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg disabled:opacity-60 flex items-center gap-2">
                            <x-heroicon-o-check class="w-4 h-4"/>
                            <span wire:loading.remove wire:target="saveProfile">{{ __('Save Profile') }}</span>
                            <span wire:loading wire:target="saveProfile">{{ __('Saving…') }}</span>
                        </button>
                    </div>
                </div>
                @else
                {{-- View mode --}}
                <div class="p-6">
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-4">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Full Name') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-medium">{{ $record->full_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('User Type') }}</dt>
                            <dd class="mt-1">
                                @if($record->userType)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeClasses[$record->userType->slug] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ $record->userType->name }}
                                </span>
                                @else
                                <span class="text-sm text-gray-400">—</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Email') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->email ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Phone') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->phone ?? '—' }}</dd>
                        </div>
                        @if($record->employee_id)
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Employee ID') }}</dt>
                            <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-white">{{ $record->employee_id }}</dd>
                        </div>
                        @endif
                        @if($record->organization)
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Organization') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->organization }}</dd>
                        </div>
                        @endif
                        <div class="col-span-2">
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Access Window') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $record->accessWindowLabel() }}</dd>
                        </div>
                    </dl>
                </div>
                @endif
            </div>

            {{-- Audit Log --}}
            @php $logs = $this->getAuditLogs(); @endphp
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-clock class="w-5 h-5 text-gray-400"/> {{ __('Activity Log') }}
                    </h2>
                </div>
                @if($logs->isEmpty())
                <div class="py-10 text-center text-sm text-gray-400">{{ __('No activity recorded yet.') }}</div>
                @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/50 text-left">
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Action') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Before') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('After') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('By') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('When') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($logs as $log)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-4 py-3">
                                    <span class="text-xs font-medium {{ $log->actionColor() }}">{{ $log->actionLabel() }}</span>
                                </td>
                                <td class="px-4 py-3 max-w-[180px]">
                                    <p class="text-xs text-red-500 dark:text-red-400 line-through opacity-75 break-all">{{ Str::limit($log->old_value, 60) }}</p>
                                </td>
                                <td class="px-4 py-3 max-w-[180px]">
                                    <p class="text-xs text-green-600 dark:text-green-400 font-medium break-all">{{ Str::limit($log->new_value, 60) }}</p>
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-300">{{ $log->causer?->name ?? __('System') }}</td>
                                <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap" title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
                                    {{ $log->created_at->diffForHumans() }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </div>

        {{-- ── Right: Info + Danger Zone ─────────────────────────── --}}
        <div class="space-y-4">

            {{-- Account info card --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white text-sm">{{ __('Account Info') }}</h3>
                </div>
                <div class="px-5 py-4 space-y-3">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">{{ __('Company') }}</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $record->company?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">{{ __('Status') }}</p>
                        @if($record->is_active)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span> {{ __('Active') }}
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-current"></span> {{ __('Disabled') }}
                        </span>
                        @endif
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">{{ __('Access Window') }}</p>
                        <p class="text-sm text-gray-900 dark:text-white">{{ $record->accessWindowLabel() }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">{{ __('Created') }}</p>
                        <p class="text-sm text-gray-900 dark:text-white" title="{{ $record->created_at->format('Y-m-d H:i:s') }}">
                            {{ $record->created_at->diffForHumans() }}
                            @if($record->creator)
                            <span class="text-xs text-gray-400">{{ __('by') }} {{ $record->creator->name }}</span>
                            @endif
                        </p>
                    </div>
                    @if($record->updated_at != $record->created_at)
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-0.5">{{ __('Last Updated') }}</p>
                        <p class="text-sm text-gray-900 dark:text-white" title="{{ $record->updated_at->format('Y-m-d H:i:s') }}">
                            {{ $record->updated_at->diffForHumans() }}
                            @if($record->updater)
                            <span class="text-xs text-gray-400">{{ __('by') }} {{ $record->updater->name }}</span>
                            @endif
                        </p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Danger zone --}}
            @if($canDisable)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-red-200 dark:ring-red-800/50">
                <div class="px-5 py-4 border-b border-red-100 dark:border-red-800/50">
                    <h3 class="font-semibold text-red-700 dark:text-red-400 text-sm flex items-center gap-2">
                        <x-heroicon-o-exclamation-triangle class="w-4 h-4"/> {{ __('Danger Zone') }}
                    </h3>
                </div>
                <div class="px-5 py-4">
                    @if($record->is_active)
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                        {{ __('Disabling this user will revoke their locker access immediately.') }}
                    </p>
                    <button wire:click="disableUser"
                        wire:confirm="{{ __('Are you sure you want to disable') }} {{ $record->full_name }}? {{ __('They will lose locker access immediately.') }}"
                        wire:loading.attr="disabled" wire:target="disableUser"
                        class="w-full px-4 py-2.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg disabled:opacity-60 flex items-center justify-center gap-2">
                        <x-heroicon-o-no-symbol class="w-4 h-4"/>
                        <span wire:loading.remove wire:target="disableUser">{{ __('Disable User') }}</span>
                        <span wire:loading wire:target="disableUser">{{ __('Disabling…') }}</span>
                    </button>
                    @else
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                        {{ __('This user is currently disabled. Re-enable to restore locker access.') }}
                    </p>
                    <button wire:click="enableUser"
                        wire:loading.attr="disabled" wire:target="enableUser"
                        class="w-full px-4 py-2.5 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg disabled:opacity-60 flex items-center justify-center gap-2">
                        <x-heroicon-o-check-circle class="w-4 h-4"/>
                        <span wire:loading.remove wire:target="enableUser">{{ __('Enable User') }}</span>
                        <span wire:loading wire:target="enableUser">{{ __('Enabling…') }}</span>
                    </button>
                    @endif
                </div>
            </div>
            @endif

        </div>
    </div>
</x-filament-panels::page>
