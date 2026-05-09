<?php

namespace App\Services\TikTok;

use App\Models\TiktokCredential;
use GuzzleHttp\Client as Guzzle;
use Illuminate\Support\Facades\Log;

/**
 * Thin client untuk TikTok Shop Open API.
 *
 * Catatan fase 1: implementasi live adalah skeleton.
 * Signature/endpoint resmi TikTok Shop bisa berubah; verifikasi lagi saat
 * akun partner sudah diapprove. Untuk awal kita pakai mode 'mock' yang
 * membaca storage/app/tiktok/mock_orders.json.
 */
class TikTokShopClient
{
    private TiktokCredential $cred;
    private Guzzle $http;

    public function __construct(?TiktokCredential $cred = null)
    {
        $this->cred = $cred ?? TiktokCredential::current();
        $this->http = new Guzzle([
            'base_uri' => config('tiktok.base_url'),
            'timeout' => 20,
        ]);
    }

    public function mode(): string
    {
        return $this->cred->mode ?: config('tiktok.mode', 'mock');
    }

    /**
     * Return array of normalized order payloads.
     * Format:
     * [
     *   [
     *     'id' => string,
     *     'tracking_number' => ?string,
     *     'buyer_name' => ?string,
     *     'status' => string,
     *     'total_amount' => float,
     *     'line_items' => [
     *        ['sku_id' => string, 'product_name' => string, 'quantity' => int, 'sale_price' => float],
     *        ...
     *     ],
     *   ],
     *   ...
     * ]
     */
    public function fetchOrders(?\DateTimeInterface $since = null): array
    {
        return $this->mode() === 'live'
            ? $this->fetchLive($since)
            : $this->fetchMock();
    }

    private function fetchMock(): array
    {
        $path = config('tiktok.mock_fixture_path');
        if (! is_file($path)) {
            Log::warning('TikTok mock fixture tidak ditemukan', ['path' => $path]);
            return [];
        }
        $data = json_decode(file_get_contents($path), true) ?? [];
        return $data['orders'] ?? [];
    }

    /**
     * Live call ke TikTok Shop. SKELETON — butuh disesuaikan saat go-live.
     */
    private function fetchLive(?\DateTimeInterface $since = null): array
    {
        if (! $this->cred->app_key || ! $this->cred->access_token) {
            Log::warning('TikTok credentials kosong, fallback ke mock.');
            return $this->fetchMock();
        }

        $params = [
            'app_key' => $this->cred->app_key,
            'access_token' => $this->cred->access_token,
            'shop_cipher' => $this->cred->shop_cipher,
            'timestamp' => time(),
            'version' => '202309',
        ];

        $body = [
            'page_size' => 50,
            'order_status' => 'AWAITING_COLLECTION',
        ];
        if ($since) {
            $body['update_time_ge'] = $since->getTimestamp();
        }

        $path = '/order/202309/orders/search';
        $params['sign'] = $this->sign($path, $params, $body);

        try {
            $resp = $this->http->post($path.'?'.http_build_query($params), [
                'json' => $body,
            ]);
            $json = json_decode((string) $resp->getBody(), true) ?? [];
            return $this->normalizeLiveOrders($json['data']['orders'] ?? []);
        } catch (\Throwable $e) {
            Log::error('TikTok API call gagal', ['err' => $e->getMessage()]);
            return [];
        }
    }

    private function normalizeLiveOrders(array $raw): array
    {
        return array_map(function (array $o) {
            return [
                'id' => (string) ($o['id'] ?? ''),
                'tracking_number' => $o['tracking_number'] ?? ($o['packages'][0]['tracking_number'] ?? null),
                'buyer_name' => $o['buyer_name'] ?? ($o['user_id'] ?? null),
                'status' => $o['status'] ?? 'UNKNOWN',
                'total_amount' => (float) ($o['payment']['total_amount'] ?? 0),
                'line_items' => array_map(fn ($li) => [
                    'sku_id' => (string) ($li['sku_id'] ?? $li['seller_sku'] ?? ''),
                    'product_name' => (string) ($li['product_name'] ?? ''),
                    'quantity' => (int) ($li['quantity'] ?? 1),
                    'sale_price' => (float) ($li['sale_price'] ?? 0),
                ], $o['line_items'] ?? []),
                '_raw' => $o,
            ];
        }, $raw);
    }

    /**
     * HMAC-SHA256 sign TikTok style. Implementasi minimal.
     */
    private function sign(string $path, array $params, array $body): string
    {
        ksort($params);
        $base = $path;
        foreach ($params as $k => $v) {
            if ($k === 'sign' || $k === 'access_token') continue;
            $base .= $k.$v;
        }
        $base .= json_encode($body, JSON_UNESCAPED_SLASHES);
        return hash_hmac('sha256', $base, (string) $this->cred->app_secret);
    }
}
