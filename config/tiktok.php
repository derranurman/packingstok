<?php

return [
    // 'mock' | 'live'
    'mode' => env('TIKTOK_MODE', 'mock'),

    'app_key' => env('TIKTOK_APP_KEY'),
    'app_secret' => env('TIKTOK_APP_SECRET'),
    'shop_cipher' => env('TIKTOK_SHOP_CIPHER'),
    'base_url' => env('TIKTOK_BASE_URL', 'https://open-api.tiktokglobalshop.com'),

    // Fallback access token / refresh token (bisa juga disimpan di DB)
    'access_token' => env('TIKTOK_ACCESS_TOKEN'),
    'refresh_token' => env('TIKTOK_REFRESH_TOKEN'),

    // Path untuk mock fixture
    'mock_fixture_path' => storage_path('app/tiktok/mock_orders.json'),

    // Hanya ambil order yang sudah dalam status ini (ada resi)
    'fetch_statuses' => ['AWAITING_COLLECTION', 'IN_TRANSIT'],
];
