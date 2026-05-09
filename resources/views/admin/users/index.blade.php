@extends('layouts.app')
@section('title', 'User')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">User</h1>
    <a href="{{ route('admin.users.create') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white rounded px-4 py-2">+ Tambah User</a>
</div>

<div class="bg-white rounded-xl border overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left">
            <tr><th class="px-4 py-2">Nama</th><th class="px-4 py-2">Email</th><th class="px-4 py-2">Role</th><th class="px-4 py-2">Status</th><th class="px-4 py-2"></th></tr>
        </thead>
        <tbody>
            @foreach($users as $u)
                <tr class="border-t">
                    <td class="px-4 py-2">{{ $u->name }}</td>
                    <td class="px-4 py-2">{{ $u->email }}</td>
                    <td class="px-4 py-2"><span class="text-xs px-2 py-0.5 rounded bg-slate-100">{{ $u->role }}</span></td>
                    <td class="px-4 py-2">
                        @if($u->is_active)
                            <span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">aktif</span>
                        @else
                            <span class="text-xs px-2 py-0.5 rounded bg-slate-100">nonaktif</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-right">
                        <a href="{{ route('admin.users.edit', $u) }}" class="text-sky-700 hover:underline">Edit</a>
                        @if($u->id !== auth()->id())
                            <form method="POST" action="{{ route('admin.users.destroy', $u) }}" class="inline" onsubmit="return confirm('Hapus user ini?')">
                                @csrf @method('DELETE')
                                <button class="text-red-600 hover:underline ml-2">Hapus</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $users->links() }}</div>
@endsection
