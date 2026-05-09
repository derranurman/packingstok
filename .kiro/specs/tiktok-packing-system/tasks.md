# Implementation Tasks

## Fase 1 — Scaffolding Minimal (Sprint ini)
- [ ] Laravel 11 skeleton project
- [ ] Auth login + migration `users.role`
- [ ] Migrations: products, orders, order_items, stock_movements, tiktok_credentials
- [ ] Models + relationships
- [ ] Middleware `role`
- [ ] Seeder: admin + packer demo + 5 produk contoh (termasuk "Stir Skeleton")
- [ ] `TikTokShopClient` (mock mode only di fase 1)
- [ ] `OrderSyncService`
- [ ] `PackingService` + controller scan
- [ ] Halaman scan packer dengan html5-qrcode + Alpine
- [ ] Admin: CRUD produk (list + create + edit + delete)
- [ ] Admin: list orders + detail
- [ ] Admin: mapping unmapped items
- [ ] Admin: form kredensial TikTok
- [ ] Command `tiktok:poll` + schedule
- [ ] README dengan cara install & run

## Fase 2 — TikTok Live Integration
- [ ] Implementasi `TikTokShopClient::getOrders()` dengan signed request (HMAC-SHA256)
- [ ] Auto refresh access token
- [ ] Webhook endpoint + Cloudflare Tunnel instruction
- [ ] Retry & error log

## Fase 3 — Quality of Life
- [ ] Laporan: export stok ke Excel
- [ ] Cetak label packing
- [ ] Notifikasi stok menipis (email / WA API)
- [ ] Dashboard grafik packing 7/30 hari
- [ ] Retur / batal order

## Fase 4 — Multi-toko
- [ ] Support >1 shop TikTok
- [ ] Support >1 warehouse
