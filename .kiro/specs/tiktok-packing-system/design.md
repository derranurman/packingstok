# Design — TikTok Packing & Stock System

## Tech Stack
- **Framework:** Laravel 11 (PHP 8.2+)
- **DB:** SQLite (default untuk laptop), bisa switch MySQL via `.env`
- **Frontend:** Blade + Tailwind CSS + Alpine.js (ringan, tanpa build complex)
- **Scan barcode (kamera):** `html5-qrcode` (CDN)
- **Auth:** Laravel built-in (session-based) + middleware `role`
- **TikTok API client:** Service class custom `App\Services\TikTokShopClient`

## Database Schema

### users
| col | type | note |
|---|---|---|
| id | pk | |
| name | string | |
| email | string unique | |
| password | string | |
| role | enum('admin','packer') | default 'packer' |
| timestamps | | |

### products
| col | type | note |
|---|---|---|
| id | pk | |
| sku | string unique | |
| name | string | |
| description | text nullable | |
| price | decimal(12,2) default 0 | |
| stock | integer default 0 | |
| photo_path | string nullable | |
| is_active | bool default true | |
| timestamps | | |

### orders
| col | type | note |
|---|---|---|
| id | pk | |
| tiktok_order_id | string unique | |
| tracking_number | string index nullable | no resi JNT |
| courier | string default 'JNT' | |
| buyer_name | string nullable | |
| status | enum('pending','ready_to_pack','packed','cancelled') | |
| total_amount | decimal(12,2) default 0 | |
| raw_payload | json | mentah dari TikTok, untuk debug |
| packed_at | datetime nullable | |
| packed_by_user_id | fk users nullable | |
| timestamps | | |

### order_items
| col | type | note |
|---|---|---|
| id | pk | |
| order_id | fk orders cascade | |
| product_id | fk products nullable | null = unmapped |
| tiktok_sku | string | SKU dari TikTok |
| tiktok_product_name | string | |
| qty | integer | |
| price | decimal(12,2) default 0 | |
| timestamps | | |

### stock_movements
| col | type | note |
|---|---|---|
| id | pk | |
| product_id | fk products | |
| type | enum('IN','OUT','ADJUST') | |
| qty | integer | + untuk IN, - untuk OUT |
| reference_type | string nullable | mis. 'order' |
| reference_id | unsignedBigInt nullable | |
| note | string nullable | |
| user_id | fk users nullable | |
| timestamps | | |

### tiktok_credentials (singleton)
| col | type | note |
|---|---|---|
| id | pk | |
| app_key | string | |
| app_secret | string encrypted | |
| shop_cipher | string | |
| access_token | string encrypted | |
| refresh_token | string encrypted | |
| token_expires_at | datetime | |
| last_polled_at | datetime nullable | |
| mode | enum('live','mock') default 'mock' | |
| timestamps | | |

## Komponen

### Services
- `App\Services\TikTokShopClient` — signed HTTP request ke TikTok Open API. Method: `getOrders(sinceTs)`, `refreshToken()`, `getOrderDetail(id)`.
- `App\Services\OrderSyncService` — ambil order dari client, simpan ke DB, map SKU ke products.
- `App\Services\PackingService` — handle scan resi: validasi → kurangi stok → catat movement → update order. Semua dalam 1 DB transaction.

### Artisan Commands
- `tiktok:poll` — panggil `OrderSyncService::sync()`. Dijadwalkan di `routes/console.php` atau `app/Console/Kernel.php` tiap menit.

### Middleware
- `role:admin` dan `role:packer` (atau `role:admin,packer`).

### Routes (ringkas)
```
GET  /login                        auth view
POST /login
POST /logout

# Admin
GET  /admin                        dashboard
RES  /admin/products               CRUD
RES  /admin/users                  CRUD
GET  /admin/orders                 list + filter
GET  /admin/orders/{id}            detail
POST /admin/orders/sync-now        tombol manual trigger
GET  /admin/mappings               daftar unmapped items
POST /admin/mappings/{item}        assign product_id
GET  /admin/tiktok                 form kredensial
POST /admin/tiktok                 save

# Packer
GET  /packing                      halaman scan
POST /packing/scan                 {tracking_number} -> JSON response
GET  /packing/history              history scan saya
```

### Scan Flow (Packing)
1. Packer buka `/packing`.
2. Input field `tracking_number` auto-focus, listen keydown `Enter`.
3. Tombol "Scan pakai kamera" buka modal `html5-qrcode`, hasil scan diisi ke input dan submit.
4. POST ke `/packing/scan` (AJAX) → return JSON `{success, order, items, warnings[]}`.
5. UI tampilkan card hasil: produk + qty yang dikurangi, stok sekarang.

### TikTok Polling Flow
1. Command `tiktok:poll` jalan tiap menit via scheduler.
2. Cek `mode` dari `tiktok_credentials`:
   - `mock` → baca `storage/app/tiktok/mock_orders.json`
   - `live` → call TikTok Open API `/order/202309/orders/search` dengan filter `update_time_ge = last_polled_at`
3. Untuk tiap order: upsert ke `orders`, upsert items ke `order_items`, auto-match product berdasarkan `tiktok_sku == products.sku`.
4. Update `last_polled_at`.

## Keamanan
- Password hashed (bcrypt default Laravel).
- Token TikTok di-encrypt via `Crypt::encryptString` sebelum disimpan.
- CSRF aktif di semua form.
- Role check via middleware, bukan di view.
- Rate limit login 5/menit.

## Skalabilitas Catatan
- 100 produk + 100 order/hari = ringan banget. SQLite cukup.
- Polling tiap menit → 1440 call/hari. Aman dari rate limit TikTok.
- Nanti kalau mau realtime, tinggal tambah endpoint webhook `/api/tiktok/webhook` + Cloudflare Tunnel.
