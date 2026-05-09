@extends('layouts.app')
@section('title', $user->exists ? 'Edit User' : 'Tambah User')
@section('content')
<h1 class="text-2xl font-bold mb-4">{{ $user->exists ? 'Edit User' : 'Tambah User' }}</h1>

<form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}"
      class="grid md:grid-cols-2 gap-4 bg-white rounded-xl border p-5">
    @csrf @if($user->exists) @method('PUT') @endif

    <div>
        <label class="block text-sm mb-1">Nama</label>
        <input name="name" value="{{ old('name', $user->name) }}" required class="w-full border rounded px-3 py-2">
    </div>
    <div>
        <label class="block text-sm mb-1">Email</label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full border rounded px-3 py-2">
    </div>
    <div>
        <label class="block text-sm mb-1">Password {{ $user->exists ? '(kosongkan jika tidak diubah)' : '' }}</label>
        <input type="password" name="password" {{ $user->exists ? '' : 'required' }} class="w-full border rounded px-3 py-2">
    </div>
    <div>
        <label class="block text-sm mb-1">Role</label>
        <select name="role" required class="w-full border rounded px-3 py-2">
            <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
            <option value="packer" {{ old('role', $user->role) === 'packer' ? 'selected' : '' }}>Packer</option>
        </select>
    </div>
    <div class="flex items-center gap-2 md:col-span-2">
        <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $user->is_active ?? true) ? 'checked' : '' }}>
        <label for="is_active">Aktif</label>
    </div>
    <div class="md:col-span-2 flex justify-between">
        <a href="{{ route('admin.users.index') }}" class="text-slate-500 hover:underline">&larr; Kembali</a>
        <button class="bg-emerald-600 hover:bg-emerald-700 text-white rounded px-5 py-2 font-medium">Simpan</button>
    </div>
</form>
@endsection
