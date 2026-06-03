<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OrderHistorySeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ambil data produk & user pelanggan
        $products = Product::all();
        if ($products->isEmpty()) {
            $this->command->error('Tidak ada produk di database. Silakan jalankan DatabaseSeeder utama terlebih dahulu.');
            return;
        }

        $customer = User::where('role', 'pelanggan')->first();
        $customerId = $customer ? $customer->id : null;

        // Kosongkan tabel order & order_items terlebih dahulu untuk reset data penjualan dummy secara database-agnostic
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('order_items')->truncate();
            DB::table('orders')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } else {
            DB::statement('TRUNCATE TABLE order_items, orders CASCADE;');
        }

        $this->command->info('Memulai pembuatan data dummy penjualan 3 tahun kebelakang...');

        $startDate = Carbon::now()->subYears(3)->startOfDay();
        $endDate = Carbon::now()->subDay()->endOfDay();
        
        $ordersBatch = [];
        $orderItemsBatch = [];
        $orderCounter = 1;
        $orderSeqPerYear = [];

        // Loop harian
        $currentDate = clone $startDate;
        $totalDays = $startDate->diffInDays($endDate) + 1;
        
        // Buat pola penjualan per produk
        // Kita berikan produk-produk tertentu tren yang sangat jelas untuk ARIMA training
        while ($currentDate->lte($endDate)) {
            $year = $currentDate->year;
            if (!isset($orderSeqPerYear[$year])) {
                $orderSeqPerYear[$year] = 1;
            }

            // Tentukan jumlah order untuk hari ini (1 s/d 3 order per hari)
            $dayOfWeek = $currentDate->dayOfWeek; // 0 (Sun) s/d 6 (Sat)
            $month = $currentDate->month;

            // Naikkan jumlah penjualan di akhir pekan (Jumat, Sabtu, Minggu)
            $isWeekend = in_array($dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY, Carbon::SUNDAY]);
            $numOrders = $isWeekend ? rand(2, 4) : rand(1, 2);

            for ($o = 0; $o < $numOrders; $o++) {
                $channel = rand(1, 10) > 3 ? 'pos' : 'web';
                $paymentMethod = $channel === 'pos' 
                    ? (rand(1, 3) === 1 ? 'Cash' : (rand(1, 2) === 1 ? 'QRIS' : 'Transfer Bank')) 
                    : 'WhatsApp Checkout';

                $incrementId = sprintf('ORD-%s-%05d', $year, $orderSeqPerYear[$year]);
                $orderSeqPerYear[$year]++;

                // Tentukan produk yang dibeli (1 s/d 3 produk per order)
                $numItems = rand(1, 3);
                $selectedProducts = $products->random($numItems);
                
                $orderTotalPrice = 0;
                $orderItems = [];

                foreach ($selectedProducts as $product) {
                    // Logika Kuantitas Penjualan (qty) yang memiliki Musiman, Tren, dan Hari
                    // Spesifik produk ID 1 (atau ATS-001) kita beri pattern yang SANGAT jelas untuk ARIMA
                    if ($product->sku === 'ATS-001') {
                        // Base qty
                        $qty = 2;
                        
                        // 1. Weekend effect (+2 s/d +4)
                        if ($isWeekend) {
                            $qty += rand(2, 4);
                        } else {
                            $qty += rand(0, 1);
                        }

                        // 2. Trend effect (bertambah seiring tahun)
                        $yearsDiff = $currentDate->diffInYears($startDate);
                        $qty += intval($yearsDiff * 1.5);

                        // 3. Seasonal effect (Ramadhan/Lebaran di sekitar Maret/April/Mei, dan Year End di Desember)
                        if (in_array($month, [4, 5, 12])) {
                            $qty = intval($qty * 2.2);
                        }

                        // 4. Outliers (1% chance untuk lonjakan promo ekstrim)
                        if (rand(1, 100) === 99) {
                            $qty += rand(15, 25);
                        }
                    } 
                    // Spesifik produk ID 2 (atau ATS-002) kita beri pattern mingguan/cyclical yang lain
                    elseif ($product->sku === 'ATS-002') {
                        $qty = 1;
                        if ($dayOfWeek === Carbon::SATURDAY || $dayOfWeek === Carbon::SUNDAY) {
                            $qty += rand(3, 5);
                        } else {
                            $qty += rand(0, 2);
                        }
                        if ($month === 12 || $month === 1) { // Peak tahun baru
                            $qty += rand(2, 4);
                        }
                    }
                    else {
                        // Produk lain penjualan acak
                        $qty = rand(1, 3);
                        if ($isWeekend) {
                            $qty += rand(0, 2);
                        }
                    }

                    if ($qty <= 0) $qty = 1;

                    $priceAtPurchase = $product->price;
                    $itemTotal = $priceAtPurchase * $qty;
                    $orderTotalPrice += $itemTotal;

                    $orderItems[] = [
                        'order_id' => $orderCounter,
                        'product_id' => $product->id,
                        'qty' => $qty,
                        'price_at_purchase' => $priceAtPurchase,
                        'created_at' => $currentDate->copy()->addHours(rand(9, 21))->toDateTimeString(),
                        'updated_at' => $currentDate->copy()->addHours(rand(9, 21))->toDateTimeString(),
                    ];
                }

                // Tambahkan data Order ke batch
                $ordersBatch[] = [
                    'id' => $orderCounter,
                    'increment_id' => $incrementId,
                    'user_id' => $channel === 'web' ? $customerId : null,
                    'channel' => $channel,
                    'status' => 'completed', // Status COMPLETED agar di-training ARIMA
                    'total_price' => $orderTotalPrice,
                    'payment_method' => $paymentMethod,
                    'created_at' => $currentDate->copy()->addHours(rand(9, 21))->toDateTimeString(),
                    'updated_at' => $currentDate->copy()->addHours(rand(9, 21))->toDateTimeString(),
                ];

                // Gabungkan item order
                foreach ($orderItems as $item) {
                    $orderItemsBatch[] = $item;
                }

                $orderCounter++;

                // Lakukan insert berkala jika batch sudah terlalu besar (mengurangi beban memory RAM)
                if (count($ordersBatch) >= 500) {
                    DB::table('orders')->insert($ordersBatch);
                    DB::table('order_items')->insert($orderItemsBatch);
                    $ordersBatch = [];
                    $orderItemsBatch = [];
                }
            }

            $currentDate->addDay();
        }

        // Sisa batch yang belum di-insert
        if (count($ordersBatch) > 0) {
            DB::table('orders')->insert($ordersBatch);
            DB::table('order_items')->insert($orderItemsBatch);
        }

        $this->command->info('Sukses membuat ' . ($orderCounter - 1) . ' dummy orders beserta detail itemnya!');
    }
}
