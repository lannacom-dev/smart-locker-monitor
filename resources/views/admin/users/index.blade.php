@extends('layouts.admin')
@section('title', 'Users')
@section('heading', 'Admin Users')
@section('subheading', 'ค้นหา / เรียง — คลิก Edit เพื่อแก้ไข roles')

@section('content')

<div class="mb-4 flex items-center gap-3">
    <input id="tbl-search" placeholder="Search users…"
           class="w-64 rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
    @can('create users')
    <a href="{{ route('admin.users.create') }}"
       class="ml-auto rounded-lg bg-[#7367f0] px-4 py-2 text-sm font-semibold text-white hover:bg-[#6259d4]">
        + New User
    </a>
    @endcan
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
            { title: 'Name', field: 'name', headerFilter: 'input', minWidth: 150,
              formatter: function(cell){ return '<span style="font-weight:600;color:#1e293b">'+cell.getValue()+'</span>'; } },
            { title: 'Email', field: 'email', headerFilter: 'input', minWidth: 200 },
            { title: 'Roles', field: 'roles', minWidth: 160, headerSort: false,
              formatter: function(cell){
                var v = cell.getValue();
                if (!v) return '<span style="color:#dbdade">—</span>';
                return v.split(', ').map(function(r){
                    return '<span style="background:#ece9fd;color:#7367f0;border-radius:999px;padding:2px 10px;font-size:11px;font-weight:600;margin-right:3px">'+r+'</span>';
                }).join('');
              }
            },
            { title: 'Company', field: 'company', headerFilter: 'input', minWidth: 130 },
            { title: '', field: 'id', width: 80, hozAlign: 'right', headerSort: false,
              formatter: function(cell){
                return '<a href="/admin/users/'+cell.getValue()+'" style="font-size:.75rem;font-weight:600;color:#7367f0">Edit →</a>';
              }
            },
        ],
    });

    document.getElementById('tbl-search').addEventListener('input', function () {
        table.setFilter([
            [{ field: 'name', type: 'like', value: this.value },
             { field: 'email', type: 'like', value: this.value },
             { field: 'roles', type: 'like', value: this.value },
             { field: 'company', type: 'like', value: this.value }]
        ]);
    });
});
</script>
@endpush
