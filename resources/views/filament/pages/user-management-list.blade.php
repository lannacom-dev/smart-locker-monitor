<x-filament-panels::page>
    @php
        $roleBadge = \App\Models\User::roleBadgeClasses();
    @endphp

    {{-- ── Header ────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Admin Users') }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ __('Manage users, roles, and access within your organisation.') }}</p>
        </div>
        @can('create users')
        <button wire:click="$set('showCreateForm', true)"
            class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white text-sm font-medium rounded-lg">
            <x-heroicon-o-user-plus class="w-4 h-4"/>
            {{ __('New User') }}
        </button>
        @endcan
    </div>

    {{-- ── Temp password notice ────────────────────────────────────── --}}
    @if($this->createdPassword)
    <div class="mb-4 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-300 dark:border-amber-700 rounded-xl flex items-start gap-3">
        <x-heroicon-o-key class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5"/>
        <div class="flex-1 text-sm">
            <p class="font-semibold text-amber-800 dark:text-amber-300">{{ __('User created — temporary password (shown once):') }}</p>
            <code class="mt-1 block font-mono text-base font-bold text-amber-900 dark:text-amber-200 bg-amber-100 dark:bg-amber-900/40 px-3 py-1 rounded">{{ $this->createdPassword }}</code>
            <p class="mt-1 text-amber-700 dark:text-amber-400">{{ __('Share this securely. It will not be shown again.') }}</p>
        </div>
        <button wire:click="$set('createdPassword', null)" class="text-amber-500 hover:text-amber-700">
            <x-heroicon-o-x-mark class="w-4 h-4"/>
        </button>
    </div>
    @endif

    {{-- ── Create User Panel ──────────────────────────────────────── --}}
    @if($this->showCreateForm)
    <div class="mb-6 bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <x-heroicon-o-user-plus class="w-5 h-5 text-primary-500"/>
                {{ __('Create New User') }}
            </h2>
            <button wire:click="$set('showCreateForm', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                <x-heroicon-o-x-mark class="w-5 h-5"/>
            </button>
        </div>
        <div class="p-6 grid grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Name') }} <span class="text-red-500">*</span></label>
                <input wire:model="newName" type="text" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="{{ __('Full name') }}">
                @error('newName') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Email') }} <span class="text-red-500">*</span></label>
                <input wire:model="newEmail" type="email" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="{{ __('user@company.com') }}">
                @error('newEmail') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Temporary Password') }} <span class="text-red-500">*</span></label>
                <input wire:model="newPassword" type="text" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-primary-500" placeholder="{{ __('Min 8 characters') }}">
                @error('newPassword') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Role') }} <span class="text-red-500">*</span></label>
                <select wire:model="newRole" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">{{ __('— Select role —') }}</option>
                    @foreach($this->getAssignableRoles() as $role)
                        <option value="{{ $role }}">{{ ucfirst(str_replace('_', ' ', $role)) }}</option>
                    @endforeach
                </select>
                @error('newRole') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ __('Company') }}</label>
                <select wire:model="newCompanyId" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">{{ __('— None —') }}</option>
                    @foreach($this->getCompanies() as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center gap-3 mt-5">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" wire:model="newIsActive" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-primary-500 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600 dark:bg-gray-600"></div>
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('Active on creation') }}</span>
                </label>
            </div>
            <div class="col-span-2 flex justify-end gap-3 pt-2 border-t border-gray-100 dark:border-gray-700">
                <button wire:click="$set('showCreateForm', false)"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600">
                    {{ __('Cancel') }}
                </button>
                <button wire:click="createUser" wire:loading.attr="disabled"
                    class="px-4 py-2 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg disabled:opacity-60 flex items-center gap-2">
                    <x-heroicon-o-user-plus class="w-4 h-4"/>
                    <span wire:loading.remove wire:target="createUser">{{ __('Create User') }}</span>
                    <span wire:loading wire:target="createUser">{{ __('Creating…') }}</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ── Filters ─────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-4">
        <div class="flex flex-wrap items-center gap-3 p-4">
            <div class="flex items-center gap-1.5 text-gray-400 dark:text-gray-500 shrink-0 mr-1">
                <x-heroicon-o-funnel class="w-4 h-4"/>
                <span class="text-xs font-medium uppercase tracking-wider">{{ __('Filter') }}</span>
            </div>
            <div class="flex-1 min-w-48">
                <div class="relative">
                    <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"/>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('Search name or email…') }}"
                        class="w-full pl-9 pr-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                </div>
            </div>
            <select wire:model.live="filterRole"
                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">{{ __('All Roles') }}</option>
                @foreach($this->getRoles() as $role)
                    <option value="{{ $role }}">{{ ucfirst(str_replace('_', ' ', $role)) }}</option>
                @endforeach
            </select>
            <select wire:model.live="filterActive"
                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">{{ __('All Status') }}</option>
                <option value="1">{{ __('Active') }}</option>
                <option value="0">{{ __('Disabled') }}</option>
            </select>
            @if(count($this->getCompanies()) > 1)
            <select wire:model.live="filterCompany"
                class="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                <option value="">{{ __('All Companies') }}</option>
                @foreach($this->getCompanies() as $company)
                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
            @endif
        </div>
    </div>

    {{-- ── Users Table ─────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
        @php $users = $this->getUsers(); @endphp

        @if($users->isEmpty())
        <div class="py-16 text-center text-gray-400 dark:text-gray-500">
            <x-heroicon-o-users class="w-10 h-10 mx-auto mb-3 opacity-40"/>
            <p class="text-sm">{{ __('No users found.') }}</p>
        </div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50 text-left border-b border-gray-200 dark:border-gray-700">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('User') }}</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Company') }}</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Roles') }}</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('Created') }}</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($users as $user)
                    <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-700/30 transition-colors group {{ $user->is_active ? 'border-l-4 border-l-green-400' : 'border-l-4 border-l-red-400' }}">
                        <td class="px-4 py-3">
                            <a href="{{ route('filament.admin.pages.user-management.{record}', ['record' => $user->id]) }}"
                               class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-700 dark:text-primary-300 font-bold text-sm shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                </div>
                            </a>
                        </td>
                        <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                            {{ $user->company->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1">
                                @forelse($user->roles as $role)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $roleBadge[$role->name] ?? 'bg-gray-100 text-gray-600' }}">
                                        {{ ucfirst(str_replace('_',' ',$role->name)) }}
                                    </span>
                                @empty
                                    <span class="text-gray-400 text-xs">{{ __('No role') }}</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @if($user->is_active)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span> {{ __('Active') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current"></span> {{ __('Disabled') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 dark:text-gray-400 whitespace-nowrap">
                            {{ $user->created_at->format('d M Y') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('filament.admin.pages.user-management.{record}', ['record' => $user->id]) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600">
                                    <x-heroicon-o-pencil-square class="w-3.5 h-3.5"/> {{ __('Edit') }}
                                </a>
                                @can('edit users')
                                @if($user->id !== auth()->id())
                                <button wire:click="toggleActive({{ $user->id }})"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg
                                    {{ $user->is_active
                                        ? 'text-red-700 bg-red-50 hover:bg-red-100 dark:text-red-400 dark:bg-red-900/20 dark:hover:bg-red-900/40'
                                        : 'text-green-700 bg-green-50 hover:bg-green-100 dark:text-green-400 dark:bg-green-900/20 dark:hover:bg-green-900/40'
                                    }}">
                                    @if($user->is_active)
                                        <x-heroicon-o-no-symbol class="w-3.5 h-3.5"/> {{ __('Disable') }}
                                    @else
                                        <x-heroicon-o-check-circle class="w-3.5 h-3.5"/> {{ __('Enable') }}
                                    @endif
                                </button>
                                @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-700">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</x-filament-panels::page>
