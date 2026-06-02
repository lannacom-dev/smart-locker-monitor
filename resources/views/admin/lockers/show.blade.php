@extends('layouts.admin')
@section('title', $locker->name)
@section('heading', $locker->name)
@section('subheading', 'Locker details and status history')

@section('content')

{{-- ── API offline banner ── --}}
@if(($locker->connection_status ?? '') === 'offline')
<div class="mb-5 flex items-start gap-3 rounded-xl border border-[#f5c6c6] bg-[#fde8e9] px-4 py-3">
    <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#ea5455]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
    </svg>
    <div class="text-sm text-[#ea5455]">
        <span class="font-semibold">เชื่อมต่อ api ไม่ได้</span>
        — Locker นี้ไม่ตอบสนองต่อการเชื่อมต่อ
        @if($locker->last_seen_at)
        <span class="ml-1 text-xs text-[#ea5455]/70">ข้อมูลล่าสุด: {{ $locker->last_seen_at->diffForHumans() }} ({{ $locker->last_seen_at->toDateTimeString() }})</span>
        @else
        <span class="ml-1 text-xs text-[#ea5455]/70">ไม่มีข้อมูลการเชื่อมต่อ</span>
        @endif
    </div>
</div>
@endif

<div class="grid gap-5 lg:grid-cols-2">

    {{-- Info card --}}
    <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
        <div class="border-b border-[#dbdade] px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-800">Locker Info</h2>
        </div>
        <div class="divide-y divide-[#f0f0f0]">
            @php
                $statusBadge = match($locker->status) {
                    'available' => 'bg-[#dff7e9] text-[#28c76f]',
                    'in_use'    => 'bg-[#ece9fd] text-[#7367f0]',
                    'fault'     => 'bg-[#fde8e9] text-[#ea5455]',
                    'offline'   => 'bg-[#f5f5f9] text-[#a5a3ae]',
                    'disabled'  => 'bg-[#fff3e8] text-[#ff9f43]',
                    default     => 'bg-[#f5f5f9] text-[#a5a3ae]',
                };
                $connDot = match($locker->connection_status ?? '') {
                    'online'  => 'bg-[#28c76f]',
                    'warning' => 'bg-[#ff9f43]',
                    'offline' => 'bg-[#ea5455]',
                    default   => 'bg-[#dbdade]',
                };
            @endphp
            <div class="flex items-center justify-between px-5 py-3">
                <span class="text-xs font-medium text-[#a5a3ae]">Status</span>
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium capitalize {{ $statusBadge }}">
                    {{ str_replace('_', ' ', $locker->status) }}
                </span>
            </div>
            <div class="flex items-center justify-between px-5 py-3">
                <span class="text-xs font-medium text-[#a5a3ae]">Connection</span>
                <span class="flex items-center gap-2 text-sm text-[#5d596c]">
                    <span class="h-2 w-2 rounded-full {{ $connDot }}"></span>
                    {{ $locker->connection_status ?? '—' }}
                </span>
            </div>
            <div class="flex items-center justify-between px-5 py-3">
                <span class="text-xs font-medium text-[#a5a3ae]">Company</span>
                <span class="text-sm text-[#5d596c]">{{ $locker->company?->name ?? '—' }}</span>
            </div>
            <div class="flex items-center justify-between px-5 py-3">
                <span class="text-xs font-medium text-[#a5a3ae]">Location</span>
                <span class="text-sm text-[#5d596c]">{{ $locker->location?->name ?? '—' }}</span>
            </div>
            <div class="flex items-center justify-between px-5 py-3">
                <span class="text-xs font-medium text-[#a5a3ae]">External Unit ID</span>
                <span class="font-mono text-sm text-[#5d596c]">{{ $locker->external_unit_id ?? '—' }}</span>
            </div>
            <div class="flex items-center justify-between px-5 py-3">
                <span class="text-xs font-medium text-[#a5a3ae]">Last seen</span>
                <span class="text-sm text-[#5d596c]">{{ $locker->last_seen_at?->toDateTimeString() ?? '—' }}</span>
            </div>
        </div>
        @can('edit lockers')
        <div class="border-t border-[#dbdade] px-5 py-3">
            <a href="{{ route('admin.lockers.edit', $locker) }}"
               class="text-xs font-medium text-[#7367f0] hover:underline">Edit locker →</a>
        </div>
        @endcan
    </div>

    {{-- Status log card --}}
    <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
        <div class="border-b border-[#dbdade] px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-800">Recent Status Logs</h2>
        </div>
        <div class="max-h-96 divide-y divide-[#f0f0f0] overflow-y-auto">
            @forelse($locker->statusLogs as $log)
            <div class="flex items-center gap-3 px-5 py-3">
                <div class="flex items-center gap-2 text-xs">
                    <span class="rounded-full bg-[#fde8e9] px-2 py-0.5 text-[#ea5455]">{{ $log->old_status }}</span>
                    <svg class="h-3 w-3 text-[#a5a3ae]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                    </svg>
                    <span class="rounded-full bg-[#dff7e9] px-2 py-0.5 text-[#28c76f]">{{ $log->new_status }}</span>
                </div>
                <span class="ml-auto text-xs text-[#a5a3ae]">{{ $log->changedBy?->name ?? 'System' }}</span>
            </div>
            @empty
            <div class="flex flex-col items-center gap-3 py-12 text-center text-[#a5a3ae]">
                <p class="text-sm">No status logs yet</p>
            </div>
            @endforelse
        </div>
    </div>

</div>

{{-- ── Locker boxes grid ── --}}
@if($locker->boxes->isNotEmpty())
<div class="mt-5 overflow-hidden rounded-xl bg-white sneat-shadow">
    <div class="flex items-center justify-between border-b border-[#dbdade] px-5 py-4">
        <div>
            <h2 class="text-sm font-semibold text-slate-800">Locker Boxes</h2>
            <p class="text-xs text-[#a5a3ae]">{{ $locker->boxes->count() }} ช่องทั้งหมด</p>
        </div>
        @php
            $boxStats = [
                'available' => $locker->boxes->where('status','available')->count(),
                'occupied'  => $locker->boxes->where('status','occupied')->count(),
                'open'      => $locker->boxes->where('status','open')->count(),
                'error'     => $locker->boxes->where('status','error')->count(),
                'disabled'  => $locker->boxes->where('status','disabled')->count(),
            ];
        @endphp
        <div class="flex items-center gap-3 text-xs">
            @if($boxStats['available']) <span class="rounded-full bg-[#dff7e9] px-2 py-0.5 font-medium text-[#28c76f]">{{ $boxStats['available'] }} ว่าง</span> @endif
            @if($boxStats['occupied'])  <span class="rounded-full bg-[#ece9fd] px-2 py-0.5 font-medium text-[#7367f0]">{{ $boxStats['occupied'] }} ใช้อยู่</span> @endif
            @if($boxStats['open'])      <span class="rounded-full bg-[#d9f7fb] px-2 py-0.5 font-medium text-[#00cfe8]">{{ $boxStats['open'] }} เปิด</span> @endif
            @if($boxStats['error'])     <span class="rounded-full bg-[#fde8e9] px-2 py-0.5 font-medium text-[#ea5455]">{{ $boxStats['error'] }} error</span> @endif
        </div>
    </div>
    <div class="grid grid-cols-4 gap-3 p-5 sm:grid-cols-6 lg:grid-cols-8">
        @foreach($locker->boxes->sortBy('box_number') as $box)
        @php
            [$boxBg, $boxText, $boxLabel] = match($box->status) {
                'available' => ['bg-[#dff7e9]', 'text-[#28c76f]', 'ว่าง'],
                'occupied'  => ['bg-[#ece9fd]', 'text-[#7367f0]', 'ใช้อยู่'],
                'open'      => ['bg-[#d9f7fb]', 'text-[#00cfe8]', 'เปิด'],
                'error'     => ['bg-[#fde8e9]', 'text-[#ea5455]', 'Error'],
                'disabled'  => ['bg-[#fff3e8]', 'text-[#ff9f43]', 'ปิด'],
                default     => ['bg-[#f5f5f9]', 'text-[#a5a3ae]', $box->status],
            };
        @endphp
        <div class="flex flex-col items-center rounded-lg {{ $boxBg }} p-3 text-center"
             title="Box {{ $box->box_number }} — {{ $box->status }}{{ $box->last_opened_at ? ' — เปิดล่าสุด '.$box->last_opened_at->diffForHumans() : '' }}">
            <span class="text-lg font-bold {{ $boxText }}">{{ $box->box_number }}</span>
            <span class="mt-0.5 text-[10px] font-medium {{ $boxText }}">{{ $boxLabel }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif

<div class="mt-4">
    <a href="{{ route('admin.lockers.index') }}"
       class="inline-flex items-center gap-1 text-sm font-medium text-[#7367f0] hover:underline">
        ← Back to lockers
    </a>
</div>

@endsection
