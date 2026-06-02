@extends('layouts.admin')
@section('title', $floorPlan->name)
@section('heading', $floorPlan->name)
@section('subheading', 'Floor plan details and locker placements')

@section('content')

<div class="max-w-2xl space-y-5">

    {{-- Info card --}}
    <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
        <div class="border-b border-[#dbdade] px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-800">Floor Plan Info</h2>
        </div>
        <div class="divide-y divide-[#f0f0f0]">
            <div class="flex items-center justify-between px-5 py-3">
                <span class="text-xs font-medium text-[#a5a3ae]">Location</span>
                <span class="text-sm text-[#5d596c]">{{ $floorPlan->location?->name ?? '—' }}</span>
            </div>
            <div class="flex items-center justify-between px-5 py-3">
                <span class="text-xs font-medium text-[#a5a3ae]">Company</span>
                <span class="text-sm text-[#5d596c]">{{ $floorPlan->company?->name ?? '—' }}</span>
            </div>
        </div>
    </div>

    {{-- Placements card --}}
    <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
        <div class="flex items-center justify-between border-b border-[#dbdade] px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-800">Locker Placements</h2>
            <span class="rounded-full bg-[#ece9fd] px-2.5 py-0.5 text-xs font-bold text-[#7367f0]">{{ $placements->count() }}</span>
        </div>
        @if($placements->count())
        <div class="divide-y divide-[#f0f0f0]">
            @foreach($placements as $p)
            <div class="flex items-center justify-between px-5 py-3">
                <span class="text-sm font-medium text-slate-800">{{ $p->locker?->name ?? '—' }}</span>
                <span class="font-mono text-xs text-[#a5a3ae]">x: {{ $p->x }}, y: {{ $p->y }}</span>
            </div>
            @endforeach
        </div>
        @else
        <div class="flex flex-col items-center gap-3 py-12 text-center text-[#a5a3ae]">
            <p class="text-sm font-medium text-[#5d596c]">No placements configured</p>
        </div>
        @endif
    </div>

    <a href="{{ route('admin.floor-plans.index') }}"
       class="inline-flex items-center gap-1 text-sm font-medium text-[#7367f0] hover:underline">
        ← Back to floor plans
    </a>

</div>

@endsection
