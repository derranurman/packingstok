@extends('layouts.app')
@section('title', 'Produk')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">Produk</h1>
    <a href="{{ route('admin.products.create') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white rounded px-4 py-2">+ Tambah Produk</a>
</div>

<form class="mb-4">
    <input type="text" name="q" value="{{ $q }}" placeholder="Cari nama atau SKU..." class="border rounded px-3 py-2 w-full max-w-sm">
</form>

<div class="bg-white rounded-xl border overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left">
            <tr>
                <th class="px-4 py-2">SKU</th>
                <th class="px-4 py-2">Nama</th>
                <th class="px-4 py-2 text-right">Harga</th>
                <th class="px-4 py-2 text-right">Stok</th>
                <th class="px-4 py-2">Status</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($products as $p)
                <tr class="border-t {{ $p->isLowStock() ? 'bg-amber-50' : '' }}">
                    <td class="px-4 py-2 font-mono">{{ $p->sku }}</td>
                    <td class="px-4 py-2">{{ $p->name }}</td>
                    <td class="px-4 py-2 text-right">Rp{{ number_format($p->price, 0, ',', '.') }}</td>
                    <td class="px-4 py-2 text-right font-semibold {{ $p->isLowStock() ? 'text-amber-700' : '' }}">{{ $p->stock }}</td>
                    <td class="px-4 py-2">
                        @if($p->is_active)
                            <span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-800">aktif</span>
                        @else
                            <span class="text-xs px-2 py-0.5 rounded bg-slate-100 text-slate-600">nonaktif</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-right">
                        <a href="{{ route('admin.products.edit', $p) }}" class="text-sky-700 hover:underline">Edit</a>
                        <form action="{{ route('admin.products.destroy', $p) }}" method="POST" class="inline" onsubmit="return confirm('Hapus produk ini?')">
                            @csrf @method('DELETE')
                            <button class="text-red-600 hover:underline ml-2">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Belum ada produk.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $products->links() }}</div>
@endsection
