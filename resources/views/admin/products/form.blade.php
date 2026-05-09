@extends('layouts.app')
@section('title', $product->exists ? 'Edit Produk' : 'Tambah Produk')
@section('content')
<h1 class="text-2xl font-bold mb-4">{{ $product->exists ? 'Edit Produk' : 'Tambah Produk' }}</h1>

<form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}"
      class="grid md:grid-cols-2 gap-4 bg-white rounded-xl border p-5">
    @csrf
    @if($product->exists) @method('PUT') @endif

    <div>
        <label class="block text-sm mb-1">SKU</label>
        <input name="sku" value="{{ old('sku', $product->sku) }}" required class="w-full border rounded px-3 py-2 font-mono">
    </div>
    <div>
        <label class="block text-sm mb-1">Nama Produk</label>
        <input name="name" value="{{ old('name', $product->name) }}" required class="w-full border rounded px-3 py-2">
    </div>
    <div class="md:col-span-2">
        <label class="block text-sm mb-1">Deskripsi</label>
        <textarea name="description" rows="3" class="w-full border rounded px-3 py-2">{{ old('description', $product->description) }}</textarea>
    </div>
    <div>
        <label class="block text-sm mb-1">Harga (Rp)</label>
        <input type="number" step="0.01" name="price" value="{{ old('price', $product->price ?? 0) }}" required class="w-full border rounded px-3 py-2">
    </div>
    <div>
        <label class="block text-sm mb-1">Stok</label>
        <input type="number" name="stock" value="{{ old('stock', $product->stock ?? 0) }}" required class="w-full border rounded px-3 py-2">
        @if($product->exists)
            <p class="text-xs text-slate-500 mt-1">Perubahan stok akan dicatat sebagai ADJUST di riwayat.</p>
        @endif
    </div>
    <div>
        <label class="block text-sm mb-1">Batas stok menipis</label>
        <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', $product->low_stock_threshold ?? 5) }}" required class="w-full border rounded px-3 py-2">
    </div>
    <div class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $product->is_active ?? true) ? 'checked' : '' }}>
        <label for="is_active">Aktif</label>
    </div>

    <div class="md:col-span-2 flex justify-between">
        <a href="{{ route('admin.products.index') }}" class="text-slate-500 hover:underline">&larr; Kembali</a>
        <button class="bg-emerald-600 hover:bg-emerald-700 text-white rounded px-5 py-2 font-medium">Simpan</button>
    </div>
</form>

@if(!empty($movements))
    <div class="mt-6 bg-white rounded-xl border">
        <div class="px-4 py-3 border-b font-semibold">Riwayat Pergerakan Stok</div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left">
                <tr><th class="px-4 py-2">Waktu</th><th class="px-4 py-2">Tipe</th><th class="px-4 py-2 text-right">Qty</th><th class="px-4 py-2">Referensi</th><th class="px-4 py-2">User</th><th class="px-4 py-2">Catatan</th></tr>
            </thead>
            <tbody>
                @foreach($movements as $m)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $m->created_at->format('d/m H:i') }}</td>
                        <td class="px-4 py-2">{{ $m->type }}</td>
                        <td class="px-4 py-2 text-right font-mono {{ $m->qty < 0 ? 'text-red-700' : 'text-emerald-700' }}">{{ $m->qty > 0 ? '+' : '' }}{{ $m->qty }}</td>
                        <td class="px-4 py-2">{{ $m->reference_type }}#{{ $m->reference_id }}</td>
                        <td class="px-4 py-2">{{ $m->user?->name ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $m->note }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
