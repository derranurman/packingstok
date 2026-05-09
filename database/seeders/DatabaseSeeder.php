<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Users demo
        User::updateOrCreate(
            ['email' => 'admin@toko.test'],
            [
                'name' => 'Admin Toko',
                'password' => Hash::make('admin123'),
                'role' => User::ROLE_ADMIN,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'packer@toko.test'],
            [
                'name' => 'Packer Satu',
                'password' => Hash::make('packer123'),
                'role' => User::ROLE_PACKER,
                'is_active' => true,
            ]
        );

        // Produk contoh — SKU harus match dengan sample_tiktok_orders.csv supaya langsung ke-mapping
        $products = [
            ['sku' => 'SKU-STIR-SKEL', 'name' => 'Stir Skeleton', 'price' => 125000, 'stock' => 50, 'description' => 'Stir motor custom skeleton'],
            ['sku' => 'SKU-HELM-RETRO', 'name' => 'Helm Retro Half Face', 'price' => 185000, 'stock' => 20, 'description' => 'Helm retro half face SNI'],
            ['sku' => 'SKU-KAOS-KAKI', 'name' => 'Kaos Kaki Motor', 'price' => 25000, 'stock' => 100, 'description' => 'Kaos kaki tebal untuk touring'],
            ['sku' => 'SKU-SPION-SK', 'name' => 'Spion Skeleton', 'price' => 75000, 'stock' => 30, 'description' => 'Spion motor skeleton'],
            ['sku' => 'SKU-COVER-SEAT', 'name' => 'Cover Jok Universal', 'price' => 55000, 'stock' => 8, 'description' => 'Cover jok universal semua motor'],
        ];

        foreach ($products as $p) {
            Product::updateOrCreate(['sku' => $p['sku']], $p + ['is_active' => true, 'low_stock_threshold' => 10]);
        }

        $this->command->info('Seed selesai.');
        $this->command->info('  Admin  -> admin@toko.test / admin123');
        $this->command->info('  Packer -> packer@toko.test / packer123');
        $this->command->info('Sample CSV ada di storage/app/sample/sample_tiktok_orders.csv');
    }
}
