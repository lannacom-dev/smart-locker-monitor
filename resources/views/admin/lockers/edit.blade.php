@extends('layouts.admin')
@section('title', 'Edit '.$locker->name)
@section('heading', 'Edit Locker')
@section('subheading', $locker->name)

@section('content')

<div class="max-w-lg">
    <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
        <div class="border-b border-[#dbdade] px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-800">Locker Details</h2>
        </div>
        <form method="POST" action="{{ route('admin.lockers.update', $locker) }}" class="space-y-4 p-5">
            @csrf @method('PUT')

            <div>
                <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">Name</label>
                <input name="name" value="{{ old('name', $locker->name) }}" required
                       class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">Description</label>
                <textarea name="description" rows="3"
                          class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">{{ old('description', $locker->description) }}</textarea>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">External Unit ID</label>
                <input name="external_unit_id" type="number"
                       value="{{ old('external_unit_id', $locker->external_unit_id) }}"
                       class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
            </div>

            <label class="flex cursor-pointer items-center gap-2">
                <input type="checkbox" name="is_active" value="1"
                       @checked(old('is_active', $locker->is_active))
                       class="h-4 w-4 rounded border-[#dbdade] text-[#7367f0] focus:ring-[#7367f0]">
                <span class="text-sm font-medium text-[#5d596c]">Active</span>
            </label>

            <div class="flex items-center gap-3 pt-1">
                <button type="submit"
                        class="rounded-lg bg-[#7367f0] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#6259d4]">
                    Save changes
                </button>
                <a href="{{ route('admin.lockers.show', $locker) }}"
                   class="rounded-lg border border-[#dbdade] px-4 py-2 text-sm font-medium text-[#5d596c] transition-colors hover:bg-[#f5f5f9]">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
