# Packing Stok — TikTok Shop Integration (CSV Import)

Sistem web internal toko untuk mengelola stok produk dari order TikTok Shop dan mempercepat proses packing lewat scan resi JNT.

## Fitur
- **Multi-user dengan role**: `admin` dan `packer`
- **Kelola produk**: CRUD produk + riwayat pergerakan stok
- **Import order dari TikTok**: upload CSV/Excel hasil export Seller Center (support banyak variasi nama kolom)
- **Scan resi JNT**: HP camera (html5-qrcode) + USB barcode scanner
- **Auto-decrement stok** saat scan + audit trail di `stock_movements`
- **Mapping SKU TikTok** → produk toko (sekali map, apply untuk semua order berikutnya)
- **Riwayat import** dengan log peringatan per file

## Tech
- Laravel 11, PHP 8.2+
- SQLite (default) / MySQL
- Blade + Tailwind CDN + Alpine.js
- [PhpSpreadsheet](https://phpspreadsheet.readthedocs.io/) untuk baca .xlsx
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

# 5. Buat symlink storage
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

## Cara coba flow lengkap

1. Login sebagai **admin** → menu **Import TikTok**.
2. Upload file `storage/app/sample/sample_tiktok_orders.csv` (ikut di repo, sebagai sample).
3. 6 order akan masuk (1 dilewati karena status `Delivered`, 1 lagi ada SKU yang belum ada di toko → akan muncul di **Mapping SKU**).
4. Logout, login sebagai **packer** → halaman **Scan Packing**.
5. Ketik/scan resi `JNT0000000001` → tekan Enter.
6. Stok "Stir Skeleton" otomatis berkurang 1. Cek di menu **Produk**.

## Cara export CSV dari TikTok Seller Center

1. Login ke [TikTok Seller Center](https://seller-id.tiktok.com/).
2. Menu **Orders** → filter status **Awaiting Collection** (order yang sudah ada resi tapi belum dikirim).
3. Klik tombol **Export** → pilih range tanggal → download.
4. Buka menu **Import TikTok** di web ini → upload file.

**Tips:** Export setiap pagi setelah kamu print resi batch. Order yang sudah dipacking kemarin tidak akan ter-overwrite (historynya aman).

## Kolom yang dibaca

Parser **fleksibel** — mendukung banyak variasi header (English Seller Center & terjemahan Bahasa Indonesia):

| Field internal | Alias yang dikenali |
|---|---|
| `order_id` (wajib) | Order ID, Order No, Order Number, No Pesanan |
| `tracking_number` (wajib) | Tracking ID, Tracking Number, Waybill Number, No Resi, AWB |
| `quantity` (wajib) | Quantity, Qty, Jumlah |
| `sku` | Seller SKU, SKU ID, SKU |
| `product_name` | Product Name, Nama Produk, Variation |
| `courier` | Shipping Provider, Courier, Kurir |
| `status` | Order Status, Status, Status Pesanan |
| `buyer_name` | Buyer Username, Buyer, Recipient, Customer |
| `price` | Unit Price, Sale Price, Harga |
| `total_amount` | Order Amount, Total Amount, Total Pembayaran |

**Status yang diimport:** hanya order dengan status yang mengandung `Awaiting Collection`, `Awaiting Shipment`, `To Ship`, `In Transit`, `Shipping`. Status lain (`Delivered`, `Completed`, `Cancelled`) di-skip.

Kalau TikTok ubah nama kolomnya, tinggal tambah alias di `app/Services/OrderImportService.php` → `HEADER_ALIASES`.

## Struktur folder penting

```
app/
  Exceptions/PackingException.php
  Http/
    Controllers/Admin/   # dashboard, products, orders (+import), mappings, users
    Controllers/Auth/LoginController.php
    Controllers/Packing/PackingController.php
    Middleware/EnsureUserHasRole.php
  Models/                # User, Product, Order, OrderItem, StockMovement, OrderImport
  Services/
    OrderImportService.php   # parser CSV/XLSX
    PackingService.php       # logic scan resi + kurangi stok
database/
  migrations/              # 6 migration files
  seeders/DatabaseSeeder.php
resources/views/
  layouts/app.blade.php
  auth/login.blade.php
  packing/index.blade.php
  admin/{dashboard,products,orders,mappings,users}/
routes/web.php
storage/app/sample/
  sample_tiktok_orders.csv   # contoh file untuk test
.kiro/specs/tiktok-packing-system/
  requirements.md design.md tasks.md
```

## Keamanan

- Password di-hash (bcrypt via `casts 'hashed'`).
- CSRF aktif di semua form.
- Rate limit login 10/menit.
- Role check via middleware `role:admin` atau `role:admin,packer`.
- Upload file divalidasi: max 20 MB, hanya mime csv/txt/xlsx/xls.

## Konfigurasi stok

Di `.env`:
```
ALLOW_NEGATIVE_STOCK=false   # true = boleh scan walau stok 0; false = block scan
```

## Troubleshooting

**Kamera tidak jalan di browser HP?** Browser butuh HTTPS (atau localhost). Di laptop toko kalau mau diakses HP lain di jaringan sama, pakai [ngrok](https://ngrok.com/) atau [expose](https://expose.dev/).

**USB scanner tidak auto-submit?** Pastikan scanner di-set mode "Keyboard Emulation + CR/Enter suffix" (biasanya default).

**Upload gagal "Kolom wajib tidak ditemukan"?** Buka file CSV di spreadsheet, cek nama-nama kolom. Copy salah satu header yang muncul di pesan error, lalu tambahkan ke `HEADER_ALIASES` di `OrderImportService.php`.

**Port 8000 bentrok?** `php artisan serve --port=8080`

## Roadmap
- [ ] Cetak label packing PDF
- [ ] Export laporan ke Excel
- [ ] Notifikasi stok menipis ke WhatsApp
- [ ] Auto-skip row kalau order sudah pernah dipacking (supaya re-upload aman)
- [ ] Dashboard grafik packing 7/30 hari
- [ ] Multi-shop / multi-warehouse
