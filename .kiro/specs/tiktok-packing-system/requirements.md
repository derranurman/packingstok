# Requirements — TikTok Packing & Stock System

## Ringkasan
Sistem web internal toko untuk menerima order dari TikTok Shop (via upload CSV/Excel hasil export dari TikTok Seller Center), mengelola stok produk, dan mempercepat proses packing lewat scan resi JNT. Ketika packer men-scan resi, stok produk pada order tersebut otomatis berkurang.

## Aktor / Role
- **Admin** — kelola produk, user, lihat semua order, lihat laporan stok, upload file order dari TikTok.
- **Packer** — halaman scan resi + riwayat scan sendiri.

## User Story

### US-01 Login & Role
Sebagai user, saya bisa login dengan email & password. Sistem mengarahkan ke dashboard sesuai role.

### US-02 Kelola Produk (Admin)
- Admin bisa CRUD produk: `name`, `sku` (unik), `stock`, `price`, `photo` (opsional).
- Admin bisa melihat riwayat pergerakan stok per produk.

### US-03 Import Order dari TikTok (Admin)
- Admin export order dari TikTok Seller Center sebagai CSV/XLSX (biasanya harian, setelah print resi batch).
- Admin upload file ke halaman **Import TikTok** di web.
- Parser membaca file:
  - Mendukung CSV (auto-detect delimiter `,` / `;`) dan Excel (`.xlsx`, `.xls`).
  - Header fleksibel: mendukung banyak alias (English & Indonesia). Didefinisikan di `OrderImportService::HEADER_ALIASES`.
  - Kolom WAJIB: `order_id`, `tracking_number`, `quantity`. Jika tidak ada → error, tidak ada row yang diimport.
  - Kolom opsional: `status`, `courier`, `buyer_name`, `sku`, `product_name`, `price`, `total_amount`.
- Hanya order dengan status `Awaiting Collection`, `Awaiting Shipment`, `To Ship`, `In Transit`, `Shipping` yang diimport. Status lain di-skip dan dicatat di warnings.
- 1 order dengan banyak item di CSV (multiple rows dengan `order_id` sama) digabung jadi 1 Order di DB.
- SKU TikTok di-match ke `products.sku`. Yang tidak cocok → `order_items.product_id = null` dan tampil di menu **Mapping SKU**.
- Order yang sudah dipacking (status `packed` di DB) tidak akan di-overwrite saat re-upload.
- Setiap upload dicatat di tabel `order_imports` (file, user, stats, warnings).

### US-04 Scan Resi (Packer)
- Packer membuka halaman scan.
- Input resi bisa via: (a) barcode scanner USB (keystroke + Enter), (b) kamera HP (html5-qrcode).
- Sistem cari `orders.tracking_number = resi`.
- Jika ketemu & status `ready_to_pack` & semua item ter-mapping ke `products`:
  - Kurangi `products.stock` untuk tiap item (qty × item).
  - Catat di `stock_movements` (tipe `OUT`, reference `order_id`).
  - Ubah status order ke `packed`, set `packed_at` dan `packed_by_user_id`.
  - Tampilkan konfirmasi sukses (produk + qty yang dikurangi, stok sisa).
- Jika order tidak ketemu → error "Resi tidak ditemukan".
- Jika status bukan `ready_to_pack` → warning "Sudah di-pack".
- Jika ada item `unmapped` → block scan, minta Admin mapping dulu.
- Jika stok produk tidak cukup → boleh scan TAPI stok bisa minus (ditandai `allow_negative`, `.env` setting), plus warning. Default: block.

### US-05 Mapping SKU TikTok ↔ Produk (Admin)
- Admin bisa melihat daftar `unmapped` items dan memilih produk lokal yang sesuai.
- Opsional "apply ke semua SKU sama" agar sekali map, semua item berikutnya ikut ter-mapping.
- Setelah dimapping, order bisa di-scan.

### US-06 Kelola User (Admin)
- Admin bisa tambah/edit/hapus user, set role aktif/nonaktif.

### US-07 Dashboard
- Admin: total order hari ini, order belum dipacking, total stok menipis, recent packed.
- Packer: jumlah yang sudah dipacking hari ini, antrian siap pack, riwayat scan sendiri.

## Non-Functional
- Jalan di **laptop toko** (localhost) untuk awal. Laravel + SQLite + PHP 8.2+.
- Semua mutasi stok **wajib tercatat** di `stock_movements` (audit trail).
- Scan dengan USB scanner harus auto-submit pada Enter key. Kamera pakai library client-side.
- Upload file max 20 MB, validasi mime (csv/txt/xlsx/xls).

## Out of Scope (fase 1)
- Cetak label/invoice
- Integrasi TikTok API langsung (ditolak karena proses partner approval sulit)
- Retur / pembatalan otomatis dari TikTok
- Multi-warehouse / multi-toko
