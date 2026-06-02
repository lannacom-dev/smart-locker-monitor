@extends('layouts.admin')
@section('title', 'Companies')
@section('heading', 'Companies')
@section('subheading', 'parent-child hierarchy + API endpoint configuration')

@section('content')

@php
    $byParent = $companies->groupBy('parent_company_id');
    $roots    = $byParent->get(null, collect());

    /**
     * Render one tree node (and its children recursively).
     */
    $renderNode = function ($company, int $depth = 0) use (&$renderNode, $byParent): string {
        $children   = $byParent->get($company->id, collect());
        $hasKids    = $children->isNotEmpty();
        $childrenId = 'cmp-c-' . $company->id;

        $apiBg     = $company->api_enabled
            ? 'bg-[#dff7e9] text-[#28c76f]'
            : 'bg-[#f5f5f9] text-[#a5a3ae]';
        $apiDot    = $company->api_enabled ? 'bg-[#28c76f]' : 'bg-[#a5a3ae]';
        $activeDot = ($company->is_active ?? true)  ? 'bg-[#28c76f]' : 'bg-[#ea5455]';
        $ml        = $depth > 0 ? 'ml-8' : '';

        $h  = '<div class="company-node ' . $ml . ' mb-1">';

        // ── row: [chevron] [card] ──────────────────────────────
        $h .= '<div class="flex items-start gap-1">';

        // chevron (or spacer)
        if ($hasKids) {
            $h .= '<button
                     data-target="' . $childrenId . '"
                     onclick="cmpToggle(this)"
                     class="cmp-btn mt-3.5 flex h-5 w-5 shrink-0 items-center justify-center rounded text-[#a5a3ae] transition-colors hover:text-[#7367f0] focus:outline-none"
                     aria-expanded="' . ($depth === 0 ? 'true' : 'false') . '">';
            $h .= '<svg class="cmp-chevron h-4 w-4 transition-transform duration-200 '
                  . ($depth === 0 ? 'rotate-90' : '') . '"'
                  . ' fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">'
                  . '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>'
                  . '</svg>';
            $h .= '</button>';
        } else {
            $h .= '<div class="mt-3.5 h-5 w-5 shrink-0"></div>';
        }

        // card
        $h .= '<div class="min-w-0 flex-1 overflow-hidden rounded-xl bg-white sneat-shadow">';

        // header
        $h .= '<div class="flex items-center justify-between gap-2 border-b border-[#dbdade] px-4 py-2.5">';
        $h .= '<div class="flex min-w-0 items-center gap-2">';
        // depth pips
        if ($depth > 0) {
            $h .= '<div class="flex gap-0.5 shrink-0">';
            for ($i = 0; $i < $depth; $i++) {
                $h .= '<span class="h-1.5 w-1.5 rounded-full bg-[#ece9fd]"></span>';
            }
            $h .= '</div>';
        }
        $h .= '<p class="truncate text-sm font-semibold text-slate-800">' . e($company->name) . '</p>';
        $h .= '<span class="shrink-0 font-mono text-[11px] text-[#a5a3ae]">(' . e($company->code) . ')</span>';
        $h .= '</div>'; // left

        // right badges + edit
        $h .= '<div class="flex shrink-0 items-center gap-2">';
        if ($hasKids) {
            $h .= '<span class="rounded-full bg-[#ece9fd] px-2 py-0.5 text-[11px] font-semibold text-[#7367f0]">'
                . $children->count() . ' sub</span>';
        }
        $h .= '<span class="inline-flex items-center gap-1 rounded-full ' . $apiBg . ' px-2 py-0.5 text-[11px] font-semibold">'
            . '<span class="h-1.5 w-1.5 rounded-full ' . $apiDot . '"></span> API</span>';
        $h .= '<a href="' . route('admin.companies.edit', $company) . '"'
            . ' class="rounded-lg border border-[#dbdade] px-2.5 py-1 text-xs font-medium text-[#5d596c] transition-colors'
            . ' hover:border-[#7367f0] hover:bg-[#ece9fd] hover:text-[#7367f0]">Edit</a>';
        $h .= '</div>';
        $h .= '</div>'; // header

        // body: meta row
        $h .= '<div class="flex flex-wrap items-center gap-x-4 gap-y-1 px-4 py-2 text-xs">';

        // parent
        $h .= '<span class="flex items-center gap-1 text-[#a5a3ae]">'
            . '<svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">'
            . '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z"/>'
            . '</svg>'
            . e($company->parentCompany?->name ?? 'Root') . '</span>';

        // endpoint
        if ($company->api_base_url) {
            $h .= '<span class="flex min-w-0 items-center gap-1 text-[#a5a3ae]">'
                . '<svg class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">'
                . '<path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/>'
                . '</svg>'
                . '<span class="truncate font-mono">' . e($company->api_base_url) . '</span>'
                . '</span>';
        } else {
            $h .= '<span class="italic text-[#dbdade]">no endpoint</span>';
        }

        // active
        $h .= '<span class="ml-auto flex items-center gap-1">'
            . '<span class="h-1.5 w-1.5 rounded-full ' . $activeDot . '"></span>'
            . '<span class="text-[#a5a3ae]">' . (($company->is_active ?? true) ? 'Active' : 'Inactive') . '</span>'
            . '</span>';

        $h .= '</div>'; // body
        $h .= '</div>'; // card
        $h .= '</div>'; // row flex

        // children panel ── hidden by default (JS opens depth-0 on load)
        if ($hasKids) {
            $initialStyle = $depth === 0 ? '' : 'display:none';
            $h .= '<div id="' . $childrenId . '" class="cmp-children border-l-2 border-[#ece9fd] pl-5 ml-2.5 mt-1 space-y-0"'
                . ' style="' . $initialStyle . '">';
            foreach ($children as $child) {
                $h .= $renderNode($child, $depth + 1);
            }
            $h .= '</div>';
        }

        $h .= '</div>'; // company-node
        return $h;
    };
@endphp

{{-- ── Toolbar ── --}}
<div class="mb-5 flex items-center justify-between">
    <div class="flex items-center gap-2">
        <p class="text-sm text-[#5d596c]">
            Total: <span class="font-semibold text-slate-800">{{ $companies->count() }}</span> companies
        </p>
        <button onclick="cmpExpandAll()"
                class="rounded-lg border border-[#dbdade] px-3 py-1 text-xs font-medium text-[#5d596c] transition-colors hover:bg-[#f5f5f9]">
            Expand all
        </button>
        <button onclick="cmpCollapseAll()"
                class="rounded-lg border border-[#dbdade] px-3 py-1 text-xs font-medium text-[#5d596c] transition-colors hover:bg-[#f5f5f9]">
            Collapse all
        </button>
    </div>
    <a href="{{ route('admin.companies.create') }}"
       class="inline-flex items-center gap-1.5 rounded-lg bg-[#7367f0] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#6259d4]">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        New Company
    </a>
</div>

{{-- ── Tree ── --}}
@if($roots->isEmpty())
<div class="flex flex-col items-center gap-3 rounded-xl border border-dashed border-[#dbdade] py-16 text-center">
    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f5f5f9]">
        <svg class="h-6 w-6 text-[#a5a3ae]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18"/>
        </svg>
    </div>
    <p class="font-medium text-[#5d596c]">ไม่พบบริษัท</p>
    <a href="{{ route('admin.companies.create') }}" class="text-xs font-medium text-[#7367f0] hover:underline">เพิ่มบริษัทใหม่ →</a>
</div>
@else
<div id="company-tree" class="space-y-1">
    @foreach($roots as $root)
    {!! $renderNode($root, 0) !!}
    @endforeach
</div>
@endif

@endsection

@push('scripts')
<script>
(function () {
    /* ── single toggle ── */
    window.cmpToggle = function (btn) {
        var id  = btn.dataset.target;
        var el  = document.getElementById(id);
        var ch  = btn.querySelector('.cmp-chevron');
        if (!el) return;

        var open = btn.getAttribute('aria-expanded') === 'true';

        if (open) {
            // collapse
            el.style.overflow   = 'hidden';
            el.style.maxHeight  = el.scrollHeight + 'px';
            el.style.transition = 'max-height .22s ease, opacity .18s ease';
            el.style.opacity    = '1';
            requestAnimationFrame(function () {
                el.style.maxHeight = '0';
                el.style.opacity   = '0';
            });
            setTimeout(function () {
                el.style.display  = 'none';
                el.style.maxHeight = el.style.overflow = el.style.opacity = el.style.transition = '';
            }, 230);
            btn.setAttribute('aria-expanded', 'false');
            if (ch) ch.classList.remove('rotate-90');
        } else {
            // expand
            el.style.display    = 'block';
            el.style.overflow   = 'hidden';
            el.style.maxHeight  = '0';
            el.style.opacity    = '0';
            el.style.transition = 'max-height .22s ease, opacity .18s ease';
            requestAnimationFrame(function () {
                el.style.maxHeight = el.scrollHeight + 'px';
                el.style.opacity   = '1';
            });
            setTimeout(function () {
                el.style.maxHeight = el.style.overflow = el.style.opacity = el.style.transition = '';
            }, 230);
            btn.setAttribute('aria-expanded', 'true');
            if (ch) ch.classList.add('rotate-90');
        }
    };

    /* ── expand / collapse all ── */
    window.cmpExpandAll = function () {
        document.querySelectorAll('.cmp-children').forEach(function (el) {
            el.style.display   = 'block';
            el.style.maxHeight = el.style.overflow = el.style.opacity = '';
        });
        document.querySelectorAll('.cmp-btn').forEach(function (btn) {
            btn.setAttribute('aria-expanded', 'true');
            var ch = btn.querySelector('.cmp-chevron');
            if (ch) ch.classList.add('rotate-90');
        });
    };

    window.cmpCollapseAll = function () {
        document.querySelectorAll('.cmp-children').forEach(function (el) {
            el.style.display = 'none';
        });
        document.querySelectorAll('.cmp-btn').forEach(function (btn) {
            btn.setAttribute('aria-expanded', 'false');
            var ch = btn.querySelector('.cmp-chevron');
            if (ch) ch.classList.remove('rotate-90');
        });
    };
})();
</script>
@endpush
