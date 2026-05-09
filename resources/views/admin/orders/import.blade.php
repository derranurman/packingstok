@extends('layouts.app')
@section('title', 'Import Order TikTok')
@section('content')
<a href="{{ route('admin.orders.index') }}" class="text-slate-500 hover:underline">&larr; Kembali ke Order</a>
<h1 class="text-2xl font-bold mb-4">Import Order dari TikTok</h1>

<div class="grid md:grid-cols-3 gap-6">
    <div class="md:col-span-2 bg-white rounded-xl border p-5">
        <form method="POST" action="{{ route('admin.orders.import') }}" enctype="multipart/form-data">
            @csrf
            <label class="block text-sm font-medium mb-2">File CSV / Excel dari TikTok Seller Center</label>
            <input type="file" name="file" accept=".csv,.xlsx,.xls,.txt" required
                   class="block w-full text-sm border rounded px-3 py-2">
            <p class="text-xs text-slate-500 mt-2">
                Max 20 MB. Format yang didukung: <code>.csv</code>, <code>.xlsx</code>, <code>.xls</code>.
            </p>
            <button class="mt-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded px-5 py-2 font-medium">
                Upload &amp; Import
            </button>
        </form>
    </div>

    <div class="bg-sky-50 border border-sky-200 rounded-xl p-4 text-sm text-sky-900">
        <div class="font-semibold mb-2">Cara export dari TikTok Seller Center</div>
        <ol class="list-decimal pl-5 space-y-1">
            <li>Login ke TikTok Seller Center.</li>
            <li>Menu <strong>Orders</strong> &rarr; filter status <em>Awaiting Collection</em> / <em>To Ship</em>.</li>
            <li>Klik tombol <strong>Export</strong> &rarr; pilih range tanggal &rarr; download.</li>
            <li>Upload file di form sebelah kiri.</li>
        </ol>
        <div class="mt-3 font-semibold">Kolom yang dibaca:</div>
        <ul class="list-disc pl-5 text-xs space-y-0.5">
            <li>Order ID, Tracking Number (wajib)</li>
            <li>Seller SKU, Product Name, Quantity</li>
            <li>Shipping Provider, Buyer, Status</li>
        </ul>
        <p class="mt-2 text-xs">Format header fleksibel &mdash; support nama kolom TikTok maupun terjemahan Bahasa Indonesia.</p>
    </div>
</div>

<div class="mt-6 bg-white rounded-xl border">
    <div class="px-4 py-3 border-b font-semibold">Riwayat Import Terakhir</div>
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left">
            <tr>
                <th class="px-4 py-2">Waktu</th>
                <th class="px-4 py-2">File</th>
                <th class="px-4 py-2">Oleh</th>
                <th class="px-4 py-2 text-right">Baris</th>
                <th class="px-4 py-2 text-right">Baru</th>
                <th class="px-4 py-2 text-right">Update</th>
                <th class="px-4 py-2 text-right">Skip</th>
                <th class="px-4 py-2">Peringatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recentImports as $imp)
                <tr class="border-t align-top">
                    <td class="px-4 py-2 whitespace-nowrap">{{ $imp->created_at->format('d/m H:i') }}</td>
                    <td class="px-4 py-2 font-mono text-xs">{{ $imp->filename }}</td>
                    <td class="px-4 py-2">{{ $imp->user?->name ?? '-' }}</td>
                    <td class="px-4 py-2 text-right">{{ $imp->rows_read }}</td>
                    <td class="px-4 py-2 text-right text-emerald-700 font-semibold">{{ $imp->orders_created }}</td>
                    <td class="px-4 py-2 text-right">{{ $imp->orders_updated }}</td>
                    <td class="px-4 py-2 text-right text-amber-700">{{ $imp->rows_skipped }}</td>
                    <td class="px-4 py-2 text-xs">
                        @if(!empty($imp->warnings))
                            <details>
                                <summary class="cursor-pointer text-amber-700">{{ count($imp->warnings) }} peringatan</summary>
                                <ul class="list-disc pl-5 mt-1 max-h-40 overflow-auto">
                                    @foreach($imp->warnings as $w)<li>{{ $w }}</li>@endforeach
                                </ul>
                            </details>
                        @else
                            <span class="text-slate-400">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-6 text-center text-slate-500">Belum ada import.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
