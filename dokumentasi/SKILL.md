---
name: diana-fashion-omnichannel
description: >
  Skill acuan untuk coding proyek Diana Fashion Omnichannel.
  Sistem e-commerce + POS + admin panel berbasis Laravel 11 + Vue.js 3 (Vue Router SPA) + Tailwind CSS + Flask ARIMA Microservice.
  Gunakan skill ini sebagai referensi utama saat menulis kode untuk seluruh modul.
  Semua resolusi cacat logika dari Resolusi Logika Final Custom telah diterapkan.
---

# Diana Fashion Omnichannel — Coding Skill Reference

> **Dokumen Acuan Resolusi:** Resolusi Logika Final Custom.md
> Semua aturan di SKILL ini sudah memperhitungkan 12 resolusi cacat logika yang ditemukan.

## 1. Ringkasan Proyek

Sistem **omnichannel** toko Diana Fashion yang terdiri dari 5 output utama:

| Modul | Stack | Pengguna | Rute Utama |
|-------|-------|----------|------------|
| E-Commerce Storefront | Laravel + Vue.js | Pelanggan (publik) | `/` |
| Customer Portal | Laravel + Vue.js | Pelanggan (auth) | `/customer/*` |
| POS Terminal | Laravel + Vue.js SPA | Kasir | `/pos` |
| Admin Panel | Laravel + Vue.js SPA | Admin | `/admin/*` |
| ARIMA Microservice | Flask + Python | Internal (server-to-server) | `localhost:5000/api/v1/*` |

---

## 2. Arsitektur & Konvensi

### 2.1 Tech Stack

```
Backend:   Laravel 11 (PHP 8.2+)
Frontend:  Vue.js 3 (Composition API) + Tailwind CSS
Bundler:   Vite
Database:  MySQL 8.0+ (InnoDB)
Auth:      Laravel Sanctum (SPA cookie-based)
AI:        Flask + pmdarima (Python 3.10+)
```

### 2.2 Pendekatan Arsitektur

- **Frontend:** Vue Router SPA murni (BUKAN Inertia.js). 3 entry point terpisah:
  - `resources/js/app.js` → E-Commerce + Customer Portal
  - `resources/js/pos.js` → POS Terminal
  - `resources/js/admin.js` → Admin Panel
- **Backend:** API-first approach via `routes/api.php` + Sanctum SPA Auth
- **Blade:** Hanya sebagai shell/kerangka HTML yang memuat entry point Vue via Vite
- **State:** Pinia untuk state management di Vue

### 2.3 Struktur Direktori Proyek

```
diana_fashion_app/
├── app/
│   └── Http/
│       ├── Controllers/
│       │   ├── Auth/                          # Custom auth controllers (Sanctum SPA)
│       │   ├── Admin/                         # Admin back-office controllers
│       │   │   ├── AdminDashboardController.php
│       │   │   ├── AdminProductController.php
│       │   │   ├── AdminOrderController.php
│       │   │   ├── AdminCustomerController.php
│       │   │   ├── AdminStaffController.php
│       │   │   ├── AdminReportController.php
│       │   │   └── ArimaController.php
│       │   ├── StorefrontController.php       # E-Commerce publik
│       │   ├── CustomerPortalController.php   # Portal pelanggan
│       │   └── PosController.php              # POS kasir
│       └── Middleware/
│           └── RoleMiddleware.php
├── app/Models/
│   ├── User.php
│   ├── Category.php
│   ├── Product.php
│   ├── Order.php
│   ├── OrderItem.php
│   └── PredictionLog.php
├── database/
│   ├── migrations/
│   └── seeders/
│       └── DatabaseSeeder.php                 # Seed admin + kategori awal
├── resources/
│   ├── js/
│   │   ├── app.js                             # Entry: E-Commerce
│   │   ├── pos.js                             # Entry: POS SPA
│   │   ├── admin.js                           # Entry: Admin SPA
│   │   └── Pages/
│   │       ├── Storefront/                    # Komponen E-Commerce
│   │       │   ├── Layouts/
│   │       │   ├── Components/
│   │       │   ├── Views/
│   │       │   └── Customer/
│   │       ├── POS/                           # Komponen POS
│   │       │   ├── Components/
│   │       │   └── Views/
│   │       └── Admin/                         # Komponen Admin
│   │           ├── Layouts/
│   │           ├── Dashboard/
│   │           ├── Products/
│   │           ├── Orders/
│   │           ├── Users/
│   │           └── Reports/
│   └── views/
│       ├── storefront/index.blade.php         # Shell → app.js
│       ├── pos/index.blade.php                # Shell → pos.js
│       └── admin/app.blade.php                # Shell → admin.js
├── routes/
│   ├── web.php                                # Catch-all routes untuk SPA
│   ├── api.php                                # Semua API endpoints
│   └── console.php                            # Schedulers (ARIMA cron, auto-cancel)
└── diana_arima_service/                       # Flask microservice (TERPISAH)
    ├── venv/
    ├── app.py
    ├── requirements.txt
    └── .env
```

---

## 3. Database Schema

### 3.1 Tabel Inti

```sql
-- users (modifikasi bawaan Laravel)
-- Tambah: role ENUM('admin','kasir','pelanggan') DEFAULT 'pelanggan'

-- categories
id, name (unique), timestamps

-- products
id, category_id (FK→categories, restrictOnDelete),
sku (unique, indexed), name (FULLTEXT indexed — Resolusi #6),
stock (default 0), price (decimal 12,2),
image_url (nullable), timestamps

-- orders
id, increment_id (unique, format: ORD-YYYY-NNN),
user_id (nullable FK→users, nullOnDelete),
channel ENUM('web','pos'), status ENUM('pending','completed','canceled'),
total_price (decimal 12,2), payment_method (nullable),
timestamps

-- order_items
id, order_id (FK→orders, cascadeOnDelete),
product_id (FK→products, restrictOnDelete),
qty (integer — Resolusi #3: BUKAN qty_ordered),
price_at_purchase (decimal 12,2), timestamps

-- prediction_logs (Resolusi #4: model WAJIB dibuat)
id, user_id (nullable FK→users),
product_name, forecast_periods, tuning_parameters (JSON),
arima_order, mape_score (decimal 5,2),
execution_time_ms (integer), timestamps
```

### 3.2 Aturan Penting Database

- **SELALU** gunakan `DB::transaction()` + `lockForUpdate()` saat mengurangi stok
- **JANGAN** gunakan soft delete — gunakan status enum untuk lifecycle management
- Kolom `price_at_purchase` di order_items = harga snapshot saat transaksi (immutable)
- Kolom `stock` di products = stok aktual real-time (mutable, atomic)

---

## 4. RBAC (Role-Based Access Control)

### 4.1 Roles & Permissions

| Role | Akses | Rute | Middleware |
|------|-------|------|-----------|
| `admin` | Full back-office | `/admin/*` | `auth:sanctum, role:admin` |
| `kasir` | POS terminal only | `/pos` | `auth:sanctum, role:kasir` |
| `pelanggan` | E-Commerce + Portal | `/customer/*` | `auth:sanctum, role:pelanggan` |
| Guest | Katalog publik only | `/`, `/api/storefront/products` | Tidak ada |

### 4.2 Aturan Auth

- Registrasi publik: **SELALU** paksa `role = 'pelanggan'`
- Admin & Kasir dibuat **HANYA** dari Admin Panel (`AdminStaffController`)
- Login terpusat: satu form `/login`, redirect berdasarkan role
- Checkout web: **WAJIB** login (pelanggan). Guest hanya bisa lihat katalog
- **JANGAN** pernah return password hash di API response

---

## 5. Pola Kode (Code Patterns)

### 5.1 Controller Pattern — Checkout dengan Lock

```php
// WAJIB digunakan di SEMUA checkout flow (Web & POS)
public function processCheckout(Request $request)
{
    return DB::transaction(function () use ($request) {
        $order = Order::create([...]);

        foreach ($request->items as $item) {
            $product = Product::where('id', $item['id'])
                ->lockForUpdate()
                ->first();

            if ($product->stock < $item['qty']) {
                throw new \Exception("Stok {$product->name} tidak cukup.");
            }

            $product->decrement('stock', $item['qty']);

            OrderItem::create([
                'order_id'          => $order->id,
                'product_id'        => $product->id,
                'qty'               => $item['qty'],
                'price_at_purchase' => $product->price,
            ]);
        }

        return $order;
    });
}
```

### 5.2 Vue Pattern — Debounce Search (POS)

```javascript
// WAJIB gunakan debounce 300ms untuk pencarian POS
let debounceTimer = null;

const onSearchInput = (keyword) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(async () => {
        if (keyword.length < 2) return;
        const response = await axios.get('/api/pos/products/search', {
            params: { keyword }
        });
        searchResults.value = response.data;
    }, 300);
};
```

### 5.3 Vue Pattern — Filter, Sort, Paginate (E-Commerce)

```javascript
// Reaktivitas Vue: auto-fetch saat filter berubah
const filters = reactive({ category: '', sort: 'newest', page: 1 });

const fetchProducts = async () => {
    const response = await axios.get('/api/storefront/products', {
        params: filters
    });
    productList.value = response.data.data;
    paginationInfo.value = response.data;
};

watch(() => filters, () => {
    filters.page = 1; // Reset ke halaman 1 saat filter berubah
    fetchProducts();
}, { deep: true });
```

### 5.4 Laravel Pattern — StreamedResponse Export CSV

```php
// WAJIB gunakan cursor() bukan get() untuk memory efficiency
public function exportCSV(Request $request)
{
    $response = new StreamedResponse(function() use ($request) {
        $handle = fopen('php://output', 'w');
        fputcsv($handle, ['No. Nota', 'Tanggal', 'Kanal', 'Total', 'Metode']);

        Order::where('status', 'completed')
            ->whereBetween('created_at', [$request->start_date, $request->end_date])
            ->orderBy('created_at', 'asc')
            ->cursor()
            ->each(function ($order) use ($handle) {
                fputcsv($handle, [
                    $order->increment_id,
                    $order->created_at->format('Y-m-d H:i:s'),
                    $order->channel,
                    $order->total_price,
                    $order->payment_method,
                ]);
            });

        fclose($handle);
    });

    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="Laporan.csv"');
    return $response;
}
```

### 5.5 Flask Pattern — ARIMA Prediction (MAPE Adaptif — Resolusi #8)

```python
# Resolusi #8: MAPE Adaptif berdasarkan jumlah data
if len(ts_data) >= 15:
    # === DATA CUKUP: Train-Test Split 80/20 ===
    split_point = int(len(ts_data) * 0.8)
    train = ts_data[:split_point]
    test = ts_data[split_point:]

    model = auto_arima(train, start_p=0, start_q=0, max_p=5, max_q=5,
                       d=None, seasonal=False, stepwise=True,
                       error_action='ignore', suppress_warnings=True)

    # Evaluasi out-of-sample (MAPE jujur)
    test_preds = model.predict(n_periods=len(test))
    mape = mean_absolute_percentage_error(test, test_preds) * 100
    mape_method = "out_of_sample"

    # Refit dengan seluruh data untuk forecast final
    model.update(test)
else:
    # === DATA MINIM: In-sample + Warning Flag ===
    model = auto_arima(ts_data, start_p=0, start_q=0, max_p=5, max_q=5,
                       d=None, seasonal=False, stepwise=True,
                       error_action='ignore', suppress_warnings=True)

    in_sample_preds = model.predict_in_sample()
    mape = mean_absolute_percentage_error(ts_data, in_sample_preds) * 100
    mape_method = "in_sample_warning"  # Flag: akurasi belum reliable

forecast_values = model.predict(n_periods=periods)
# Sertakan mape_method di JSON response
```

### 5.6 Laravel Pattern — Pencarian Produk (Resolusi #6)

```php
// POS: Prefix match untuk SKU (index B-Tree efektif)
$products = Product::where('sku', 'LIKE', $keyword . '%')->get();

// E-Commerce: FULLTEXT search untuk nama produk
$products = Product::whereRaw(
    'MATCH(name) AGAINST(? IN BOOLEAN MODE)', [$keyword . '*']
)->paginate(12);
```

---

## 6. Design System

### 6.1 Warna

```css
/* Brand */
--color-primary: #FF1F8F;        /* Magenta — HANYA untuk CTA utama */
--color-primary-hover: #D91678;

/* Netral */
--color-surface-1: #FFFFFF;      /* Background utama (62%) */
--color-surface-2: #F9FAFB;      /* Background sekunder (38%) */
--color-text-primary: #111827;
--color-text-secondary: #6B7280;
--color-border: #E5E7EB;

/* Semantik */
--color-success: #34D399;
--color-warning: #FBBF24;
--color-danger: #F87171;
```

### 6.2 Tipografi (Golden Ratio)

```css
/* Font: Inter atau Roboto (Google Fonts, sans-serif) */
--font-base: 16px;              /* Body text */
--font-h3: 26px;                /* 16 × 1.618 */
--font-h1: 42px;                /* 26 × 1.618 */
--font-caption: 10px;           /* 16 ÷ 1.618 */
```

### 6.3 Layout Golden Ratio

```
Grid 12 kolom:
- Col-span-8 (66%) : Konten utama
- Col-span-4 (33%) : Panel samping
→ Mendekati Golden Ratio (61.8% : 38.2%)
```

### 6.4 Aturan UI

- ✅ Gunakan SVG stroke-based icons (1.5px - 2px), unfilled
- ✅ Border-radius: 4px (rounded-sm)
- ✅ Warna #FF1F8F HANYA untuk tombol CTA utama (Bayar, ACC, Badge ARIMA)
- ❌ DILARANG: Emotikon/emoji, shadow tebal, dekorasi berlebihan
- ❌ DILARANG: Warna mencolok di luar CTA

---

## 7. API Endpoints Reference

### 7.1 Publik (Tanpa Auth)

```
GET  /api/storefront/products?category=&sort=&page=
GET  /api/storefront/recommendations
```

### 7.2 E-Commerce (Auth: Pelanggan)

```
POST /api/storefront/checkout               # Wajib login
GET  /api/customer/orders
GET  /api/customer/orders/{id}
POST /api/customer/profile/update
```

### 7.3 POS (Auth: Kasir)

```
GET  /api/pos/products/search?keyword=
POST /api/pos/checkout
GET  /api/pos/queue
POST /api/pos/queue/{id}/validate            # body: { action: 'acc'|'reject' }
POST /api/pos/cart/validate-prices           # body: { items: [...] }
```

### 7.4 Admin (Auth: Admin)

```
GET  /api/admin/dashboard/metrics
POST /api/admin/predict-arima
GET  /api/admin/arima-logs

CRUD /api/admin/products
GET  /api/admin/customers
GET  /api/admin/customers/{id}
CRUD /api/admin/staff

GET  /api/admin/orders/history?channel=&status=
GET  /api/admin/sales/export?start_date=&end_date=
```

### 7.5 Flask Microservice (Internal)

```
POST http://localhost:5000/api/v1/predict
Body: {
    "product_name": "Tunik molek",
    "forecast_periods": 7,
    "tuning_parameters": { "fill_missing_dates": true, "smooth_outliers": true },
    "historical_data": [
        { "date": "2026-01-01", "total_sales": 5 },
        ...
    ]
}
```

---

## 8. Schedulers & Automation

### 8.1 ARIMA Cronjob (Harian, 01:00 WITA — Resolusi #9)

```php
// routes/console.php
// Resolusi #9: DILARANG hardcode produk. Ambil dinamis dari DB.
Schedule::call(function () {
    // Top 5 produk terlaris 30 hari terakhir (DINAMIS)
    $topProducts = \App\Models\OrderItem::select('products.name')
        ->join('products', 'order_items.product_id', '=', 'products.id')
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->where('orders.status', 'completed')
        ->where('orders.created_at', '>=', now()->subDays(30))
        ->groupBy('products.name')
        ->orderByRaw('SUM(order_items.qty) DESC')
        ->limit(5)
        ->pluck('products.name');

    foreach ($topProducts as $productName) {
        // Trigger ArimaController::runPrediction()
        // Cache hasil untuk badge di storefront
    }
})->dailyAt('01:00')->timezone('Asia/Makassar');
```

### 8.2 Auto-Cancel Expired Orders (Setiap Jam — Resolusi #2 & #11)

```php
// routes/console.php
// Resolusi #2 & #11: Cancel pesanan web pending > 12 jam + restock
Schedule::call(function () {
    $expiredOrders = \App\Models\Order::where('status', 'pending')
        ->where('channel', 'web')
        ->where('created_at', '<', now()->subHours(12))
        ->get();

    foreach ($expiredOrders as $order) {
        \Illuminate\Support\Facades\DB::transaction(function () use ($order) {
            $order->update(['status' => 'canceled']);
            foreach ($order->items as $item) {
                \App\Models\Product::where('id', $item->product_id)
                    ->increment('stock', $item->qty);
            }
        });
    }

    \Log::info('Auto-cancel: ' . count($expiredOrders) . ' pesanan expired dibatalkan.');
})->hourly()->timezone('Asia/Makassar')->withoutOverlapping();
// ↑ withoutOverlapping() mencegah bentrok jika scheduler sebelumnya belum selesai
```

---

## 9. Aturan Wajib (MUST Rules)

> Aturan ini merupakan hukum tertinggi proyek, disarikan dari Resolusi Logika Final Custom.

### SELALU (DO)
1. **SELALU** gunakan `DB::transaction()` + `lockForUpdate()` saat modifikasi stok
2. **SELALU** validasi role di backend (middleware), jangan hanya di frontend
3. **SELALU** gunakan `price_at_purchase` di order_items, bukan reference ke harga produk saat ini
4. **SELALU** gunakan debounce 300ms untuk search di POS
5. **SELALU** reset state Vue setelah checkout berhasil (Fire & Forget)
6. **SELALU** validasi harga/stok saat Recall held cart
7. **SELALU** gunakan `cursor()` bukan `get()` untuk export data besar (Resolusi #7)
8. **SELALU** gunakan MAPE adaptif: train-test split jika ≥15 hari, in-sample+flag jika <15 hari (Resolusi #8)
9. **SELALU** gunakan nama kolom `qty` (bukan `qty_ordered`) di seluruh codebase (Resolusi #3)
10. **SELALU** filter response staff/user via `.only()` atau API Resources (Resolusi #12)
11. **SELALU** tambahkan `withoutOverlapping()` pada scheduler auto-cancel (Resolusi #11)
12. **SELALU** wajibkan auth checkout web — `POST /api/storefront/checkout` dalam grup `auth:sanctum` (Resolusi #1)

### JANGAN (DON'T)
13. **JANGAN** install Breeze/Inertia — gunakan Vue Router SPA murni (Resolusi #10)
14. **JANGAN** hardcode daftar produk untuk cronjob ARIMA — ambil dinamis dari DB (Resolusi #9)
15. **JANGAN** return password hash atau data sensitif di API response (Resolusi #12)
16. **JANGAN** biarkan pesanan pending tanpa auto-expire 12 jam (Resolusi #2 & #11)
17. **JANGAN** gunakan `LIKE '%keyword%'` — prefix match untuk SKU, FULLTEXT untuk nama (Resolusi #6)
18. **JANGAN** duplikasi endpoint ARIMA proxy — satu controller `ArimaController` saja (Resolusi #5)
19. **JANGAN** gunakan emoji/emotikon di antarmuka — hanya SVG icons
20. **JANGAN** lupa buat Model `PredictionLog` beserta relasi `belongsTo(User)` (Resolusi #4)

---

## 10. Environment Variables

```env
# Laravel .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=diana_fashion_db
DB_USERNAME=root
DB_PASSWORD=

ARIMA_SERVICE_URL=http://127.0.0.1:5000
WHATSAPP_NUMBER=628xxxxxxxxxx

# Flask .env
FLASK_HOST=0.0.0.0
FLASK_PORT=5000
FLASK_ENV=development
```

---

## 11. Testing Checklist

Sebelum deploy, pastikan semua skenario berikut lolos:

### Transaksi & Inventaris
- [ ] Checkout web (wajib login): stok berkurang, status pending, redirect WA
- [ ] Checkout tanpa login → 401 Unauthorized (Resolusi #1)
- [ ] ACC antrean: status → completed, stok tetap
- [ ] Reject antrean: status → canceled, stok restock
- [ ] Race condition: 2 checkout simultan stok 1 → satu gagal 422
- [ ] Stok: tidak pernah negatif di database

### POS
- [ ] Debounce: ketik cepat → hanya 1 request API
- [ ] Hold & Recall: harga ter-update + notifikasi
- [ ] Fire & Forget: state reset instan tanpa reload

### ARIMA & AI
- [ ] ARIMA: grafik muncul, MAPE tercatat, log terisi
- [ ] MAPE adaptif: ≥15 hari → out_of_sample, <15 hari → in_sample_warning (Resolusi #8)
- [ ] Badge rekomendasi: muncul di storefront untuk produk trending
- [ ] Cronjob ARIMA: top 5 produk DINAMIS, berjalan jam 01:00 WITA (Resolusi #9)

### Automation & Keamanan
- [ ] Auto-cancel: pesanan pending > 12 jam → canceled + restock (Resolusi #2 & #11)
- [ ] Export CSV: data valid, Web + POS menyatu, memory stabil (Resolusi #7)
- [ ] CORS: tidak ada error saat Admin → Flask
- [ ] Response staff/user: TIDAK mengandung password hash (Resolusi #12)
