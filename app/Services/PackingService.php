<?php

namespace App\Services;

use App\Exceptions\PackingException;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PackingService
{
    /**
     * Proses scan resi. Mengurangi stok semua produk di order, catat movement,
     * update status order. Semua dalam satu transaction.
     *
     * @return array ['order' => Order, 'changes' => [[product_name, qty, stock_after], ...]]
     */
    public function scanResi(string $tracking, User $user): array
    {
        $tracking = trim($tracking);
        if ($tracking === '') {
            throw new PackingException('Nomor resi kosong.', 'empty');
        }

        return DB::transaction(function () use ($tracking, $user) {
            /** @var Order|null $order */
            $order = Order::with('items.product')
                ->where('tracking_number', $tracking)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw new PackingException("Resi {$tracking} tidak ditemukan di sistem.", 'not_found');
            }

            if ($order->status === Order::STATUS_PACKED) {
                throw new PackingException(
                    "Order {$order->tiktok_order_id} sudah dipacking pada ".$order->packed_at?->format('d/m/Y H:i').'.',
                    'already_packed'
                );
            }

            if ($order->status === Order::STATUS_CANCELLED) {
                throw new PackingException('Order ini telah dibatalkan.', 'cancelled');
            }

            if ($order->hasUnmappedItems()) {
                throw new PackingException(
                    'Ada produk di order ini yang belum ter-mapping ke produk toko. Minta admin untuk mapping dulu.',
                    'unmapped'
                );
            }

            $allowNegative = (bool) config('stock.allow_negative', false);
            $warnings = [];
            $changes = [];

            foreach ($order->items as $item) {
                $product = $item->product;
                if (! $product) {
                    throw new PackingException('Item tanpa produk.', 'unmapped');
                }

                if (! $allowNegative && $product->stock < $item->qty) {
                    throw new PackingException(
                        "Stok {$product->name} tidak cukup (tersedia {$product->stock}, butuh {$item->qty}).",
                        'insufficient'
                    );
                }

                $product->decrement('stock', $item->qty);
                $product->refresh();

                if ($product->stock < 0) {
                    $warnings[] = "Stok {$product->name} minus: {$product->stock}.";
                }

                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => StockMovement::TYPE_OUT,
                    'qty' => -$item->qty,
                    'reference_type' => 'order',
                    'reference_id' => $order->id,
                    'note' => "Scan resi {$tracking}",
                    'user_id' => $user->id,
                ]);

                $changes[] = [
                    'product_name' => $product->name,
                    'sku' => $product->sku,
                    'qty' => $item->qty,
                    'stock_after' => $product->stock,
                    'low_stock' => $product->isLowStock(),
                ];
            }

            $order->update([
                'status' => Order::STATUS_PACKED,
                'packed_at' => now(),
                'packed_by_user_id' => $user->id,
            ]);

            return [
                'order' => $order->fresh('items.product', 'packedBy'),
                'changes' => $changes,
                'warnings' => $warnings,
            ];
        });
    }
}
