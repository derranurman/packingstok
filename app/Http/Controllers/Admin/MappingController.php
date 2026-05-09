<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MappingController extends Controller
{
    public function index(): View
    {
        $items = OrderItem::query()
            ->whereNull('product_id')
            ->whereHas('order', fn ($b) => $b->where('status', Order::STATUS_READY))
            ->with('order')
            ->orderBy('tiktok_sku')
            ->paginate(30);

        $products = Product::orderBy('name')->get(['id', 'sku', 'name', 'stock']);

        return view('admin.mappings.index', compact('items', 'products'));
    }

    public function update(Request $request, OrderItem $item): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'apply_all_same_sku' => ['sometimes', 'boolean'],
        ]);

        if ($request->boolean('apply_all_same_sku') && $item->tiktok_sku) {
            OrderItem::where('tiktok_sku', $item->tiktok_sku)
                ->whereNull('product_id')
                ->update(['product_id' => $data['product_id']]);
        } else {
            $item->update(['product_id' => $data['product_id']]);
        }

        return back()->with('success', 'Mapping disimpan.');
    }
}
