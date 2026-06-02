{{--
    IssueStatusChanger component
    Props (from IssueStatusChanger PHP class):
      $issue              — Issue model
      $allowedTransitions — array of status strings the user may transition to
      $canChange          — bool
      $wireMethod         — parent Livewire method name, default 'changeStatus'
      $badgeClasses       — status → Tailwind badge classes
      $buttonClasses      — status → Tailwind button classes
      $statusLabels       — status → human label
--}}

<div
    x-data="{
        selected: null,
        note: '',
        loading: false,
        open(status) { this.selected = status; this.note = ''; },
        cancel()     { this.selected = null; },
        async confirm() {
            this.loading = true;
            await $wire.{{ $wireMethod }}(this.selected, this.note || null);
            this.selected = null;
            this.note     = '';
            this.loading  = false;
        },
    }"
    class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden"
>
    {{-- Header --}}
    <div class="px-5 pt-5 pb-3">
        <div class="flex items-center justify-between mb-1">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Status</h3>
            <span class="text-xs text-gray-400 dark:text-gray-500">current</span>
        </div>

        {{-- Current status badge --}}
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-semibold ring-1 ring-inset
                         {{ $badgeClasses[$issue->status] ?? 'bg-gray-100 text-gray-600' }}">
                {{-- Animated dot for active statuses --}}
                @if(in_array($issue->status, ['open', 'in_progress', 'pending']))
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75
                                 {{ \App\Models\Issue::statusDotClasses()[$issue->status] ?? 'bg-gray-400' }}"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2
                                 {{ \App\Models\Issue::statusDotClasses()[$issue->status] ?? 'bg-gray-400' }}"></span>
                </span>
                @else
                <span class="h-2 w-2 rounded-full {{ \App\Models\Issue::statusDotClasses()[$issue->status] ?? 'bg-gray-400' }}"></span>
                @endif
                {{ $statusLabels[$issue->status] ?? ucfirst($issue->status) }}
            </span>

            @if($issue->resolved_at)
            <span class="text-xs text-gray-400 dark:text-gray-500">
                {{ $issue->resolved_at->diffForHumans() }}
            </span>
            @elseif($issue->created_at)
            <span class="text-xs text-gray-400 dark:text-gray-500">
                since {{ $issue->created_at->diffForHumans() }}
            </span>
            @endif
        </div>
    </div>

    {{-- Allowed transitions --}}
    @if($canChange && count($allowedTransitions) > 0)

    <div class="px-5 pb-4">
        <p class="text-xs text-gray-400 dark:text-gray-500 mb-2 mt-1">Change to:</p>
        <div class="flex flex-wrap gap-2">
            @foreach($allowedTransitions as $status)
            <button
                type="button"
                @click="open('{{ $status }}')"
                :class="selected === '{{ $status }}'
                    ? 'ring-2 ring-offset-1 ring-primary-400 shadow-sm '
                    : 'opacity-80 hover:opacity-100'"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-150
                       {{ $buttonClasses[$status] ?? 'bg-gray-100 text-gray-600 border border-gray-200' }}"
            >
                {{-- Direction arrow --}}
                @php
                    $order = ['closed' => 5, 'resolved' => 4, 'pending' => 2, 'in_progress' => 1, 'open' => 0];
                    $arrow = ($order[$status] ?? 0) > ($order[$issue->status] ?? 0) ? '→' : '↩';
                @endphp
                <span class="opacity-60">{{ $arrow }}</span>
                {{ $statusLabels[$status] ?? ucfirst($status) }}
            </button>
            @endforeach
        </div>
    </div>

    {{-- Confirmation panel (slides in when a status is selected) --}}
    <div
        x-show="selected !== null"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        class="border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-750 px-5 py-4"
        x-cloak
    >
        {{-- Summary line --}}
        <p class="text-xs font-medium text-gray-600 dark:text-gray-300 mb-3">
            Confirm:
            <span class="line-through text-gray-400 mr-1">{{ $statusLabels[$issue->status] ?? $issue->status }}</span>
            <span class="text-base">→</span>
            <span
                x-text="selected ? (@js($statusLabels))[selected] ?? selected : ''"
                class="font-semibold text-gray-800 dark:text-gray-100 ml-1"
            ></span>
        </p>

        {{-- Note textarea --}}
        <textarea
            x-model="note"
            rows="2"
            placeholder="Add a note about this change (optional)…"
            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700
                   px-3 py-2 text-xs text-gray-900 dark:text-gray-100
                   focus:ring-2 focus:ring-primary-500 focus:border-transparent resize-none mb-3"
        ></textarea>

        {{-- Action buttons --}}
        <div class="flex items-center gap-2">
            <button
                type="button"
                @click="confirm()"
                :disabled="loading"
                class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg
                       bg-primary-600 text-white text-xs font-medium
                       hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
                <svg x-show="loading" class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="loading ? 'Saving…' : 'Confirm Change'"></span>
            </button>
            <button
                type="button"
                @click="cancel()"
                :disabled="loading"
                class="px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600
                       text-xs text-gray-600 dark:text-gray-400
                       hover:bg-gray-100 dark:hover:bg-gray-700 disabled:opacity-50 transition-colors"
            >
                Cancel
            </button>
        </div>
    </div>

    @elseif(!$canChange)
    <div class="px-5 pb-4">
        <p class="text-xs text-gray-400 dark:text-gray-500 italic">
            Status can only be changed by the assignee or an admin.
        </p>
    </div>
    @else
    <div class="px-5 pb-4">
        <p class="text-xs text-gray-400 dark:text-gray-500 italic">
            No further transitions available from this status.
        </p>
    </div>
    @endif

</div>
