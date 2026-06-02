@extends('layouts.admin')
@section('title', 'New Issue')
@section('heading', 'Create Issue')
@section('subheading', 'รายงานปัญหาหรือ Fault ใหม่')

@section('content')

<div class="max-w-2xl">
    <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
        <div class="border-b border-[#dbdade] px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-800">Issue Details</h2>
        </div>

        <form method="POST" action="{{ route('admin.issues.store') }}" class="p-5 space-y-4">
            @csrf

            {{-- Title --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-[#5d596c]">Title <span class="text-[#ea5455]">*</span></label>
                <input name="title" value="{{ old('title') }}" required
                       placeholder="Brief description of the issue"
                       class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0] @error('title') border-[#ea5455] @enderror">
                @error('title')
                    <p class="mt-1 text-xs text-[#ea5455]">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-[#5d596c]">Description</label>
                <textarea name="description" rows="4"
                          placeholder="Detailed description of the problem…"
                          class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">{{ old('description') }}</textarea>
            </div>

            {{-- Category + Severity --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5d596c]">Category <span class="text-[#ea5455]">*</span></label>
                    <select name="category" required
                            class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0] @error('category') border-[#ea5455] @enderror">
                        <option value="">— Select —</option>
                        @foreach($categories as $val => $label)
                            <option value="{{ $val }}" @selected(old('category') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category')
                        <p class="mt-1 text-xs text-[#ea5455]">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5d596c]">Severity <span class="text-[#ea5455]">*</span></label>
                    <select name="severity" required
                            class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0] @error('severity') border-[#ea5455] @enderror">
                        <option value="">— Select —</option>
                        @foreach($severities as $val => $label)
                            <option value="{{ $val }}" @selected(old('severity', 'medium') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('severity')
                        <p class="mt-1 text-xs text-[#ea5455]">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Locker + Due date --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5d596c]">Related Locker</label>
                    <select name="locker_id"
                            class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                        <option value="">— None —</option>
                        @foreach($lockers as $locker)
                            <option value="{{ $locker->id }}" @selected(old('locker_id') == $locker->id)>{{ $locker->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5d596c]">Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date') }}"
                           class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                </div>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="rounded-lg bg-[#7367f0] px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#6259d4]">
                    Create Issue
                </button>
                <a href="{{ route('admin.issues.index') }}"
                   class="rounded-lg border border-[#dbdade] px-5 py-2 text-sm font-semibold text-[#5d596c] transition-colors hover:bg-[#f5f5f9]">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
