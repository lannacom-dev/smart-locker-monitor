@extends('layouts.admin')
@section('title', $issue->title)
@section('heading', $issue->title)
@section('subheading', 'Issue #{{ $issue->id }} · สร้างเมื่อ {{ $issue->created_at->format(\'d M Y H:i\') }}')

@section('content')

@php
    $statusStyle = match($issue->status) {
        'open'        => 'background:#fde8e9;color:#ea5455',
        'in_progress' => 'background:#ece9fd;color:#7367f0',
        'pending'     => 'background:#fff3e8;color:#ff9f43',
        'resolved'    => 'background:#dff7e9;color:#28c76f',
        'closed'      => 'background:#f5f5f9;color:#a5a3ae',
        default       => 'background:#f5f5f9;color:#a5a3ae',
    };
    $sevStyle = match($issue->severity) {
        'critical' => 'background:#fde8e9;color:#ea5455',
        'high'     => 'background:#fff3e8;color:#ff9f43',
        'medium'   => 'background:#ece9fd;color:#7367f0',
        'low'      => 'background:#d9f7fb;color:#00cfe8',
        default    => 'background:#f5f5f9;color:#a5a3ae',
    };
@endphp

{{-- Flash --}}
@if(session('success'))
<div class="mb-4 flex items-center gap-2 rounded-lg bg-[#dff7e9] px-4 py-3 text-sm font-medium text-[#28c76f]">
    <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
    {{ session('success') }}
</div>
@endif

<div class="grid gap-5 lg:grid-cols-3">

    {{-- ── Left: Details ── --}}
    <div class="space-y-5 lg:col-span-2">

        {{-- Header card --}}
        <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
            <div class="border-b border-[#dbdade] px-5 py-4 flex items-center justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold capitalize"
                          style="{{ $statusStyle }}">
                        {{ str_replace('_', ' ', $issue->status) }}
                    </span>
                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold capitalize"
                          style="{{ $sevStyle }}">
                        {{ $issue->severity }}
                    </span>
                    <span class="rounded-full bg-[#f5f5f9] px-3 py-1 text-xs font-semibold text-[#5d596c] capitalize">
                        {{ $issue->categoryLabel() }}
                    </span>
                </div>
                <a href="{{ route('admin.issues.index') }}"
                   class="text-xs font-medium text-[#7367f0] hover:underline shrink-0">← Back to issues</a>
            </div>
            <div class="p-5">
                @if($issue->description)
                    <p class="text-sm leading-relaxed text-[#5d596c] whitespace-pre-wrap">{{ $issue->description }}</p>
                @else
                    <p class="text-sm italic text-[#a5a3ae]">No description provided.</p>
                @endif

                {{-- Meta grid --}}
                <dl class="mt-5 grid grid-cols-2 gap-x-6 gap-y-3 border-t border-[#f0f0f0] pt-4 sm:grid-cols-3 text-sm">
                    <div>
                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-[#a5a3ae]">Company</dt>
                        <dd class="mt-0.5 font-medium text-[#5d596c]">{{ $issue->company?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-[#a5a3ae]">Locker</dt>
                        <dd class="mt-0.5 font-medium text-[#5d596c]">
                            @if($issue->locker)
                                <a href="{{ route('admin.lockers.show', $issue->locker) }}"
                                   class="text-[#7367f0] hover:underline">{{ $issue->locker->name }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-[#a5a3ae]">Reported By</dt>
                        <dd class="mt-0.5 font-medium text-[#5d596c]">{{ $issue->reporter?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-[#a5a3ae]">Assigned To</dt>
                        <dd class="mt-0.5 font-medium text-[#5d596c]">{{ $issue->assignee?->name ?? 'Unassigned' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-[#a5a3ae]">Due Date</dt>
                        <dd class="mt-0.5 font-medium text-[#5d596c]">{{ $issue->due_date?->format('d M Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-semibold uppercase tracking-wide text-[#a5a3ae]">Resolved At</dt>
                        <dd class="mt-0.5 font-medium text-[#5d596c]">{{ $issue->resolved_at?->format('d M Y H:i') ?? '—' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Comments --}}
        <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
            <div class="border-b border-[#dbdade] px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-800">
                    Comments
                    @if($issue->comments->count())
                        <span class="ml-1.5 rounded-full bg-[#ece9fd] px-2 py-0.5 text-xs font-bold text-[#7367f0]">{{ $issue->comments->count() }}</span>
                    @endif
                </h2>
            </div>

            {{-- Comment list --}}
            <div class="divide-y divide-[#f0f0f0]">
                @forelse($issue->comments as $comment)
                <div class="p-5 @if($comment->is_internal) bg-[#fffef0] @endif">
                    <div class="mb-2 flex items-center gap-2">
                        <div class="flex h-7 w-7 items-center justify-center rounded-full bg-[#7367f0] text-xs font-bold text-white shrink-0">
                            {{ strtoupper(substr($comment->user?->name ?? '?', 0, 1)) }}
                        </div>
                        <span class="text-sm font-semibold text-[#5d596c]">{{ $comment->user?->name ?? 'Unknown' }}</span>
                        @if($comment->is_internal)
                            <span class="rounded-full bg-[#fff3e8] px-2 py-0.5 text-[10px] font-bold text-[#ff9f43]">Internal</span>
                        @endif
                        <span class="ml-auto text-xs text-[#a5a3ae]">{{ $comment->created_at->format('d M Y H:i') }}</span>
                    </div>
                    <p class="text-sm leading-relaxed text-[#5d596c] whitespace-pre-wrap pl-9">{{ $comment->body }}</p>
                </div>
                @empty
                <div class="p-6 text-center text-sm text-[#a5a3ae]">ยังไม่มี Comment</div>
                @endforelse
            </div>

            {{-- Add comment form --}}
            @can('edit issues')
            <div class="border-t border-[#dbdade] p-5">
                <form method="POST" action="{{ route('admin.issues.comment', $issue) }}">
                    @csrf
                    <textarea name="body" rows="3" required
                              placeholder="เพิ่ม comment…"
                              class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]"></textarea>
                    <div class="mt-2 flex items-center gap-3">
                        <button type="submit"
                                class="rounded-lg bg-[#7367f0] px-4 py-1.5 text-sm font-semibold text-white hover:bg-[#6259d4]">
                            Post Comment
                        </button>
                        <label class="flex items-center gap-1.5 text-xs text-[#a5a3ae] cursor-pointer">
                            <input type="checkbox" name="is_internal" value="1"
                                   class="rounded border-[#dbdade] text-[#7367f0]">
                            Internal note
                        </label>
                    </div>
                </form>
            </div>
            @endcan
        </div>

    </div>{{-- /left --}}

    {{-- ── Right: Actions + Timeline ── --}}
    <div class="space-y-5">

        {{-- Status update --}}
        @can('edit issues')
        <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
            <div class="border-b border-[#dbdade] px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-800">Update Status</h2>
            </div>
            <form method="POST" action="{{ route('admin.issues.status', $issue) }}" class="p-5 space-y-3">
                @csrf @method('PATCH')
                <div>
                    <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">New Status</label>
                    <select name="status"
                            class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                        @foreach(\App\Models\Issue::statusOptions() as $val => $label)
                            <option value="{{ $val }}" @selected($issue->status === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">Note (optional)</label>
                    <input name="note" placeholder="Add a note…"
                           class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                </div>
                <button type="submit"
                        class="w-full rounded-lg bg-[#7367f0] px-4 py-2 text-sm font-semibold text-white hover:bg-[#6259d4]">
                    Update Status
                </button>
            </form>
        </div>

        {{-- Assign --}}
        <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
            <div class="border-b border-[#dbdade] px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-800">Assign</h2>
            </div>
            <form method="POST" action="{{ route('admin.issues.assign', $issue) }}" class="p-5 space-y-3">
                @csrf
                <div>
                    <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">Assign To</label>
                    <select name="assigned_to"
                            class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                        <option value="">— Unassigned —</option>
                        @foreach($assignableUsers as $u)
                            <option value="{{ $u->id }}" @selected($issue->assigned_to === $u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">Note (optional)</label>
                    <input name="note" placeholder="Add a note…"
                           class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
                </div>
                <button type="submit"
                        class="w-full rounded-lg border border-[#7367f0] px-4 py-2 text-sm font-semibold text-[#7367f0] hover:bg-[#ece9fd]">
                    Save Assignee
                </button>
            </form>
        </div>
        @endcan

        {{-- Timeline --}}
        <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
            <div class="border-b border-[#dbdade] px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-800">History</h2>
            </div>
            <div class="p-5">
                @php
                    $dotColor = fn($status) => match($status) {
                        'open'        => '#ea5455',
                        'in_progress' => '#7367f0',
                        'pending'     => '#ff9f43',
                        'resolved'    => '#28c76f',
                        'closed'      => '#a5a3ae',
                        default       => '#dbdade',
                    };
                @endphp

                @if($issue->statusHistories->isEmpty() && $issue->assignments->isEmpty())
                    <p class="text-center text-sm text-[#a5a3ae]">ยังไม่มีประวัติ</p>
                @else
                    {{-- Merge status histories and assignments into a single timeline --}}
                    @php
                        $events = collect();
                        foreach ($issue->statusHistories as $h) {
                            $events->push(['at' => $h->created_at, 'type' => 'status', 'item' => $h]);
                        }
                        foreach ($issue->assignments as $a) {
                            $events->push(['at' => $a->created_at, 'type' => 'assign', 'item' => $a]);
                        }
                        $events = $events->sortBy('at')->values();
                    @endphp

                    <ol class="space-y-4">
                        @foreach($events as $event)
                        @php $item = $event['item']; @endphp
                        <li class="relative pl-5">
                            {{-- dot --}}
                            <span class="absolute left-0 top-1.5 h-2.5 w-2.5 rounded-full border-2 border-white ring-1 ring-[#dbdade]"
                                  style="background:{{ $event['type'] === 'status' ? $dotColor($item->to_status) : '#a5a3ae' }}"></span>

                            @if($event['type'] === 'status')
                                <p class="text-xs font-semibold text-[#5d596c]">
                                    <span class="capitalize">{{ str_replace('_', ' ', $item->from_status) }}</span>
                                    → <span class="capitalize">{{ str_replace('_', ' ', $item->to_status) }}</span>
                                </p>
                                <p class="text-[11px] text-[#a5a3ae]">
                                    by {{ $item->changedBy?->name ?? 'System' }}
                                    · {{ $item->created_at->format('d M Y H:i') }}
                                </p>
                                @if($item->note)
                                    <p class="mt-1 text-xs italic text-[#5d596c]">"{{ $item->note }}"</p>
                                @endif
                            @else
                                <p class="text-xs font-semibold text-[#5d596c]">
                                    {{ $item->typeLabel() }}
                                    @if($item->new_value)
                                        → <span class="text-[#7367f0]">{{ $item->new_value }}</span>
                                    @endif
                                </p>
                                <p class="text-[11px] text-[#a5a3ae]">
                                    by {{ $item->performer?->name ?? 'System' }}
                                    · {{ $item->created_at->format('d M Y H:i') }}
                                </p>
                                @if($item->note)
                                    <p class="mt-1 text-xs italic text-[#5d596c]">"{{ $item->note }}"</p>
                                @endif
                            @endif
                        </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </div>

    </div>{{-- /right --}}

</div>

@endsection
