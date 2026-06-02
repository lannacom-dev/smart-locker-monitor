{{--
    IssueStatusTimeline component
    Props (from IssueStatusTimeline PHP class):
      $histories     — Collection<IssueStatusHistory> with changedBy loaded
      $compact       — bool, show a condensed variant
      $badgeClasses  — status → Tailwind badge classes
      $dotClasses    — status → dot color classes
      $statusLabels  — status → human label
--}}

@php
    $items = collect($histories)->sortBy('created_at');
@endphp

@if($items->isEmpty())
<div class="text-center py-8 text-gray-400 dark:text-gray-500">
    <svg class="mx-auto mb-2 h-8 w-8 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
    </svg>
    <p class="text-sm">No status changes recorded yet.</p>
</div>
@else

<ol class="relative {{ $compact ? 'space-y-3' : 'space-y-4' }}">

    @foreach($items as $index => $entry)
    @php
        $isLast    = $index === $items->count() - 1;
        $fromBadge = $badgeClasses[$entry->from_status] ?? 'bg-gray-100 text-gray-600';
        $toBadge   = $badgeClasses[$entry->to_status]   ?? 'bg-gray-100 text-gray-600';
        $toDot     = $dotClasses[$entry->to_status]     ?? 'bg-gray-400';
        $toLabel   = $statusLabels[$entry->to_status]   ?? ucfirst($entry->to_status);
        $fromLabel = $statusLabels[$entry->from_status] ?? ucfirst($entry->from_status);
        $isReopen  = in_array($entry->from_status, ['resolved','closed']) && $entry->to_status === 'open';
        $isClose   = $entry->to_status === 'closed';
        $isResolve = $entry->to_status === 'resolved';
    @endphp

    <li class="flex gap-4 group">

        {{-- Timeline spine --}}
        <div class="flex flex-col items-center flex-shrink-0">
            {{-- Dot --}}
            <div class="relative z-10 flex items-center justify-center">
                <span class="h-3 w-3 rounded-full ring-2 ring-white dark:ring-gray-800 {{ $toDot }}"></span>
                @if($isLast)
                {{-- Pulsing outer ring on the latest entry --}}
                <span class="absolute h-3 w-3 rounded-full animate-ping opacity-50 {{ $toDot }}"></span>
                @endif
            </div>
            {{-- Connector line --}}
            @if(!$isLast)
            <div class="w-px flex-1 mt-1 mb-0 bg-gray-200 dark:bg-gray-600 min-h-[16px]"></div>
            @endif
        </div>

        {{-- Content --}}
        <div class="flex-1 min-w-0 {{ $compact ? 'pb-3' : 'pb-4' }}">

            {{-- Transition row --}}
            <div class="flex flex-wrap items-center gap-1.5 mb-1">

                {{-- "From" badge --}}
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium opacity-70
                             {{ $fromBadge }}">
                    {{ $fromLabel }}
                </span>

                {{-- Arrow --}}
                <svg class="h-3 w-3 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                </svg>

                {{-- "To" badge --}}
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                             {{ $toBadge }}">
                    {{ $toLabel }}
                </span>

                {{-- Special icons --}}
                @if($isReopen)
                <span class="text-sm" title="Reopened">🔓</span>
                @elseif($isClose)
                <span class="text-sm" title="Closed">🔒</span>
                @elseif($isResolve)
                <span class="text-sm" title="Resolved">✅</span>
                @endif

            </div>

            {{-- Actor + timestamp --}}
            <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <span class="font-medium text-gray-600 dark:text-gray-300">
                    {{ $entry->changedBy?->name ?? 'System' }}
                </span>
                <span class="text-gray-300 dark:text-gray-600">·</span>
                <time datetime="{{ $entry->created_at->toIso8601String() }}"
                      title="{{ $entry->created_at->format('d M Y H:i:s') }}">
                    {{ $entry->created_at->diffForHumans() }}
                </time>

                {{-- Source badge (api / command) --}}
                @if(($entry->metadata['source'] ?? 'web') !== 'web')
                <span class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700
                             text-gray-500 dark:text-gray-400 uppercase tracking-wide font-mono"
                      style="font-size:0.6rem">
                    {{ $entry->metadata['source'] }}
                </span>
                @endif
            </div>

            {{-- Note --}}
            @if($entry->note)
            <p class="mt-1.5 text-xs text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-gray-750
                      rounded-md px-3 py-2 italic border-l-2 {{ $toDot === 'bg-gray-400' ? 'border-gray-300' : str_replace('bg-', 'border-', $toDot) }}">
                "{{ $entry->note }}"
            </p>
            @endif

        </div>
    </li>
    @endforeach

</ol>
@endif
