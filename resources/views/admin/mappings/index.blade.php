@extends('layouts.app')
@section('title', 'Mapping SKU')
@section('content')
<h1 class="text-2xl font-bold mb-4">Mapping SKU TikTok &rarr; Produk</h1>
<p class="text-sm text-slate-600 mb-4">Item order yang belum terhubung ke produk di toko. Pilih produk yang sesuai agar bisa di-scan packing.</p>

<div class="bg-white rounded-xl border overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left">
            <tr>
                <th class="px-4 py-2">SKU TikTok</th>
                <th class="px-4 py-2">Nama Produk TikTok</th>
                <th class="px-4 py-2">Qty</th>
                <th class="px-4 py-2">Order</th>
                <th class="px-4 py-2">Mapping ke Produk</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $it)
                <tr class="border-t">
                    <form method="POST" action="{{ route('admin.mappings.update', $it) }}">@csrf
                        <td class="px-4 py-2 font-mono">{{ $it->tiktok_sku }}</td>
                        <td class="px-4 py-2">{{ $it->tiktok_product_name }}</td>
                        <td class="px-4 py-2">{{ $it->qty }}</td>
                        <td class="px-4 py-2 font-mono text-xs">{{ $it->order->tiktok_order_id }}</td>
                        <td class="px-4 py-2">
                            <select name="product_id" required class="border rounded px-2 py-1">
                                <option value="">-- Pilih produk --</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }}) · stok {{ $p->stock }}</option>
                                @endforeach
                            </select>
                            <label class="ml-2 text-xs"><input type="checkbox" name="apply_all_same_sku" value="1" checked> Apply ke semua SKU sama</label>
                        </td>
                        <td class="px-4 py-2 text-right"><button class="bg-emerald-600 hover:bg-emerald-700 text-white rounded px-3 py-1 text-sm">Simpan</button></td>
                    </form>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-center text-slate-500">Semua item sudah ter-mapping &#128077;</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
