@extends('layouts.app')
@section('title', 'Watch Folder')
@section('content')
<div class="flex items-center justify-between mb-4 flex-wrap gap-2">
    <h1 class="text-2xl font-bold">Watch Folder</h1>
    <form method="POST" action="{{ route('admin.watchfolder.run') }}">@csrf
        <button class="bg-emerald-600 hover:bg-emerald-700 text-white rounded px-4 py-2">Jalankan sekarang</button>
    </form>
</div>

<div class="mb-6 rounded-lg border p-4 text-sm
    {{ $enabled ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-slate-100 border-slate-300 text-slate-700' }}">
    <div class="flex items-center gap-2">
        <span class="inline-block w-2 h-2 rounded-full {{ $enabled ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400' }}"></span>
        <strong>
            @if($enabled) Watch Folder AKTIF @else Watch Folder NONAKTIF @endif
        </strong>
        @if($enabled)
            <span class="text-xs">&mdash; sistem cek folder tiap 2 menit (lewat scheduler OS).</span>
        @else
            <span class="text-xs">Aktifkan dengan set <code>WATCH_FOLDER_ENABLED=true</code> di <code>.env</code>.</span>
        @endif
    </div>
    @if($lastRun)
        <div class="text-xs mt-1">Auto-import terakhir: <strong>{{ $lastRun->diffForHumans() }}</strong> ({{ $lastRun->format('d/m/Y H:i:s') }})</div>
    @endif
</div>

<div class="grid md:grid-cols-3 gap-4 mb-6 text-sm">
    <div class="bg-white rounded-xl border p-4">
        <div class="text-xs text-slate-500 uppercase">Inbox (drop file di sini)</div>
        <div class="font-mono text-xs break-all mt-1">{{ $inbox }}</div>
        <div class="mt-2 text-3xl font-bold text-sky-700">{{ count($inboxFiles) }}</div>
        <div class="text-xs text-slate-500">menunggu diproses</div>
    </div>
    <div class="bg-white rounded-xl border p-4">
        <div class="text-xs text-slate-500 uppercase">Processed</div>
        <div class="font-mono text-xs break-all mt-1">{{ $processed }}</div>
        <div class="text-xs text-slate-500 mt-2">File yang sukses di-import dipindah ke sini dengan prefix timestamp.</div>
    </div>
    <div class="bg-white rounded-xl border p-4">
        <div class="text-xs text-slate-500 uppercase">Failed</div>
        <div class="font-mono text-xs break-all mt-1">{{ $failed }}</div>
        <div class="mt-2 text-3xl font-bold {{ count($failedFiles) > 0 ? 'text-red-700' : 'text-slate-400' }}">{{ count($failedFiles) }}</div>
        <div class="text-xs text-slate-500">file gagal (lihat detail di bawah)</div>
    </div>
</div>

<div class="grid md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border">
        <div class="px-4 py-3 border-b font-semibold">File di Inbox</div>
        @if(empty($inboxFiles))
            <div class="px-4 py-6 text-center text-slate-500 text-sm">Tidak ada file menunggu. Drop file .csv / .xlsx ke folder di atas.</div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs">
                    <tr><th class="px-4 py-2">Nama file</th><th class="px-4 py-2 text-right">Ukuran</th><th class="px-4 py-2">Waktu</th></tr>
                </thead>
                <tbody>
                    @foreach($inboxFiles as $f)
                        <tr class="border-t">
                            <td class="px-4 py-2 font-mono text-xs">{{ $f['name'] }}</td>
                            <td class="px-4 py-2 text-right">{{ number_format($f['size'] / 1024, 1) }} KB</td>
                            <td class="px-4 py-2 text-xs">{{ \Carbon\Carbon::createFromTimestamp($f['mtime'])->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="bg-white rounded-xl border">
        <div class="px-4 py-3 border-b font-semibold">File Gagal ({{ count($failedFiles) }})</div>
        @if(empty($failedFiles))
            <div class="px-4 py-6 text-center text-slate-500 text-sm">Tidak ada file gagal. &#127881;</div>
        @else
            <ul class="divide-y text-sm">
                @foreach($failedFiles as $f)
                    <li class="px-4 py-2">
                        <div class="font-mono text-xs">{{ $f['name'] }}</div>
                        <div class="text-xs text-slate-500">{{ \Carbon\Carbon::createFromTimestamp($f['mtime'])->diffForHumans() }}</div>
                        @if(!empty($f['error']))
                            <div class="mt-1 text-xs text-red-700 bg-red-50 border border-red-200 rounded px-2 py-1">{{ $f['error'] }}</div>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

<div class="mt-6 bg-white rounded-xl border">
    <div class="px-4 py-3 border-b font-semibold">Riwayat Auto-Import</div>
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-left text-xs">
            <tr>
                <th class="px-4 py-2">Waktu</th>
                <th class="px-4 py-2">File</th>
                <th class="px-4 py-2 text-right">Baris</th>
                <th class="px-4 py-2 text-right">Baru</th>
                <th class="px-4 py-2 text-right">Update</th>
                <th class="px-4 py-2 text-right">Skip</th>
                <th class="px-4 py-2">Peringatan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($recentAutoImports as $imp)
                <tr class="border-t align-top">
                    <td class="px-4 py-2 whitespace-nowrap">{{ $imp->created_at->format('d/m H:i') }}</td>
                    <td class="px-4 py-2 font-mono text-xs break-all">{{ $imp->filename }}</td>
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
                <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500 text-sm">Belum ada auto-import.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-6 bg-sky-50 border border-sky-200 rounded-xl p-5 text-sm text-sky-900">
    <h2 class="font-semibold mb-2">Cara pakai</h2>
    <ol class="list-decimal pl-5 space-y-1">
        <li>Export order dari TikTok Seller Center (CSV atau XLSX).</li>
        <li>Copy / drag file ke folder <code class="font-mono text-xs break-all">{{ $inbox }}</code></li>
        <li>Tunggu maksimal 2 menit &mdash; sistem otomatis import.</li>
        <li>File sukses dipindah ke folder <strong>Processed</strong> dengan prefix timestamp.</li>
        <li>File error dipindah ke folder <strong>Failed</strong>, bareng file <code>.error.txt</code> berisi pesan error.</li>
    </ol>
    <div class="mt-3 text-xs">
        <strong>Filter:</strong> hanya file dengan ekstensi <code>{{ implode(', ', $allowed) }}</code> dan yang sudah diam minimal <strong>{{ $minAge }} detik</strong> yang diproses (mencegah baca file yang masih di-upload/di-sync).
    </div>
    <div class="mt-3 text-xs">
        <strong>Tips Google Drive / Dropbox:</strong> set path inbox ke folder lokal yang sync dengan cloud storage-nya. Admin bisa upload file dari HP, laptop toko auto-import.
    </div>
</div>
@endsection
