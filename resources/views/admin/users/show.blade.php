@extends('layouts.admin')
@section('title', $user->name)
@section('heading', 'Edit User')
@section('subheading', $user->email)

@section('content')

<div class="max-w-lg">
    <div class="overflow-hidden rounded-xl bg-white sneat-shadow">
        <div class="border-b border-[#dbdade] px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-800">Account Details</h2>
        </div>
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-4 p-5">
            @csrf @method('PUT')

            <div>
                <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">Name</label>
                <input name="name" value="{{ old('name', $user->name) }}" required
                       class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">Email</label>
                <input name="email" type="email" value="{{ old('email', $user->email) }}" required
                       class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]">
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium text-[#a5a3ae]">Roles</label>
                <select name="roles[]" multiple
                        class="w-full rounded-lg border border-[#dbdade] bg-white px-3 py-2 text-sm text-[#5d596c] focus:border-[#7367f0] focus:outline-none focus:ring-1 focus:ring-[#7367f0]"
                        style="height: 8rem;">
                    @foreach($roles as $role)
                    <option value="{{ $role }}" @selected($user->hasRole($role))>{{ $role }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-[#a5a3ae]">Hold Ctrl / Cmd to select multiple roles</p>
            </div>

            <div class="flex items-center gap-3 pt-1">
                <button type="submit"
                        class="rounded-lg bg-[#7367f0] px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#6259d4]">
                    Save changes
                </button>
                <a href="{{ route('admin.users.index') }}"
                   class="rounded-lg border border-[#dbdade] px-4 py-2 text-sm font-medium text-[#5d596c] transition-colors hover:bg-[#f5f5f9]">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
