@extends('layouts.admin')
@section('title', 'Issues')
@section('heading', 'Issue Tracker')
@section('subheading', 'คลิก Status เพื่อเปลี่ยนสถานะได้เลย')

@section('content')

{{-- ── Stat cards ── --}}
@php
    $statCards = [
        ['key' => 'open',       'label' => 'Open',        'dot' => 'bg-[#ea5455]'],
        ['key' => 'inProgress', 'label' => 'In Progress', 'dot' => 'bg-[#7367f0]'],
        ['key' => 'pending',    'label' => 'Pending',     'dot' => 'bg-[#ff9f43]'],
        ['key' => 'resolved',   'label' => 'Resolved',    'dot' => 'bg-[#28c76f]'],
        ['key' => 'closed',     'label' => 'Closed',      'dot' => 'bg-[#a5a3ae]'],
        ['key' => 'critical',   'label' => 'Critical',    'dot' => 'bg-[#ea5455]'],
    ];
@endphp
<div class="mb-5 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
    @foreach($statCards as $card)
    <div class="rounded-xl bg-white p-4 sneat-shadow">
        <div class="mb-1 flex items-center gap-2">
            <span class="h-2 w-2 rounded-full {{ $card['dot'] }}"></span>
            <p class="text-[11px] font-semibold uppercase tracking-wide text-[#a5a3ae]">{{ $card['label'] }}</p>
        </div>
        <p class="text-2xl font-bold text-slate-800">{{ number_format($stats[$card['key']] ?? 0) }}</p>
    </div>
    @endforeach
</div>

{{-- ── Datagrid ── --}}
<div class="mb-4 flex items-center gap-3">
    <input id="tbl-search" placeholder="Search issues…"
           class="w-64 rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
    <p class="text-xs text-[#a5a3ae]">คลิก Status cell เพื่อเปลี่ยน</p>
    @can('create issues')
    <a href="{{ route('admin.issues.create') }}"
       class="ml-auto rounded-lg bg-[#7367f0] px-4 py-2 text-sm font-semibold text-white hover:bg-[#6259d4]">
        + New Issue
    </a>
    @endcan
</div>

<div id="data-table" class="rounded-xl bg-white sneat-shadow overflow-hidden"></div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var CSRF = document.querySelector('meta[name="csrf-token"]').content;

    var statusColors = {
        open:        { bg: '#fde8e9', color: '#ea5455' },
        in_progress: { bg: '#ece9fd', color: '#7367f0' },
        pending:     { bg: '#fff3e8', color: '#ff9f43' },
        resolved:    { bg: '#dff7e9', color: '#28c76f' },
        closed:      { bg: '#f5f5f9', color: '#a5a3ae' },
    };
    var sevColors = {
        critical: { bg: '#fde8e9', color: '#ea5455' },
        high:     { bg: '#fff3e8', color: '#ff9f43' },
        medium:   { bg: '#ece9fd', color: '#7367f0' },
        low:      { bg: '#d9f7fb', color: '#00cfe8' },
    };

    function badgeFmt(val, map) {
        var c = map[val] || { bg: '#f5f5f9', color: '#a5a3ae' };
        var label = val ? val.replace(/_/g, ' ') : '—';
        return '<span style="background:'+c.bg+';color:'+c.color+';border-radius:999px;padding:3px 10px;font-size:11px;font-weight:600;text-transform:capitalize">'+label+'</span>';
    }

    @can('edit issues')
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
        paginationSize: 20,
        paginationSizeSelector: [10, 20, 50, 100],
        placeholder: 'ไม่มี Issue',
        columns: [
            { title: 'Title', field: 'title', headerFilter: 'input', minWidth: 220,
              formatter: function(cell){ return '<span style="font-weight:600;color:#1e293b">'+cell.getValue()+'</span>'; } },
            { title: 'Status', field: 'status', minWidth: 140, headerFilter: 'input',
              editable: function(){ return canEdit; },
              editor: 'select',
              editorParams: { values: { open:'Open', in_progress:'In Progress', pending:'Pending', resolved:'Resolved', closed:'Closed' } },
              formatter: function(cell){ return badgeFmt(cell.getValue(), statusColors); },
              cellEdited: saveStatus },
            { title: 'Severity', field: 'severity', minWidth: 110, editable: false,
              formatter: function(cell){ return badgeFmt(cell.getValue(), sevColors); } },
            { title: 'Company', field: 'company', headerFilter: 'input', minWidth: 130, editable: false },
            { title: '', field: 'id', width: 80, hozAlign: 'right', headerSort: false,
              formatter: function(cell){
                return '<a href="/admin/issues/'+cell.getValue()+'" style="font-size:.75rem;font-weight:600;color:#7367f0">View →</a>';
              }
            },
        ],
    });

    document.getElementById('tbl-search').addEventListener('input', function () {
        table.setFilter([
            [{ field: 'title',    type: 'like', value: this.value },
             { field: 'status',   type: 'like', value: this.value },
             { field: 'severity', type: 'like', value: this.value },
             { field: 'company',  type: 'like', value: this.value }]
        ]);
    });

    function saveStatus(cell) {
        var row = cell.getRow().getData();
        fetch('/admin/issues/' + row.id + '/status', {
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
