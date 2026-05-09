@extends('layouts.app')
@section('title', 'Order ' . $order->tiktok_order_id)
@section('content')
<a href="{{ route('admin.orders.index') }}" class="text-slate-500 hover:underline">&larr; Kembali</a>
<h1 class="text-2xl font-bold mb-4">Order {{ $order->tiktok_order_id }}</h1>

<div class="grid md:grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-xl border p-4 text-sm space-y-1">
        <div><span class="text-slate-500">Status:</span> <strong>{{ $order->status }}</strong></div>
        <div><span class="text-slate-500">Resi:</span> <span class="font-mono">{{ $order->tracking_number ?? '-' }}</span> ({{ $order->courier }})</div>
        <div><span class="text-slate-500">Pembeli:</span> {{ $order->buyer_name ?? '-' }}</div>
        <div><span class="text-slate-500">Total:</span> Rp{{ number_format($order->total_amount, 0, ',', '.') }}</div>
        <div><span class="text-slate-500">Dibuat:</span> {{ $order->created_at->format('d/m/Y H:i') }}</div>
        @if($order->packed_at)
            <div><span class="text-slate-500">Dipacking:</span> {{ $order->packed_at->format('d/m/Y H:i') }} oleh {{ $order->packedBy?->name }}</div>
        @endif
    </div>
    <div class="bg-white rounded-xl border p-4">
        <div class="font-semibold mb-2">Raw Payload</div>
        <pre class="text-xs bg-slate-50 p-2 rounded overflow-auto max-h-64">{{ json_encode($order->raw_payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
</div>

<div class="bg-white rounded-xl border">
    <div class="px-4 py-3 border-b font-semibold">Item</div>
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left">
            <tr><th class="px-4 py-2">SKU TikTok</th><th class="px-4 py-2">Nama (TikTok)</th><th class="px-4 py-2">Mapping Produk</th><th class="px-4 py-2 text-right">Qty</th><th class="px-4 py-2 text-right">Harga</th></tr>
        </thead>
        <tbody>
            @foreach($order->items as $it)
                <tr class="border-t">
                    <td class="px-4 py-2 font-mono">{{ $it->tiktok_sku }}</td>
                    <td class="px-4 py-2">{{ $it->tiktok_product_name }}</td>
                    <td class="px-4 py-2">
                        @if($it->product)
                            {{ $it->product->name }} <span class="text-xs text-slate-500">({{ $it->product->sku }})</span>
                        @else
                            <span class="text-amber-700">belum mapping</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-right">{{ $it->qty }}</td>
                    <td class="px-4 py-2 text-right">Rp{{ number_format($it->price, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
