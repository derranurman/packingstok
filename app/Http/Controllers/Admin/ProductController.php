<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));

        $products = Product::query()
            ->when($q !== '', fn ($b) => $b->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('sku', 'like', "%{$q}%");
            }))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', compact('products', 'q'));
    }

    public function create(): View
    {
        return view('admin.products.form', ['product' => new Product()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);

        Product::create($data);

        return redirect()->route('admin.products.index')
            ->with('success', 'Produk dibuat.');
    }

    public function edit(Product $product): View
    {
        $movements = $product->stockMovements()
            ->with('user')
            ->latest()
            ->limit(25)
            ->get();

        return view('admin.products.form', compact('product', 'movements'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validateData($request, $product->id);

        // Jika stock berubah manual, catat sebagai ADJUST
        if ($data['stock'] !== $product->stock) {
            $delta = $data['stock'] - $product->stock;
            DB::transaction(function () use ($product, $data, $delta, $request) {
                $product->update($data);
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => StockMovement::TYPE_ADJUST,
                    'qty' => $delta,
                    'note' => 'Penyesuaian manual via edit produk',
                    'user_id' => $request->user()?->id,
                ]);
            });
        } else {
            $product->update($data);
        }

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Produk diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();
        return redirect()->route('admin.products.index')
            ->with('success', 'Produk dihapus.');
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $rules = [
            'sku' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer'],
            'low_stock_threshold' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
        $rules['sku'][] = 'unique:products,sku'.($ignoreId ? ",{$ignoreId}" : '');

        $data = $request->validate($rules);
        $data['is_active'] = $request->boolean('is_active');
        return $data;
    }
}
