@extends('layouts.admin')
@section('title', 'New User')
@section('heading', 'Create Admin User')
@section('subheading', 'เพิ่มผู้ใช้งานระบบใหม่')

@section('content')

<div class="max-w-lg">
    <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
        <div class="border-b border-[#dbdade] px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-800">Account Details</h2>
        </div>
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4 p-5">
            @csrf

            {{-- Name --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-[#5d596c]">
                    ชื่อ <span class="text-[#ea5455]">*</span>
                </label>
                <input name="name" value="{{ old('name') }}" required
                       placeholder="ชื่อ-นามสกุล หรือชื่อที่แสดง"
                       class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0] @error('name') border-[#ea5455] @enderror">
                @error('name')<p class="mt-1 text-xs text-[#ea5455]">{{ $message }}</p>@enderror
            </div>

            {{-- Email --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-[#5d596c]">
                    Email <span class="text-[#ea5455]">*</span>
                </label>
                <input name="email" type="email" value="{{ old('email') }}" required
                       placeholder="user@example.com"
                       class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0] @error('email') border-[#ea5455] @enderror">
                @error('email')<p class="mt-1 text-xs text-[#ea5455]">{{ $message }}</p>@enderror
            </div>

            {{-- Password --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5d596c]">
                        Password <span class="text-[#ea5455]">*</span>
                    </label>
                    <input name="password" type="password" required minlength="8"
                           placeholder="อย่างน้อย 8 ตัวอักษร"
                           class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0] @error('password') border-[#ea5455] @enderror">
                    @error('password')<p class="mt-1 text-xs text-[#ea5455]">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5d596c]">
                        Confirm Password <span class="text-[#ea5455]">*</span>
                    </label>
                    <input name="password_confirmation" type="password" required minlength="8"
                           placeholder="ยืนยันรหัสผ่าน"
                           class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                </div>
            </div>

            {{-- Company --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-[#5d596c]">บริษัท</label>
                <select name="company_id"
                        class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0] @error('company_id') border-[#ea5455] @enderror">
                    <option value="">— ไม่ระบุ (Super Admin) —</option>
                    @foreach($companies as $company)
                    <option value="{{ $company->id }}" @selected(old('company_id') == $company->id)>{{ $company->name }}</option>
                    @endforeach
                </select>
                @error('company_id')<p class="mt-1 text-xs text-[#ea5455]">{{ $message }}</p>@enderror
            </div>

            {{-- Roles --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-[#5d596c]">Role</label>
                <select name="roles[]" multiple
                        class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]"
                        style="height: 8rem;">
                    @foreach($roles as $role)
                    <option value="{{ $role }}" @selected(in_array($role, old('roles', [])))>{{ $role }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-[#a5a3ae]">Hold Ctrl / Cmd เพื่อเลือกหลาย role</p>
            </div>

            {{-- Active --}}
            <div class="flex items-center gap-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1"
                       @checked(old('is_active', true))
                       class="h-4 w-4 rounded border-[#dbdade] accent-[#7367f0]">
                <label for="is_active" class="text-sm text-[#5d596c]">Active</label>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 border-t border-[#dbdade] pt-4">
                <button type="submit"
                        class="rounded-lg bg-[#7367f0] px-5 py-2 text-sm font-semibold text-white hover:bg-[#6259d4]">
                    สร้าง User
                </button>
                <a href="{{ route('admin.users.index') }}"
                   class="rounded-lg border border-[#dbdade] px-5 py-2 text-sm font-semibold text-[#5d596c] hover:bg-[#f5f5f9]">
                    ยกเลิก
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
