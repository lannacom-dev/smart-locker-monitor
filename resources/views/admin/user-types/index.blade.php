@extends('layouts.admin')
@section('title', 'User Types')
@section('heading', 'Locker User Types')
@section('subheading', 'คลิกชื่อเพื่อแก้ไขได้เลย')

@section('content')

<div class="mb-4">
    <input id="tbl-search" placeholder="Search user types…"
           class="w-64 rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
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
            { title: 'Name', field: 'name', editor: 'input', headerFilter: 'input', minWidth: 200,
              formatter: function(cell){ return '<span style="font-weight:600;color:#1e293b">'+cell.getValue()+'</span>'; },
              cellEdited: saveCell },
            { title: 'Company', field: 'company', headerFilter: 'input', minWidth: 160, editable: false },
        ],
    });

    document.getElementById('tbl-search').addEventListener('input', function () {
        table.setFilter([
            [{ field: 'name', type: 'like', value: this.value },
             { field: 'company', type: 'like', value: this.value }]
        ]);
    });

    function saveCell(cell) {
        var row = cell.getRow().getData();
        fetch('/admin/user-types/' + row.id, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
            body: JSON.stringify({ name: row.name }),
        }).then(function (r) {
            if (r.ok) { sneatToast('บันทึกแล้ว', 'ok'); }
            else { cell.restoreOldValue(); sneatToast('เกิดข้อผิดพลาด', 'error'); }
        }).catch(function () { cell.restoreOldValue(); sneatToast('เกิดข้อผิดพลาด', 'error'); });
    }
});
</script>
@endpush
