@extends('layouts.admin')
@section('title', $location->exists ? 'Edit Location' : 'New Location')
@section('heading', $location->exists ? 'Edit Location' : 'New Location')
@section('subheading', $location->exists ? $location->name : 'เพิ่มสถานที่ใหม่')

@section('content')

<div class="max-w-lg">
    <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
        <div class="border-b border-[#dbdade] px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-800">Location Details</h2>
        </div>
        <form method="POST"
              action="{{ $location->exists ? route('admin.locations.update', $location) : route('admin.locations.store') }}"
              class="space-y-4 p-5">
            @csrf
            @if($location->exists) @method('PUT') @endif

            @if(!$location->exists)
            <div>
                <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">Company</label>
                <select name="company_id" required
                        class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                    <option value="">— Select company —</option>
                    @foreach($companies as $c)
                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">Name</label>
                <input name="name" value="{{ old('name', $location->name) }}" required
                       class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">Address</label>
                <textarea name="address" rows="3"
                          class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">{{ old('address', $location->address) }}</textarea>
            </div>

            <div class="flex items-center gap-3 pt-1">
                <button type="submit"
                        class="rounded-lg bg-[#7367f0] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#6259d4]">
                    Save
                </button>
                <a href="{{ route('admin.locations.index') }}"
                   class="rounded-lg border border-[#dbdade] px-4 py-2 text-sm font-medium text-[#5d596c] transition-colors hover:bg-[#f5f5f9]">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
