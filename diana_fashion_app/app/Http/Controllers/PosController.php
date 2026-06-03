<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function searchProducts(Request $request)
    {
        $request->validate([
            'keyword' => ['nullable', 'string'],
            'category' => ['nullable', 'string']
        ]);

        $query = Product::with('category');

        // Filter berdasarkan kategori jika disediakan
        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('name', $request->category);
            });
        }

        // Pencarian berdasarkan keyword SKU (prefix match) atau nama (FULLTEXT) (Resolusi #6)
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('sku', 'LIKE', $keyword . '%')
                  ->orWhereRaw("MATCH(name) AGAINST(? IN BOOLEAN MODE)", [$keyword . '*']);
            });
        }

        // Jika tidak ada kriteria pencarian, default tampilkan 15 produk terbaru
        if (!$request->filled('keyword') && !$request->filled('category')) {
            $products = $query->orderBy('created_at', 'desc')->take(15)->get();
        } else {
            $products = $query->get();
        }

        return response()->json($products);
    }

    public function processCheckout(Request $request)
    {
        $request->validate([
            'customer_id' => ['nullable', 'exists:users,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'], // Resolusi #3: qty
            'payment_method' => ['required', 'string', 'in:Cash,QRIS,Transfer']
        ]);

        $maxAttempts = 3;
        $attempt = 0;

        while (true) {
            $attempt++;
            try {
                return DB::transaction(function () use ($request) {
                    $items = $request->items;
                    $totalPrice = 0;
                    $validatedItems = [];

                    // 1. Validasi Stok Terlebih Dahulu dengan Lock
                    foreach ($items as $item) {
                        $product = Product::where('id', $item['id'])
                            ->lockForUpdate() // SKILL.md 5.1
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

                    // 2. Generate increment_id (Format: ORD-YYYY-NNN)
                    $year = date('Y');
                    $latestOrder = Order::whereYear('created_at', $year)
                        ->orderBy('id', 'desc')
                        ->first();

                    $sequence = 1;
                    if ($latestOrder) {
                        $parts = explode('-', $latestOrder->increment_id);
                        if (count($parts) === 3) {
                            $sequence = intval($parts[2]) + 1;
                        }
                    }
                    $incrementId = sprintf('ORD-%s-%03d', $year, $sequence);

                    // 3. Buat Data Order (Status: completed, Kanal: pos)
                    $order = Order::create([
                        'increment_id' => $incrementId,
                        'user_id' => $request->customer_id, // Nullable untuk support Guest Checkout
                        'channel' => 'pos',
                        'status' => 'completed',
                        'total_price' => $totalPrice,
                        'payment_method' => $request->payment_method
                    ]);

                    // 4. Potong Stok & Simpan Item Transaksi
                    foreach ($validatedItems as $vItem) {
                        $product = $vItem['product'];
                        
                        // Potong stok (decrement) secara atomic
                        $product->decrement('stock', $vItem['qty']);

                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'qty' => $vItem['qty'], // Resolusi #3: menggunakan qty
                            'price_at_purchase' => $vItem['price_at_purchase']
                        ]);
                    }

                    return response()->json([
                        'message' => 'Transaksi POS berhasil diselesaikan.',
                        'order' => $order->load('items.product')
                    ], 201);
                });
            } catch (\Exception $e) {
                if ($e->getCode() === 422) {
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

    public function getOnlineQueue()
    {
        // Ambil antrean pesanan online web yang pending (menunggu ACC kasir)
        $orders = Order::with(['user', 'items.product'])
            ->where('status', 'pending')
            ->where('channel', 'web')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($orders);
    }

    public function validateQueue(Request $request, $id)
    {
        $request->validate([
            'action' => ['required', 'string', 'in:acc,reject']
        ]);

        return DB::transaction(function () use ($request, $id) {
            $order = Order::where('id', $id)
                ->where('status', 'pending')
                ->where('channel', 'web')
                ->lockForUpdate()
                ->first();

            if (!$order) {
                return response()->json([
                    'message' => 'Antrean pesanan tidak ditemukan atau sudah diproses.'
                ], 404);
            }

            if ($request->action === 'acc') {
                // ACC: update status pesanan menjadi completed (Stok TIDAK dipotong lagi karena sudah dikurangi saat checkout web)
                $order->update(['status' => 'completed']);
                $message = 'Pesanan online berhasil disetujui (ACC).';
            } else {
                // Reject: update status pesanan menjadi canceled + lakukan restock (increment)
                $order->update(['status' => 'canceled']);
                
                foreach ($order->items as $item) {
                    Product::where('id', $item->product_id)
                        ->lockForUpdate()
                        ->increment('stock', $item->qty); // Resolusi #2 & #11: restock stok pesanan batal
                }
                
                $message = 'Pesanan online berhasil ditolak (Reject). Stok dikembalikan ke inventaris.';
            }

            return response()->json([
                'message' => $message,
                'order' => $order->load('items.product')
            ]);
        });
    }

    public function validateHeldCart(Request $request)
    {
        $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1']
        ]);

        $validatedItems = [];
        $hasChanges = false;
        $insufficientStock = false;

        foreach ($request->items as $item) {
            $product = Product::find($item['id']);
            
            $status = 'ok';
            if ($product->stock < $item['qty']) {
                $status = 'insufficient_stock';
                $insufficientStock = true;
            }

            // Cek jika harga berubah
            if (isset($item['price_at_hold']) && $item['price_at_hold'] != $product->price) {
                $hasChanges = true;
            }

            $validatedItems[] = [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'price' => $product->price,
                'qty' => $item['qty'],
                'stock' => $product->stock,
                'status' => $status
            ];
        }

        return response()->json([
            'items' => $validatedItems,
            'has_changes' => $hasChanges,
            'insufficient_stock' => $insufficientStock
        ]);
    }
}
