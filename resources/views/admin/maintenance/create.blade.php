@extends('layouts.admin')
@section('title', 'New Maintenance')
@section('heading', 'Create Maintenance')
@section('subheading', 'บันทึกรายการบำรุงรักษาใหม่')

@section('content')

<div class="max-w-2xl">
    <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
        <div class="border-b border-[#dbdade] px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-800">Maintenance Details</h2>
        </div>

        <form method="POST" action="{{ route('admin.maintenance.store') }}"
              enctype="multipart/form-data" class="p-5 space-y-4">
            @csrf

            {{-- Type + Priority --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5d596c]">
                        ประเภท Maintenance <span class="text-[#ea5455]">*</span>
                    </label>
                    <select name="type" required
                            class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0] @error('type') border-[#ea5455] @enderror">
                        <option value="">— เลือก —</option>
                        @foreach($types as $val => $label)
                            <option value="{{ $val }}" @selected(old('type') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type')<p class="mt-1 text-xs text-[#ea5455]">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5d596c]">
                        Priority <span class="text-[#ea5455]">*</span>
                    </label>
                    <select name="priority" required
                            class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0] @error('priority') border-[#ea5455] @enderror">
                        <option value="">— เลือก —</option>
                        @foreach($priorities as $val => $label)
                            <option value="{{ $val }}" @selected(old('priority', 'medium') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('priority')<p class="mt-1 text-xs text-[#ea5455]">{{ $message }}</p>@enderror
                </div>
            </div>

            {{-- Locker (Asset) --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-[#5d596c]">
                    Locker / Asset <span class="text-[#ea5455]">*</span>
                </label>
                <select name="locker_id" required
                        class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0] @error('locker_id') border-[#ea5455] @enderror">
                    <option value="">— เลือก Locker —</option>
                    @foreach($lockers as $locker)
                        <option value="{{ $locker->id }}" @selected(old('locker_id') == $locker->id)>{{ $locker->name }}</option>
                    @endforeach
                </select>
                @error('locker_id')<p class="mt-1 text-xs text-[#ea5455]">{{ $message }}</p>@enderror
            </div>

            {{-- Title --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-[#5d596c]">
                    ชื่อรายการ <span class="text-[#ea5455]">*</span>
                </label>
                <input name="title" value="{{ old('title') }}" required
                       placeholder="เช่น ตรวจเช็กประตูล็อคเกอร์ประจำเดือน"
                       class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0] @error('title') border-[#ea5455] @enderror">
                @error('title')<p class="mt-1 text-xs text-[#ea5455]">{{ $message }}</p>@enderror
            </div>

            {{-- Description --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-[#5d596c]">
                    รายละเอียด / อาการ <span class="text-[#ea5455]">*</span>
                </label>
                <textarea name="description" rows="3" required
                          placeholder="ระบุอาการ ปัญหา หรือเหตุผลที่ต้อง Maintenance…"
                          class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">{{ old('description') }}</textarea>
            </div>

            {{-- Root Cause --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-[#5d596c]">สาเหตุที่ทราบ (Root Cause)</label>
                <textarea name="root_cause" rows="2"
                          placeholder="ระบุสาเหตุที่ทราบแล้ว (ถ้ามี)…"
                          class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">{{ old('root_cause') }}</textarea>
            </div>

            {{-- Technician + Scheduled date --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5d596c]">ผู้รับผิดชอบ</label>
                    <select name="technician_id"
                            class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                        <option value="">— ยังไม่ระบุ —</option>
                        @foreach($technicians as $tech)
                            <option value="{{ $tech->id }}" @selected(old('technician_id') == $tech->id)>{{ $tech->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5d596c]">วันที่นัดหมาย</label>
                    <input type="datetime-local" name="scheduled_date" value="{{ old('scheduled_date') }}"
                           class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                </div>
            </div>

            {{-- Cost estimate + Notes --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5d596c]">ค่าใช้จ่ายประมาณการ (บาท)</label>
                    <input type="number" name="cost_estimate" value="{{ old('cost_estimate') }}"
                           step="0.01" min="0" placeholder="0.00"
                           class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-[#5d596c]">หมายเหตุเพิ่มเติม</label>
                    <input name="notes" value="{{ old('notes') }}" placeholder="อะไหล่ เครื่องมือ หรือหมายเหตุ…"
                           class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                </div>
            </div>

            {{-- Image upload (before) --}}
            <div>
                <label class="mb-1 block text-xs font-semibold text-[#5d596c]">รูปภาพก่อนดำเนินการ</label>
                <input type="file" name="images[]" multiple accept="image/*"
                       class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c]">
                <p class="mt-1 text-[11px] text-[#a5a3ae]">JPEG / PNG / GIF / WebP — สูงสุด 5 MB ต่อไฟล์</p>
                @error('images.*')<p class="mt-1 text-xs text-[#ea5455]">{{ $message }}</p>@enderror
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="rounded-lg bg-[#7367f0] px-5 py-2 text-sm font-semibold text-white hover:bg-[#6259d4]">
                    บันทึกรายการ
                </button>
                <a href="{{ route('admin.maintenance.index') }}"
                   class="rounded-lg border border-[#dbdade] px-5 py-2 text-sm font-semibold text-[#5d596c] hover:bg-[#f5f5f9]">
                    ยกเลิก
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
