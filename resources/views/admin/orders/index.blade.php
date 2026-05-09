@extends('layouts.app')
@section('title', 'Order')
@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <h1 class="text-2xl font-bold">Order</h1>
    <form method="POST" action="{{ route('admin.orders.sync') }}">@csrf
        <button class="bg-emerald-600 hover:bg-emerald-700 text-white rounded px-4 py-2">Sync dari TikTok</button>
    </form>
</div>

<form class="mb-4 flex gap-2 flex-wrap">
    <input type="text" name="q" value="{{ $q }}" placeholder="Order ID / resi / pembeli..." class="border rounded px-3 py-2 flex-1 max-w-sm">
    <select name="status" class="border rounded px-3 py-2">
        <option value="">Semua status</option>
        @foreach(['pending', 'ready_to_pack', 'packed', 'cancelled'] as $s)
            <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ $s }}</option>
        @endforeach
    </select>
    <button class="bg-slate-100 rounded px-3 py-2">Filter</button>
</form>

<div class="bg-white rounded-xl border overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left">
            <tr>
                <th class="px-4 py-2">Tanggal</th>
                <th class="px-4 py-2">Order ID</th>
                <th class="px-4 py-2">Resi</th>
                <th class="px-4 py-2">Pembeli</th>
                <th class="px-4 py-2">Item</th>
                <th class="px-4 py-2">Status</th>
                <th class="px-4 py-2">Packed By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($orders as $o)
                <tr class="border-t align-top">
                    <td class="px-4 py-2 whitespace-nowrap">{{ $o->created_at->format('d/m H:i') }}</td>
                    <td class="px-4 py-2 font-mono"><a href="{{ route('admin.orders.show', $o) }}" class="text-emerald-700 hover:underline">{{ $o->tiktok_order_id }}</a></td>
                    <td class="px-4 py-2 font-mono">{{ $o->tracking_number }}</td>
                    <td class="px-4 py-2">{{ $o->buyer_name ?? '-' }}</td>
                    <td class="px-4 py-2">
                        @foreach($o->items as $it)
                            <div class="{{ !$it->product_id ? 'text-amber-700' : '' }}">
                                {{ $it->qty }}× {{ $it->product?->name ?? $it->tiktok_product_name }}
                                @if(!$it->product_id) <span class="text-xs">[belum mapping]</span> @endif
                            </div>
                        @endforeach
                    </td>
                    <td class="px-4 py-2">
                        @php $color = ['ready_to_pack' => 'sky', 'packed' => 'emerald', 'cancelled' => 'red', 'pending' => 'slate'][$o->status] ?? 'slate'; @endphp
                        <span class="text-xs px-2 py-0.5 rounded bg-{{ $color }}-100 text-{{ $color }}-800">{{ $o->status }}</span>
                    </td>
                    <td class="px-4 py-2">{{ $o->packedBy?->name ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500">Belum ada order. Klik "Sync dari TikTok".</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $orders->links() }}</div>
@endsection
