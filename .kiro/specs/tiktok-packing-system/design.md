# Design — TikTok Packing & Stock System

## Tech Stack
- **Framework:** Laravel 11 (PHP 8.2+)
- **DB:** SQLite (default untuk laptop), bisa switch MySQL via `.env`
- **Frontend:** Blade + Tailwind CSS (CDN) + Alpine.js — ringan, tanpa build step
- **Scan barcode (kamera):** `html5-qrcode` (CDN)
- **Parse Excel:** `phpoffice/phpspreadsheet`
- **Auth:** Laravel built-in (session-based) + middleware `role`

## Database Schema

### users
| col | type | note |
|---|---|---|
| id | pk | |
| name | string | |
| email | string unique | |
| password | string (hashed) | |
| role | enum('admin','packer') | default 'packer' |
| is_active | bool default true | |
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
| low_stock_threshold | integer default 5 | |
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
| raw_payload | json | data mentah dari CSV untuk debug |
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

### order_imports
| col | type | note |
|---|---|---|
| id | pk | |
| user_id | fk users | |
| filename | string | |
| rows_read | uint | |
| orders_created | uint | |
| orders_updated | uint | |
| rows_skipped | uint | |
| warnings | json nullable | list pesan peringatan per row |
| timestamps | | |

## Komponen

### Services
- **`App\Services\OrderImportService`** — parse CSV/XLSX, normalize header, group rows by order_id, upsert ke `orders` + `order_items`. Auto-match SKU. Skip order yang sudah dipacking dan status yang tidak packable.
- **`App\Services\PackingService`** — handle scan resi: validasi → kurangi stok → catat movement → update order. Semua dalam 1 DB transaction dengan `lockForUpdate()`.

### Middleware
- `role:admin` dan `role:admin,packer`.

### Routes (ringkas)
```
GET  /login                         auth view
POST /login
POST /logout

# Admin
GET  /admin                         dashboard
RES  /admin/products                CRUD (kecuali show)
RES  /admin/users                   CRUD (kecuali show)
GET  /admin/orders                  list + filter
GET  /admin/orders/import           form upload
POST /admin/orders/import           proses upload
GET  /admin/orders/{order}          detail
GET  /admin/mappings                daftar unmapped items
POST /admin/mappings/{item}         assign product_id

# Packer
GET  /packing                       halaman scan
POST /packing/scan                  {tracking_number} -> JSON response
```

### Scan Flow (Packing)
1. Packer buka `/packing`.
2. Input field `tracking_number` auto-focus, listen keydown `Enter`.
3. Tombol "Scan pakai kamera" buka modal `html5-qrcode`, hasil scan diisi ke input dan submit.
4. POST ke `/packing/scan` (AJAX) → return JSON `{success, order, changes[], warnings[]}`.
5. UI tampilkan card hasil + beep sound (success 880Hz, error 220Hz).

### Import Flow
1. Admin export order di TikTok Seller Center → dapat file CSV/XLSX.
2. Buka `/admin/orders/import` → upload file.
3. Parser:
   - Baca semua row, normalize header ke lowercase.
   - Map header ke field internal lewat `HEADER_ALIASES`.
   - Validate kolom wajib (`order_id`, `tracking_number`, `quantity`).
   - Filter row berdasarkan status packable.
   - Group by `order_id` (1 order bisa punya banyak item).
   - Upsert order + replace items (kecuali order yang sudah di-`packed`).
   - Auto-match SKU ke `products.sku`.
4. Catat di `order_imports` dengan stats dan warnings.
5. Redirect back dengan flash message.

## Keamanan
- Password hashed via cast `hashed`.
- CSRF aktif di semua form.
- Role check via middleware.
- Rate limit login 10/menit.
- Upload file: validate mime types (`csv,txt,xlsx,xls`), max 20 MB.

## Skalabilitas Catatan
- 100 produk + 100 order/hari = sangat ringan. SQLite cukup.
- PhpSpreadsheet bisa handle ribuan row — untuk 100 order/hari tidak masalah.
- Kalau nanti mau auto-import tanpa upload manual, tinggal tambah folder "dropbox" (watch folder) + artisan command scheduled.
