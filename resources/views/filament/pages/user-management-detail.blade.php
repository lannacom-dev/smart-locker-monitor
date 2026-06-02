<x-filament-panels::page>
    @php
        $roleBadge = \App\Models\User::roleBadgeClasses();
        $record    = $this->record;
    @endphp

    {{-- ── Gradient Header Banner ──────────────────────────────────── --}}
    <div class="rounded-2xl bg-gradient-to-r from-blue-700 to-indigo-800 p-6 mb-6 shadow-lg text-white">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-4">
                <a href="{{ route('filament.admin.pages.user-management') }}"
                   class="mt-1 inline-flex items-center gap-1 text-sm text-white/70 hover:text-white transition-colors">
                    <x-heroicon-o-arrow-left class="w-4 h-4"/> {{ __('Back') }}
                </a>
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-white/20 border-2 border-white/30 flex items-center justify-center text-white font-bold text-xl shrink-0">
                        {{ strtoupper(substr($record->name, 0, 2)) }}
                    </div>
                    <div>
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <h1 class="text-2xl font-bold text-white">{{ $record->name }}</h1>
                            @if($record->is_active)
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-400/30 text-white border border-green-300/40">{{ __('Active') }}</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-400/30 text-white border border-red-300/40">{{ __('Disabled') }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-sm text-white/70">{{ $record->email }}</span>
                            @foreach($record->roles as $role)
                                <span class="px-2 py-0.5 rounded text-xs font-semibold bg-white/20 text-white border border-white/25">
                                    {{ ucfirst(str_replace('_',' ',$role->name)) }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Temp password notice ────────────────────────────────────── --}}
    @if($this->tempPassword)
    <div class="mb-4 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 rounded-xl flex items-start gap-3">
        <x-heroicon-o-key class="w-5 h-5 text-amber-600 shrink-0 mt-0.5"/>
        <div class="flex-1 text-sm">
            <p class="font-semibold text-amber-800 dark:text-amber-300">{{ __('Temporary password (shown once):') }}</p>
            <code class="mt-1 block font-mono text-base font-bold bg-amber-100 dark:bg-amber-900/40 px-3 py-1 rounded text-amber-900 dark:text-amber-200">{{ $this->tempPassword }}</code>
        </div>
        <button wire:click="$set('tempPassword', null)" class="text-amber-500 hover:text-amber-700">
            <x-heroicon-o-x-mark class="w-4 h-4"/>
        </button>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- ══════════════════════════════════════════════════════════
             LEFT COLUMN
        ══════════════════════════════════════════════════════════ --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- ── Profile ─────────────────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-user class="w-5 h-5 text-gray-400"/> {{ __('Profile') }}
                    </h2>
                    @can('edit users')
                    <div x-data="{ editing: @entangle('editingProfile') }">
                        <button x-show="!editing" @click="editing = true"
                            class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 font-medium flex items-center gap-1">
                            <x-heroicon-o-pencil-square class="w-4 h-4"/> {{ __('Edit') }}
                        </button>
                    </div>
                    @endcan
                </div>
                <div x-data="{ editing: @entangle('editingProfile') }" class="p-6">
                    <div x-show="!editing" class="grid grid-cols-2 gap-x-8 gap-y-4 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('Full Name') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $record->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('Email') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $record->email }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('Company') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $record->company->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400 mb-0.5">{{ __('Member Since') }}</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $record->created_at->format('d M Y') }}</dd>
                        </div>
                    </div>
                    <div x-show="editing" x-cloak>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Name') }} *</label>
                                <input wire:model="editName" type="text" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                                @error('editName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Email') }} *</label>
                                <input wire:model="editEmail" type="email" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                                @error('editEmail') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Company') }}</label>
                                <select wire:model="editCompanyId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                                    <option value="">{{ __('— None —') }}</option>
                                    @foreach($this->getCompanies() as $company)
                                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3 mt-5 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button @click="editing = false; $wire.editingProfile = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">
                                {{ __('Cancel') }}
                            </button>
                            <button wire:click="saveProfile" wire:loading.attr="disabled"
                                class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg flex items-center gap-2 disabled:opacity-60">
                                <x-heroicon-o-check class="w-4 h-4"/>
                                <span wire:loading.remove wire:target="saveProfile">{{ __('Save') }}</span>
                                <span wire:loading wire:target="saveProfile">{{ __('Saving…') }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Role Assignment ──────────────────────────────────── --}}
            @can('edit users')
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-shield-check class="w-5 h-5 text-gray-400"/> {{ __('Role Assignment') }}
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ __('You can only assign roles below your own privilege level.') }}
                    </p>
                </div>
                <div class="p-6">
                    <div class="flex flex-wrap gap-3 mb-4">
                        @foreach($this->getAssignableRoles() as $role)
                        <label class="flex items-center gap-2 px-3 py-2 rounded-lg border cursor-pointer transition-colors
                            {{ in_array($role, $this->selectedRoles) ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' : 'border-gray-200 dark:border-gray-600 hover:border-gray-300' }}">
                            <input type="checkbox"
                                wire:model="selectedRoles"
                                value="{{ $role }}"
                                class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ ucfirst(str_replace('_', ' ', $role)) }}
                            </span>
                        </label>
                        @endforeach
                    </div>
                    <div class="flex items-center gap-3">
                        <input wire:model="roleNote" type="text" maxlength="500"
                            class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                            placeholder="{{ __('Reason for change (optional, saved to audit log)') }}">
                        <button wire:click="saveRoles" wire:loading.attr="disabled"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg flex items-center gap-2 disabled:opacity-60 whitespace-nowrap">
                            <x-heroicon-o-shield-check class="w-4 h-4"/>
                            <span wire:loading.remove wire:target="saveRoles">{{ __('Save Roles') }}</span>
                            <span wire:loading wire:target="saveRoles">{{ __('Saving…') }}</span>
                        </button>
                    </div>
                </div>
            </div>
            @endcan

            {{-- ── Effective Permissions ────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-key class="w-5 h-5 text-gray-400"/> {{ __('Effective Permissions') }}
                    </h2>
                </div>
                <div class="p-6">
                    @php $perms = $this->getUserPermissions(); @endphp
                    @if(empty($perms))
                        <p class="text-sm text-gray-400">{{ __('No permissions assigned.') }}</p>
                    @else
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($perms as $perm)
                            <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-mono">
                                {{ $perm }}
                            </span>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            {{-- ── Audit Log ────────────────────────────────────────── --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <x-heroicon-o-clock class="w-5 h-5 text-gray-400"/> {{ __('Permission Audit Log') }}
                    </h2>
                </div>
                @php $logs = $this->getAuditLogs(); @endphp
                @if($logs->isEmpty())
                    <div class="py-10 text-center text-sm text-gray-400">{{ __('No changes recorded.') }}</div>
                @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/50 text-left">
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Action') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Before') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('After') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('By') }}</th>
                                <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('When') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($logs as $log)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5 font-medium {{ $log->actionColor() }}">
                                        <x-dynamic-component :component="$log->actionIcon()" class="w-4 h-4 shrink-0"/>
                                        {{ $log->actionLabel() }}
                                    </div>
                                    @if($log->note)
                                        <p class="text-xs text-gray-400 mt-0.5 italic">{{ $log->note }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-red-500 dark:text-red-400 font-mono max-w-[160px] break-all line-through opacity-75">
                                    {{ $log->old_value ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-xs text-green-600 dark:text-green-400 font-mono max-w-[160px] break-all font-medium">
                                    {{ $log->new_value ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $log->causer->name ?? __('System') }}</td>
                                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap" title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
                                    {{ $log->created_at->diffForHumans() }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

        </div>{{-- /left --}}

        {{-- ══════════════════════════════════════════════════════════
             RIGHT SIDEBAR
        ══════════════════════════════════════════════════════════ --}}
        <div class="space-y-6">

            {{-- Account card --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 p-5">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">{{ __('Account Info') }}</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Status') }}</dt>
                        <dd class="{{ $record->is_active ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }} font-semibold">
                            {{ $record->is_active ? __('Active') : __('Disabled') }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Company') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-white text-right">{{ $record->company->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Created') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $record->created_at->format('d M Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">{{ __('Updated') }}</dt>
                        <dd class="font-medium text-gray-900 dark:text-white">{{ $record->updated_at->diffForHumans() }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Reset password --}}
            @can('edit users')
            @if($record->id !== auth()->id())
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 p-5">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">{{ __('Password') }}</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">{{ __('Generate a new temporary password and invalidate all existing sessions.') }}</p>
                <button wire:click="resetPassword" wire:loading.attr="disabled"
                    wire:confirm="{{ __('Generate a new random password for') }} {{ $record->name }}?"
                    class="w-full px-4 py-2 text-sm font-medium text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg hover:bg-amber-100 dark:hover:bg-amber-900/40 flex items-center justify-center gap-2 disabled:opacity-60">
                    <x-heroicon-o-key class="w-4 h-4"/>
                    <span wire:loading.remove wire:target="resetPassword">{{ __('Reset Password') }}</span>
                    <span wire:loading wire:target="resetPassword">{{ __('Resetting…') }}</span>
                </button>
            </div>

            {{-- Danger zone --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-red-200 dark:ring-red-800/50 p-5">
                <h3 class="text-sm font-semibold text-red-700 dark:text-red-400 mb-3">{{ __('Danger Zone') }}</h3>
                @if($record->is_active)
                <button wire:click="disableUser"
                    wire:confirm="{{ __('Disable') }} {{ $record->name }}? {{ __('They will lose access immediately.') }}"
                    class="w-full px-4 py-2 text-sm font-medium text-red-700 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 flex items-center justify-center gap-2">
                    <x-heroicon-o-no-symbol class="w-4 h-4"/> {{ __('Disable Account') }}
                </button>
                @else
                <button wire:click="enableUser"
                    class="w-full px-4 py-2 text-sm font-medium text-green-700 dark:text-green-400 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/40 flex items-center justify-center gap-2">
                    <x-heroicon-o-check-circle class="w-4 h-4"/> {{ __('Enable Account') }}
                </button>
                @endif
            </div>
            @endif
            @endcan

        </div>{{-- /sidebar --}}
    </div>
</x-filament-panels::page>
