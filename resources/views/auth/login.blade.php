@extends('layouts.app')
@section('title', 'Login')
@section('content')
<div class="min-h-[70vh] flex items-center justify-center">
    <div class="w-full max-w-sm bg-white p-6 rounded-xl shadow">
        <h1 class="text-xl font-bold mb-1">Packing Stok</h1>
        <p class="text-sm text-slate-500 mb-5">Masuk untuk melanjutkan.</p>

        @if($errors->any())
            <div class="mb-3 rounded bg-red-50 border border-red-200 text-red-800 px-3 py-2 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-3">@csrf
            <div>
                <label class="block text-sm mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-sm mb-1">Password</label>
                <input type="password" name="password" required
                       class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-emerald-500 focus:outline-none">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded"> Ingat saya
            </label>
            <button class="w-full bg-emerald-600 hover:bg-emerald-700 text-white rounded py-2 font-medium">Login</button>
        </form>
    </div>
</div>
@endsection
