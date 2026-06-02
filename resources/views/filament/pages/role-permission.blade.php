<x-filament-panels::page>
    @php
        $roles      = $this->getRoleNames();
        $groups     = $this->getPermissionGroups();
        $userCounts = $this->getRoleUserCounts();
        $superPerms = $this->getSuperAdminPermissions();
    @endphp

    {{-- ── Header ────────────────────────────────────────────────── --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('Role Permissions') }}</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
            {{ __('Configure which permissions each role has. Super Admin always has all permissions.') }}
        </p>
    </div>

    {{-- ── Role summary cards ──────────────────────────────────────── --}}
    @php
        $roleLabels  = [
            'super_admin'  => 'Super Admin',
            'tenant_admin' => 'Tenant Admin',
            'operator'     => 'Operator',
            'technician'   => 'Technician',
            'support'      => 'Support',
            'viewer'       => 'Viewer',
        ];
        $roleGradients = [
            'super_admin'  => 'from-purple-600 to-violet-700',
            'tenant_admin' => 'from-blue-600 to-indigo-700',
            'operator'     => 'from-sky-500 to-blue-600',
            'technician'   => 'from-amber-500 to-yellow-600',
            'support'      => 'from-teal-500 to-cyan-600',
            'viewer'       => 'from-slate-500 to-gray-700',
        ];
        $roleIcons = [
            'super_admin'  => 'heroicon-s-shield-check',
            'tenant_admin' => 'heroicon-s-building-office',
            'operator'     => 'heroicon-s-computer-desktop',
            'technician'   => 'heroicon-s-wrench-screwdriver',
            'support'      => 'heroicon-s-chat-bubble-left-right',
            'viewer'       => 'heroicon-s-eye',
        ];
    @endphp
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        {{-- Super Admin fixed card --}}
        <div class="rounded-xl bg-gradient-to-br from-purple-600 to-violet-700 p-4 shadow-md text-white flex flex-col gap-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold opacity-80 uppercase tracking-wider">{{ __('Super Admin') }}</span>
                <div class="bg-white/20 rounded-lg p-1.5"><x-heroicon-s-shield-check class="w-4 h-4"/></div>
            </div>
            <p class="text-2xl font-extrabold leading-none">{{ $userCounts['super_admin'] ?? '∞' }}</p>
            <p class="text-[10px] opacity-70 uppercase tracking-wider">{{ __('All permissions') }}</p>
        </div>
        @foreach($roles as $role)
        @php $grad = $roleGradients[$role] ?? 'from-slate-500 to-gray-700'; @endphp
        <div class="rounded-xl bg-gradient-to-br {{ $grad }} p-4 shadow-md text-white flex flex-col gap-2">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold opacity-80 uppercase tracking-wider">{{ $roleLabels[$role] ?? ucfirst(str_replace('_',' ',$role)) }}</span>
                <div class="bg-white/20 rounded-lg p-1.5">
                    @php $icon = $roleIcons[$role] ?? 'heroicon-s-user'; @endphp
                    <x-dynamic-component :component="$icon" class="w-4 h-4"/>
                </div>
            </div>
            <p class="text-2xl font-extrabold leading-none">{{ $userCounts[$role] ?? 0 }}</p>
            <p class="text-[10px] opacity-70 uppercase tracking-wider">{{ __('user(s)') }}</p>
        </div>
        @endforeach
    </div>

    {{-- ── Matrix table ─────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/60 border-b border-gray-200 dark:border-gray-700">
                        <th class="sticky left-0 bg-gray-50 dark:bg-gray-700/60 z-10 px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider w-56">
                            {{ __('Permission') }}
                        </th>
                        {{-- Super Admin: read-only all ✓ --}}
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-purple-600 dark:text-purple-400 min-w-[110px]">
                            <div>{{ __('Super Admin') }}</div>
                            <div class="text-[10px] font-normal opacity-60 normal-case">{{ __('(read-only)') }}</div>
                        </th>
                        @foreach($roles as $role)
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 min-w-[110px]">
                            {{ $roleLabels[$role] ?? ucfirst(str_replace('_',' ',$role)) }}
                        </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($groups as $groupName => $permissions)
                    {{-- Category header row --}}
                    <tr class="bg-gray-50/70 dark:bg-gray-700/30">
                        <td colspan="{{ 2 + count($roles) }}" class="px-4 py-2 text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ $groupName }}
                        </td>
                    </tr>
                    @foreach($permissions as $permission)
                    <tr class="border-t border-gray-100 dark:border-gray-700/50 hover:bg-gray-50/50 dark:hover:bg-gray-700/20">
                        <td class="sticky left-0 bg-white dark:bg-gray-800 z-10 px-4 py-2.5 text-gray-700 dark:text-gray-300 font-mono text-xs border-r border-gray-100 dark:border-gray-700">
                            {{ $permission }}
                        </td>
                        {{-- Super Admin always ✓ --}}
                        <td class="px-4 py-2.5 text-center">
                            <x-heroicon-o-check-circle class="w-5 h-5 text-purple-400 mx-auto"/>
                        </td>
                        @foreach($roles as $role)
                        <td class="px-4 py-2.5 text-center">
                            <label class="inline-flex items-center justify-center cursor-pointer">
                                <input
                                    type="checkbox"
                                    wire:model="matrix.{{ $role }}.{{ $permission }}"
                                    class="w-4 h-4 rounded border-gray-300 dark:border-gray-500 text-primary-600 focus:ring-primary-500 cursor-pointer">
                            </label>
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t-2 border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/40">
                        <td class="sticky left-0 bg-gray-50 dark:bg-gray-700/40 px-4 py-3 text-xs text-gray-500 font-medium">{{ __('Save changes') }}</td>
                        {{-- Super Admin: no save --}}
                        <td class="px-4 py-3 text-center">
                            <span class="text-xs text-gray-400">—</span>
                        </td>
                        @foreach($roles as $role)
                        <td class="px-4 py-3 text-center">
                            <button
                                wire:click="saveRole('{{ $role }}')"
                                wire:loading.attr="disabled"
                                wire:target="saveRole('{{ $role }}')"
                                class="px-3 py-1.5 text-xs font-medium text-white bg-primary-600 hover:bg-primary-700 rounded-lg disabled:opacity-60 flex items-center gap-1 mx-auto">
                                <x-heroicon-o-check class="w-3.5 h-3.5"/>
                                <span wire:loading.remove wire:target="saveRole('{{ $role }}')">{{ __('Save') }}</span>
                                <span wire:loading wire:target="saveRole('{{ $role }}')">…</span>
                            </button>
                        </td>
                        @endforeach
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- ── Audit Log ───────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <h2 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <x-heroicon-o-clock class="w-5 h-5 text-gray-400"/> {{ __('Permission Change History') }}
            </h2>
        </div>
        @php $logs = $this->getAuditLogs(); @endphp
        @if($logs->isEmpty())
            <div class="py-10 text-center text-sm text-gray-400">{{ __('No changes recorded yet.') }}</div>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/50 text-left">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Role') }}</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Before') }}</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('After') }}</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Changed By') }}</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('When') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($logs as $log)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                        <td class="px-4 py-3 font-semibold text-gray-800 dark:text-gray-200">
                            {{ ucfirst(str_replace('_', ' ', $log->target_name)) }}
                        </td>
                        <td class="px-4 py-3 max-w-xs">
                            <p class="text-xs text-red-500 dark:text-red-400 line-through opacity-75 break-all">{{ Str::limit($log->old_value, 80) }}</p>
                        </td>
                        <td class="px-4 py-3 max-w-xs">
                            <p class="text-xs text-green-600 dark:text-green-400 font-medium break-all">{{ Str::limit($log->new_value, 80) }}</p>
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
</x-filament-panels::page>
