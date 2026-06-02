@extends('layouts.admin')
@section('title', 'Locker Users')
@section('heading', 'Locker Users')
@section('subheading', 'คลิกเซลล์เพื่อแก้ไข name / email / phone ได้เลย')

@section('content')

<div class="mb-4">
    <input id="tbl-search" placeholder="Search users…"
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
        placeholder: 'ไม่พบผู้ใช้งาน',
        columns: [
            { title: 'Name', field: 'name', editor: 'input', headerFilter: 'input', minWidth: 150, cellEdited: saveCell },
            { title: 'Email', field: 'email', editor: 'input', minWidth: 180, cellEdited: saveCell,
              formatter: function(cell){ return cell.getValue() || '<span style="color:#dbdade">—</span>'; } },
            { title: 'Phone', field: 'phone', editor: 'input', minWidth: 120, cellEdited: saveCell,
              formatter: function(cell){ return cell.getValue() || '<span style="color:#dbdade">—</span>'; } },
            { title: 'Company', field: 'company', headerFilter: 'input', minWidth: 130, editable: false },
            { title: '', field: 'id', width: 80, hozAlign: 'right', headerSort: false,
              formatter: function(cell){
                return '<a href="/admin/locker-users/'+cell.getValue()+'" style="font-size:.75rem;font-weight:600;color:#7367f0">View →</a>';
              }
            },
        ],
    });

    document.getElementById('tbl-search').addEventListener('input', function () {
        table.setFilter([
            [{ field: 'name', type: 'like', value: this.value },
             { field: 'email', type: 'like', value: this.value },
             { field: 'phone', type: 'like', value: this.value },
             { field: 'company', type: 'like', value: this.value }]
        ]);
    });

    function saveCell(cell) {
        var row = cell.getRow().getData();
        fetch('/admin/locker-users/' + row.id, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
            body: JSON.stringify({ name: row.name, email: row.email, phone: row.phone }),
        }).then(function (r) {
            if (r.ok) { sneatToast('บันทึกแล้ว', 'ok'); }
            else { cell.restoreOldValue(); sneatToast('เกิดข้อผิดพลาด', 'error'); }
        }).catch(function () { cell.restoreOldValue(); sneatToast('เกิดข้อผิดพลาด', 'error'); });
    }
});
</script>
@endpush
