@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('content')
<h1 class="text-2xl font-bold mb-4">Dashboard</h1>

<div class="grid md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border p-4">
        <div class="text-xs text-slate-500">Order hari ini</div>
        <div class="text-3xl font-bold">{{ $stats['orders_today'] }}</div>
    </div>
    <div class="bg-white rounded-xl border p-4">
        <div class="text-xs text-slate-500">Siap dipacking</div>
        <div class="text-3xl font-bold text-sky-700">{{ $stats['ready_to_pack'] }}</div>
    </div>
    <div class="bg-white rounded-xl border p-4">
        <div class="text-xs text-slate-500">Dipacking hari ini</div>
        <div class="text-3xl font-bold text-emerald-700">{{ $stats['packed_today'] }}</div>
    </div>
    <div class="bg-white rounded-xl border p-4">
        <div class="text-xs text-slate-500">Stok menipis</div>
        <div class="text-3xl font-bold text-amber-700">{{ $stats['low_stock'] }}</div>
    </div>
</div>

@if($unmapped > 0)
    <div class="mb-6 bg-amber-50 border border-amber-200 text-amber-900 rounded-lg p-4">
        <strong>{{ $unmapped }}</strong> order punya item TikTok yang belum ter-mapping ke produk toko.
        <a href="{{ route('admin.mappings.index') }}" class="underline font-semibold">Mapping sekarang &rarr;</a>
    </div>
@endif

<div class="bg-white rounded-xl border">
    <div class="px-4 py-3 border-b flex items-center justify-between">
        <div class="font-semibold">Recently Packed</div>
        <a href="{{ route('admin.orders.index') }}" class="text-sm text-emerald-700 hover:underline">Semua order &rarr;</a>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left">
            <tr><th class="px-4 py-2">Waktu</th><th class="px-4 py-2">Order ID</th><th class="px-4 py-2">Resi</th><th class="px-4 py-2">Packed By</th></tr>
        </thead>
        <tbody>
            @forelse($recentPacked as $o)
                <tr class="border-t">
                    <td class="px-4 py-2">{{ $o->packed_at?->format('d/m H:i') }}</td>
                    <td class="px-4 py-2 font-mono"><a href="{{ route('admin.orders.show', $o) }}" class="text-emerald-700 hover:underline">{{ $o->tiktok_order_id }}</a></td>
                    <td class="px-4 py-2 font-mono">{{ $o->tracking_number }}</td>
                    <td class="px-4 py-2">{{ $o->packedBy?->name ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Belum ada.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
