@extends('layouts.admin')
@section('title', 'Roles')
@section('heading', 'Role Permissions')
@section('subheading', 'จัดการสิทธิ์ของแต่ละ role')

@section('content')

{{-- ── Create new role ── --}}
<div class="mb-6 overflow-hidden rounded-xl bg-white sneat-shadow">
    <div class="border-b border-[#dbdade] px-5 py-4">
        <h2 class="text-sm font-semibold text-slate-800">สร้าง Role ใหม่</h2>
        <p class="mt-0.5 text-xs text-[#a5a3ae]">ใช้ได้เฉพาะ a-z, 0-9 และ _ เช่น <code class="rounded bg-[#f5f5f9] px-1">site_manager</code></p>
    </div>
    <form method="POST" action="{{ route('admin.roles.store') }}" class="flex items-start gap-3 p-5">
        @csrf
        <div class="flex-1">
            <input name="name" value="{{ old('name') }}" placeholder="ชื่อ role เช่น site_manager"
                   class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0] @error('name') border-[#ea5455] @enderror">
            @error('name')<p class="mt-1 text-xs text-[#ea5455]">{{ $message }}</p>@enderror
        </div>
        <button type="submit"
                class="shrink-0 rounded-lg bg-[#7367f0] px-4 py-2 text-sm font-semibold text-white hover:bg-[#6259d4]">
            + สร้าง Role
        </button>
    </form>
</div>

{{-- ── Role cards ── --}}
@php $protected = ['super_admin', 'tenant_admin', 'viewer']; @endphp
<div class="space-y-5">
    @foreach($roles as $role)
    <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
        <div class="flex items-center justify-between border-b border-[#dbdade] px-5 py-4">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#ece9fd]">
                    <svg class="h-4 w-4 text-[#7367f0]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-sm font-semibold capitalize text-slate-800">{{ $role->name }}</h2>
                    @if(in_array($role->name, $protected))
                    <span class="text-[10px] text-[#a5a3ae]">protected — ลบไม่ได้</span>
                    @endif
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="rounded-full bg-[#f5f5f9] px-2.5 py-0.5 text-xs font-medium text-[#a5a3ae]">
                    {{ isset($matrix[$role->name]) ? count(array_filter($matrix[$role->name])) : 0 }} / {{ $perms->count() }} permissions
                </span>
                @if(!in_array($role->name, $protected))
                <form method="POST" action="{{ route('admin.roles.destroy', $role->name) }}"
                      onsubmit="return confirm('ลบ role \'{{ $role->name }}\' ? การกระทำนี้ไม่สามารถย้อนกลับได้')">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="flex h-7 w-7 items-center justify-center rounded-lg text-[#a5a3ae] hover:bg-[#fde8e9] hover:text-[#ea5455] transition-colors">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                        </svg>
                    </button>
                </form>
                @endif
            </div>
        </div>
        <form method="POST" action="{{ route('admin.roles.update', $role->name) }}" class="p-5">
            @csrf @method('PUT')
            <div class="mb-4 grid grid-cols-2 gap-x-6 gap-y-2 md:grid-cols-3 max-h-64 overflow-y-auto pr-2">
                @foreach($perms as $perm)
                <label class="flex cursor-pointer items-center gap-2 text-xs text-[#5d596c]">
                    <input type="checkbox" name="permissions[]" value="{{ $perm->name }}"
                           @checked(isset($matrix[$role->name][$perm->name]))
                           class="h-3.5 w-3.5 rounded border-[#dbdade] accent-[#7367f0]">
                    {{ $perm->name }}
                </label>
                @endforeach
            </div>
            <button type="submit"
                    class="rounded-lg bg-[#7367f0] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#6259d4]">
                Save permissions
            </button>
        </form>
    </div>
    @endforeach
</div>

@endsection
