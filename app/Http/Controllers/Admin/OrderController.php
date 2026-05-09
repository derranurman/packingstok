<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderSyncService;
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

    public function syncNow(OrderSyncService $sync): RedirectResponse
    {
        $result = $sync->sync();
        return redirect()->route('admin.orders.index')
            ->with('success', "Sync selesai: fetched {$result['fetched']}, baru {$result['created']}, update {$result['updated']}.");
    }
}
