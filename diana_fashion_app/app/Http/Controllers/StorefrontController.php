<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class StorefrontController extends Controller
{
    public function getProducts(Request $request)
    {
        $category = $request->get('category', '');
        $keyword = $request->get('keyword', '');
        $sort = $request->get('sort', 'newest');
        $page = $request->get('page', 1);

        $cacheKey = "storefront_products_p_{$page}_c_{$category}_s_{$sort}_k_{$keyword}";

        $productsData = Cache::remember($cacheKey, 60, function () use ($category, $keyword, $sort) {
            $query = Product::with('category');

            // 1. Filter Kategori
            if (!empty($category)) {
                $query->whereHas('category', function ($q) use ($category) {
                    $q->where('name', $category);
                });
            }

            // 2. Pencarian Nama Produk (kompatibel cross-database)
            if (!empty($keyword)) {
                if (\Illuminate\Support\Facades\DB::getDriverName() === 'pgsql') {
                    $query->where('name', 'ILIKE', '%' . $keyword . '%');
                } else {
                    $query->whereRaw("MATCH(name) AGAINST(? IN BOOLEAN MODE)", [$keyword . '*']);
                }
            }

            // 3. Sorting (newest, price_asc, price_desc)
            if ($sort === 'price_asc') {
                $query->orderBy('price', 'asc');
            } elseif ($sort === 'price_desc') {
                $query->orderBy('price', 'desc');
            } else {
                $query->orderBy('created_at', 'desc'); // newest
            }

            return $query->paginate(12);
        });

        return response()->json($productsData);
    }

    public function getArimaRecommendations()
    {
        // Ambil hasil rekomendasi yang disimpan di cache oleh ARIMA Scheduler (Resolusi #9)
        $recommendations = Cache::remember('arima_storefront_recommendations', now()->addHours(12), function () {
            // Fallback jika cache kosong: ambil 4 produk terlaris atau stok terbanyak
            return Product::with('category')
                ->orderBy('stock', 'desc')
                ->take(4)
                ->get();
        });

        // Pengamanan data: Jika data di dalam cache mengalami kegagalan deserialisasi (Incomplete Class)
        // atau bukan merupakan instance Eloquent Product asli, muat ulang dari database (Resolusi #9)
        $isSafe = true;
        if ($recommendations instanceof \Illuminate\Support\Collection || is_array($recommendations)) {
            foreach ($recommendations as $rec) {
                if (!($rec instanceof Product)) {
                    $isSafe = false;
                    break;
                }
            }
        } else {
            $isSafe = false;
        }

        if (!$isSafe) {
            // Muat ulang 4 produk langsung dari database untuk memastikan keaslian data
            $recommendations = Product::with('category')
                ->orderBy('stock', 'desc')
                ->take(4)
                ->get();
            
            // Simpan ulang ke cache sebagai raw model data yang bersih
            Cache::put('arima_storefront_recommendations', $recommendations, now()->addHours(12));
        }

        return response()->json($recommendations);
    }

    public function processWebCheckout(Request $request)
    {
        // Resolusi #1: Checkout Web WAJIB terautentikasi (dilindungi oleh auth:sanctum di routing)
        $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'], // Resolusi #3: menggunakan qty
        ]);

        $maxAttempts = 3;
        $attempt = 0;

        while (true) {
            $attempt++;
            try {
                return DB::transaction(function () use ($request) {
                    $user = $request->user();
                    $items = $request->items;

                    // 1. Hitung Total & Validasi Stok Terlebih Dahulu dengan Lock
                    $totalPrice = 0;
                    $validatedItems = [];

                    foreach ($items as $item) {
                        // Gunakan lockForUpdate untuk mencegah race condition (SKILL.md 5.1)
                        $product = Product::where('id', $item['id'])
                            ->lockForUpdate()
                            ->first();

                        if ($product->stock < $item['qty']) {
                            throw new \Exception("Stok untuk produk '{$product->name}' tidak cukup. Stok tersisa: {$product->stock}.", 422);
                        }

                        $totalPrice += $product->price * $item['qty'];
                        $validatedItems[] = [
                            'product' => $product,
                            'qty' => $item['qty'],
                            'price_at_purchase' => $product->price
                        ];
                    }

                    // 2. Generate Nota increment_id (Format: ORD-YYYY-NNN)
                    $year = date('Y');
                    $latestOrder = Order::whereYear('created_at', $year)
                        ->orderBy('id', 'desc')
                        ->first();

                    $sequence = 1;
                    if ($latestOrder) {
                        // Ekstrak angka urut terakhir
                        $parts = explode('-', $latestOrder->increment_id);
                        if (count($parts) === 3) {
                            $sequence = intval($parts[2]) + 1;
                        }
                    }
                    $incrementId = sprintf('ORD-%s-%03d', $year, $sequence);

                    // 3. Buat Data Order (Status: pending, Kanal: web)
                    $order = Order::create([
                        'increment_id' => $incrementId,
                        'user_id' => $user->id,
                        'channel' => 'web',
                        'status' => 'pending',
                        'total_price' => $totalPrice,
                        'payment_method' => 'WhatsApp Checkout'
                    ]);

                    // 4. Pengurangan Stok & Simpan Order Items
                    $waMessageText = "*Diana Fashion — Nota Belanja*\n";
                    $waMessageText .= "=============================\n";
                    $waMessageText .= "No. Nota: `{$incrementId}`\n";
                    $waMessageText .= "Pelanggan: {$user->name}\n";
                    $waMessageText .= "Kanal: Web E-Commerce Storefront\n";
                    $waMessageText .= "=============================\n\n";

                    foreach ($validatedItems as $index => $vItem) {
                        $product = $vItem['product'];
                        
                        // Potong stok (decrement) secara atomic
                        $product->decrement('stock', $vItem['qty']);

                        // Buat item order
                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'qty' => $vItem['qty'], // Resolusi #3: menggunakan qty
                            'price_at_purchase' => $vItem['price_at_purchase']
                        ]);

                        $no = $index + 1;
                        $subtotal = number_format($product->price * $vItem['qty'], 0, ',', '.');
                        $waMessageText .= "{$no}. {$product->name} (x{$vItem['qty']}) - Rp {$subtotal}\n";
                    }

                    $formattedTotal = number_format($totalPrice, 0, ',', '.');
                    $waMessageText .= "\n=============================\n";
                    $waMessageText .= "*Total Belanja: Rp {$formattedTotal}*\n";
                    $waMessageText .= "=============================\n\n";
                    $waMessageText .= "Mohon konfirmasi pembayaran agar pesanan Anda dapat segera kami proses. Terima kasih.";

                    // 5. Generate WhatsApp Redirect URL
                    $waPhone = config('services.whatsapp.number', '628123456789');
                    $waUrl = 'https://api.whatsapp.com/send?phone=' . $waPhone . '&text=' . urlencode($waMessageText);

                    return response()->json([
                        'message' => 'Pesanan berhasil dibuat. Silakan selesaikan pembayaran via WhatsApp.',
                        'order' => $order,
                        'whatsapp_url' => $waUrl
                    ], 201);
                });
            } catch (\Exception $e) {
                if ($e->getCode() == 422) {
                    return response()->json([
                        'message' => $e->getMessage()
                    ], 422);
                }
                // Retry jika terjadi duplicate entry pada increment_id (SQLSTATE 23000)
                if ($e instanceof \Illuminate\Database\QueryException && $e->getCode() == '23000' && $attempt < $maxAttempts) {
                    usleep(rand(50000, 200000)); // sleep 50-200ms
                    continue;
                }
                throw $e;
            }
        }
    }
}
