@extends('layouts.admin')
@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('subheading', 'ข้อมูลจากฐานข้อมูล — sync จาก API อัตโนมัติทุกนาที')

@section('content')

{{-- ── Welcome banner ── --}}
<div class="relative mb-6 overflow-hidden rounded-xl bg-gradient-to-br from-[#7367f0] via-[#7f72f5] to-[#9984f2] p-6 text-white sneat-shadow">
    {{-- Decorative circles --}}
    <div class="pointer-events-none absolute -right-10 -top-10 h-48 w-48 rounded-full bg-white/10"></div>
    <div class="pointer-events-none absolute -right-4 bottom-[-30px] h-32 w-32 rounded-full bg-white/10"></div>
    <div class="pointer-events-none absolute right-24 -bottom-8 h-20 w-20 rounded-full bg-white/10"></div>

    <div class="relative flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-white/70 text-xs font-medium uppercase tracking-widest mb-1">Smart Locker Monitor</p>
            <h2 class="text-2xl font-bold">ยินดีต้อนรับ, {{ auth()->user()->name }}! 👋</h2>
            <p class="mt-1 text-white/75 text-sm">ระบบพร้อมใช้งาน — ตรวจสอบสถานะ locker ด้านล่าง</p>
            <div class="mt-4 flex flex-wrap gap-6">
                <div>
                    <p class="text-white/60 text-xs">Lockers ทั้งหมด</p>
                    <p class="text-2xl font-bold">{{ $lockers['total'] ?? 0 }}</p>
                </div>
                <div>
                    <p class="text-white/60 text-xs">พร้อมใช้งาน</p>
                    <p class="text-2xl font-bold text-[#28c76f]">{{ $lockers['available'] ?? 0 }}</p>
                </div>
                <div>
                    <p class="text-white/60 text-xs">ออนไลน์</p>
                    <p class="text-2xl font-bold">{{ $connection['online'] ?? 0 }}</p>
                </div>
            </div>
        </div>
        <svg class="hidden h-24 w-24 text-white/20 sm:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
    </div>
</div>

{{-- ── API status banner (shown when sync is stale OR lockers are offline) ── --}}
@php
    $syncIsStale = !isset($lastSync) || !$lastSync?->finished_at || $lastSync->finished_at->lt(now()->subMinutes(10));
    $offlineLockers = ($connection['offline'] ?? 0);
@endphp
@if($syncIsStale || $offlineLockers > 0)
<div class="mb-5 flex items-start gap-3 rounded-xl border border-[#f5c6c6] bg-[#fde8e9] px-4 py-3">
    <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#ea5455]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
    </svg>
    <div class="flex-1 text-sm text-[#ea5455]">
        <span class="font-semibold">เชื่อมต่อ api ไม่ได้</span>
        @if($offlineLockers > 0)
        — {{ $offlineLockers }} locker{{ $offlineLockers > 1 ? 's' : '' }} ไม่ตอบสนอง
        @endif
        @if($syncIsStale)
        — Sync ล่าสุด: {{ isset($lastSync) ? ($lastSync->finished_at?->diffForHumans() ?? 'ไม่ทราบ') : 'ยังไม่เคย Sync' }}
        @endif
        <a href="{{ route('admin.lockers.index') }}"
           class="ml-2 inline-flex items-center gap-1 text-xs font-semibold underline underline-offset-2">
            ดูรายละเอียด →
        </a>
    </div>
</div>
@endif

{{-- ── Stat cards ── --}}
@php
    $total = max(1, $lockers['total'] ?? 1);
    $cards = [
        [
            'label'      => 'Total Lockers',
            'value'      => $lockers['total'] ?? 0,
            'sub'        => 'ทั้งหมดในระบบ',
            'icon_bg'    => 'bg-[#ece9fd]',
            'icon_color' => 'text-[#7367f0]',
            'dot'        => 'bg-[#7367f0]',
            'icon'       => 'M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z',
            'icon_rule'  => 'evenodd',
        ],
        [
            'label'      => 'Available',
            'value'      => $lockers['available'] ?? 0,
            'sub'        => round((($lockers['available'] ?? 0) / $total) * 100) . '% ของทั้งหมด',
            'icon_bg'    => 'bg-[#dff7e9]',
            'icon_color' => 'text-[#28c76f]',
            'dot'        => 'bg-[#28c76f]',
            'icon'       => 'M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z',
            'icon_rule'  => 'evenodd',
        ],
        [
            'label'      => 'In Use',
            'value'      => $lockers['in_use'] ?? 0,
            'sub'        => round((($lockers['in_use'] ?? 0) / $total) * 100) . '% กำลังใช้งาน',
            'icon_bg'    => 'bg-[#d9f7fb]',
            'icon_color' => 'text-[#00cfe8]',
            'dot'        => 'bg-[#00cfe8]',
            'icon'       => 'M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.074.003z',
            'icon_rule'  => null,
        ],
        [
            'label'      => 'Fault',
            'value'      => $lockers['fault'] ?? 0,
            'sub'        => ($lockers['fault'] ?? 0) > 0 ? 'ต้องการการดูแล' : 'ระบบปกติ',
            'icon_bg'    => 'bg-[#fde8e9]',
            'icon_color' => 'text-[#ea5455]',
            'dot'        => 'bg-[#ea5455]',
            'icon'       => 'M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z',
            'icon_rule'  => 'evenodd',
        ],
    ];
@endphp

<div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
    @foreach($cards as $card)
    <div class="rounded-xl bg-white p-5 sneat-shadow">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0 flex-1">
                <p class="text-xs font-medium text-[#a5a3ae]">{{ $card['label'] }}</p>
                <p class="mt-1.5 text-2xl font-bold text-slate-800">{{ number_format($card['value']) }}</p>
                <p class="mt-1 flex items-center gap-1 text-xs text-[#a5a3ae]">
                    <span class="h-1.5 w-1.5 rounded-full {{ $card['dot'] }}"></span>
                    {{ $card['sub'] }}
                </p>
            </div>
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $card['icon_bg'] }} {{ $card['icon_color'] }}">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                    <path @if($card['icon_rule']) fill-rule="{{ $card['icon_rule'] }}" clip-rule="{{ $card['icon_rule'] }}" @endif d="{{ $card['icon'] }}"/>
                </svg>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Charts row ── --}}
<div class="mb-6 grid gap-5 lg:grid-cols-2">

    {{-- Donut: Locker status --}}
    <div class="rounded-xl bg-white p-5 sneat-shadow">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-slate-800">Locker Status Distribution</h3>
                <p class="text-xs text-[#a5a3ae]">สัดส่วนสถานะ locker ทั้งหมด</p>
            </div>
        </div>
        <div class="flex items-center justify-center">
            <canvas id="lockerDonutChart" style="max-height:220px; max-width:280px;"></canvas>
        </div>
    </div>

    {{-- Bar: Connection status --}}
    <div class="rounded-xl bg-white p-5 sneat-shadow">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-slate-800">Connection Status</h3>
                <p class="text-xs text-[#a5a3ae]">จำนวน locker ตามสถานะการเชื่อมต่อ</p>
            </div>
        </div>
        <canvas id="connectionBarChart" style="max-height:220px;"></canvas>
    </div>
</div>

{{-- ── Bottom row ── --}}
<div class="grid gap-5 lg:grid-cols-3">

    {{-- Boxes --}}
    <div class="rounded-xl bg-white p-5 sneat-shadow">
        <div class="mb-4 flex items-center gap-2.5">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#ece9fd]">
                <svg class="h-4 w-4 text-[#7367f0]" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10.362 1.093a.75.75 0 00-.724 0L2.523 5.018 10 9.143l7.477-4.125-7.115-3.925zM18 6.443l-7.25 3.998v8.668l6.498-3.582A.75.75 0 0018 14.9V6.443zm-8.75 12.666V10.44L2 6.443V14.9a.75.75 0 00.752.627L9.25 19.11z" clip-rule="evenodd"/>
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-slate-800">Boxes</h3>
        </div>
        <div class="space-y-3">
            <div class="flex items-center justify-between rounded-lg bg-[#f5f5f9] px-4 py-3">
                <span class="text-sm text-[#5d596c]">Total Boxes</span>
                <span class="text-lg font-bold text-slate-800">{{ number_format($boxes['total'] ?? 0) }}</span>
            </div>
            <div class="flex items-center justify-between rounded-lg bg-[#ece9fd] px-4 py-3">
                <span class="text-sm text-[#7367f0]">Occupied</span>
                <span class="text-lg font-bold text-[#7367f0]">{{ number_format($boxes['occupied'] ?? 0) }}</span>
            </div>
        </div>
    </div>

    {{-- Online summary --}}
    <div class="rounded-xl bg-white p-5 sneat-shadow">
        <div class="mb-4 flex items-center gap-2.5">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#dff7e9]">
                <svg class="h-4 w-4 text-[#28c76f]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z"/>
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-slate-800">Network</h3>
        </div>
        @php
            $totalConn = max(1, ($connection['online'] ?? 0) + ($connection['warning'] ?? 0) + ($connection['offline'] ?? 0));
            $connPct = round((($connection['online'] ?? 0) / $totalConn) * 100);
        @endphp
        <div class="mb-2 flex items-end justify-between">
            <span class="text-3xl font-bold text-slate-800">{{ $connPct }}%</span>
            <span class="text-xs text-[#a5a3ae]">Online rate</span>
        </div>
        <div class="h-2 w-full overflow-hidden rounded-full bg-[#f5f5f9]">
            <div class="h-full rounded-full bg-[#28c76f]" style="width: {{ $connPct }}%"></div>
        </div>
        <div class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
            <div><p class="font-bold text-[#28c76f]">{{ $connection['online'] ?? 0 }}</p><p class="text-[#a5a3ae]">Online</p></div>
            <div><p class="font-bold text-[#ff9f43]">{{ $connection['warning'] ?? 0 }}</p><p class="text-[#a5a3ae]">Warning</p></div>
            <div><p class="font-bold text-[#ea5455]">{{ $connection['offline'] ?? 0 }}</p><p class="text-[#a5a3ae]">Offline</p></div>
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="rounded-xl bg-white p-5 sneat-shadow">
        <div class="mb-4 flex items-center gap-2.5">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#fff3e8]">
                <svg class="h-4 w-4 text-[#ff9f43]" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M2 3.5A1.5 1.5 0 013.5 2h9A1.5 1.5 0 0114 3.5v11.75A2.75 2.75 0 0016.75 18h-12A2.75 2.75 0 012 15.25V3.5zm3.75 7a.75.75 0 000 1.5h4.5a.75.75 0 000-1.5h-4.5zm0 3a.75.75 0 000 1.5h4.5a.75.75 0 000-1.5h-4.5zM5 5.75A.75.75 0 015.75 5h4.5a.75.75 0 01.75.75v2.5a.75.75 0 01-.75.75h-4.5A.75.75 0 015 8.25v-2.5z" clip-rule="evenodd"/>
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-slate-800">Quick Access</h3>
        </div>
        <div class="space-y-2">
            <a href="{{ route('admin.lockers.index') }}"
               class="flex items-center justify-between rounded-lg border border-[#dbdade] px-4 py-2.5 text-sm font-medium text-[#5d596c] transition-colors hover:border-[#7367f0] hover:bg-[#ece9fd] hover:text-[#7367f0]">
                Live Monitor
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </a>
            <a href="{{ route('admin.usage.index') }}"
               class="flex items-center justify-between rounded-lg border border-[#dbdade] px-4 py-2.5 text-sm font-medium text-[#5d596c] transition-colors hover:border-[#7367f0] hover:bg-[#ece9fd] hover:text-[#7367f0]">
                Usage Statistics
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </a>
            @can('view issues')
            <a href="{{ route('admin.issues.index') }}"
               class="flex items-center justify-between rounded-lg border border-[#dbdade] px-4 py-2.5 text-sm font-medium text-[#5d596c] transition-colors hover:border-[#7367f0] hover:bg-[#ece9fd] hover:text-[#7367f0]">
                View Issues
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/></svg>
            </a>
            @endcan
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
    // ── Donut chart: Locker status ──
    new Chart(document.getElementById('lockerDonutChart'), {
        type: 'doughnut',
        data: {
            labels: ['Available', 'In Use', 'Fault', 'Other'],
            datasets: [{
                data: [
                    {{ $lockers['available'] ?? 0 }},
                    {{ $lockers['in_use'] ?? 0 }},
                    {{ $lockers['fault'] ?? 0 }},
                    {{ max(0, ($lockers['total'] ?? 0) - ($lockers['available'] ?? 0) - ($lockers['in_use'] ?? 0) - ($lockers['fault'] ?? 0)) }}
                ],
                backgroundColor: ['#28c76f', '#7367f0', '#ea5455', '#a5a3ae'],
                borderWidth: 0,
                hoverOffset: 4,
            }]
        },
        options: {
            responsive: true,
            cutout: '72%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 16, usePointStyle: true, pointStyleWidth: 8, font: { size: 12 } }
                },
                tooltip: {
                    callbacks: {
                        label: ctx => ` ${ctx.label}: ${ctx.parsed} lockers`
                    }
                }
            }
        }
    });

    // ── Bar chart: Connection status ──
    new Chart(document.getElementById('connectionBarChart'), {
        type: 'bar',
        data: {
            labels: ['Online', 'Warning', 'Offline'],
            datasets: [{
                data: [
                    {{ $connection['online'] ?? 0 }},
                    {{ $connection['warning'] ?? 0 }},
                    {{ $connection['offline'] ?? 0 }}
                ],
                backgroundColor: ['#28c76f', '#ff9f43', '#ea5455'],
                borderRadius: 6,
                barThickness: 40,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f0f0f0', drawBorder: false },
                    ticks: { color: '#a5a3ae', font: { size: 12 } }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: '#5d596c', font: { size: 12 } }
                }
            }
        }
    });
</script>
@endpush
