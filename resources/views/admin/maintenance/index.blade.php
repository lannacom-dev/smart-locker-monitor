@extends('layouts.admin')
@section('title', 'Maintenance')
@section('heading', 'Corrective Maintenance')
@section('subheading', 'คลิก Status เพื่อเปลี่ยนสถานะได้เลย')

@section('content')

<div class="mb-4 flex items-center gap-3">
    <input id="tbl-search" placeholder="Search maintenance…"
           class="w-64 rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
    <p class="text-xs text-[#a5a3ae]">คลิก Status cell เพื่อเปลี่ยน</p>
    @can('create maintenance')
    <a href="{{ route('admin.maintenance.create') }}"
       class="ml-auto rounded-lg bg-[#7367f0] px-4 py-2 text-sm font-semibold text-white hover:bg-[#6259d4]">
        + New Maintenance
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
        completed:   { bg: '#dff7e9', color: '#28c76f' },
        closed:      { bg: '#f5f5f9', color: '#a5a3ae' },
    };
    var priColors = {
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

    @can('edit maintenance')
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
        placeholder: 'ไม่มีรายการซ่อมบำรุง',
        columns: [
            { title: 'Title', field: 'title', headerFilter: 'input', minWidth: 220,
              formatter: function(cell){ return '<span style="font-weight:600;color:#1e293b">'+cell.getValue()+'</span>'; } },
            { title: 'Status', field: 'status', minWidth: 140, headerFilter: 'input',
              editable: function(){ return canEdit; },
              editor: 'select',
              editorParams: { values: { open:'Open', in_progress:'In Progress', completed:'Completed', closed:'Closed' } },
              formatter: function(cell){ return badgeFmt(cell.getValue(), statusColors); },
              cellEdited: saveStatus },
            { title: 'Priority', field: 'priority', minWidth: 110, editable: false,
              formatter: function(cell){ return badgeFmt(cell.getValue(), priColors); } },
            { title: 'Company', field: 'company', headerFilter: 'input', minWidth: 130, editable: false },
            { title: '', field: 'id', width: 80, hozAlign: 'right', headerSort: false,
              formatter: function(cell){
                return '<a href="/admin/maintenance/'+cell.getValue()+'" style="font-size:.75rem;font-weight:600;color:#7367f0">View →</a>';
              }
            },
        ],
    });

    document.getElementById('tbl-search').addEventListener('input', function () {
        table.setFilter([
            [{ field: 'title',    type: 'like', value: this.value },
             { field: 'status',   type: 'like', value: this.value },
             { field: 'priority', type: 'like', value: this.value },
             { field: 'company',  type: 'like', value: this.value }]
        ]);
    });

    function saveStatus(cell) {
        var row = cell.getRow().getData();
        fetch('/admin/maintenance/' + row.id + '/transition', {
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
