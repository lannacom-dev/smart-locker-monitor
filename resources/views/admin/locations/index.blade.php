@extends('layouts.admin')
@section('title', 'Locations')
@section('heading', 'Locations')
@section('subheading', 'คลิกเซลล์เพื่อแก้ไขข้อมูลได้เลย')

@section('content')

<div class="mb-4 flex items-center justify-between">
    <input id="tbl-search" placeholder="Search locations…"
           class="w-64 rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
    <a href="{{ route('admin.locations.create') }}"
       class="inline-flex items-center gap-1.5 rounded-lg bg-[#7367f0] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#6259d4]">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        New Location
    </a>
</div>

<div id="data-table" class="rounded-xl bg-white sneat-shadow overflow-hidden"></div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var CSRF = document.querySelector('meta[name="csrf-token"]').content;

    var table = new Tabulator('#data-table', {
        ajaxURL: '{{ url()->current() }}',
        ajaxParams: { _fmt: 'json' },
        ajaxConfig: { headers: { Accept: 'application/json', 'X-CSRF-TOKEN': CSRF } },
        layout: 'fitColumns',
        pagination: true,
        paginationSize: 20,
        paginationSizeSelector: [10, 20, 50, 100],
        placeholder: 'ไม่พบข้อมูล',
        columns: [
            { title: 'Name', field: 'name', editor: 'input', headerFilter: 'input', minWidth: 160, cellEdited: saveCell },
            { title: 'Address', field: 'address', editor: 'input', minWidth: 200, cellEdited: saveCell,
              formatter: function(cell){ return cell.getValue() || '<span style="color:#dbdade">—</span>'; } },
            { title: 'Company', field: 'company', headerFilter: 'input', minWidth: 130, editable: false },
            { title: '', field: 'id', width: 80, hozAlign: 'right', headerSort: false,
              formatter: function(cell){
                return '<a href="/admin/locations/'+cell.getValue()+'/edit" style="font-size:.75rem;font-weight:600;color:#7367f0">Edit →</a>';
              }
            },
        ],
    });

    document.getElementById('tbl-search').addEventListener('input', function () {
        table.setFilter([
            [{ field: 'name', type: 'like', value: this.value },
             { field: 'address', type: 'like', value: this.value },
             { field: 'company', type: 'like', value: this.value }]
        ]);
    });

    function saveCell(cell) {
        var row = cell.getRow().getData();
        fetch('/admin/locations/' + row.id, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
            body: JSON.stringify({ name: row.name, address: row.address }),
        }).then(function (r) {
            if (r.ok) { sneatToast('บันทึกแล้ว', 'ok'); }
            else { cell.restoreOldValue(); sneatToast('เกิดข้อผิดพลาด', 'error'); }
        }).catch(function () { cell.restoreOldValue(); sneatToast('เกิดข้อผิดพลาด', 'error'); });
    }
});
</script>
@endpush
