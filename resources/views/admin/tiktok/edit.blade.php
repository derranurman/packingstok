@extends('layouts.app')
@section('title', 'Kredensial TikTok')
@section('content')
<h1 class="text-2xl font-bold mb-4">Kredensial TikTok Shop</h1>

<div class="mb-4 rounded-lg border bg-sky-50 border-sky-200 text-sky-900 p-4 text-sm">
    <strong>Mode <code>mock</code></strong> membaca file <code>storage/app/tiktok/mock_orders.json</code> untuk simulasi.
    Pakai mode ini selama menunggu akun TikTok Partner disetujui.<br>
    <strong>Mode <code>live</code></strong> melakukan call ke TikTok Open API. Wajib isi semua kredensial.
    <br>
    Status: terakhir polling <strong>{{ $cred->last_polled_at?->diffForHumans() ?? 'belum pernah' }}</strong>.
</div>

<form method="POST" action="{{ route('admin.tiktok.update') }}" class="grid md:grid-cols-2 gap-4 bg-white rounded-xl border p-5">
    @csrf @method('PUT')

    <div>
        <label class="block text-sm mb-1">Mode</label>
        <select name="mode" class="w-full border rounded px-3 py-2">
            <option value="mock" {{ $cred->mode === 'mock' ? 'selected' : '' }}>Mock (testing)</option>
            <option value="live" {{ $cred->mode === 'live' ? 'selected' : '' }}>Live (production)</option>
        </select>
    </div>
    <div></div>

    <div>
        <label class="block text-sm mb-1">App Key</label>
        <input name="app_key" value="{{ old('app_key', $cred->app_key) }}" class="w-full border rounded px-3 py-2 font-mono">
    </div>
    <div>
        <label class="block text-sm mb-1">App Secret</label>
        <input type="password" name="app_secret" placeholder="{{ $cred->app_secret ? '(ada, kosongkan jika tidak diubah)' : '' }}" class="w-full border rounded px-3 py-2 font-mono">
    </div>
    <div>
        <label class="block text-sm mb-1">Shop Cipher</label>
        <input name="shop_cipher" value="{{ old('shop_cipher', $cred->shop_cipher) }}" class="w-full border rounded px-3 py-2 font-mono">
    </div>
    <div></div>
    <div>
        <label class="block text-sm mb-1">Access Token</label>
        <input type="password" name="access_token" placeholder="{{ $cred->access_token ? '(ada, kosongkan jika tidak diubah)' : '' }}" class="w-full border rounded px-3 py-2 font-mono">
    </div>
    <div>
        <label class="block text-sm mb-1">Refresh Token</label>
        <input type="password" name="refresh_token" placeholder="{{ $cred->refresh_token ? '(ada, kosongkan jika tidak diubah)' : '' }}" class="w-full border rounded px-3 py-2 font-mono">
    </div>

    <div class="md:col-span-2 flex justify-end">
        <button class="bg-emerald-600 hover:bg-emerald-700 text-white rounded px-5 py-2 font-medium">Simpan</button>
    </div>
</form>
@endsection
