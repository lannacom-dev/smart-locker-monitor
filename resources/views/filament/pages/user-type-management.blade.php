<x-filament-panels::page>
    @php
        $badgeClasses = $this->getBadgeClasses();
        $canManage    = $this->canManage();
        $companies    = $this->getCompanies();
    @endphp

    {{-- ── Header ──────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('User Types') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                {{ __('Manage Smart Locker user type categories. System types cannot be deactivated.') }}
            </p>
        </div>
        @if($canManage)
        <button wire:click="$set('showCreateForm', true)"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors">
            <x-heroicon-o-plus class="w-4 h-4"/>
            {{ __('New User Type') }}
        </button>
        @endif
    </div>

    {{-- ── Create form ──────────────────────────────────────────────── --}}
    @if($this->showCreateForm)
    <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <x-heroicon-o-tag class="w-5 h-5 text-gray-400"/> {{ __('New User Type') }}
            </h2>
            <button wire:click="$set('showCreateForm', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <x-heroicon-o-x-mark class="w-5 h-5"/>
            </button>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Name') }} <span class="text-red-500">*</span></label>
                    <input wire:model="newName" type="text" placeholder="{{ __('e.g. Contractor') }}"
                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('newName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Slug') }} <span class="text-red-500">*</span></label>
                    <input wire:model="newSlug" type="text" placeholder="{{ __('e.g. contractor') }}"
                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('newSlug') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-gray-400">{{ __('Lowercase, underscores only. Must be globally unique.') }}</p>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Description') }}</label>
                    <textarea wire:model="newDescription" rows="2" placeholder="{{ __('Optional description…') }}"
                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"></textarea>
                    @error('newDescription') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                @if($companies->count() > 1)
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Scope (leave blank for system-wide)') }}</label>
                    <select wire:model="newCompanyId"
                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">{{ __('— System-wide —') }}</option>
                        @foreach($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="{{ $companies->count() > 1 ? '' : 'col-span-2' }}">
                    <label class="flex items-center gap-3 cursor-pointer mt-6">
                        <input wire:model="newIsActive" type="checkbox"
                            class="w-4 h-4 rounded border-gray-300 dark:border-gray-500 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Active') }}</span>
                    </label>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
                <button wire:click="$set('showCreateForm', false)"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">
                    {{ __('Cancel') }}
                </button>
                <button wire:click="createType" wire:loading.attr="disabled" wire:target="createType"
                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg disabled:opacity-60 flex items-center gap-2">
                    <span wire:loading.remove wire:target="createType">{{ __('Create Type') }}</span>
                    <span wire:loading wire:target="createType">{{ __('Creating…') }}</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Search ────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-end gap-3 px-4 py-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm mb-5">
        <div class="flex items-center gap-1.5 text-gray-400 dark:text-gray-500 shrink-0 mr-1">
            <x-heroicon-o-funnel class="w-4 h-4"/>
            <span class="text-xs font-medium uppercase tracking-wider">{{ __('Filter') }}</span>
        </div>
        <div class="relative flex-1 min-w-[180px] max-w-xs">
            <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"/>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('Search types…') }}"
                class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
        </div>
    </div>

    {{-- ── Table ─────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/60 border-b border-gray-200 dark:border-gray-700 text-left">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Type') }}</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Slug') }}</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Scope') }}</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Users') }}</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Status') }}</th>
                        @if($canManage)
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">{{ __('Actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($this->getUserTypes() as $type)

                    {{-- ── Edit row ──────────────────────────────────── --}}
                    @if($this->editingTypeId === $type->id)
                    <tr class="bg-blue-50/50 dark:bg-blue-900/10">
                        <td class="px-4 py-3" colspan="{{ $canManage ? 6 : 5 }}">
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Name') }}</label>
                                    <input wire:model="editName" type="text"
                                        class="w-full px-2.5 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                                    @error('editName') <p class="mt-0.5 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Slug') }}</label>
                                    <input wire:model="editSlug" type="text"
                                        @if($type->is_system) readonly class="w-full px-2.5 py-1.5 text-sm rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-400 cursor-not-allowed" @else class="w-full px-2.5 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500" @endif>
                                    @error('editSlug') <p class="mt-0.5 text-xs text-red-500">{{ $message }}</p> @enderror
                                </div>
                                <div class="flex items-end gap-2">
                                    <label class="flex items-center gap-2 mb-1.5 cursor-pointer">
                                        <input wire:model="editIsActive" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('Active') }}</span>
                                    </label>
                                </div>
                                <div class="col-span-3">
                                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Description') }}</label>
                                    <input wire:model="editDescription" type="text"
                                        class="w-full px-2.5 py-1.5 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                                </div>
                                <div class="col-span-3 flex justify-end gap-2 pt-1">
                                    <button wire:click="cancelEdit"
                                        class="px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">
                                        {{ __('Cancel') }}
                                    </button>
                                    <button wire:click="saveType" wire:loading.attr="disabled" wire:target="saveType"
                                        class="px-3 py-1.5 text-xs font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg disabled:opacity-60">
                                        <span wire:loading.remove wire:target="saveType">{{ __('Save') }}</span>
                                        <span wire:loading wire:target="saveType">{{ __('Saving…') }}</span>
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>

                    {{-- ── Display row ───────────────────────────────── --}}
                    @else
                    <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition-colors {{ $type->is_active ? 'border-l-4 border-l-green-400' : 'border-l-4 border-l-red-400' }}">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClasses[$type->slug] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $type->name }}
                                </span>
                                @if($type->is_system)
                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                    <x-heroicon-o-lock-closed class="w-2.5 h-2.5"/> {{ __('System') }}
                                </span>
                                @endif
                            </div>
                            @if($type->description)
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 max-w-xs truncate">{{ $type->description }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <code class="text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-1.5 py-0.5 rounded">{{ $type->slug }}</code>
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                            {{ $type->is_system ? __('System-wide') : ($type->company?->name ?? '—') }}
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ number_format($type->locker_users_count) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if($type->is_active)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                <span class="w-1.5 h-1.5 rounded-full bg-current"></span> {{ __('Active') }}
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-current"></span> {{ __('Disabled') }}
                            </span>
                            @endif
                        </td>
                        @if($canManage)
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button wire:click="selectForEdit({{ $type->id }})"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <x-heroicon-o-pencil-square class="w-3.5 h-3.5"/> {{ __('Edit') }}
                                </button>
                                @if($type->is_system)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs text-gray-400 dark:text-gray-500 cursor-not-allowed" title="{{ __('System types cannot be deactivated') }}">
                                    <x-heroicon-o-lock-closed class="w-3.5 h-3.5"/>
                                </span>
                                @else
                                <button wire:click="toggleActive({{ $type->id }})" wire:loading.attr="disabled"
                                    wire:confirm="{{ $type->is_active ? __('Disable this user type?') : __('Enable this user type?') }}"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg border {{ $type->is_active ? 'text-red-600 dark:text-red-400 border-red-200 dark:border-red-800 hover:bg-red-50 dark:hover:bg-red-900/20' : 'text-green-600 dark:text-green-400 border-green-200 dark:border-green-800 hover:bg-green-50 dark:hover:bg-green-900/20' }}">
                                    @if($type->is_active)
                                    <x-heroicon-o-no-symbol class="w-3.5 h-3.5"/> {{ __('Disable') }}
                                    @else
                                    <x-heroicon-o-check-circle class="w-3.5 h-3.5"/> {{ __('Enable') }}
                                    @endif
                                </button>
                                @endif
                            </div>
                        </td>
                        @endif
                    </tr>
                    @endif

                    @empty
                    <tr>
                        <td colspan="{{ $canManage ? 6 : 5 }}" class="px-4 py-12 text-center text-sm text-gray-400">
                            <x-heroicon-o-tag class="w-8 h-8 mx-auto mb-2 text-gray-300"/>
                            {{ __('No user types found.') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($this->getUserTypes()->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
            {{ $this->getUserTypes()->links() }}
        </div>
        @endif
    </div>
</x-filament-panels::page>
