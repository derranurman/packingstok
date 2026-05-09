@extends('layouts.app')
@section('title', 'Scan Packing')
@section('content')
<div x-data="packingScan()" x-cloak>
    <h1 class="text-2xl font-bold mb-4">Scan Resi Packing</h1>

    <div class="grid md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border p-4">
            <div class="text-xs text-slate-500">Dipacking hari ini (saya)</div>
            <div class="text-3xl font-bold text-emerald-700">{{ $myPackedToday }}</div>
        </div>
        <div class="bg-white rounded-xl border p-4">
            <div class="text-xs text-slate-500">Antrian siap pack</div>
            <div class="text-3xl font-bold text-sky-700">{{ $queue }}</div>
        </div>
        <div class="bg-white rounded-xl border p-4">
            <div class="text-xs text-slate-500">Tips</div>
            <div class="text-sm">Scanner USB: tembak resi lalu tekan Enter. Tanpa scanner? Klik "Scan pakai kamera".</div>
        </div>
    </div>

    <div class="bg-white rounded-xl border p-5 mb-6">
        <form @submit.prevent="submit">
            <label class="block text-sm font-medium mb-1">Nomor Resi JNT</label>
            <div class="flex gap-2">
                <input x-ref="input" x-model="tracking" :disabled="loading" autofocus autocomplete="off"
                       placeholder="Tembak barcode atau ketik resi..."
                       class="flex-1 border rounded px-4 py-3 text-lg font-mono tracking-wider focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                <button type="submit" :disabled="loading || !tracking"
                        class="bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white rounded px-5 font-medium">
                    <span x-show="!loading">Proses</span>
                    <span x-show="loading">...</span>
                </button>
                <button type="button" @click="openCamera"
                        class="bg-slate-100 hover:bg-slate-200 rounded px-3 text-sm">
                    Scan kamera
                </button>
            </div>
        </form>

        <div x-show="lastResult" class="mt-5 rounded-lg p-4"
             :class="lastResult?.success ? 'bg-emerald-50 border border-emerald-200' : 'bg-red-50 border border-red-200'">
            <template x-if="lastResult?.success">
                <div>
                    <div class="font-semibold text-emerald-800" x-text="'Berhasil: ' + lastResult.order.tiktok_order_id"></div>
                    <div class="text-sm text-slate-700 mb-2">
                        Resi: <span class="font-mono" x-text="lastResult.order.tracking_number"></span>
                        · Pembeli: <span x-text="lastResult.order.buyer_name ?? '-'"></span>
                        · Packed: <span x-text="lastResult.order.packed_at"></span>
                    </div>
                    <div class="text-sm">Stok dikurangi:</div>
                    <ul class="text-sm list-disc pl-6">
                        <template x-for="c in lastResult.changes" :key="c.sku">
                            <li>
                                <span x-text="c.product_name"></span>
                                (<span class="font-mono" x-text="c.sku"></span>)
                                -<span x-text="c.qty"></span>
                                &rarr; stok sekarang <strong x-text="c.stock_after"></strong>
                                <span x-show="c.low_stock" class="text-xs ml-2 px-2 py-0.5 rounded bg-amber-100 text-amber-800">stok menipis!</span>
                            </li>
                        </template>
                    </ul>
                    <template x-if="lastResult.warnings?.length">
                        <div class="mt-2 text-amber-800 text-sm">
                            <template x-for="w in lastResult.warnings" :key="w"><div>&#9888; <span x-text="w"></span></div></template>
                        </div>
                    </template>
                </div>
            </template>
            <template x-if="!lastResult?.success">
                <div>
                    <div class="font-semibold text-red-800" x-text="lastResult?.message"></div>
                    <div class="text-xs text-slate-500 mt-1" x-text="'Kode: ' + (lastResult?.code ?? '-')"></div>
                </div>
            </template>
        </div>
    </div>

    {{-- Camera modal --}}
    <div x-show="showCamera" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4">
        <div class="bg-white rounded-xl p-4 w-full max-w-md">
            <div class="flex justify-between items-center mb-2">
                <h3 class="font-semibold">Scan Barcode</h3>
                <button @click="closeCamera" class="text-sm text-slate-500 hover:text-red-600">Tutup</button>
            </div>
            <div id="reader" class="w-full"></div>
            <p class="text-xs text-slate-500 mt-2">Izinkan akses kamera dan arahkan ke barcode resi.</p>
        </div>
    </div>

    <div class="bg-white rounded-xl border">
        <div class="px-4 py-3 border-b font-semibold">Riwayat Scan Saya</div>
        <table class="w-full text-sm">
            <thead class="bg-slate-50 text-left">
                <tr><th class="px-4 py-2">Waktu</th><th class="px-4 py-2">Order</th><th class="px-4 py-2">Resi</th><th class="px-4 py-2">Item</th></tr>
            </thead>
            <tbody>
                @forelse($recent as $o)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $o->packed_at?->format('d/m H:i') }}</td>
                        <td class="px-4 py-2 font-mono">{{ $o->tiktok_order_id }}</td>
                        <td class="px-4 py-2 font-mono">{{ $o->tracking_number }}</td>
                        <td class="px-4 py-2">
                            @foreach($o->items as $it)
                                <div>{{ $it->qty }}× {{ $it->product?->name ?? $it->tiktok_product_name }}</div>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Belum ada.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
function packingScan() {
    return {
        tracking: '',
        loading: false,
        lastResult: null,
        showCamera: false,
        html5QrCode: null,

        async submit() {
            const val = this.tracking.trim();
            if (!val) return;
            this.loading = true;
            try {
                const res = await fetch(@json(route('packing.scan')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ tracking_number: val }),
                });
                const data = await res.json();
                this.lastResult = data;
                if (data.success) {
                    this.tracking = '';
                    this.beep(true);
                } else {
                    this.beep(false);
                }
            } catch (e) {
                this.lastResult = { success: false, code: 'network', message: 'Gagal terhubung ke server.' };
                this.beep(false);
            } finally {
                this.loading = false;
                this.$nextTick(() => this.$refs.input?.focus());
            }
        },

        openCamera() {
            this.showCamera = true;
            this.$nextTick(() => {
                this.html5QrCode = new Html5Qrcode('reader');
                this.html5QrCode.start(
                    { facingMode: 'environment' },
                    { fps: 10, qrbox: { width: 280, height: 120 } },
                    (decoded) => {
                        this.tracking = decoded;
                        this.closeCamera();
                        this.submit();
                    },
                    () => {}
                ).catch(err => alert('Gagal membuka kamera: ' + err));
            });
        },

        closeCamera() {
            if (this.html5QrCode) {
                this.html5QrCode.stop().catch(() => {});
                this.html5QrCode.clear();
                this.html5QrCode = null;
            }
            this.showCamera = false;
        },

        beep(ok) {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain); gain.connect(ctx.destination);
                osc.frequency.value = ok ? 880 : 220;
                gain.gain.value = 0.15;
                osc.start();
                setTimeout(() => { osc.stop(); ctx.close(); }, ok ? 120 : 320);
            } catch (_) {}
        }
    }
}
</script>
@endsection
