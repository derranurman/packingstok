<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = now()->startOfDay();

        $stats = [
            'orders_today' => Order::where('created_at', '>=', $today)->count(),
            'ready_to_pack' => Order::where('status', Order::STATUS_READY)->count(),
            'packed_today' => Order::where('status', Order::STATUS_PACKED)
                ->where('packed_at', '>=', $today)->count(),
            'low_stock' => Product::whereColumn('stock', '<=', 'low_stock_threshold')->count(),
        ];

        $unmapped = Order::whereHas('items', fn ($q) => $q->whereNull('product_id'))
            ->where('status', Order::STATUS_READY)
            ->count();

        $recentPacked = Order::where('status', Order::STATUS_PACKED)
            ->with('packedBy')
            ->latest('packed_at')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'unmapped', 'recentPacked'));
    }
}
