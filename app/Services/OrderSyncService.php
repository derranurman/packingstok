<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\TiktokCredential;
use App\Services\TikTok\TikTokShopClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderSyncService
{
    public function __construct(private TikTokShopClient $client)
    {
    }

    public function sync(): array
    {
        $cred = TiktokCredential::current();
        $orders = $this->client->fetchOrders($cred->last_polled_at);

        $created = 0;
        $updated = 0;

        foreach ($orders as $raw) {
            $tiktokId = (string) ($raw['id'] ?? '');
            if ($tiktokId === '') {
                continue;
            }

            DB::transaction(function () use ($raw, $tiktokId, &$created, &$updated) {
                $existing = Order::where('tiktok_order_id', $tiktokId)->first();

                $order = Order::updateOrCreate(
                    ['tiktok_order_id' => $tiktokId],
                    [
                        'tracking_number' => $raw['tracking_number'] ?? null,
                        'courier' => 'JNT',
                        'buyer_name' => $raw['buyer_name'] ?? null,
                        'status' => $existing?->status === Order::STATUS_PACKED
                            ? Order::STATUS_PACKED
                            : Order::STATUS_READY,
                        'total_amount' => $raw['total_amount'] ?? 0,
                        'raw_payload' => $raw,
                    ]
                );

                if ($existing) {
                    $updated++;
                } else {
                    $created++;
                }

                // Refresh items (hanya jika belum packed, supaya history tidak rusak)
                if ($order->status !== Order::STATUS_PACKED) {
                    $order->items()->delete();
                    foreach ($raw['line_items'] ?? [] as $li) {
                        $sku = $li['sku_id'] ?? null;
                        $product = $sku ? Product::where('sku', $sku)->first() : null;

                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $product?->id,
                            'tiktok_sku' => $sku,
                            'tiktok_product_name' => $li['product_name'] ?? null,
                            'qty' => (int) ($li['quantity'] ?? 1),
                            'price' => (float) ($li['sale_price'] ?? 0),
                        ]);
                    }
                }
            });
        }

        $cred->update(['last_polled_at' => now()]);

        Log::info('TikTok sync selesai', compact('created', 'updated'));

        return [
            'fetched' => count($orders),
            'created' => $created,
            'updated' => $updated,
        ];
    }
}
