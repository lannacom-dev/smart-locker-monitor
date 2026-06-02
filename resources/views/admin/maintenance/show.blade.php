@extends('layouts.admin')
@section('title', $maintenance->title)
@section('heading', $maintenance->title)
@section('subheading', 'Maintenance #{{ $maintenance->id }} · สร้างเมื่อ {{ $maintenance->created_at->format(\'d M Y H:i\') }}')

@section('content')

@php
    $statusStyle = match($maintenance->status) {
        'created'     => 'background:#d9f7fb;color:#00cfe8',
        'in_progress' => 'background:#fff3e8;color:#ff9f43',
        'completed'   => 'background:#dff7e9;color:#28c76f',
        'cancelled'   => 'background:#f5f5f9;color:#a5a3ae',
        default       => 'background:#f5f5f9;color:#a5a3ae',
    };
    $priStyle = match($maintenance->priority) {
        'urgent' => 'background:#fde8e9;color:#ea5455',
        'high'   => 'background:#fff3e8;color:#ff9f43',
        'medium' => 'background:#ece9fd;color:#7367f0',
        'low'    => 'background:#f5f5f9;color:#a5a3ae',
        default  => 'background:#f5f5f9;color:#a5a3ae',
    };
    $typeStyle = match($maintenance->type ?? 'corrective') {
        'preventive' => 'background:#dff7e9;color:#28c76f',
        'emergency'  => 'background:#fde8e9;color:#ea5455',
        default      => 'background:#ece9fd;color:#7367f0',
    };
    $attachmentsByPhase = $maintenance->attachments->groupBy('phase');
@endphp

{{-- Flash --}}
@if(session('success'))
<div class="mb-4 flex items-center gap-2 rounded-lg bg-[#dff7e9] px-4 py-3 text-sm font-medium text-[#28c76f]">
    <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
    {{ session('success') }}
</div>
@endif

<div class="grid gap-5 lg:grid-cols-3">

    {{-- ── Left column ── --}}
    <div class="space-y-5 lg:col-span-2">

        {{-- Header / Details card --}}
        <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
            <div class="border-b border-[#dbdade] px-5 py-4 flex flex-wrap items-center gap-2 justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold capitalize"
                          style="{{ $typeStyle }}">{{ $maintenance->typeLabel() }}</span>
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold capitalize"
                          style="{{ $priStyle }}">{{ $maintenance->priorityLabel() }}</span>
                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold capitalize"
                          style="{{ $statusStyle }}">{{ str_replace('_', ' ', $maintenance->status) }}</span>
                </div>
                <a href="{{ route('admin.maintenance.index') }}"
                   class="text-xs font-medium text-[#7367f0] hover:underline shrink-0">← Back to list</a>
            </div>
            <div class="p-5">
                @if($maintenance->description)
                    <p class="text-sm leading-relaxed text-[#5d596c] whitespace-pre-wrap">{{ $maintenance->description }}</p>
                @else
                    <p class="text-sm italic text-[#a5a3ae]">No description provided.</p>
                @endif

                {{-- Meta grid --}}
                <dl class="mt-5 grid grid-cols-2 gap-x-6 gap-y-3 border-t border-[#f0f0f0] pt-4 sm:grid-cols-3 text-sm">
                    <div>
                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-[#a5a3ae]">Company</dt>
                        <dd class="mt-0.5 font-medium text-[#5d596c]">{{ $maintenance->company?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-[#a5a3ae]">Locker / Asset</dt>
                        <dd class="mt-0.5 font-medium text-[#5d596c]">
                            @if($maintenance->locker)
                                <a href="{{ route('admin.lockers.show', $maintenance->locker) }}"
                                   class="text-[#7367f0] hover:underline">{{ $maintenance->locker->name }}</a>
                            @else —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-[#a5a3ae]">Created By</dt>
                        <dd class="mt-0.5 font-medium text-[#5d596c]">{{ $maintenance->creator?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-[#a5a3ae]">Technician</dt>
                        <dd class="mt-0.5 font-medium text-[#5d596c]">{{ $maintenance->technician?->name ?? 'Unassigned' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-[#a5a3ae]">Scheduled</dt>
                        <dd class="mt-0.5 font-medium text-[#5d596c]">{{ $maintenance->scheduled_date?->format('d M Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-[#a5a3ae]">Started</dt>
                        <dd class="mt-0.5 font-medium text-[#5d596c]">{{ $maintenance->started_at?->format('d M Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-[#a5a3ae]">Completed</dt>
                        <dd class="mt-0.5 font-medium text-[#5d596c]">{{ $maintenance->completed_at?->format('d M Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-[#a5a3ae]">Duration</dt>
                        <dd class="mt-0.5 font-medium text-[#5d596c]">{{ $maintenance->formattedDuration() }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-[#a5a3ae]">Cost (est / actual)</dt>
                        <dd class="mt-0.5 font-medium text-[#5d596c]">
                            {{ $maintenance->cost_estimate ? '฿'.number_format((float)$maintenance->cost_estimate, 2) : '—' }}
                            /
                            {{ $maintenance->cost_actual   ? '฿'.number_format((float)$maintenance->cost_actual,   2) : '—' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Root cause & solution --}}
        @can('edit maintenance')
        <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
            <div class="border-b border-[#dbdade] px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-800">Root Cause & Solution</h2>
            </div>
            <form method="POST" action="{{ route('admin.maintenance.fields', $maintenance) }}" class="p-5 space-y-4">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">สาเหตุ (Root Cause)</label>
                    <textarea name="root_cause" rows="3"
                              placeholder="ระบุสาเหตุที่พบ…"
                              class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">{{ $maintenance->root_cause }}</textarea>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">วิธีแก้ไข (Solution)</label>
                    <textarea name="solution" rows="3"
                              placeholder="อธิบายการแก้ไขที่ดำเนินการ…"
                              class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">{{ $maintenance->solution }}</textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">ค่าใช้จ่ายจริง (บาท)</label>
                        <input type="number" name="cost_actual" step="0.01" min="0"
                               value="{{ $maintenance->cost_actual }}" placeholder="0.00"
                               class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">หมายเหตุการบันทึก</label>
                        <input name="note" placeholder="เหตุผลที่แก้ไข…"
                               class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                    </div>
                </div>
                <button type="submit"
                        class="rounded-lg bg-[#7367f0] px-4 py-2 text-sm font-semibold text-white hover:bg-[#6259d4]">
                    บันทึก
                </button>
            </form>
        </div>
        @endcan

        {{-- Notes (add note) --}}
        @can('edit maintenance')
        <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
            <div class="border-b border-[#dbdade] px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-800">เพิ่มหมายเหตุ</h2>
            </div>
            <form method="POST" action="{{ route('admin.maintenance.note', $maintenance) }}" class="p-5">
                @csrf
                <textarea name="note" rows="2" required
                          placeholder="บันทึกหมายเหตุ อะไหล่ เครื่องมือ หรือรายละเอียดการดำเนินงาน…"
                          class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]"></textarea>
                <button type="submit"
                        class="mt-2 rounded-lg bg-[#7367f0] px-4 py-1.5 text-sm font-semibold text-white hover:bg-[#6259d4]">
                    เพิ่มหมายเหตุ
                </button>
            </form>
        </div>
        @endcan

        {{-- Image gallery --}}
        <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
            <div class="border-b border-[#dbdade] px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-800">
                    รูปภาพ
                    @if($maintenance->attachments->count())
                        <span class="ml-1.5 rounded-full bg-[#ece9fd] px-2 py-0.5 text-xs font-bold text-[#7367f0]">{{ $maintenance->attachments->count() }}</span>
                    @endif
                </h2>
            </div>
            <div class="p-5 space-y-6">
                @foreach(['before' => 'ก่อนดำเนินการ', 'during' => 'ระหว่างดำเนินการ', 'after' => 'หลังดำเนินการ'] as $phase => $phaseLabel)
                @php $phaseAttachments = $attachmentsByPhase->get($phase, collect()); @endphp
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-[#a5a3ae]">{{ $phaseLabel }}</h3>
                        @can('edit maintenance')
                        <button type="button" onclick="openUpload('{{ $phase }}')"
                                class="text-xs font-semibold text-[#7367f0] hover:underline">+ อัปโหลด</button>
                        @endcan
                    </div>
                    @if($phaseAttachments->isEmpty())
                        <p class="text-sm italic text-[#a5a3ae]">ยังไม่มีรูปภาพ</p>
                    @else
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                            @foreach($phaseAttachments as $att)
                            <a href="{{ $att->url() }}" target="_blank" rel="noopener"
                               class="group block overflow-hidden rounded-lg border border-[#dbdade] hover:border-[#7367f0]">
                                <img src="{{ $att->url() }}" alt="{{ $att->original_name }}"
                                     class="h-24 w-full object-cover group-hover:opacity-90">
                                <div class="p-1 text-center text-[10px] text-[#a5a3ae] truncate" title="{{ $att->original_name }}">
                                    {{ $att->original_name }}
                                </div>
                            </a>
                            @endforeach
                        </div>
                    @endif
                </div>
                @endforeach

                {{-- Hidden upload forms (one per phase) --}}
                @can('edit maintenance')
                @foreach(['before', 'during', 'after'] as $phase)
                <form id="upload-{{ $phase }}" method="POST"
                      action="{{ route('admin.maintenance.attachment', $maintenance) }}"
                      enctype="multipart/form-data" class="hidden">
                    @csrf
                    <input type="hidden" name="phase" value="{{ $phase }}">
                    <input type="file" name="images[]" multiple accept="image/*"
                           onchange="this.form.submit()">
                </form>
                @endforeach
                @endcan
            </div>
        </div>

    </div>{{-- /left --}}

    {{-- ── Right column ── --}}
    <div class="space-y-5">

        {{-- Status transition --}}
        @can('edit maintenance')
        <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
            <div class="border-b border-[#dbdade] px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-800">Update Status</h2>
            </div>
            <form method="POST" action="{{ route('admin.maintenance.transition', $maintenance) }}"
                  id="transition-form" class="p-5 space-y-3">
                @csrf @method('PATCH')
                <div>
                    <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">สถานะใหม่</label>
                    @if(count($allowedTransitions) > 0)
                    <select name="status" id="status-select"
                            class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                        <option value="">— เลือก —</option>
                        @foreach($allowedTransitions as $s)
                            <option value="{{ $s }}">{{ \App\Models\CorrectiveMaintenance::statusOptions()[$s] ?? ucfirst($s) }}</option>
                        @endforeach
                    </select>
                    @else
                        <p class="text-xs text-[#a5a3ae] italic">ไม่มีสถานะที่สามารถเปลี่ยนได้ในขณะนี้</p>
                    @endif
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">หมายเหตุ (optional)</label>
                    <input name="note" placeholder="เหตุผลการเปลี่ยนสถานะ…"
                           class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                </div>

                {{-- Extra fields shown only when "completed" is selected --}}
                <div id="complete-fields" class="hidden space-y-3 border-t border-[#f0f0f0] pt-3">
                    <p class="text-xs font-semibold text-[#5d596c]">ข้อมูลการเสร็จงาน</p>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">Solution / ผลการแก้ไข</label>
                        <textarea name="solution" rows="2"
                                  placeholder="สรุปสิ่งที่ทำไป…"
                                  class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]"></textarea>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">ค่าใช้จ่ายจริง (บาท)</label>
                            <input type="number" name="cost_actual" step="0.01" min="0" placeholder="0.00"
                                   class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">วันที่เสร็จจริง</label>
                            <input type="datetime-local" name="completed_at"
                                   value="{{ now()->format('Y-m-d\TH:i') }}"
                                   class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                        </div>
                    </div>
                </div>

                @if(count($allowedTransitions) > 0)
                <button type="submit"
                        class="w-full rounded-lg bg-[#7367f0] px-4 py-2 text-sm font-semibold text-white hover:bg-[#6259d4]">
                    เปลี่ยนสถานะ
                </button>
                @endif
            </form>
        </div>

        {{-- Assign technician --}}
        <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
            <div class="border-b border-[#dbdade] px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-800">Assign Technician</h2>
            </div>
            <form method="POST" action="{{ route('admin.maintenance.assign', $maintenance) }}" class="p-5 space-y-3">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">ผู้รับผิดชอบ</label>
                    <select name="technician_id"
                            class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                        <option value="">— ยังไม่ระบุ —</option>
                        @foreach($assignableUsers as $u)
                            <option value="{{ $u->id }}" @selected($maintenance->technician_id === $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">หมายเหตุ</label>
                    <input name="note" placeholder="เหตุผลการ assign…"
                           class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                </div>
                <button type="submit"
                        class="w-full rounded-lg border border-[#7367f0] px-4 py-2 text-sm font-semibold text-[#7367f0] hover:bg-[#ece9fd]">
                    บันทึก Assignee
                </button>
            </form>
        </div>
        @endcan

        {{-- History timeline --}}
        <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
            <div class="border-b border-[#dbdade] px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-800">
                    History
                    @if($maintenance->logs->count())
                        <span class="ml-1 rounded-full bg-[#ece9fd] px-2 py-0.5 text-xs font-bold text-[#7367f0]">{{ $maintenance->logs->count() }}</span>
                    @endif
                </h2>
            </div>
            <div class="p-5">
                @if($maintenance->logs->isEmpty())
                    <p class="text-center text-sm text-[#a5a3ae]">ยังไม่มีประวัติ</p>
                @else
                    <ol class="space-y-4">
                        @foreach($maintenance->logs->sortBy('created_at') as $log)
                        <li class="relative pl-6">
                            <span class="absolute left-0 top-1.5 text-sm leading-none">{{ $log->actionIcon() }}</span>
                            <p class="text-xs font-semibold text-[#5d596c]">{{ $log->actionLabel() }}
                                @if($log->field_name && $log->action !== 'attachment_added')
                                    <span class="font-normal text-[#a5a3ae]">— {{ $log->fieldLabel() }}</span>
                                @endif
                            </p>
                            @if($log->new_value && $log->action === 'attachment_added')
                                <p class="text-[11px] text-[#a5a3ae]">{{ $log->new_value }} ({{ $log->note }})</p>
                            @elseif($log->old_value || $log->new_value)
                                <p class="text-[11px] text-[#a5a3ae] capitalize">
                                    {{ str_replace('_', ' ', $log->old_value ?? '—') }}
                                    → {{ str_replace('_', ' ', $log->new_value ?? '—') }}
                                </p>
                            @endif
                            @if($log->note && $log->action !== 'attachment_added')
                                <p class="mt-0.5 text-xs italic text-[#5d596c]">"{{ $log->note }}"</p>
                            @endif
                            <p class="text-[11px] text-[#a5a3ae]">
                                by {{ $log->changedBy?->name ?? 'System' }}
                                · {{ $log->created_at->format('d M Y H:i') }}
                            </p>
                        </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </div>

    </div>{{-- /right --}}

</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Show/hide completed extra fields based on status select
    var statusSelect = document.getElementById('status-select');
    var completeFields = document.getElementById('complete-fields');

    if (statusSelect && completeFields) {
        statusSelect.addEventListener('change', function () {
            if (this.value === 'completed') {
                completeFields.classList.remove('hidden');
            } else {
                completeFields.classList.add('hidden');
            }
        });
    }
});

function openUpload(phase) {
    document.getElementById('upload-' + phase).querySelector('input[type="file"]').click();
}
</script>
@endpush
