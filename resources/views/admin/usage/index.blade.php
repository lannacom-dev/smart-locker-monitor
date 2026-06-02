@extends('layouts.admin')
@section('title', 'Usage')
@section('heading', 'Usage Statistics')
@section('subheading', 'จาก locker_events ในฐานข้อมูล')

@section('content')

{{-- ── Date filter ── --}}
<div class="mb-5 rounded-xl bg-white p-4 sneat-shadow">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">Date From</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                   class="rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">Date To</label>
            <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                   class="rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
        </div>
        <button type="submit"
                class="flex items-center gap-1.5 rounded-lg bg-[#7367f0] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#6259d4]">
            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M2.628 1.601C5.028 1.206 7.49 1 10 1s4.973.206 7.372.601a.75.75 0 01.628.74v2.288a2.25 2.25 0 01-.659 1.59l-4.682 4.683a2.25 2.25 0 00-.659 1.59v3.037c0 .684-.31 1.33-.844 1.757l-1.937 1.55A.75.75 0 018 18.25v-5.757a2.25 2.25 0 00-.659-1.591L2.659 6.22A2.25 2.25 0 012 4.629V2.34a.75.75 0 01.628-.74z" clip-rule="evenodd"/>
            </svg>
            Apply
        </button>
        @if(!empty($filters['date_from']) || !empty($filters['date_to']))
        <a href="{{ route('admin.usage.index') }}"
           class="rounded-lg border border-[#dbdade] px-4 py-2 text-sm font-medium text-[#5d596c] transition-colors hover:border-[#ea5455] hover:bg-[#fde8e9] hover:text-[#ea5455]">
            Clear
        </a>
        @endif
    </form>
</div>

{{-- ── Stat cards ── --}}
<div class="mb-5 grid gap-4 sm:grid-cols-3">
    {{-- Lockers Total --}}
    <div class="rounded-xl bg-white p-5 sneat-shadow">
        <div class="mb-3 flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-wide text-[#a5a3ae]">Lockers Total</p>
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#ece9fd]">
                <svg class="h-5 w-5 text-[#7367f0]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                </svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-slate-800">{{ number_format($lockers['total'] ?? 0) }}</p>
        <p class="mt-1 text-xs text-[#a5a3ae]">registered lockers</p>
    </div>

    {{-- Boxes Occupied --}}
    <div class="rounded-xl bg-white p-5 sneat-shadow">
        <div class="mb-3 flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-wide text-[#a5a3ae]">Boxes Occupied</p>
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#dff7e9]">
                <svg class="h-5 w-5 text-[#28c76f]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-slate-800">{{ number_format($boxes['occupied'] ?? 0) }}</p>
        @php
            $total = $boxes['total'] ?? 0;
            $occupied = $boxes['occupied'] ?? 0;
            $pct = $total > 0 ? round(($occupied / $total) * 100) : 0;
        @endphp
        <div class="mt-2 flex items-center gap-2">
            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-[#f5f5f9]">
                <div class="h-full rounded-full bg-[#28c76f]" style="width: {{ $pct }}%"></div>
            </div>
            <span class="text-xs font-medium text-[#28c76f]">{{ $pct }}%</span>
        </div>
        <p class="mt-1 text-xs text-[#a5a3ae]">of {{ number_format($total) }} total boxes</p>
    </div>

    {{-- Trend Period --}}
    <div class="rounded-xl bg-white p-5 sneat-shadow">
        <div class="mb-3 flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-wide text-[#a5a3ae]">Trend Period</p>
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#fff3e8]">
                <svg class="h-5 w-5 text-[#ff9f43]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                </svg>
            </div>
        </div>
        <p class="text-3xl font-bold text-slate-800">{{ count($trend['data'] ?? []) }}</p>
        <p class="mt-1 text-xs text-[#a5a3ae]">days in selected range</p>
    </div>
</div>

{{-- ── Charts + Top lockers row ── --}}
<div class="grid gap-5 lg:grid-cols-5">

    {{-- Trend Chart (3/5) --}}
    @if(!empty($trend['labels']) && count($trend['labels']) > 0)
    <div class="overflow-hidden rounded-xl bg-white sneat-shadow lg:col-span-3">
        <div class="border-b border-[#dbdade] px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-800">Event Trend</h2>
            <p class="text-xs text-[#a5a3ae]">ปริมาณ events ต่อวัน</p>
        </div>
        <div class="p-5">
            <canvas id="trendChart" height="220"></canvas>
        </div>
    </div>
    @endif

    {{-- Top Lockers (2/5 or full) --}}
    @php $topLabels = $top['labels'] ?? []; $topData = $top['data'] ?? []; @endphp
    @if(count($topLabels) > 0)
    <div class="overflow-hidden rounded-xl bg-white sneat-shadow {{ !empty($trend['labels']) && count($trend['labels']) > 0 ? 'lg:col-span-2' : 'lg:col-span-5' }}">
        <div class="border-b border-[#dbdade] px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-800">Top Lockers</h2>
            <p class="text-xs text-[#a5a3ae]">จำนวน events มากที่สุด</p>
        </div>
        <div class="divide-y divide-[#f0f0f0]">
            @php $maxVal = max($topData ?: [1]); @endphp
            @foreach($topLabels as $i => $name)
            @php
                $val = $topData[$i] ?? 0;
                $barPct = $maxVal > 0 ? round(($val / $maxVal) * 100) : 0;
                $barColors = ['bg-[#7367f0]','bg-[#28c76f]','bg-[#ff9f43]','bg-[#00cfe8]','bg-[#ea5455]'];
                $barColor = $barColors[$i % count($barColors)];
            @endphp
            <div class="flex items-center gap-3 px-5 py-3">
                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full {{ $barColor }} text-xs font-bold text-white">
                    {{ $i + 1 }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-slate-800">{{ $name }}</p>
                    <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-[#f5f5f9]">
                        <div class="h-full rounded-full {{ $barColor }}" style="width: {{ $barPct }}%"></div>
                    </div>
                </div>
                <span class="shrink-0 text-sm font-bold text-slate-800">{{ number_format($val) }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="overflow-hidden rounded-xl bg-white sneat-shadow lg:col-span-2">
        <div class="border-b border-[#dbdade] px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-800">Top Lockers</h2>
        </div>
        <div class="flex flex-col items-center gap-3 py-14 text-center text-[#a5a3ae]">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f5f5f9]">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                </svg>
            </div>
            <p class="font-medium text-[#5d596c]">ไม่มีข้อมูล</p>
        </div>
    </div>
    @endif

</div>

@endsection

@push('scripts')
@if(!empty($trend['labels']) && count($trend['labels']) > 0)
<script>
(function () {
    const labels = @json($trend['labels'] ?? []);
    const data   = @json($trend['data']   ?? []);

    const ctx = document.getElementById('trendChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Events',
                data,
                fill: true,
                backgroundColor: 'rgba(115,103,240,.10)',
                borderColor: '#7367f0',
                borderWidth: 2,
                pointBackgroundColor: '#7367f0',
                pointRadius: 4,
                pointHoverRadius: 6,
                tension: 0.4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#fff',
                    titleColor: '#5d596c',
                    bodyColor: '#5d596c',
                    borderColor: '#dbdade',
                    borderWidth: 1,
                    padding: 10,
                    callbacks: {
                        label: ctx => ' ' + ctx.parsed.y.toLocaleString() + ' events',
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: '#f5f5f9' },
                    ticks: { color: '#a5a3ae', font: { size: 11 } },
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f5f5f9' },
                    ticks: { color: '#a5a3ae', font: { size: 11 }, precision: 0 },
                }
            }
        }
    });
})();
</script>
@endif
@endpush
