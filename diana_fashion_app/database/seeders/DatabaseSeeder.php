<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Akun Default
        User::create([
            'name' => 'Admin Diana',
            'email' => 'admin@diana.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Kasir Toko',
            'email' => 'kasir@diana.com',
            'password' => Hash::make('password'),
            'role' => 'kasir',
        ]);

        User::create([
            'name' => 'Pelanggan Setia',
            'email' => 'customer@diana.com',
            'password' => Hash::make('password'),
            'role' => 'pelanggan',
        ]);

        // 2. Seed Kategori Awal
        $atasan = Category::create(['name' => 'Atasan']);
        $bawahan = Category::create(['name' => 'Bawahan']);
        $gamis = Category::create(['name' => 'Gamis']);

        // Map category names to IDs
        $categoryMap = [
            'Atasan' => $atasan->id,
            'Bawahan' => $bawahan->id,
            'Gamis' => $gamis->id,
        ];

        // 3. Seed Produk from JSON
        $jsonPath = database_path('seeders/products_data.json');
        if (File::exists($jsonPath)) {
            $products = json_decode(File::get($jsonPath), true);
            foreach ($products as $p) {
                Product::create([
                    'category_id' => $categoryMap[$p['category']] ?? $atasan->id,
                    'sku' => $p['sku'],
                    'name' => $p['name'],
                    'stock' => $p['stock'],
                    'price' => $p['price'],
                ]);
            }
        }
    }
}
