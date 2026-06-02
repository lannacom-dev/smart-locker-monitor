<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') · Smart Locker Monitor</title>
    @vite(['resources/css/app.css'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    {{-- Tabulator datagrid --}}
    <link rel="stylesheet" href="https://unpkg.com/tabulator-tables@6.3.0/dist/css/tabulator_simple.min.css">
    <script src="https://unpkg.com/tabulator-tables@6.3.0/dist/js/tabulator.min.js" defer></script>
    <style>
        /* ── Tabulator → Sneat theme overrides ── */
        .tabulator{border:none!important;background:transparent!important;font-size:.875rem;color:#5d596c}
        .tabulator .tabulator-header{background:#f5f5f9;border-bottom:1px solid #dbdade!important;border-top:none}
        .tabulator .tabulator-header .tabulator-col{border-right:none!important;background:#f5f5f9;color:#a5a3ae;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;padding:12px 20px}
        .tabulator .tabulator-header .tabulator-col.tabulator-sortable:hover{background:#ece9fd}
        .tabulator .tabulator-header .tabulator-col .tabulator-col-sorter .tabulator-arrow{border-bottom-color:#a5a3ae;border-top-color:#a5a3ae}
        .tabulator .tabulator-header .tabulator-col.tabulator-col-sorter-element.tabulator-sortable.tabulator-col-header-sort-asc .tabulator-col-sorter .tabulator-arrow,
        .tabulator .tabulator-header .tabulator-col.tabulator-col-sorter-element.tabulator-sortable.tabulator-col-header-sort-desc .tabulator-col-sorter .tabulator-arrow{border-bottom-color:#7367f0;border-top-color:#7367f0}
        .tabulator .tabulator-tableholder .tabulator-table{background:transparent}
        .tabulator .tabulator-row{border-bottom:1px solid #f0f0f0;color:#5d596c;min-height:46px}
        .tabulator .tabulator-row:last-child{border-bottom:none}
        .tabulator .tabulator-row:hover,.tabulator .tabulator-row.tabulator-row-even:hover{background:#f9f9fc!important}
        .tabulator .tabulator-row.tabulator-selectable:hover{background:#f9f9fc!important}
        .tabulator .tabulator-row.tabulator-selected{background:#ece9fd!important}
        .tabulator .tabulator-cell{border-right:none!important;padding:10px 20px;vertical-align:middle}
        .tabulator .tabulator-cell.tabulator-editing{padding:4px 12px}
        .tabulator .tabulator-cell.tabulator-editing input,.tabulator .tabulator-cell.tabulator-editing select{width:100%;border:1.5px solid #7367f0;border-radius:6px;padding:5px 10px;outline:none;font-size:.8125rem;color:#5d596c;background:#fff}
        .tabulator .tabulator-footer{background:#fff;border-top:1px solid #dbdade;padding:8px 16px}
        .tabulator .tabulator-footer .tabulator-page-size{border:1px solid #dbdade;border-radius:6px;padding:3px 6px;color:#5d596c;font-size:.75rem;outline:none}
        .tabulator .tabulator-footer .tabulator-page{border:1px solid #dbdade;border-radius:6px;color:#5d596c;margin:0 2px;padding:3px 9px;font-size:.75rem;background:#fff;cursor:pointer;transition:all .15s}
        .tabulator .tabulator-footer .tabulator-page:hover{border-color:#7367f0;color:#7367f0;background:#ece9fd}
        .tabulator .tabulator-footer .tabulator-page.active{background:#7367f0;border-color:#7367f0;color:#fff}
        .tabulator .tabulator-footer .tabulator-page:disabled{opacity:.4;cursor:default}
        .tabulator .tabulator-header-filter input,.tabulator .tabulator-header-filter select{width:100%;border:1px solid #dbdade;border-radius:6px;padding:4px 8px;font-size:.75rem;color:#5d596c;background:#fff;outline:none;margin-top:4px}
        .tabulator .tabulator-header-filter input:focus,.tabulator .tabulator-header-filter select:focus{border-color:#7367f0}
        .tabulator .tabulator-placeholder{color:#a5a3ae;padding:48px 0}
        /* Toast */
        #sneat-toast{position:fixed;bottom:24px;right:24px;z-index:9999;transition:opacity .3s,transform .3s;opacity:0;transform:translateY(8px);pointer-events:none}
        #sneat-toast.show{opacity:1;transform:translateY(0)}
    </style>
    @stack('head')
</head>
<body class="bg-[#f5f5f9] text-[#5d596c] antialiased">

<div class="flex h-screen overflow-hidden">

    {{-- ═══════════════ SIDEBAR ═══════════════ --}}
    <aside class="hidden w-64 shrink-0 flex-col bg-white border-r border-[#dbdade] lg:flex overflow-y-auto">

        {{-- Logo --}}
        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-[#dbdade] px-6">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#7367f0]">
                <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
            <div>
                <span class="block text-[15px] font-bold leading-none text-slate-800">SmartLocker</span>
                <span class="block mt-0.5 text-[10px] text-[#a5a3ae]">Monitor System</span>
            </div>
        </div>

        {{-- Nav --}}
        @php
            $nav = fn(bool $active) => $active
                ? 'flex items-center gap-3 rounded-lg border-l-[3px] border-[#7367f0] bg-[#ece9fd] py-2 pl-[9px] pr-3 text-sm font-semibold text-[#7367f0]'
                : 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-[#5d596c] transition-colors hover:bg-[#f5f5f9] hover:text-[#7367f0]';
        @endphp

        <nav class="flex-1 space-y-0.5 px-4 py-5">

            {{-- ── Monitor ── --}}
            @can('view lockers')
            <p class="mb-2 px-2 text-[10px] font-semibold uppercase tracking-widest text-[#a5a3ae]">Monitor</p>

            <a href="{{ route('admin.dashboard') }}" class="{{ $nav(request()->routeIs('admin.dashboard')) }}">
                <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M9.293 2.293a1 1 0 011.414 0l7 7A1 1 0 0117 11h-1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-3a1 1 0 00-1-1H9a1 1 0 00-1 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-6H3a1 1 0 01-.707-1.707l7-7z" clip-rule="evenodd"/>
                </svg>
                Dashboard
            </a>

            <a href="{{ route('admin.lockers.index') }}" class="{{ $nav(request()->routeIs('admin.lockers.*')) }}">
                <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M2 4.25A2.25 2.25 0 014.25 2h11.5A2.25 2.25 0 0118 4.25v8.5A2.25 2.25 0 0115.75 15h-3.105a3.501 3.501 0 001.1 1.677A.75.75 0 0113.26 18H6.74a.75.75 0 01-.484-1.323A3.501 3.501 0 007.355 15H4.25A2.25 2.25 0 012 12.75v-8.5z" clip-rule="evenodd"/>
                </svg>
                Live Monitor
            </a>

            <a href="{{ route('admin.usage.index') }}" class="{{ $nav(request()->routeIs('admin.usage.*')) }}">
                <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M15.5 2A1.5 1.5 0 0014 3.5v13a1.5 1.5 0 001.5 1.5h1a1.5 1.5 0 001.5-1.5v-13A1.5 1.5 0 0016.5 2h-1zM9.5 6A1.5 1.5 0 008 7.5v9A1.5 1.5 0 009.5 18h1a1.5 1.5 0 001.5-1.5v-9A1.5 1.5 0 0010.5 6h-1zM3.5 10A1.5 1.5 0 002 11.5v5A1.5 1.5 0 003.5 18h1A1.5 1.5 0 006 16.5v-5A1.5 1.5 0 004.5 10h-1z"/>
                </svg>
                Usage Stats
            </a>
            @endcan

            {{-- ── Operations ── --}}
            @canany(['view issues', 'view maintenance', 'view system health'])
            <p class="mb-2 mt-5 px-2 text-[10px] font-semibold uppercase tracking-widest text-[#a5a3ae]">Operations</p>
            @endcanany

            @can('view issues')
            <a href="{{ route('admin.issues.index') }}" class="{{ $nav(request()->routeIs('admin.issues.*')) }}">
                <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                </svg>
                Issues
            </a>
            @endcan

            @can('view maintenance')
            <a href="{{ route('admin.maintenance.index') }}" class="{{ $nav(request()->routeIs('admin.maintenance.*')) }}">
                <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M19 5.5a4.5 4.5 0 01-4.791 4.49c-.873-.055-1.808.128-2.368.8l-6.024 7.23a2.724 2.724 0 11-3.837-3.837L9.21 8.16c.672-.56.855-1.495.8-2.368A4.5 4.5 0 0115.5 1a.75.75 0 01.53 1.28l-1.63 1.63a.75.75 0 00.22.52l1.45 1.45a.75.75 0 00.52.22l1.63-1.63A.75.75 0 0119 5.5zM3 16a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                </svg>
                Maintenance
            </a>
            @endcan

            @can('view system health')
            <a href="{{ route('admin.health.index') }}" class="{{ $nav(request()->routeIs('admin.health.*')) }}">
                <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.653 16.915l-.005-.003-.019-.01a20.759 20.759 0 01-1.162-.682 22.045 22.045 0 01-2.582-2.184C4.045 12.733 2 10.352 2 7.5a4.5 4.5 0 018-2.828A4.5 4.5 0 0118 7.5c0 2.852-2.044 5.233-3.885 6.82a22.049 22.049 0 01-3.744 2.814l-.019.01-.005.003h-.002a.739.739 0 01-.69.001l-.002-.001z"/>
                </svg>
                System Health
            </a>
            @endcan

            {{-- ── Management ── --}}
            @canany(['view users', 'view locker users', 'manage user types', 'view locations'])
            <p class="mb-2 mt-5 px-2 text-[10px] font-semibold uppercase tracking-widest text-[#a5a3ae]">Management</p>
            @endcanany

            @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.companies.index') }}" class="{{ $nav(request()->routeIs('admin.companies.*')) }}">
                <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 16.5v-13h-.25a.75.75 0 010-1.5h12.5a.75.75 0 010 1.5H16v13h.25a.75.75 0 010 1.5h-3.5a.75.75 0 01-.75-.75v-2.5a.75.75 0 00-.75-.75h-2.5a.75.75 0 00-.75.75v2.5a.75.75 0 01-.75.75h-3.5a.75.75 0 010-1.5H4zm3-11a.75.75 0 01.75-.75h.5a.75.75 0 010 1.5h-.5A.75.75 0 017 5.5zm.75 2.25a.75.75 0 000 1.5h.5a.75.75 0 000-1.5h-.5zM7 11.5a.75.75 0 01.75-.75h.5a.75.75 0 010 1.5h-.5a.75.75 0 01-.75-.75zm3.25-7.25a.75.75 0 000 1.5h.5a.75.75 0 000-1.5h-.5zm-.75 4a.75.75 0 01.75-.75h.5a.75.75 0 010 1.5h-.5a.75.75 0 01-.75-.75zm.75 2.25a.75.75 0 000 1.5h.5a.75.75 0 000-1.5h-.5z" clip-rule="evenodd"/>
                </svg>
                Companies
            </a>
            @endif

            @can('view locations')
            <a href="{{ route('admin.locations.index') }}" class="{{ $nav(request()->routeIs('admin.locations.*')) }}">
                <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M9.69 18.933l.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 00.281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 103 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 002.273 1.765 11.842 11.842 0 00.976.544l.062.029.018.008.006.003zM10 11.25a2.25 2.25 0 100-4.5 2.25 2.25 0 000 4.5z" clip-rule="evenodd"/>
                </svg>
                Locations
            </a>
            @endcan

            @can('view users')
            <a href="{{ route('admin.users.index') }}" class="{{ $nav(request()->routeIs('admin.users.*')) }}">
                <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.074.003z"/>
                </svg>
                Admin Users
            </a>
            @endcan

            @can('view locker users')
            <a href="{{ route('admin.locker-users.index') }}" class="{{ $nav(request()->routeIs('admin.locker-users.*')) }}">
                <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M7 8a3 3 0 100-6 3 3 0 000 6zM14.5 9a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM1.615 16.428a1.224 1.224 0 01-.569-1.175 6.002 6.002 0 0111.908 0c.058.467-.172.92-.57 1.174A9.953 9.953 0 017 18a9.953 9.953 0 01-5.385-1.572zM14.5 16h-.106c.07-.297.088-.611.048-.933a7.47 7.47 0 00-1.588-3.755 4.502 4.502 0 015.874 2.636.818.818 0 01-.36.98A7.465 7.465 0 0114.5 16z"/>
                </svg>
                Locker Users
            </a>
            @endcan

            @can('manage user types')
            <a href="{{ route('admin.user-types.index') }}" class="{{ $nav(request()->routeIs('admin.user-types.*')) }}">
                <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M1 6a3 3 0 013-3h12a3 3 0 013 3v8a3 3 0 01-3 3H4a3 3 0 01-3-3V6zm4 1.5a2 2 0 114 0 2 2 0 01-4 0zm2 3a4 4 0 00-3.665 2.395.75.75 0 00.372.955A7.47 7.47 0 007 14.5a7.47 7.47 0 003.293-.65.75.75 0 00.372-.955A4 4 0 009 10.5zm5-3.75a.75.75 0 000 1.5h2a.75.75 0 000-1.5h-2zm0 3a.75.75 0 000 1.5h2a.75.75 0 000-1.5h-2zm0 3a.75.75 0 000 1.5h2a.75.75 0 000-1.5h-2z" clip-rule="evenodd"/>
                </svg>
                User Types
            </a>
            @endcan

            @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.roles.index') }}" class="{{ $nav(request()->routeIs('admin.roles.*')) }}">
                <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8 7a5 5 0 113.61 4.804l-1.903 1.903A1 1 0 019 14H8v1a1 1 0 01-1 1H6v1a1 1 0 01-1 1H3a1 1 0 01-1-1v-2a1 1 0 01.293-.707L8.196 8.39A5.002 5.002 0 018 7zm5-3a.75.75 0 000 1.5A1.5 1.5 0 0114.5 7 .75.75 0 0016 7a3 3 0 00-3-3z" clip-rule="evenodd"/>
                </svg>
                Roles & Permissions
            </a>
            @endif

            {{-- Floor Plans: hidden until ready --}}
            {{-- @can('view lockers')
            <a href="{{ route('admin.floor-plans.index') }}" class="{{ $nav(request()->routeIs('admin.floor-plans.*')) }}">
                Floor Plans
            </a>
            @endcan --}}

        </nav>

        {{-- User card --}}
        <div class="shrink-0 border-t border-[#dbdade] p-4">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#7367f0] text-xs font-bold text-white">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                    <p class="truncate text-[11px] text-[#a5a3ae]">{{ auth()->user()->email }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button type="submit" title="Logout"
                            class="flex h-7 w-7 items-center justify-center rounded-lg text-[#a5a3ae] transition-colors hover:bg-[#fde8e9] hover:text-[#ea5455]">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- ═══════════════ MAIN ═══════════════ --}}
    <div class="flex min-w-0 flex-1 flex-col overflow-hidden">

        {{-- Top header --}}
        <header class="flex h-16 shrink-0 items-center justify-between border-b border-[#dbdade] bg-white px-6 sticky top-0 z-10">
            <div>
                <h1 class="text-[15px] font-semibold text-slate-800">@yield('heading', 'Dashboard')</h1>
                @hasSection('subheading')
                <p class="text-xs text-[#a5a3ae]">@yield('subheading')</p>
                @endif
            </div>
            <div class="flex items-center gap-3">
                @php
                    $syncOk = isset($lastSync) && $lastSync?->finished_at && $lastSync->finished_at->gt(now()->subMinutes(10));
                    $syncAge = isset($lastSync) ? ($lastSync?->finished_at?->diffForHumans() ?? 'ไม่เคย') : null;
                @endphp
                @if($syncOk)
                <span class="hidden items-center gap-1.5 text-xs text-[#a5a3ae] sm:flex">
                    <span class="h-2 w-2 rounded-full bg-[#28c76f]"></span>
                    Synced {{ $syncAge }}
                </span>
                @elseif(isset($lastSync))
                <span class="hidden items-center gap-1.5 rounded-lg bg-[#fde8e9] px-2 py-1 text-xs font-medium text-[#ea5455] sm:flex" title="Last sync: {{ $syncAge }}">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                    เชื่อมต่อ api ไม่ได้
                </span>
                @endif

                @can('view lockers')
                <form method="POST" action="{{ route('admin.sync') }}">@csrf
                    <button type="submit"
                            class="flex items-center gap-1.5 rounded-lg bg-[#7367f0] px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-[#6259d4] active:scale-95">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Sync
                    </button>
                </form>
                @endcan

                {{-- Mobile logout --}}
                <form method="POST" action="{{ route('logout') }}" class="lg:hidden">@csrf
                    <button type="submit"
                            class="rounded-lg border border-[#dbdade] bg-white px-3 py-1.5 text-xs font-medium text-[#5d596c] hover:bg-[#f5f5f9] transition-colors">
                        Logout
                    </button>
                </form>
            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1 overflow-y-auto p-6">

            @if(session('success'))
            <div class="mb-5 flex items-center gap-3 rounded-xl border border-[#b8efce] bg-[#dff7e9] px-4 py-3 text-sm text-[#1a6b3c]">
                <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                </svg>
                {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div class="mb-5 flex items-center gap-3 rounded-xl border border-[#f5b7b7] bg-[#fde8e9] px-4 py-3 text-sm text-[#8f1a1a]">
                <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                </svg>
                {{ session('error') }}
            </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
{{-- Global toast (used by datagrid pages) --}}
<div id="sneat-toast" role="alert"></div>
<script>
function sneatToast(msg, type) {
    var t = document.getElementById('sneat-toast');
    if (!t) return;
    var ok = type !== 'error';
    t.innerHTML = '<div style="display:flex;align-items:center;gap:10px;background:#fff;border:1px solid '
        + (ok ? '#b8efce' : '#f5b7b7')
        + ';border-radius:12px;padding:12px 18px;box-shadow:0 4px 16px rgba(67,89,113,.18);font-size:.8125rem;color:'
        + (ok ? '#1a6b3c' : '#8f1a1a') + '">'
        + '<span style="font-weight:600">' + (ok ? '✓' : '✕') + '</span>'
        + '<span>' + msg + '</span></div>';
    t.classList.add('show');
    clearTimeout(t._tid);
    t._tid = setTimeout(function(){ t.classList.remove('show'); }, 2800);
}
</script>
</body>
</html>
