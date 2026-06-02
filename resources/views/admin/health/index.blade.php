@extends('layouts.admin')
@section('title', 'System Health')
@section('heading', 'System Health')
@section('subheading', 'ตรวจสอบสถานะระบบและ alerts ที่ยังค้างอยู่')

@section('content')

{{-- ── Overall health banner ── --}}
@php
    [$bannerBg, $bannerText, $iconBg, $iconColor, $overallLabel] = match($overall) {
        'healthy' => ['bg-[#dff7e9] border-[#b8efce]', 'text-[#1a6b3c]', 'bg-[#28c76f]', 'text-white', 'HEALTHY'],
        'warning' => ['bg-[#fff3e8] border-[#ffd8aa]', 'text-[#7a4400]', 'bg-[#ff9f43]', 'text-white', 'WARNING'],
        default   => ['bg-[#fde8e9] border-[#f5b7b7]', 'text-[#8f1a1a]', 'bg-[#ea5455]', 'text-white', 'CRITICAL'],
    };
@endphp

<div class="mb-6 flex items-center gap-4 rounded-xl border {{ $bannerBg }} px-5 py-4 sneat-shadow">
    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $iconBg }}">
        @if($overall === 'healthy')
        <svg class="h-6 w-6 {{ $iconColor }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        @else
        <svg class="h-6 w-6 {{ $iconColor }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
        </svg>
        @endif
    </div>
    <div>
        <p class="text-[11px] font-semibold uppercase tracking-widest {{ $bannerText }} opacity-60">Overall Status</p>
        <p class="text-xl font-bold {{ $bannerText }}">{{ $overallLabel }}</p>
    </div>
</div>

{{-- ── Health checks ── --}}
@if($checks->count())
<div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach($checks as $check)
    @php
        [$cBg, $cText, $cBar] = match(true) {
            in_array(strtolower($check->status ?? ''), ['healthy','ok','good']) => ['bg-[#dff7e9]','text-[#28c76f]','bg-[#28c76f]'],
            strtolower($check->status ?? '') === 'warning'                      => ['bg-[#fff3e8]','text-[#ff9f43]','bg-[#ff9f43]'],
            in_array(strtolower($check->status ?? ''), ['critical','error'])    => ['bg-[#fde8e9]','text-[#ea5455]','bg-[#ea5455]'],
            default                                                              => ['bg-[#f5f5f9]','text-[#a5a3ae]','bg-[#a5a3ae]'],
        };
    @endphp
    <div class="rounded-xl bg-white p-5 sneat-shadow">
        <div class="mb-3 flex items-center justify-between gap-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-[#a5a3ae]">{{ $check->check_type }}</p>
            <span class="inline-flex items-center rounded-full {{ $cBg }} {{ $cText }} px-2.5 py-0.5 text-xs font-semibold capitalize">
                {{ $check->status }}
            </span>
        </div>
        <div class="flex items-end justify-between">
            <div>
                <p class="text-xs text-[#a5a3ae]">Score</p>
                <p class="text-2xl font-bold {{ $cText }}">{{ $check->score }}</p>
            </div>
        </div>
        <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-[#f5f5f9]">
            <div class="h-full rounded-full {{ $cBar }}" style="width: {{ min(100, max(0, (int)$check->score)) }}%"></div>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- ── Alerts table ── --}}
@if($alerts->count())
<div class="overflow-hidden rounded-xl bg-white sneat-shadow">
    <div class="flex items-center justify-between border-b border-[#dbdade] px-5 py-4">
        <h2 class="flex items-center gap-2 text-sm font-semibold text-slate-800">
            <svg class="h-4 w-4 text-[#ff9f43]" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
            </svg>
            Active Alerts
        </h2>
        <span class="rounded-full bg-[#fde8e9] px-2.5 py-0.5 text-xs font-bold text-[#ea5455]">{{ $alerts->count() }}</span>
    </div>
    <table class="min-w-full text-sm">
        <thead class="border-b border-[#dbdade] bg-[#f5f5f9]">
            <tr>
                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#a5a3ae]">Alert</th>
                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#a5a3ae]">Severity</th>
                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#a5a3ae]">Status</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[#f0f0f0]">
            @foreach($alerts as $alert)
            @php
                $sevBadge = match(strtolower($alert->severity ?? '')) {
                    'critical' => 'bg-[#fde8e9] text-[#ea5455]',
                    'warning'  => 'bg-[#fff3e8] text-[#ff9f43]',
                    'info'     => 'bg-[#d9f7fb] text-[#00cfe8]',
                    default    => 'bg-[#f5f5f9] text-[#a5a3ae]',
                };
                $statBadge = $alert->status === 'open'
                    ? 'bg-[#fde8e9] text-[#ea5455]'
                    : 'bg-[#dff7e9] text-[#28c76f]';
            @endphp
            <tr class="hover:bg-[#f9f9fc] transition-colors">
                <td class="px-5 py-3.5 font-medium text-slate-800">{{ $alert->title }}</td>
                <td class="px-5 py-3.5">
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium capitalize {{ $sevBadge }}">{{ $alert->severity }}</span>
                </td>
                <td class="px-5 py-3.5">
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium capitalize {{ $statBadge }}">{{ $alert->status }}</span>
                </td>
                <td class="px-5 py-3.5 text-right">
                    @if($alert->status === 'open' && auth()->user()->can('acknowledge alerts'))
                    <form method="POST" action="{{ route('admin.health.acknowledge', $alert) }}">@csrf
                        <button type="submit"
                                class="rounded-lg border border-[#dbdade] px-3 py-1.5 text-xs font-medium text-[#5d596c] transition-colors hover:border-[#7367f0] hover:bg-[#ece9fd] hover:text-[#7367f0]">
                            Acknowledge
                        </button>
                    </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div class="flex flex-col items-center gap-3 rounded-xl border border-[#b8efce] bg-[#dff7e9] py-14 text-center">
    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#28c76f]">
        <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>
    <div>
        <p class="font-semibold text-[#1a6b3c]">ไม่มี Alert ที่ต้องดำเนินการ</p>
        <p class="text-xs text-[#28c76f]">ระบบทำงานปกติทั้งหมด</p>
    </div>
</div>
@endif

@endsection
