@extends('layouts.admin')
@section('title', 'Floor Plans')
@section('heading', 'Floor Plans')
@section('subheading', 'แผนผังสถานที่ติดตั้ง locker')

@section('content')

<div class="overflow-hidden rounded-xl bg-white sneat-shadow">
    <div class="border-b border-[#dbdade] px-5 py-4">
        <h2 class="text-sm font-semibold text-slate-800">All Floor Plans</h2>
    </div>
    <table class="min-w-full text-sm">
        <thead class="border-b border-[#dbdade] bg-[#f5f5f9]">
            <tr>
                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#a5a3ae]">Name</th>
                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[#a5a3ae]">Location</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[#f0f0f0]">
            @forelse($plans as $plan)
            <tr class="transition-colors hover:bg-[#f9f9fc]">
                <td class="px-5 py-3.5 font-medium text-slate-800">{{ $plan->name }}</td>
                <td class="px-5 py-3.5 text-[#5d596c]">{{ $plan->location?->name ?? '—' }}</td>
                <td class="px-5 py-3.5 text-right">
                    <a href="{{ route('admin.floor-plans.show', $plan) }}"
                       class="text-xs font-medium text-[#7367f0] hover:underline">View →</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="3" class="px-5 py-14 text-center">
                    <div class="flex flex-col items-center gap-3 text-[#a5a3ae]">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#f5f5f9]">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z"/>
                            </svg>
                        </div>
                        <p class="font-medium text-[#5d596c]">ไม่พบแผนผัง</p>
                    </div>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
    @if($plans->hasPages())
    <div class="border-t border-[#dbdade] px-5 py-3">{{ $plans->links() }}</div>
    @endif
</div>

@endsection
