# Requirements — TikTok Packing & Stock System

## Ringkasan
Sistem web internal toko untuk menerima order dari TikTok Shop, mengelola stok produk, dan mempercepat proses packing lewat scan resi JNT. Ketika packer men-scan resi, stok produk pada order tersebut otomatis berkurang.

## Aktor / Role
- **Admin** — kelola produk, user, lihat semua order, lihat laporan stok, koneksi TikTok API.
- **Packer** — halaman scan resi + riwayat scan sendiri.

## User Story

### US-01 Login & Role
Sebagai user, saya bisa login dengan email & password. Sistem mengarahkan ke dashboard sesuai role.

### US-02 Kelola Produk (Admin)
- Admin bisa CRUD produk: `name`, `sku` (unik), `stock`, `price`, `photo` (opsional).
- Admin bisa melihat riwayat pergerakan stok per produk.

### US-03 Sinkronisasi Order dari TikTok (Admin)
- Admin memasukkan kredensial TikTok Shop (App Key, App Secret, Shop Cipher, Access Token, Refresh Token).
- Sistem melakukan polling berkala (tiap 1–2 menit) ke TikTok Open API untuk mengambil order baru yang sudah di-ship (sudah ada no resi JNT).
- Order baru disimpan ke tabel `orders` + `order_items`. Jika SKU TikTok belum ada di tabel `products`, tandai item sebagai `unmapped` (tidak bisa di-scan sampai di-mapping).
- Status awal order: `ready_to_pack`.

### US-04 Scan Resi (Packer)
- Packer membuka halaman scan.
- Input resi bisa via: (a) barcode scanner USB (keystroke + Enter), (b) kamera HP (HTML5 camera + ZXing/html5-qrcode).
- Sistem cari `orders.tracking_number = resi`.
- Jika ketemu & status `ready_to_pack` & semua item ter-mapping ke `products`:
  - Kurangi `products.stock` untuk tiap item (qty × item).
  - Catat di `stock_movements` (tipe `OUT`, reference `order_id`).
  - Ubah status order ke `packed`, set `packed_at` dan `packed_by_user_id`.
  - Tampilkan konfirmasi sukses (produk + qty yang dikurangi).
- Jika order tidak ketemu → error "Resi tidak ditemukan".
- Jika status bukan `ready_to_pack` → warning "Sudah di-pack".
- Jika ada item `unmapped` → block scan, minta Admin mapping dulu.
- Jika stok produk tidak cukup → boleh scan TAPI stok bisa minus (ditandai `allow_negative`), plus notif warning. (Konfigurasi `.env`.)

### US-05 Mapping SKU TikTok ↔ Produk (Admin)
- Admin bisa melihat daftar `unmapped` items dan memilih produk lokal yang sesuai.
- Setelah dimapping, order bisa di-scan.

### US-06 Kelola User (Admin)
- Admin bisa tambah/edit/hapus user, set role.

### US-07 Dashboard
- Admin: total order hari ini, order belum dipacking, total stok menipis, grafik packing 7 hari.
- Packer: jumlah yang sudah dipacking hari ini, antrian hari ini.

## Non-Functional
- Jalan di **laptop toko** (localhost) untuk awal. Laravel + MySQL/SQLite + PHP 8.2+.
- Polling TikTok dijalankan oleh `php artisan tiktok:poll` via scheduler (`schedule:run` tiap menit).
- Semua mutasi stok **wajib tercatat** di `stock_movements` (audit trail).
- Scan dengan USB scanner harus auto-submit pada Enter key. Kamera pakai library client-side (tanpa backend detect).
- TikTok API integration mode: `live` | `mock`. Mode `mock` pakai file JSON fixture untuk development sebelum akun TikTok Partner disetujui.

## Out of Scope (fase 1)
- Cetak label/invoice
- Multi-warehouse / multi-toko
- Retur / pembatalan otomatis dari TikTok (akan ditambah di fase 2)
- Integrasi JNT API
