@extends('layouts.admin')
@section('title', 'Live Monitor')
@section('heading', 'Locker Status Monitor')
@section('subheading', 'อ่านจากฐานข้อมูล — กด Sync เพื่ออัปเดตจาก SmartLocker API')

@section('content')

{{-- ── API Sync status banner ── --}}
@php
    $apiOk = isset($lastSync) && $lastSync?->finished_at && $lastSync->finished_at->gt(now()->subMinutes(10));
    $offlineCount = $lockers->where('connection_status', 'offline')->count();
@endphp
@if(!$apiOk || $offlineCount > 0)
<div class="mb-5 flex items-start gap-3 rounded-xl border border-[#f5c6c6] bg-[#fde8e9] px-4 py-3">
    <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#ea5455]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
    </svg>
    <div class="text-sm text-[#ea5455]">
        <span class="font-semibold">เชื่อมต่อ api ไม่ได้</span>
        @if($offlineCount > 0)
        — {{ $offlineCount }} locker{{ $offlineCount > 1 ? 's' : '' }} แสดงสถานะ offline
        @endif
        @if(!$apiOk)
        — Sync ล่าสุด: {{ isset($lastSync) ? ($lastSync->finished_at?->diffForHumans() ?? 'ไม่ทราบ') : 'ยังไม่เคย Sync' }}
        @endif
        <span class="ml-2 text-xs text-[#ea5455]/70">ข้อมูลที่แสดงอาจไม่ใช่ปัจจุบัน — กด Sync เพื่ออัปเดต</span>
    </div>
</div>
@endif

{{-- ── Status summary ── --}}
@php
    $summary = [
        'available' => ['bg-[#dff7e9]', 'text-[#28c76f]', 'Available'],
        'in_use'    => ['bg-[#ece9fd]', 'text-[#7367f0]', 'In Use'],
        'fault'     => ['bg-[#fde8e9]', 'text-[#ea5455]', 'Fault'],
        'offline'   => ['bg-[#f5f5f9]', 'text-[#a5a3ae]', 'Offline'],
        'disabled'  => ['bg-[#fff3e8]', 'text-[#ff9f43]', 'Disabled'],
    ];
@endphp

<div class="mb-5 grid grid-cols-2 gap-4 sm:grid-cols-5">
    @foreach($summary as $key => [$bg, $color, $label])
    <div class="rounded-xl bg-white p-4 text-center sneat-shadow">
        <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-lg {{ $bg }}">
            <span class="text-lg font-bold {{ $color }}">{{ $counts[$key] ?? 0 }}</span>
        </div>
        <p class="text-xs font-medium text-[#a5a3ae]">{{ $label }}</p>
    </div>
    @endforeach
</div>

{{-- ── Search bar ── --}}
<div class="mb-4 flex items-center gap-3">
    <input id="tbl-search" placeholder="Search lockers…"
           class="w-64 rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
    @can('edit lockers')
    <p class="text-xs text-[#a5a3ae]">คลิก Status cell เพื่อเปลี่ยน</p>
    @endcan
</div>

{{-- ── Datagrid ── --}}
<div id="data-table" class="rounded-xl bg-white sneat-shadow overflow-hidden"></div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var CSRF = document.querySelector('meta[name="csrf-token"]').content;

    var statusColors = {
        available: { bg: '#dff7e9', color: '#28c76f' },
        in_use:    { bg: '#ece9fd', color: '#7367f0' },
        fault:     { bg: '#fde8e9', color: '#ea5455' },
        offline:   { bg: '#f5f5f9', color: '#a5a3ae' },
        disabled:  { bg: '#fff3e8', color: '#ff9f43' },
    };
    var connColors = {
        online:  '#28c76f',
        warning: '#ff9f43',
        offline: '#ea5455',
    };

    @can('edit lockers')
    var canEdit = true;
    @else
    var canEdit = false;
    @endcan

    var table = new Tabulator('#data-table', {
        ajaxURL: '{{ url()->current() }}',
        ajaxParams: { _fmt: 'json' },
        ajaxConfig: { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF } },
        layout: 'fitColumns',
        pagination: true,
        paginationSize: 25,
        paginationSizeSelector: [10, 25, 50, 100],
        placeholder: 'ไม่พบ Locker — กด Sync เพื่ออัปเดตข้อมูล',
        initialSort: [{ column: 'name', dir: 'asc' }],
        columns: [
            { title: 'Locker', field: 'name', headerFilter: 'input', minWidth: 150,
              formatter: function(cell){
                var row = cell.getRow().getData();
                return '<a href="/admin/lockers/'+row.id+'" style="font-weight:700;color:#7367f0">'+cell.getValue()+'</a>';
              }
            },
            { title: 'Company', field: 'company', headerFilter: 'input', minWidth: 130, editable: false },
            { title: 'Location', field: 'location', headerFilter: 'input', minWidth: 120, editable: false },
            { title: 'Status', field: 'status', minWidth: 130, headerFilter: 'input',
              editable: function(){ return canEdit; },
              editor: 'select',
              editorParams: { values: { available:'Available', in_use:'In Use', fault:'Fault', offline:'Offline', disabled:'Disabled' } },
              formatter: function(cell){
                var v = cell.getValue();
                var c = statusColors[v] || { bg: '#f5f5f9', color: '#a5a3ae' };
                var label = v ? v.replace(/_/g, ' ') : '—';
                return '<span style="background:'+c.bg+';color:'+c.color+';border-radius:999px;padding:3px 10px;font-size:11px;font-weight:600;text-transform:capitalize">'+label+'</span>';
              },
              cellEdited: saveStatus },
            { title: 'Connection', field: 'connection_status', minWidth: 120, editable: false,
              formatter: function(cell){
                var v = cell.getValue() || '';
                var dot = connColors[v] || '#dbdade';
                return '<span style="display:flex;align-items:center;gap:6px">'
                    + '<span style="width:8px;height:8px;border-radius:50%;background:'+dot+';flex-shrink:0"></span>'
                    + (v || '—') + '</span>';
              }
            },
            { title: 'Last seen', field: 'last_seen', minWidth: 110, editable: false,
              formatter: function(cell){ return '<span style="font-size:11px;color:#a5a3ae">'+cell.getValue()+'</span>'; } },
        ],
    });

    document.getElementById('tbl-search').addEventListener('input', function () {
        table.setFilter([
            [{ field: 'name',              type: 'like', value: this.value },
             { field: 'company',           type: 'like', value: this.value },
             { field: 'location',          type: 'like', value: this.value },
             { field: 'status',            type: 'like', value: this.value },
             { field: 'connection_status', type: 'like', value: this.value }]
        ]);
    });

    function saveStatus(cell) {
        var row = cell.getRow().getData();
        fetch('/admin/lockers/' + row.id + '/status', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
            body: JSON.stringify({ status: row.status }),
        }).then(function (r) {
            if (r.ok) { sneatToast('อัปเดตสถานะแล้ว', 'ok'); }
            else { cell.restoreOldValue(); sneatToast('เกิดข้อผิดพลาด', 'error'); }
        }).catch(function () { cell.restoreOldValue(); sneatToast('เกิดข้อผิดพลาด', 'error'); });
    }
});
</script>
@endpush
