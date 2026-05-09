<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderImport;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class OrderImportService
{
    /**
     * Mapping header → field internal. Semua alias di-lowercase & tanpa spasi berlebih.
     * Kalau TikTok rilis format baru, cukup tambah alias di sini.
     */
    private const HEADER_ALIASES = [
        'order_id' => [
            'order id', 'order no', 'order number', 'orderid', 'order_id', 'no pesanan',
        ],
        'tracking_number' => [
            'tracking id', 'tracking number', 'tracking no', 'waybill number',
            'shipping provider tracking number', 'awb', 'no resi', 'tracking_id',
        ],
        'courier' => [
            'shipping provider', 'courier', 'kurir', 'ekspedisi',
        ],
        'status' => [
            'order status', 'status', 'status pesanan',
        ],
        'sku' => [
            'seller sku', 'sku id', 'sku', 'sku_id', 'sku number',
        ],
        'product_name' => [
            'product name', 'nama produk', 'product', 'variation',
        ],
        'quantity' => [
            'quantity', 'qty', 'jumlah', 'sku quantity of return',
        ],
        'price' => [
            'sku unit original price', 'unit price', 'sale price', 'harga', 'subtotal before discount',
        ],
        'buyer_name' => [
            'buyer username', 'buyer', 'recipient', 'nama pembeli', 'customer',
        ],
        'total_amount' => [
            'order amount', 'total amount', 'total', 'grand total', 'total pembayaran',
        ],
    ];

    /**
     * Status TikTok yang dianggap sudah ada resi & perlu dipacking.
     * (Di-normalize lowercase tanpa underscore.)
     */
    private const PACKABLE_STATUSES = [
        'awaiting collection', 'awaiting shipment', 'to ship',
        'in transit', 'shipping',
    ];

    /**
     * @param User|null $user Null untuk import otomatis (watch folder)
     * @return array{import: OrderImport, created:int, updated:int, skipped:int, rows:int, warnings:array}
     */
    public function import(string $absolutePath, string $originalName, ?User $user, string $source = 'upload'): array
    {
        $rows = $this->readRows($absolutePath);
        if (empty($rows)) {
            throw new \RuntimeException('File kosong atau tidak ada header yang dikenali.');
        }

        $header = $this->normalizeHeader(array_shift($rows));
        $fieldIndex = $this->mapHeaderIndex($header);

        $missing = array_diff(['order_id', 'tracking_number', 'quantity'], array_keys($fieldIndex));
        if (! empty($missing)) {
            throw new \RuntimeException(
                'Kolom wajib tidak ditemukan: '.implode(', ', $missing).
                '. Header terbaca: '.implode(' | ', $header)
            );
        }

        // Group rows by order_id supaya 1 order dengan banyak item tetap jadi 1 Order
        $grouped = [];
        $rowsRead = 0;
        $skipped = 0;
        $warnings = [];

        foreach ($rows as $lineNo => $raw) {
            $rowsRead++;
            $r = $this->extractRow($raw, $fieldIndex);

            if (empty($r['order_id'])) {
                $skipped++;
                continue;
            }
            if (empty($r['tracking_number'])) {
                $warnings[] = "Baris ".($lineNo + 2).": order {$r['order_id']} tidak ada tracking number, dilewati.";
                $skipped++;
                continue;
            }
            if (! $this->isPackable($r['status'] ?? '')) {
                $warnings[] = "Baris ".($lineNo + 2).": order {$r['order_id']} status '".($r['status'] ?? '-')."' dilewati (belum siap packing).";
                $skipped++;
                continue;
            }

            $grouped[$r['order_id']] ??= [
                'tracking_number' => $r['tracking_number'],
                'courier' => $r['courier'] ?? 'JNT',
                'buyer_name' => $r['buyer_name'] ?? null,
                'total_amount' => (float) ($r['total_amount'] ?? 0),
                'items' => [],
            ];
            $grouped[$r['order_id']]['items'][] = [
                'sku' => $r['sku'] ?? null,
                'product_name' => $r['product_name'] ?? null,
                'quantity' => max(1, (int) ($r['quantity'] ?? 1)),
                'price' => (float) ($r['price'] ?? 0),
            ];
        }

        $created = 0;
        $updated = 0;

        foreach ($grouped as $tiktokOrderId => $orderData) {
            DB::transaction(function () use ($tiktokOrderId, $orderData, &$created, &$updated) {
                $existing = Order::where('tiktok_order_id', $tiktokOrderId)->first();

                // Order yang sudah dipacking, JANGAN disentuh (biar history aman)
                if ($existing && $existing->status === Order::STATUS_PACKED) {
                    $updated++;
                    return;
                }

                $order = Order::updateOrCreate(
                    ['tiktok_order_id' => $tiktokOrderId],
                    [
                        'tracking_number' => $orderData['tracking_number'],
                        'courier' => $orderData['courier'] ?? 'JNT',
                        'buyer_name' => $orderData['buyer_name'],
                        'status' => Order::STATUS_READY,
                        'total_amount' => $orderData['total_amount'],
                        'raw_payload' => $orderData,
                    ]
                );

                $existing ? $updated++ : $created++;

                // Refresh items
                $order->items()->delete();
                foreach ($orderData['items'] as $item) {
                    $product = $item['sku']
                        ? Product::where('sku', $item['sku'])->first()
                        : null;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product?->id,
                        'tiktok_sku' => $item['sku'],
                        'tiktok_product_name' => $item['product_name'],
                        'qty' => $item['quantity'],
                        'price' => $item['price'],
                    ]);
                }
            });
        }

        $import = OrderImport::create([
            'user_id' => $user?->id,
            'filename' => $originalName,
            'source' => $source,
            'rows_read' => $rowsRead,
            'orders_created' => $created,
            'orders_updated' => $updated,
            'rows_skipped' => $skipped,
            'warnings' => array_slice($warnings, 0, 100),
        ]);

        Log::info('Order import selesai', compact('created', 'updated', 'skipped'));

        return [
            'import' => $import,
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'rows' => $rowsRead,
            'warnings' => $warnings,
        ];
    }

    private function readRows(string $path): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (in_array($ext, ['xlsx', 'xls'])) {
            $spreadsheet = IOFactory::load($path);
            return $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        }

        // CSV (atau .txt). Auto-detect delimiter antara , dan ;
        $content = file_get_contents($path);
        // Strip UTF-8 BOM
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }
        $firstLine = strtok($content, "\n") ?: '';
        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';

        $rows = [];
        $handle = fopen('php://memory', 'r+');
        fwrite($handle, $content);
        rewind($handle);
        while (($row = fgetcsv($handle, 0, $delimiter, '"', '\\')) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }

    private function normalizeHeader(array $headerRow): array
    {
        return array_map(function ($h) {
            $s = (string) $h;
            $s = str_replace("\xEF\xBB\xBF", '', $s);
            return strtolower(trim(preg_replace('/\s+/', ' ', $s)));
        }, $headerRow);
    }

    /** @return array<string,int> field_name => column_index */
    private function mapHeaderIndex(array $header): array
    {
        $idx = [];
        foreach (self::HEADER_ALIASES as $field => $aliases) {
            foreach ($header as $i => $col) {
                if (in_array($col, $aliases, true)) {
                    $idx[$field] ??= $i;
                }
            }
        }
        return $idx;
    }

    private function extractRow(array $row, array $fieldIndex): array
    {
        $out = [];
        foreach ($fieldIndex as $field => $i) {
            $val = $row[$i] ?? null;
            $out[$field] = is_string($val) ? trim($val) : $val;
        }
        return $out;
    }

    private function isPackable(string $status): bool
    {
        if ($status === '') {
            // Kalau kolom status tidak ada, anggap bisa (user sudah filter sebelum export)
            return true;
        }
        $s = strtolower(str_replace('_', ' ', trim($status)));
        foreach (self::PACKABLE_STATUSES as $p) {
            if (Str::contains($s, $p)) return true;
        }
        return false;
    }
}
