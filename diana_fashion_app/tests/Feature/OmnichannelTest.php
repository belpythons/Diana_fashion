<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OmnichannelTest extends TestCase
{
    use DatabaseTransactions;

    protected $admin;
    protected $kasir;
    protected $customer;
    protected $category;
    protected $product1;
    protected $product2;

    protected function setUp(): void
    {
        parent::setUp();

        // Ambil data yang di-seed atau buat baru jika kosong
        $this->admin = User::where('role', 'admin')->first() ?: User::factory()->create(['role' => 'admin', 'password' => bcrypt('password')]);
        $this->kasir = User::where('role', 'kasir')->first() ?: User::factory()->create(['role' => 'kasir', 'password' => bcrypt('password')]);
        $this->customer = User::where('role', 'pelanggan')->first() ?: User::factory()->create(['role' => 'pelanggan', 'password' => bcrypt('password')]);

        $this->category = Category::first() ?: Category::create(['name' => 'Atasan']);
        
        $this->product1 = Product::where('sku', 'ATS-001')->first() ?: Product::create([
            'category_id' => $this->category->id,
            'sku' => 'ATS-001',
            'name' => 'Kemeja Flanel Premium',
            'stock' => 25,
            'price' => 149000.00
        ]);
        $this->product1->update(['stock' => 25, 'price' => 149000.00]);

        $this->product2 = Product::where('sku', 'ATS-002')->first() ?: Product::create([
            'category_id' => $this->category->id,
            'sku' => 'ATS-002',
            'name' => 'Kaos Polo Inter',
            'stock' => 50,
            'price' => 89000.00
        ]);
        $this->product2->update(['stock' => 50, 'price' => 89000.00]);
    }

    /**
     * T14: Checkout tanpa login -> 401 Unauthorized
     */
    public function test_web_checkout_requires_login()
    {
        $response = $this->postJson('/api/storefront/checkout', [
            'items' => [
                ['id' => $this->product1->id, 'qty' => 1]
            ]
        ]);

        $response->assertStatus(401);
    }

    /**
     * T1: Web checkout (wajib login) -> stok berkurang, status pending, redirect WA
     */
    public function test_web_checkout_success()
    {
        // Catat stok awal
        $initialStock = $this->product1->stock;

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/storefront/checkout', [
                'items' => [
                    ['id' => $this->product1->id, 'qty' => 2]
                ]
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'whatsapp_url', 'order']);

        // Pastikan stok terpotong
        $this->product1->refresh();
        $this->assertEquals($initialStock - 2, $this->product1->stock);

        // Pastikan order tersimpan di database dengan status pending dan channel web
        $this->assertDatabaseHas('orders', [
            'user_id' => $this->customer->id,
            'channel' => 'web',
            'status' => 'pending',
        ]);
    }

    /**
     * T5: Debounce / Search POS SKU Prefix & Fulltext
     */
    public function test_pos_search_sku_prefix()
    {
        $response = $this->actingAs($this->kasir, 'sanctum')
            ->getJson('/api/pos/products/search?keyword=ATS');

        $response->assertStatus(200);
        
        // SKU ATS-001 dan ATS-002 harus muncul
        $skus = collect($response->json())->pluck('sku');
        $this->assertTrue($skus->contains('ATS-001'));
        $this->assertTrue($skus->contains('ATS-002'));
    }

    /**
     * POS checkout completed
     */
    public function test_pos_checkout_success()
    {
        $initialStock = $this->product2->stock;

        $response = $this->actingAs($this->kasir, 'sanctum')
            ->postJson('/api/pos/checkout', [
                'items' => [
                    ['id' => $this->product2->id, 'qty' => 5]
                ],
                'payment_method' => 'Cash'
            ]);

        $response->assertStatus(201);
        $response->assertJsonStructure(['message', 'order']);

        // Pastikan stok terpotong
        $this->product2->refresh();
        $this->assertEquals($initialStock - 5, $this->product2->stock);

        // Pastikan order berstatus completed dan channel pos
        $this->assertDatabaseHas('orders', [
            'channel' => 'pos',
            'status' => 'completed',
            'payment_method' => 'Cash'
        ]);
    }

    /**
     * T2: ACC antrean web -> status completed, stok tetap
     */
    public function test_pos_queue_acc()
    {
        // 1. Buat order pending E-Commerce terlebih dahulu
        $checkoutResponse = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/storefront/checkout', [
                'items' => [
                    ['id' => $this->product1->id, 'qty' => 1]
                ]
            ]);
        
        $orderId = $checkoutResponse->json('order.id');
        $this->product1->refresh();
        $stockAfterWebCheckout = $this->product1->stock;

        // 2. Kasir menyetujui (ACC) antrean
        $response = $this->actingAs($this->kasir, 'sanctum')
            ->postJson("/api/pos/queue/{$orderId}/validate", [
                'action' => 'acc'
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('order.status', 'completed');

        // Status order harus berubah menjadi completed
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => 'completed'
        ]);

        // Stok produk harus tetap (tidak berkurang lagi karena sudah dipotong saat web checkout)
        $this->product1->refresh();
        $this->assertEquals($stockAfterWebCheckout, $this->product1->stock);
    }

    /**
     * T3: Reject antrean -> status canceled, stok restock
     */
    public function test_pos_queue_reject_and_restock()
    {
        $initialStock = $this->product1->stock;

        // 1. Buat order pending E-Commerce
        $checkoutResponse = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/storefront/checkout', [
                'items' => [
                    ['id' => $this->product1->id, 'qty' => 3]
                ]
            ]);
        
        $orderId = $checkoutResponse->json('order.id');
        $this->product1->refresh();
        $this->assertEquals($initialStock - 3, $this->product1->stock);

        // 2. Kasir menolak (Reject) antrean
        $response = $this->actingAs($this->kasir, 'sanctum')
            ->postJson("/api/pos/queue/{$orderId}/validate", [
                'action' => 'reject'
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('order.status', 'canceled');

        // Status order harus canceled
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => 'canceled'
        ]);

        // Stok harus dikembalikan (restock) ke nilai semula
        $this->product1->refresh();
        $this->assertEquals($initialStock, $this->product1->stock);
    }

    /**
     * Race condition: checkout qty melebihi stok -> error 422
     */
    public function test_checkout_out_of_stock_fails()
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/storefront/checkout', [
                'items' => [
                    ['id' => $this->product1->id, 'qty' => 999] // Melebihi stok yang ada
                ]
            ]);

        $response->assertStatus(422);
    }

    /**
     * Admin Dashboard Metrics: Proteksi Peran
     */
    public function test_admin_metrics_access_protection()
    {
        // 1. Guest mencoba mengakses -> 401
        $response1 = $this->getJson('/api/admin/dashboard/metrics');
        $response1->assertStatus(401);

        // 2. Kasir mencoba mengakses -> 403
        $response2 = $this->actingAs($this->kasir, 'sanctum')
            ->getJson('/api/admin/dashboard/metrics');
        $response2->assertStatus(403);

        // 3. Admin mencoba mengakses -> 200
        $response3 = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/dashboard/metrics');
        $response3->assertStatus(200);
        $response3->assertJsonStructure(['metrics', 'comparison']);
    }

    /**
     * Admin Staff CRUD & Filter Hash (Resolusi #12)
     */
    public function test_admin_staff_crud_filters_password_hash()
    {
        // Pendaftaran staf baru oleh Admin
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/staff', [
                'name' => 'Staf Baru POS',
                'email' => 'newstaff@diana.com',
                'role' => 'kasir',
                'password' => 'securesafepassword123'
            ]);

        $response->assertStatus(201);
        
        // Memastikan password hash tidak bocor di respons (Hanya ada id, name, email, role)
        $response->assertJsonMissing(['password']);
        $response->assertJsonMissing(['password_hash']);
        $response->assertJsonStructure(['message', 'staff' => ['id', 'name', 'email', 'role']]);

        // Cek data di database
        $this->assertDatabaseHas('users', [
            'email' => 'newstaff@diana.com',
            'role' => 'kasir'
        ]);
    }

    /**
     * Admin CSV Export Streamed (Resolusi #7)
     */
    public function test_admin_report_csv_streaming()
    {
        // Setup data transaksi sampel
        $order = Order::create([
            'increment_id' => 'ORD-' . date('Y') . '-99999',
            'user_id' => $this->customer->id,
            'channel' => 'web',
            'status' => 'completed',
            'total_price' => 149000.00,
            'payment_method' => 'WhatsApp Checkout'
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product1->id,
            'qty' => 1,
            'price_at_purchase' => 149000.00
        ]);

        // 1. Uji Ekspor Laporan Detail (Default / detailed)
        $responseDetailed = $this->actingAs($this->admin, 'sanctum')
            ->get('/api/admin/sales/export?start_date=' . date('Y-m-d') . '&end_date=' . date('Y-m-d') . '&export_type=detailed');

        $responseDetailed->assertStatus(200);
        $responseDetailed->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $responseDetailed->assertHeader('Content-Disposition', 'attachment; filename="Laporan_Penjualan_' . date('Y-m-d') . '_to_' . date('Y-m-d') . '.csv"');

        ob_start();
        $responseDetailed->sendContent();
        $contentDetailed = ob_get_clean();
        
        // Cek keberadaan UTF-8 BOM
        $this->assertTrue(str_starts_with($contentDetailed, "\xEF\xBB\xBF"), 'CSV harus diawali dengan UTF-8 BOM');

        // Cek header kolom detail
        $this->assertStringContainsString('Nama Produk', $contentDetailed);
        $this->assertStringContainsString('Kategori', $contentDetailed);
        $this->assertStringContainsString('Jumlah Terjual (Qty)', $contentDetailed);
        
        // Cek keberadaan data produk & kategori secara dinamis
        $this->assertStringContainsString($this->product1->name, $contentDetailed);
        $this->assertStringContainsString($this->category->name, $contentDetailed);
        $this->assertStringContainsString('ORD-' . date('Y') . '-99999', $contentDetailed);

        // 2. Uji Ekspor Laporan Ringkasan (summary)
        $responseSummary = $this->actingAs($this->admin, 'sanctum')
            ->get('/api/admin/sales/export?start_date=' . date('Y-m-d') . '&end_date=' . date('Y-m-d') . '&export_type=summary');

        $responseSummary->assertStatus(200);
        $responseSummary->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        
        ob_start();
        $responseSummary->sendContent();
        $contentSummary = ob_get_clean();
        
        // Cek keberadaan UTF-8 BOM
        $this->assertTrue(str_starts_with($contentSummary, "\xEF\xBB\xBF"), 'CSV harus diawali dengan UTF-8 BOM');

        // Cek header kolom ringkasan
        $this->assertStringContainsString('Produk Terjual', $contentSummary);
        $this->assertStringContainsString('Total Pendapatan (Rp)', $contentSummary);

        // Cek format data tergabung ringkasan secara dinamis
        $expectedSummaryItem = "[{$this->category->name}] {$this->product1->name} (x1)";
        $this->assertStringContainsString($expectedSummaryItem, $contentSummary);
    }

    /**
     * Scheduler: Auto-Cancel Pending > 12 jam & Restock (Resolusi #2 & #11)
     */
    public function test_scheduler_auto_cancel_expired_orders()
    {
        $initialStock = $this->product1->stock; // 25

        // 1. Buat pesanan pending web
        $checkoutResponse = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/storefront/checkout', [
                'items' => [
                    ['id' => $this->product1->id, 'qty' => 5]
                ]
            ]);
        
        $orderId = $checkoutResponse->json('order.id');
        $this->product1->refresh();
        $this->assertEquals($initialStock - 5, $this->product1->stock); // Berkurang ke 20

        // 2. Manipulasi tanggal pesanan menjadi 13 jam yang lalu
        DB::table('orders')->where('id', $orderId)->update([
            'created_at' => now()->subHours(13)
        ]);

        // 3. Picu logik scheduler pembatalan kadaluarsa
        $expiredOrders = Order::where('status', 'pending')
            ->where('channel', 'web')
            ->where('created_at', '<', now()->subHours(12))
            ->get();

        $this->assertEquals(1, $expiredOrders->count());

        foreach ($expiredOrders as $order) {
            DB::transaction(function () use ($order) {
                $order->update(['status' => 'canceled']);
                foreach ($order->items as $item) {
                    Product::where('id', $item->product_id)
                        ->lockForUpdate()
                        ->increment('stock', $item->qty);
                }
            });
        }

        // 4. Verifikasi status order berubah menjadi canceled
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'status' => 'canceled'
        ]);

        // 5. Verifikasi stok dikembalikan (restock) ke 25 semula
        $this->product1->refresh();
        $this->assertEquals($initialStock, $this->product1->stock);
    }

    /**
     * Scheduler: ARIMA Top 5 Dinamis (Resolusi #9)
     */
    public function test_scheduler_arima_top_5_dynamic()
    {
        // 1. Buat 1 transaksi selesai untuk Kemeja Flanel agar terhitung terlaris
        $order = Order::create([
            'increment_id' => 'ORD-TEST-999',
            'user_id' => $this->customer->id,
            'channel' => 'web',
            'status' => 'completed',
            'total_price' => 149000.00,
            'payment_method' => 'Cash'
        ]);

        \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product1->id,
            'qty' => 99999,
            'price_at_purchase' => 149000.00
        ]);

        // 2. Simulasikan query top 5 dinamis
        $topProducts = OrderItem::select('products.id', 'products.name')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'completed')
            ->where('orders.created_at', '>=', now()->subDays(30))
            ->groupBy('products.id', 'products.name')
            ->orderByRaw('SUM(order_items.qty) DESC')
            ->limit(5)
            ->get();

        // Kemeja flanel (product1) harus berada di urutan teratas
        $this->assertTrue($topProducts->isNotEmpty());
        $this->assertEquals($this->product1->name, $topProducts->first()->name);
    }

    /**
     * Test BUG-03 & BUG-04: Customer data responses (index & show) do not leak password hashes
     */
    public function test_customer_data_no_password_leak()
    {
        $response1 = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/customers');

        $response1->assertStatus(200);
        $response1->assertJsonMissing(['password']);
        $response1->assertJsonMissing(['remember_token']);

        $response2 = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/customers/{$this->customer->id}");

        $response2->assertStatus(200);
        $response2->assertJsonMissing(['password']);
        $response2->assertJsonMissing(['remember_token']);
    }

    /**
     * Test POS guest checkout (customer_id = null)
     */
    public function test_pos_guest_checkout()
    {
        $response = $this->actingAs($this->kasir, 'sanctum')
            ->postJson('/api/pos/checkout', [
                'customer_id' => null,
                'items' => [
                    ['id' => $this->product1->id, 'qty' => 1]
                ],
                'payment_method' => 'Cash'
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('order.user_id', null);
    }

    /**
     * Test Admin Product CRUD Full Cycle
     */
    public function test_admin_product_crud_full_cycle()
    {
        // 1. Create Product
        $createResponse = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/products', [
                'category_id' => $this->category->id,
                'sku' => 'ATS-099',
                'name' => 'Kemeja Batik Elegan',
                'stock' => 15,
                'price' => 199000.00
            ]);

        $createResponse->assertStatus(201);
        $productId = $createResponse->json('product.id');

        // 2. Read Product
        $readResponse = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/admin/products/{$productId}");
        $readResponse->assertStatus(200);
        $readResponse->assertJsonPath('sku', 'ATS-099');

        // 3. Update Product
        $updateResponse = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/admin/products/{$productId}", [
                'category_id' => $this->category->id,
                'sku' => 'ATS-099',
                'name' => 'Kemeja Batik Mewah',
                'stock' => 20,
                'price' => 220000.00
            ]);
        $updateResponse->assertStatus(200);
        $updateResponse->assertJsonPath('product.name', 'Kemeja Batik Mewah');

        // 4. Delete Product
        $deleteResponse = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/products/{$productId}");
        $deleteResponse->assertStatus(200);

        $this->assertDatabaseMissing('products', ['id' => $productId]);
    }

    /**
     * Test product deletion blocked if it has transaction history
     */
    public function test_admin_product_delete_with_history_blocked()
    {
        $order = Order::create([
            'increment_id' => 'ORD-TEST-DEL',
            'user_id' => $this->customer->id,
            'channel' => 'pos',
            'status' => 'completed',
            'total_price' => 149000.00
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->product1->id,
            'qty' => 1,
            'price_at_purchase' => 149000.00
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/admin/products/{$this->product1->id}");
        
        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Produk tidak dapat dihapus karena sudah memiliki riwayat transaksi.');
    }

    /**
     * Test POS Recall Cart price change validation
     */
    public function test_pos_validate_held_cart_price_change()
    {
        $response = $this->actingAs($this->kasir, 'sanctum')
            ->postJson('/api/pos/cart/validate-prices', [
                'items' => [
                    [
                        'id' => $this->product1->id,
                        'qty' => 2,
                        'price_at_hold' => $this->product1->price - 1000
                    ]
                ]
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('has_changes', true);
    }

    /**
     * Test IDOR Protection on Customer Portal Order Details
     */
    public function test_customer_order_detail_idor_protection()
    {
        $order = Order::create([
            'increment_id' => 'ORD-TEST-IDR',
            'user_id' => $this->customer->id,
            'channel' => 'web',
            'status' => 'pending',
            'total_price' => 149000.00
        ]);

        $otherCustomer = User::factory()->create(['role' => 'pelanggan']);

        $response = $this->actingAs($otherCustomer, 'sanctum')
            ->getJson("/api/customer/orders/{$order->id}");

        $response->assertStatus(404);
        $response->assertJsonPath('message', 'Pesanan tidak ditemukan atau Anda tidak memiliki hak akses.');
    }

    /**
     * Test Register API forces user role to 'pelanggan'
     */
    public function test_register_forces_pelanggan_role()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Hacker User',
            'email' => 'hacker@diana.com',
            'password' => 'securesafepassword123',
            'password_confirmation' => 'securesafepassword123',
            'role' => 'admin'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'hacker@diana.com',
            'role' => 'pelanggan'
        ]);
    }

    /**
     * Test POS Recall Cart stock validation
     */
    public function test_pos_validate_held_cart_insufficient_stock()
    {
        $response = $this->actingAs($this->kasir, 'sanctum')
            ->postJson('/api/pos/cart/validate-prices', [
                'items' => [
                    [
                        'id' => $this->product1->id,
                        'qty' => 9999, // Exceeds stock
                        'price_at_hold' => $this->product1->price
                    ]
                ]
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('insufficient_stock', true);
    }

    /**
     * Test ARIMA Config GET & POST Access and Operations
     */
    public function test_arima_config_requires_admin_role()
    {
        // 1. Guest -> 401
        $this->getJson('/api/admin/arima/config')->assertStatus(401);
        $this->postJson('/api/admin/arima/config', [])->assertStatus(401);

        // 2. Cashier/Kasir -> 403
        $this->actingAs($this->kasir, 'sanctum')
            ->getJson('/api/admin/arima/config')
            ->assertStatus(403);

        $this->actingAs($this->kasir, 'sanctum')
            ->postJson('/api/admin/arima/config', [])
            ->assertStatus(403);
    }

    public function test_arima_config_crud_success_for_admin()
    {
        // 1. Get configurations
        $response1 = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/arima/config');

        $response1->assertStatus(200);
        $response1->assertJsonStructure([
            'arima_forecast_periods',
            'arima_fill_missing_dates',
            'arima_smooth_outliers',
            'arima_start_p',
            'arima_start_q',
            'arima_max_p',
            'arima_max_q',
            'arima_seasonal',
            'arima_stepwise'
        ]);

        // 2. Save configurations
        $payload = [
            'arima_forecast_periods' => 14,
            'arima_fill_missing_dates' => false,
            'arima_smooth_outliers' => false,
            'arima_start_p' => 1,
            'arima_start_q' => 1,
            'arima_max_p' => 3,
            'arima_max_q' => 3,
            'arima_seasonal' => true,
            'arima_stepwise' => false
        ];

        $response2 = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/arima/config', $payload);

        $response2->assertStatus(200);
        $response2->assertJsonPath('message', 'Konfigurasi parameter ARIMA berhasil diperbarui.');

        // 3. Verify changes persist
        $response3 = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/arima/config');
        
        $response3->assertStatus(200);
        $this->assertEquals(14, $response3->json('arima_forecast_periods'));
        $this->assertEquals(false, $response3->json('arima_fill_missing_dates'));
        $this->assertEquals(false, $response3->json('arima_smooth_outliers'));
        $this->assertEquals(1, $response3->json('arima_start_p'));
        $this->assertEquals(1, $response3->json('arima_start_q'));
        $this->assertEquals(3, $response3->json('arima_max_p'));
        $this->assertEquals(3, $response3->json('arima_max_q'));
        $this->assertEquals(true, $response3->json('arima_seasonal'));
        $this->assertEquals(false, $response3->json('arima_stepwise'));
    }
}
