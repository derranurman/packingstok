# Packing Stok — TikTok Shop Integration

Sistem web internal toko untuk terima order dari TikTok Shop, kelola stok produk, dan mempercepat proses packing lewat scan resi JNT.

## Fitur
- **Multi-user dengan role**: `admin` dan `packer`
- **Kelola produk**: CRUD produk + riwayat pergerakan stok
- **Integrasi TikTok Shop**: sync order via polling (mode `mock` / `live`)
- **Scan resi JNT**: HP camera (html5-qrcode) + USB barcode scanner
- **Auto-decrement stok** + audit trail di `stock_movements`
- **Mapping SKU TikTok** → produk toko (sekali map, apply untuk semua order berikutnya)

## Tech
- Laravel 11, PHP 8.2+
- SQLite (default) / MySQL
- Blade + Tailwind CDN + Alpine.js
- [html5-qrcode](https://github.com/mebjas/html5-qrcode) untuk kamera

## Setup (laptop toko)

```bash
# 1. Install dependencies
composer install

# 2. Setup environment
cp .env.example .env
php artisan key:generate

# 3. Buat database SQLite (atau pakai MySQL — edit .env)
touch database/database.sqlite

# 4. Migrate + seed (admin, packer, 5 produk contoh)
php artisan migrate --seed

# 5. Buat symlink storage (untuk foto produk)
php artisan storage:link

# 6. Jalankan server
php artisan serve
```

Buka [http://localhost:8000](http://localhost:8000)

**Login demo:**
| Role | Email | Password |
|---|---|---|
| Admin | `admin@toko.test` | `admin123` |
| Packer | `packer@toko.test` | `packer123` |

## Cara coba flow lengkap (mode mock)

1. Login sebagai **admin** → menu **Order** → klik **Sync dari TikTok**.
2. 3 order dari `storage/app/tiktok/mock_orders.json` akan masuk dengan no resi `JNT0000000001..3`.
3. Logout, login sebagai **packer** → halaman **Scan Packing**.
4. Ketik/scan resi `JNT0000000001` → tekan Enter.
5. Stok produk "Stir Skeleton" otomatis berkurang 1. Cek di menu **Produk**.

## Scheduler (polling otomatis)

Supaya order TikTok terus masuk tiap menit tanpa klik manual:

**Linux/Mac (cron):**
```
* * * * * cd /path/to/packingstok && php artisan schedule:run >> /dev/null 2>&1
```

**Windows:** buka Task Scheduler → buat task "Run every 1 minute" dengan command `php artisan schedule:run` di folder project.

Atau saat development:
```bash
php artisan schedule:work
```

## Mode `live` (TikTok Shop Open API)

1. Daftar jadi [TikTok Shop Partner](https://partner.tiktokshop.com/docv2) (tunggu approval).
2. Dapatkan: **App Key**, **App Secret**, **Shop Cipher**, **Access Token**, **Refresh Token**.
3. Login admin → **TikTok** → ubah mode ke `live` dan isi semua kredensial.
4. **Catatan**: implementasi signed request di `App\Services\TikTok\TikTokShopClient::fetchLive()` adalah skeleton. Verifikasi signature/endpoint sesuai dokumen terbaru TikTok saat go-live, dan tambahkan refresh-token flow.

## Struktur folder penting

```
app/
  Console/Commands/TiktokPollCommand.php
  Exceptions/PackingException.php
  Http/
    Controllers/Admin/   # dashboard, products, orders, mappings, users, tiktok
    Controllers/Auth/LoginController.php
    Controllers/Packing/PackingController.php
    Middleware/EnsureUserHasRole.php
  Models/                # User, Product, Order, OrderItem, StockMovement, TiktokCredential
  Services/
    OrderSyncService.php
    PackingService.php
    TikTok/TikTokShopClient.php
database/
  migrations/            # 6 migration files
  seeders/DatabaseSeeder.php
resources/views/
  layouts/app.blade.php
  auth/login.blade.php
  packing/index.blade.php
  admin/{dashboard,products,orders,mappings,users,tiktok}/
routes/
  web.php
  console.php            # schedule tiktok:poll tiap menit
storage/app/tiktok/
  mock_orders.json       # fixture untuk mode mock
.kiro/specs/tiktok-packing-system/
  requirements.md design.md tasks.md
```

## Keamanan

- Password di-hash (bcrypt via `casts 'hashed'`).
- Token TikTok di-encrypt via `Crypt` sebelum disimpan ke DB.
- CSRF aktif di semua form.
- Rate limit login 10/menit.
- Role check via middleware `role:admin` atau `role:admin,packer`.

## Konfigurasi stok

Di `.env`:
```
ALLOW_NEGATIVE_STOCK=false   # true = boleh scan walau stok 0; false = block scan
```

## Troubleshooting

**Kamera tidak jalan di browser HP?** Browser butuh HTTPS (atau localhost). Di laptop toko kalau mau diakses HP lain di jaringan sama, pakai [`expose`](https://expose.dev/) atau [ngrok](https://ngrok.com/) biar dapat HTTPS.

**USB scanner tidak auto-submit?** Pastikan scanner di-set mode "Keyboard Emulation + CR/Enter suffix" (biasanya default). Input field sudah listen event Enter.

**Port 8000 bentrok?** `php artisan serve --port=8080`

## Roadmap
- [ ] Live TikTok API dengan signed request lengkap + auto refresh token
- [ ] Cetak label packing
- [ ] Export laporan ke Excel
- [ ] Notifikasi stok menipis ke WhatsApp
- [ ] Retur / pembatalan otomatis dari TikTok
- [ ] Multi-shop / multi-warehouse
