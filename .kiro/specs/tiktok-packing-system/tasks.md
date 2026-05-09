# Implementation Tasks

## Fase 1 — Scaffolding Minimal + CSV Import (DONE)
- [x] Laravel 11 skeleton project
- [x] Auth login + role middleware
- [x] Migrations: products, orders, order_items, stock_movements, order_imports
- [x] Models + relationships
- [x] Seeder: admin + packer demo + 5 produk contoh
- [x] `OrderImportService` — parser CSV/XLSX dengan alias header fleksibel
- [x] `PackingService` — scan resi + kurangi stok + audit trail
- [x] Halaman scan packer dengan html5-qrcode + Alpine
- [x] Admin: CRUD produk
- [x] Admin: list orders + detail + form upload CSV
- [x] Admin: mapping unmapped items
- [x] Admin: CRUD user
- [x] Sample CSV untuk testing
- [x] README dengan cara install, cara export dari TikTok, cara test

## Fase 2 — Automation &amp; Quality of Life
- [x] Auto watch folder: drop CSV/XLSX ke folder inbox, otomatis di-import (scheduled command `imports:watch`)
- [x] Support Google Drive / Dropbox: tinggal arahkan `WATCH_FOLDER_INBOX` ke folder cloud yg di-sync
- [ ] Cetak label packing PDF (ukuran 10&times;15 cm thermal)
- [ ] Laporan: export stok keluar per periode ke Excel
- [ ] Notifikasi stok menipis ke WhatsApp (Fonnte/Wablas)
- [ ] Dashboard grafik packing 7/30 hari
- [ ] Bulk upload foto produk

## Fase 3 — Data Integrity
- [ ] Retur / pembatalan order (reverse stock)
- [ ] Stock opname (fisik vs sistem) dengan report
- [ ] Backup otomatis DB ke Google Drive / Dropbox
- [ ] Import history: tombol revert (kembalikan ke state sebelum upload)

## Fase 4 — Multi-channel
- [ ] Support import Shopee, Tokopedia (pakai `HEADER_ALIASES` berbeda per marketplace)
- [ ] Multi-warehouse
- [ ] Multi-toko (1 instance, banyak toko)
