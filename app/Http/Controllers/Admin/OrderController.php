<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderImport;
use App\Services\OrderImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->get('status');
        $q = trim((string) $request->get('q', ''));

        $orders = Order::query()
            ->with('items.product', 'packedBy')
            ->when($status, fn ($b) => $b->where('status', $status))
            ->when($q !== '', fn ($b) => $b->where(function ($w) use ($q) {
                $w->where('tiktok_order_id', 'like', "%{$q}%")
                  ->orWhere('tracking_number', 'like', "%{$q}%")
                  ->orWhere('buyer_name', 'like', "%{$q}%");
            }))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.orders.index', compact('orders', 'status', 'q'));
    }

    public function show(Order $order): View
    {
        $order->load('items.product', 'packedBy');
        return view('admin.orders.show', compact('order'));
    }

    public function importForm(): View
    {
        $recentImports = OrderImport::with('user')
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.orders.import', compact('recentImports'));
    }

    public function import(Request $request, OrderImportService $service): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:20480'],
        ]);

        $uploaded = $request->file('file');
        try {
            $result = $service->import(
                $uploaded->getRealPath(),
                $uploaded->getClientOriginalName(),
                $request->user()
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => 'Gagal import: '.$e->getMessage()]);
        }

        $msg = "Import selesai. Baris terbaca {$result['rows']}, order baru {$result['created']}, diperbarui {$result['updated']}, dilewati {$result['skipped']}.";
        if (!empty($result['warnings'])) {
            $msg .= ' Ada '.count($result['warnings']).' peringatan, cek riwayat import.';
        }

        return redirect()->route('admin.orders.import.form')->with('success', $msg);
    }
}
