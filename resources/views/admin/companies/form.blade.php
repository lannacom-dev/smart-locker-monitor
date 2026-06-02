@extends('layouts.admin')
@section('title', $company->exists ? 'Edit Company' : 'New Company')
@section('heading', $company->exists ? 'Edit Company' : 'New Company')
@section('subheading', $company->exists ? $company->name : 'เพิ่มบริษัทใหม่')

@section('content')

<div class="max-w-2xl">
    <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
        <div class="border-b border-[#dbdade] px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-800">Company Details</h2>
        </div>
        <form method="POST"
              action="{{ $company->exists ? route('admin.companies.update', $company) : route('admin.companies.store') }}"
              class="space-y-5 p-5">
            @csrf
            @if($company->exists) @method('PUT') @endif

            {{-- Basic info --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">Name</label>
                    <input name="name" value="{{ old('name', $company->name) }}" required
                           class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">Code</label>
                    <input name="code" value="{{ old('code', $company->code) }}" required
                           class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">Parent Company</label>
                <select name="parent_company_id"
                        class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                    <option value="">— None (root) —</option>
                    @foreach($parents as $p)
                    <option value="{{ $p->id }}" @selected(old('parent_company_id', $company->parent_company_id) == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Divider --}}
            <div class="border-t border-[#dbdade] pt-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-[#5d596c]">API Endpoint</p>
                <p class="mt-0.5 text-xs text-[#a5a3ae]">กำหนด endpoint/credentials สำหรับ pull ข้อมูลจาก SmartLocker API</p>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">API Base URL</label>
                <input name="api_base_url"
                       value="{{ old('api_base_url', $company->api_base_url) }}"
                       placeholder="https://message-service.lanna.co.th:5183"
                       class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                <p class="mt-1 text-xs text-[#a5a3ae]">ตัวอย่าง nexa: http://smart-locker.lanna.co.th:5183</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">API Client ID</label>
                    <input name="api_client_id" value="{{ old('api_client_id', $company->api_client_id) }}"
                           class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">API Client Secret</label>
                    <input name="api_client_secret" value="{{ old('api_client_secret', $company->api_client_secret) }}"
                           class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                </div>
            </div>

            <div class="grid items-end gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">API Timeout (seconds)</label>
                    <input name="api_timeout" type="number" min="1" max="120"
                           value="{{ old('api_timeout', $company->api_timeout ?? 10) }}"
                           class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                </div>
                <div class="flex gap-5 pb-1">
                    <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-[#5d596c]">
                        <input type="checkbox" name="api_enabled" value="1"
                               @checked(old('api_enabled', $company->api_enabled))
                               class="h-4 w-4 rounded border-[#dbdade] text-[#7367f0] focus:ring-[#7367f0]">
                        API Enabled
                    </label>
                    <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-[#5d596c]">
                        <input type="checkbox" name="is_active" value="1"
                               @checked(old('is_active', $company->is_active ?? true))
                               class="h-4 w-4 rounded border-[#dbdade] text-[#7367f0] focus:ring-[#7367f0]">
                        Active
                    </label>
                </div>
            </div>

            <div class="flex items-center gap-3 border-t border-[#dbdade] pt-4">
                <button type="submit"
                        class="rounded-lg bg-[#7367f0] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#6259d4]">
                    Save
                </button>
                <a href="{{ route('admin.companies.index') }}"
                   class="rounded-lg border border-[#dbdade] px-4 py-2 text-sm font-medium text-[#5d596c] transition-colors hover:bg-[#f5f5f9]">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
