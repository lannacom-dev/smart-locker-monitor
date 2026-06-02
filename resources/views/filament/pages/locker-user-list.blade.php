<x-filament-panels::page>
    @php
        $badgeClasses = $this->getBadgeClasses();
        $companies    = $this->getCompanies();
        $userTypes    = $this->getUserTypes();
        $canCreate    = auth()->user()->can('create locker users');
        $canEdit      = auth()->user()->can('edit locker users');
        $canDisable   = auth()->user()->can('disable locker users');
    @endphp

    {{-- ── Header ──────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Locker Users') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                {{ __('Manage end-users who access Smart Lockers across all tenants.') }}
            </p>
        </div>
        @if($canCreate)
        <button wire:click="$set('showCreateForm', true)"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg transition-colors">
            <x-heroicon-o-plus class="w-4 h-4"/> {{ __('New Locker User') }}
        </button>
        @endif
    </div>

    {{-- ── Create form ──────────────────────────────────────────────── --}}
    @if($this->showCreateForm)
    <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <x-heroicon-o-user-plus class="w-5 h-5 text-gray-400"/> {{ __('New Locker User') }}
            </h2>
            <button wire:click="$set('showCreateForm', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <x-heroicon-o-x-mark class="w-5 h-5"/>
            </button>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-3 gap-4">
                {{-- Row 1: Identity --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Full Name') }} <span class="text-red-500">*</span></label>
                    <input wire:model="newFullName" type="text" placeholder="{{ __('Full name') }}"
                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('newFullName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Email') }}</label>
                    <input wire:model="newEmail" type="email" placeholder="email@example.com"
                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('newEmail') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Phone') }}</label>
                    <input wire:model="newPhone" type="text" placeholder="+66 8X-XXX-XXXX"
                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('newPhone') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Row 2: Tenant + Type --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Company') }} <span class="text-red-500">*</span></label>
                    <select wire:model="newCompanyId"
                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">{{ __('— Select company —') }}</option>
                        @foreach($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                    @error('newCompanyId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('User Type') }} <span class="text-red-500">*</span></label>
                    <select wire:model="newUserTypeId"
                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                        <option value="">{{ __('— Select type —') }}</option>
                        @foreach($userTypes as $ut)
                        <option value="{{ $ut->id }}">{{ $ut->name }}</option>
                        @endforeach
                    </select>
                    @error('newUserTypeId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-end">
                    <label class="flex items-center gap-3 cursor-pointer mb-2">
                        <input wire:model="newIsActive" type="checkbox"
                            class="w-4 h-4 rounded border-gray-300 dark:border-gray-500 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Active') }}</span>
                    </label>
                </div>

                {{-- Row 3: Type-specific + Dates --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Employee ID') }}
                        <span class="text-xs text-gray-400 font-normal ml-1">{{ __('(required for Employee)') }}</span>
                    </label>
                    <input wire:model="newEmployeeId" type="text" placeholder="EMP-0001"
                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('newEmployeeId') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('Organization') }}
                        <span class="text-xs text-gray-400 font-normal ml-1">{{ __('(required for Delivery/External)') }}</span>
                    </label>
                    <input wire:model="newOrganization" type="text" placeholder="{{ __('Company / agency name') }}"
                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('newOrganization') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div></div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Access Start Date') }}</label>
                    <input wire:model="newAccessStart" type="date"
                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('newAccessStart') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('Access End Date') }}</label>
                    <input wire:model="newAccessEnd" type="date"
                        class="w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    @error('newAccessEnd') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-3">
                <button wire:click="$set('showCreateForm', false)"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">
                    {{ __('Cancel') }}
                </button>
                <button wire:click="createLockerUser" wire:loading.attr="disabled" wire:target="createLockerUser"
                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg disabled:opacity-60 flex items-center gap-2">
                    <span wire:loading.remove wire:target="createLockerUser">{{ __('Create User') }}</span>
                    <span wire:loading wire:target="createLockerUser">{{ __('Creating…') }}</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Filters ───────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-4">
        <div class="flex flex-wrap items-center gap-3 p-4">
            <div class="flex items-center gap-1.5 text-gray-400 dark:text-gray-500 shrink-0 mr-1">
                <x-heroicon-o-funnel class="w-4 h-4"/>
                <span class="text-xs font-medium uppercase tracking-wider">{{ __('Filter') }}</span>
            </div>
            {{-- Search --}}
            <div class="flex-1 min-w-48">
                <div class="relative">
                    <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"/>
                    <input wire:model.live.debounce.300ms="search" type="text"
                        placeholder="{{ __('Search name, email, employee ID…') }}"
                        class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
            </div>
            {{-- Company filter (only if multiple companies) --}}
            @if($companies->count() > 1)
            <select wire:model.live="filterCompany"
                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 min-w-36">
                <option value="">{{ __('All Companies') }}</option>
                @foreach($companies as $company)
                <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
            @endif
            {{-- User Type filter --}}
            <select wire:model.live="filterUserType"
                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 min-w-36">
                <option value="">{{ __('All Types') }}</option>
                @foreach($userTypes as $ut)
                <option value="{{ $ut->id }}">{{ $ut->name }}</option>
                @endforeach
            </select>
            {{-- Status filter --}}
            <select wire:model.live="filterActive"
                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 min-w-28">
                <option value="">{{ __('All Status') }}</option>
                <option value="1">{{ __('Active') }}</option>
                <option value="0">{{ __('Disabled') }}</option>
            </select>
        </div>
    </div>

    {{-- ── Table ─────────────────────────────────────────────────────── --}}
    @php $lockerUsers = $this->getLockerUsers(); @endphp
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/60 border-b border-gray-200 dark:border-gray-700 text-left">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('User') }}</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Company') }}</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Type') }}</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Access Window') }}</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($lockerUsers as $lu)
                    <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition-colors {{ $lu->is_active ? 'border-l-4 border-l-green-400' : 'border-l-4 border-l-red-400' }}">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-primary-700 dark:text-primary-300">
                                        {{ strtoupper(substr($lu->full_name, 0, 1)) }}
                                    </span>
                                </div>
                                <div>
                                    <a href="{{ route('filament.admin.pages.locker-users--record', ['record' => $lu->id]) }}"
                                        class="text-sm font-medium text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400">
                                        {{ $lu->full_name }}
                                    </a>
                                    @if($lu->email)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $lu->email }}</p>
                                    @elseif($lu->employee_id)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">{{ $lu->employee_id }}</p>
                                    @elseif($lu->organization)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $lu->organization }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            {{ $lu->company?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($lu->userType)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badgeClasses[$lu->userType->slug] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ $lu->userType->name }}
                            </span>
                            @else
                            <span class="text-xs text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                            {{ $lu->accessWindowLabel() }}
                        </td>
                        <td class="px-4 py-3">
                            @if($lu->is_active)
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                <span class="w-1.5 h-1.5 rounded-full bg-current"></span> {{ __('Active') }}
                            </span>
                            @else
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-current"></span> {{ __('Disabled') }}
                            </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if($canEdit)
                                <a href="{{ route('filament.admin.pages.locker-users--record', ['record' => $lu->id]) }}"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <x-heroicon-o-pencil-square class="w-3.5 h-3.5"/> {{ __('Edit') }}
                                </a>
                                @endif
                                @if($canDisable)
                                <button wire:click="toggleActive({{ $lu->id }})"
                                    wire:confirm="{{ $lu->is_active ? __('Disable this user?') : __('Enable this user?') }}"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg border transition-colors {{ $lu->is_active ? 'text-red-600 dark:text-red-400 border-red-200 dark:border-red-800 hover:bg-red-50 dark:hover:bg-red-900/20' : 'text-green-600 dark:text-green-400 border-green-200 dark:border-green-800 hover:bg-green-50 dark:hover:bg-green-900/20' }}">
                                    @if($lu->is_active)
                                    <x-heroicon-o-no-symbol class="w-3.5 h-3.5"/>
                                    @else
                                    <x-heroicon-o-check-circle class="w-3.5 h-3.5"/>
                                    @endif
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-sm text-gray-400">
                            <x-heroicon-o-users class="w-8 h-8 mx-auto mb-2 text-gray-300"/>
                            {{ __('No locker users found.') }}
                            @if($this->search || $this->filterCompany || $this->filterUserType || $this->filterActive !== '')
                            <p class="mt-1">{{ __('Try adjusting your filters.') }}</p>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($lockerUsers->hasPages())
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
            {{ $lockerUsers->links() }}
        </div>
        @endif
    </div>
</x-filament-panels::page>
