<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use App\Models\User;
use App\Http\Controllers\Admin\ArimaController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Schedulers
|--------------------------------------------------------------------------
*/

// Scheduler 1: ARIMA AI Harian (Jam 01:00 WITA — Asia/Makassar)
// Resolusi #9: DINAMIS mendeteksi top 5 produk terlaris dalam 30 hari terakhir dari DB
Schedule::call(function () {
    Log::info('ARIMA Harian: Memulai kalkulasi otomatis proyeksi AI...');

    $topProducts = OrderItem::select('products.id', 'products.name')
        ->join('products', 'order_items.product_id', '=', 'products.id')
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->where('orders.status', 'completed')
        ->where('orders.created_at', '>=', now()->subDays(30))
        ->groupBy('products.id', 'products.name')
        ->orderByRaw('SUM(order_items.qty) DESC') // Resolusi #3: menggunakan qty
        ->limit(5)
        ->get();

    if ($topProducts->isEmpty()) {
        Log::warning('ARIMA Harian: Tidak ada transaksi dalam 30 hari terakhir. Peramalan dibatalkan.');
        return;
    }

    // Gunakan admin default sebagai pemicu untuk logs tracking (Resolusi #4)
    $admin = User::where('role', 'admin')->first();
    if ($admin) {
        auth()->login($admin);
    }

    $controller = new ArimaController();

    foreach ($topProducts as $prod) {
        try {
            $mockRequest = new Request([
                'product_id' => $prod->id,
                'forecast_periods' => 7,
                'fill_missing_dates' => true,
                'smooth_outliers' => true
            ]);

            $controller->runPrediction($mockRequest);
            Log::info("ARIMA Harian: Sukses menghitung proyeksi untuk produk '{$prod->name}'.");
        } catch (\Exception $e) {
            Log::error("ARIMA Harian: Gagal menghitung produk '{$prod->name}'. Error: " . $e->getMessage());
        }
    }

    Log::info('ARIMA Harian: Seluruh kalkulasi otomatis selesai.');
})->name('arima_daily_forecast')->dailyAt('01:00')->timezone('Asia/Makassar')->withoutOverlapping();


// Scheduler 2: Pembatalan Otomatis Pesanan Kadaluarsa (Setiap Jam)
// Resolusi #2 & #11: Membatalkan pesanan web pending > 12 jam, melakukan restock, dan withoutOverlapping()
Schedule::call(function () {
    Log::info('Auto-Cancel: Memulai pengecekan pesanan kadaluarsa...');

    // Ambil pesanan web pending berumur lebih dari 12 jam
    $expiredOrders = Order::where('status', 'pending')
        ->where('channel', 'web')
        ->where('created_at', '<', now()->subHours(12))
        ->get();

    if ($expiredOrders->isEmpty()) {
        Log::info('Auto-Cancel: Tidak ada pesanan pending yang kadaluarsa.');
        return;
    }

    $cancelCount = 0;

    foreach ($expiredOrders as $order) {
        try {
            DB::transaction(function () use ($order, &$cancelCount) {
                // Update status menjadi canceled
                $order->update(['status' => 'canceled']);

                // Restock kuantitas secara atomic (Resolusi #2 & #11)
                foreach ($order->items as $item) {
                    Product::where('id', $item->product_id)
                        ->lockForUpdate() // Mencegah bentrok stok saat update
                        ->increment('stock', $item->qty); // Resolusi #3: menggunakan qty
                }

                $cancelCount++;
            });
        } catch (\Exception $e) {
            Log::error("Auto-Cancel: Gagal membatalkan nota '{$order->increment_id}'. Error: " . $e->getMessage());
        }
    }

    Log::info("Auto-Cancel: Selesai. Berhasil membatalkan dan me-restock {$cancelCount} pesanan.");
})->name('auto_cancel_expired_orders')->hourly()->timezone('Asia/Makassar')->withoutOverlapping(); // Resolusi #11: withoutOverlapping()
