# Diana Fashion Omnichannel — Task Checklist

**Acuan:** [Implementation Plan (REVISED)](file:///C:/Users/Lenovo/.gemini/antigravity-ide/brain/7406afbd-34fc-43fb-a8b5-c11c1e03eb42/implementation_plan.md)
**Resolusi:** [Resolusi Logika Final Custom.md](file:///c:/laragon/www/Diana_fashion/Resolusi%20Logika%20Final%20Custom.md)
**Skill:** [SKILL.md](file:///c:/laragon/www/Diana_fashion/SKILL.md)

---

## Fase 1: Inisialisasi Proyek & Infrastruktur

### 1.1 Laravel Setup
- [x] Buat proyek Laravel 11 — `composer create-project laravel/laravel diana_fashion_app`
- [x] Install Sanctum — `composer require laravel/sanctum`
- [x] ⚠️ **JANGAN** install Breeze / Inertia (Resolusi #10)
- [x] Konfigurasi `.env` (DB_DATABASE=diana_fashion_db, dsb.)
- [x] Buat database kosong `diana_fashion_db` di MySQL (Telah diaktifkan dan dibuat secara manual)
- [x] Konfigurasi `config/sanctum.php` — set stateful domains untuk SPA
- [x] Konfigurasi `config/cors.php` — izinkan credentials + set allowed origins
- [x] Tambahkan `ARIMA_SERVICE_URL=http://127.0.0.1:5000` di `.env`
- [x] Tambahkan `WHATSAPP_NUMBER=628xxxxxxxxxx` di `.env`

### 1.2 Frontend Setup (Vue Router SPA Murni)
- [x] Install dependensi Vue — `npm install vue@3 vue-router@4 @vitejs/plugin-vue pinia axios`
- [x] Install Tailwind CSS — `npm install -D tailwindcss @tailwindcss/vite`
- [x] Konfigurasi `vite.config.js` dengan 3 entry point (`app.js`, `pos.js`, `admin.js`)
- [x] Buat file entry point:
  - [x] `resources/js/app.js` — E-Commerce + Customer Portal
  - [x] `resources/js/pos.js` — POS Terminal
  - [x] `resources/js/admin.js` — Admin Panel
- [x] Buat Blade shell templates:
  - [x] `resources/views/storefront/index.blade.php` → load `app.js`
  - [x] `resources/views/pos/index.blade.php` → load `pos.js`
  - [x] `resources/views/admin/app.blade.php` → load `admin.js`
- [x] Setup Tailwind CSS (`tailwind.config.js`, import di CSS)
- [x] Import Google Fonts (Inter / Roboto)

### 1.3 Flask Microservice Setup
- [x] Buat folder `diana_arima_service/` di root proyek
- [x] Buat virtual environment — `python -m venv venv`
- [x] Install dependensi — `pip install flask flask-cors pandas numpy pmdarima scikit-learn`
- [x] Generate `requirements.txt` — `pip freeze > requirements.txt`
- [x] Buat skeleton `app.py` (health check endpoint)
- [x] Buat `.env` (FLASK_HOST, FLASK_PORT)

### 1.4 Verifikasi Fase 1
- [ ] `php artisan serve` — Laravel jalan di `localhost:8000`
- [ ] `python app.py` — Flask jalan di `localhost:5000`
- [ ] `npm run dev` — Vite dev server jalan, 3 entry point terdeteksi
- [ ] Buka 3 Blade shell di browser — halaman kosong Vue ter-mount


---

## Fase 2: Database Migration & Model

### 2.1 Migrations
- [x] Modifikasi migration `users` bawaan — tambah kolom `role` enum('admin','kasir','pelanggan') default 'pelanggan'
- [x] Buat migration `create_categories_table` — kolom: id, name (unique), timestamps
- [x] Buat migration `create_products_table`:
  - [x] FK `category_id` → categories (restrictOnDelete)
  - [x] `sku` (unique + index)
  - [x] `name` + **FULLTEXT index** (Resolusi #6)
  - [x] `stock` (integer, default 0)
  - [x] `price` (decimal 12,2)
  - [x] `image_url` (nullable)
- [x] Buat migration `create_orders_table`:
  - [x] `increment_id` (unique, format: ORD-YYYY-NNN)
  - [x] FK `user_id` → users (nullable, nullOnDelete)
  - [x] `channel` enum('web','pos')
  - [x] `status` enum('pending','completed','canceled')
  - [x] `total_price` (decimal 12,2)
  - [x] `payment_method` (nullable)
- [x] Buat migration `create_order_items_table`:
  - [x] FK `order_id` → orders (cascadeOnDelete)
  - [x] FK `product_id` → products (restrictOnDelete)
  - [x] ⚠️ `qty` (integer) — BUKAN `qty_ordered` (Resolusi #3)
  - [x] `price_at_purchase` (decimal 12,2)
- [x] Buat migration `create_prediction_logs_table`:
  - [x] FK `user_id` → users (nullable)
  - [x] `product_name`, `forecast_periods`, `tuning_parameters` (JSON)
  - [x] `arima_order`, `mape_score` (decimal 5,2), `execution_time_ms`
- [x] Jalankan `php artisan migrate`

### 2.2 Eloquent Models
- [x] Model `User.php` — tambah `role` ke `$fillable`, relasi `hasMany(Order)`
- [x] Model `Category.php` — `$fillable=['name']`, relasi `hasMany(Product)`
- [x] Model `Product.php` — `$fillable` lengkap, relasi `belongsTo(Category)`, `hasMany(OrderItem)`
- [x] Model `Order.php` — `$fillable` lengkap, relasi `belongsTo(User)`, `hasMany(OrderItem, 'order_id')`
- [x] Model `OrderItem.php` — `$fillable` dengan `qty` (Resolusi #3), relasi `belongsTo(Order)`, `belongsTo(Product)`
- [x] ⚠️ Model `PredictionLog.php` (Resolusi #4):
  - [x] `$fillable` lengkap
  - [x] Relasi `belongsTo(User::class)`
  - [x] `$casts = ['tuning_parameters' => 'array']`

### 2.3 Seeder
- [x] `DatabaseSeeder.php` — seed akun admin default (email: admin@diana.com)
- [x] Seed kategori awal: Atasan, Bawahan, Gamis
- [x] Seed produk sample untuk development
- [x] Jalankan `php artisan db:seed`

### 2.4 Verifikasi Fase 2
- [x] Cek semua tabel di MySQL — struktur kolom sesuai
- [x] Cek FULLTEXT index di tabel `products` — `SHOW INDEX FROM products`
- [x] Cek akun admin terseed — `php artisan tinker` → `User::where('role','admin')->first()`


---

## Fase 3: RBAC & Authentication

### 3.1 Auth Controllers (Custom, tanpa Breeze)
- [x] Buat `AuthController.php` — login, register, logout (menggunakan Sanctum SPA)
- [x] Register: paksa `role = 'pelanggan'` (Resolusi #1)
- [x] Login: gateway terpusat, return role info untuk frontend redirect
- [x] Logout: revoke session

### 3.2 Middleware
- [x] Buat `RoleMiddleware.php` — cek `$request->user()->role !== $role` → abort 403
- [x] Daftarkan alias `role` di `bootstrap/app.php`

### 3.3 Routes (web.php)
- [x] Route publik: `GET /` → Blade storefront shell
- [x] Route POS: `GET /pos/{any?}` → Blade POS shell (middleware: auth, role:kasir)
- [x] Route Admin: `GET /admin/{any?}` → Blade Admin shell (middleware: auth, role:admin)
- [x] Route Customer: `GET /customer/{any?}` → Blade storefront shell (middleware: auth)
- [x] Load `routes/auth.php` untuk Login/Register API (Diintegrasikan langsung ke `routes/api.php` sebagai `/api/auth/*`)

### 3.4 Routes Auth API (api.php)
- [x] `POST /api/auth/register` — register pelanggan
- [x] `POST /api/auth/login` — login terpusat
- [x] `POST /api/auth/logout` — logout
- [x] `GET  /api/auth/user` — get current user (auth:sanctum)

### 3.5 Verifikasi Fase 3
- [x] Test register → role harus pelanggan
- [x] Test login admin → return role=admin
- [x] Test akses `/pos` tanpa login → redirect/403
- [x] Test akses `/admin` dengan role kasir → 403


---

## Fase 4: Modul E-Commerce (Storefront + Customer Portal)

### 4.1 Backend — StorefrontController
- [x] `getProducts()`:
  - [x] Filter kategori via `whereHas('category', ...)`
  - [x] Sorting: price_asc, price_desc, newest (default)
  - [x] Pagination: `paginate(12)`
  - [x] Pencarian nama: gunakan **MATCH AGAINST** (FULLTEXT) (Resolusi #6)
- [x] `getArimaRecommendations()`:
  - [x] Ambil dari `Cache::get('arima_recommendation_*')`
  - [x] Return produk dengan badge trending
- [x] `processWebCheckout()`:
  - [x] ⚠️ Middleware `auth:sanctum` WAJIB (Resolusi #1)
  - [x] `DB::transaction()` + `lockForUpdate()`
  - [x] Generate `increment_id` (ORD-YYYY-NNN)
  - [x] Potong stok (decrement)
  - [x] Set status = 'pending', channel = 'web'
  - [x] Generate WhatsApp URL (`wa.me/628...?text=...`)
  - [x] Return WhatsApp URL ke frontend

### 4.2 Backend — CustomerPortalController
- [x] `getOrderHistory()` — `Order::where('user_id', auth()->id())->paginate(10)`
- [x] `getOrderDetail($id)` — dengan `items.product` eager loading
- [x] `updateProfile()` — validasi + update nama, email, password

### 4.3 Routes API (api.php)
- [x] Grup publik: `GET /api/storefront/products`, `GET /api/storefront/recommendations`
- [x] Grup auth: `POST /api/storefront/checkout` (auth:sanctum)
- [x] Grup customer: `GET /api/customer/orders`, `GET /api/customer/orders/{id}`, `POST /api/customer/profile/update`

### 4.4 Frontend — E-Commerce Vue Components
- [x] `Storefront/Layouts/MainLayout.vue` — Header, Navbar (Atasan/Bawahan/Gamis), Footer
- [x] `Storefront/Components/BentoPromo.vue` — Hero banner (col-span-8)
- [x] `Storefront/Components/ArimaCard.vue` — Kartu rekomendasi AI (col-span-4)
- [x] `Storefront/Components/ProductCard.vue` — Kartu produk + ARIMA badge
- [x] `Storefront/Components/FilterSidebar.vue` — Checkbox kategori (col-span-3)
- [x] `Storefront/Components/Pagination.vue` — Prev/Next navigation
- [x] `Storefront/Views/Home.vue` — Bento grid layout, merakit semua komponen
- [x] `Storefront/Views/CartCheckout.vue` — Keranjang (Pinia/localStorage) + WA redirect
- [x] Vue Router setup untuk storefront routes

### 4.5 Frontend — Customer Portal Vue Components
- [x] `Storefront/Layouts/CustomerLayout.vue` — Sidebar menu akun
- [x] `Storefront/Customer/Dashboard.vue` — Ringkasan akun
- [x] `Storefront/Customer/OrderHistory.vue` — Tabel riwayat + status badge
- [x] `Storefront/Customer/ProfileSetting.vue` — Form ubah profil

### 4.6 Verifikasi Fase 4
- [x] Katalog menampilkan produk dengan pagination, filter, sort
- [x] ⚠️ Checkout tanpa login → 401 (Resolusi #1)
- [x] Checkout dengan login → stok berkurang, status pending, redirect WA
- [x] Customer portal menampilkan riwayat pesanan


---

## Fase 5: Modul POS (Point of Sale)

### 5.1 Backend — PosController
- [x] `searchProducts()`:
  - [x] SKU: prefix match `LIKE 'keyword%'` (Resolusi #6)
  - [x] Nama: FULLTEXT search
- [x] `processCheckout()`:
  - [x] `DB::transaction()` + `lockForUpdate()`
  - [x] Set status = 'completed', channel = 'pos'
  - [x] Support guest (user_id = null) & member checkout
- [x] `getOnlineQueue()` — `Order::where('status','pending')->where('channel','web')`
- [x] `validateQueue($id)`:
  - [x] ACC → update status = 'completed' (stok TIDAK dipotong lagi)
  - [x] Reject → update status = 'canceled' + restock (increment)
- [x] `validateHeldCart()` — Cek harga & stok terbaru, return validated items

### 5.2 Routes API (api.php)
- [x] Semua di bawah middleware `auth:sanctum, role:kasir`
- [x] `GET /api/pos/products/search?keyword=`
- [x] `POST /api/pos/checkout`
- [x] `GET /api/pos/queue`
- [x] `POST /api/pos/queue/{id}/validate`
- [x] `POST /api/pos/cart/validate-prices`

### 5.3 Frontend — POS Vue Components
- [x] `POS/PosLayout.vue` — Layout 2 kolom Golden Ratio (62:38)
- [x] `POS/Components/ProductSearch.vue`:
  - [x] Input dengan debounce 300ms (setTimeout)
  - [x] Tampilkan hasil pencarian sebagai grid produk
- [x] `POS/Components/CartArea.vue`:
  - [x] Rincian item + total harga
  - [x] Identitas pelanggan (guest/member input)
  - [x] Hold Cart → simpan ke localStorage
  - [x] Recall Cart → validasi API → update harga
- [x] `POS/Components/PaymentModal.vue` — Pilih Cash/QRIS/Transfer
- [x] `POS/Components/OnlineQueueTab.vue` — Tabel antrean web, tombol ACC/Reject
- [x] `POS/Views/PosTerminal.vue` — Perakitan semua komponen
- [x] Fire & Forget: `resetPosState()` setelah checkout sukses
- [x] Vue Router setup untuk POS SPA

### 5.4 Verifikasi Fase 5
- [x] Debounce: ketik cepat → hanya 1 request (cek Network tab)
- [x] Checkout POS: stok berkurang, status completed
- [x] Race condition: 2 browser simultan stok 1 → satu error 422
- [x] Hold & Recall: harga berubah → notifikasi muncul
- [x] Fire & Forget: state reset instan tanpa reload
- [x] ACC antrean web → status completed, stok tetap
- [x] Reject antrean → status canceled, stok restock


---

## Fase 6: Modul Admin Panel

### 6.1 Backend — Admin Controllers
- [x] `AdminDashboardController`:
  - [x] `getMetrics()` — total pendapatan (harian/bulanan), total pesanan, perbandingan POS vs Web
  - [x] ⚠️ **TIDAK** ada proxy ARIMA di sini (Resolusi #5)
- [x] `AdminProductController`:
  - [x] CRUD produk (index, store, show, update, destroy)
  - [x] Peringatan stok menipis (< 5 pcs)
- [x] `AdminOrderController`:
  - [x] `getHistory()` — filter by channel, status, date range
  - [x] Monitor antrean web (status = pending)
- [x] `AdminCustomerController`:
  - [x] `index()` — daftar pelanggan (role = 'pelanggan')
  - [x] `show($id)` — detail + riwayat belanja
- [x] `AdminStaffController`:
  - [x] CRUD user internal (admin/kasir)
  - [x] Validasi role: `in:admin,kasir`
  - [x] ⚠️ Response via `.only(['id','name','email','role'])` (Resolusi #12)
  - [x] Reset password
- [x] `AdminReportController`:
  - [x] `exportCSV()` — StreamedResponse + ⚠️ `cursor()` (Resolusi #7)
  - [x] Filter date range
- [x] `ArimaController` (Resolusi #4 & #5):
  - [x] ⚠️ **SATU-SATUNYA** proxy ke Flask
  - [x] `runPrediction()`:
    - [x] Tarik data historis (status=completed, join order_items+products)
    - [x] Agregasi per hari, konversi ke WITA
    - [x] ⚠️ Gunakan `$row->sum('qty')` BUKAN `qty_ordered` (Resolusi #3)
    - [x] HTTP::post ke Flask
    - [x] Simpan ke PredictionLog
    - [x] Cache hasil
  - [x] `getLogs()` — `PredictionLog::with('user:id,name')->latest()->take(10)`

### 6.2 Routes API (api.php)
- [x] Semua di bawah middleware `auth:sanctum, role:admin`
- [x] `GET /api/admin/dashboard/metrics`
- [x] `POST /api/admin/predict-arima` → `ArimaController::runPrediction`
- [x] `GET /api/admin/arima-logs` → `ArimaController::getLogs`
- [x] `apiResource('products', AdminProductController)`
- [x] `apiResource('customers', AdminCustomerController)->only(['index','show'])`
- [x] `apiResource('staff', AdminStaffController)`
- [x] `GET /api/admin/orders/history`
- [x] `GET /api/admin/sales/export`

### 6.3 Frontend — Admin Vue Components
- [x] `Admin/Layouts/AdminLayout.vue` — Sidebar navigasi + Header
- [x] `Admin/Dashboard/Index.vue` — Metrik omnichannel (kartu summary)
- [x] `Admin/Dashboard/ArimaChart.vue` (Diintegrasikan sebagai chart SVG reaktif premium langsung di dalam ArimaDashboard.vue)
- [x] `Admin/ArimaDashboard.vue`:
  - [x] Form tuning (pilih produk, periode, centang missing dates/outlier)
  - [x] Tombol "Jalankan Prediksi"
  - [x] Tabel log pemantauan algoritma
- [x] `Admin/Products/Index.vue` — DataGrid produk + indikator stok menipis
- [x] `Admin/Products/Form.vue` (Diintegrasikan sebagai modal dialog reaktif di dalam Products/Index.vue)
- [x] `Admin/Orders/Index.vue` — DataGrid transaksi + filter channel/status
- [x] `Admin/Users/Customers.vue` — Daftar pelanggan + detail belanja
- [x] `Admin/Users/Staff.vue` — Daftar karyawan + form tambah/edit + role picker
- [x] `Admin/Reports/Index.vue` — Date range picker + tombol Export CSV
- [x] Vue Router setup untuk Admin SPA

### 6.4 Verifikasi Fase 6
- [x] Dashboard menampilkan metrik (pendapatan, pesanan, perbandingan channel)
- [x] CRUD produk berfungsi penuh
- [x] ARIMA prediksi: grafik muncul, MAPE tercatat, log terisi
- [x] Export CSV: file valid, data complete
- [x] Staff management: create kasir → bisa login ke POS
- [x] ⚠️ Response staff TIDAK mengandung password hash

---

## Fase 7: Flask ARIMA Microservice

### 7.1 Core API (`app.py`)
- [x] Endpoint `POST /api/v1/predict`
- [x] Parsing payload: product_name, forecast_periods, tuning_parameters, historical_data
- [x] Validasi: minimal 7 record data historis
- [x] Preprocessing Pandas:
  - [x] Convert date, set index, sort
  - [x] Fill missing dates (jika tuning ON) — `reindex` dengan `fill_value=0`
  - [x] Smooth outliers (jika tuning ON) — IQR method, cap upper bound
- [x] ⚠️ MAPE Adaptif (Resolusi #8):
  - [x] Jika data ≥ 15 hari → train-test split 80/20, hitung MAPE, re-train 100%
  - [x] Jika data < 15 hari → in-sample MAPE + flag `"mape_method": "in_sample_warning"`
- [x] Auto-ARIMA engine: `auto_arima(ts_data, ...)`
- [x] Generate forecast result (tanggal + predicted_sales, clamp min 0)
- [x] Catat execution_time_ms
- [x] Return JSON: status, arima_order, evaluation.mape_score, **mape_method**, execution_time_ms, forecast_result
- [x] Error handling: try/except → return error 500 dengan message

### 7.2 CORS & Config
- [x] Tambahkan `flask-cors` — `CORS(app)` untuk izinkan request dari Laravel
- [x] Konfigurasi host/port dari environment

### 7.3 Schedulers Laravel (`routes/console.php`)
- [x] **Scheduler 1: ARIMA Harian (01:00 WITA)**
  - [x] ⚠️ Query top 5 produk terlaris 30 hari DINAMIS (Resolusi #9)
  - [x] Loop: trigger `ArimaController::runPrediction()` untuk tiap produk
  - [x] Cache hasil → badge storefront
  - [x] `->dailyAt('01:00')->timezone('Asia/Makassar')`
- [x] **Scheduler 2: Auto-Cancel Expired (Setiap Jam)**
  - [x] ⚠️ Query orders: status=pending, channel=web, created_at < 12 jam (Resolusi #2 & #11)
  - [x] Loop dengan `DB::transaction()`: update canceled + increment stok
  - [x] `->hourly()->timezone('Asia/Makassar')->withoutOverlapping()`
  - [x] Log info ke Laravel log

### 7.4 Verifikasi Fase 7
- [x] Flask predict endpoint: kirim data → return forecast JSON
- [x] MAPE adaptif: test dengan data ≥15 hari (out_of_sample) dan <15 hari (in_sample_warning)
- [x] Cronjob ARIMA: simulasi jam 01:00 → cache terisi
- [x] Auto-cancel: buat order pending > 12 jam → cek otomatis canceled + restock
- [x] CORS: tidak ada error saat Laravel → Flask

---

## Fase 8: Testing & QA

### 8.1 Skenario Testing Kritis
- [x] **T1:** Web checkout (wajib login) → stok berkurang, status pending, redirect WA
- [x] **T2:** ACC antrean web → status completed, stok tetap
- [x] **T3:** Reject antrean → status canceled, stok restock
- [x] **T4:** Race condition (2 checkout simultan, stok 1) → satu error 422
- [x] **T5:** Debounce search → hanya 1 request API
- [x] **T6:** Hold & Recall cart → harga ter-update + notifikasi
- [x] **T7:** Fire & Forget → state reset instan tanpa reload
- [x] **T8:** ARIMA missing dates + outlier → grafik muncul, MAPE tercatat
- [x] **T9:** MAPE adaptif → split 80/20 (≥15 hari) atau in-sample+warning (<15 hari)
- [x] **T10:** Badge rekomendasi → muncul di storefront
- [x] **T11:** Cronjob ARIMA (dinamis) → top 5 produk, log terisi, cache updated
- [x] **T12:** Auto-cancel pending >12 jam → canceled + restock
- [x] **T13:** Export CSV (cursor) → file valid, memory stabil
- [x] **T14:** Checkout tanpa login → 401 Unauthorized

### 8.2 Acceptance Criteria
- [x] Tidak ada error CORS saat Admin → Flask
- [x] Stok tidak pernah negatif di database
- [x] POS lancar tanpa reload halaman
- [x] CSV valid dengan data Web + POS menyatu
- [x] Checkout tanpa login ditolak (401)
- [x] Pesanan pending >12 jam otomatis canceled + restock

### 8.3 Final Cleanup
- [x] Review semua TODO/FIXME di codebase
- [x] Pastikan semua 12 resolusi cacat terimplementasi
- [x] Code review: tidak ada password hash di response
- [x] Code review: tidak ada `->get()` pada export CSV
- [x] Code review: tidak ada hardcoded product di cronjob
- [x] Code review: tidak ada `LIKE '%keyword%'` tanpa FULLTEXT

---

## Fase 9: Debugging & Hardening (14 Bugs)

### 9.1 Perbaikan Bug
- [x] **BUG-01:** `processWebCheckout()` — Response Inside Transaction Returns 422 Without Rollback (StorefrontController)
- [x] **BUG-02:** `processCheckout()` POS — Identical Bug Pada Response Inside Transaction (PosController)
- [x] **BUG-03:** `AdminCustomerController::show()` — Kebocoran Password Hash
- [x] **BUG-04:** `AdminCustomerController::index()` — Paginator Tanpa Filter `.only()`
- [x] **BUG-05:** `increment_id` Race Condition — Non-Atomic Sequence Generation (Opsi A: Retry Loop)
- [x] **BUG-06:** `ArimaController` — Penggunaan `env()` di Runtime (pindah ke config/services.php)
- [x] **BUG-07:** `StorefrontController` — Penggunaan `env()` untuk WhatsApp Number (pindah ke config/services.php)
- [x] **BUG-08:** `AdminOrderController::getHistory()` — Pencarian `increment_id` Menggunakan prefix match `LIKE 'keyword%'`
- [x] **BUG-09:** `console.php` ARIMA Scheduler — Tambahkan `->name()` dan `->withoutOverlapping()`
- [x] **BUG-10:** `pos.js` — Catch-All Route untuk SPA 404
- [x] **BUG-11:** `app.js` — Route Guard untuk Customer Portal
- [x] **BUG-12:** `config/sanctum.php` — Tambahkan `localhost:5173` ke stateful domains
- [x] **BUG-13:** `Category.php` — Verifikasi `$fillable` Protection yang Eksplisit
- [x] **BUG-14:** `OmnichannelTest.php` — Perluas Test Suite Coverage dengan minimal 8 test cases baru

### 9.2 Verifikasi & Cleanup
- [x] Run `php artisan test` (harus lulus semua)
- [x] Run `php artisan config:cache` & `php artisan config:clear` (tidak ada error runtime)
- [x] Buat dokumentasi penyelesaian di `walkthrough.md`

---

## Fase 14: ARIMA AI Global Configuration & Pipeline Visualizer (Fase Aktif)

### 14.1 Database & Migrations
- [x] Buat migration untuk tabel `settings`
- [x] Implementasikan seeder default untuk parameter ARIMA di tabel `settings`
- [x] Daftarkan model `Setting.php` untuk manipulasi data `settings`

### 14.2 Flask ARIMA Service Upgrades
- [x] Perbarui `diana_arima_service/app.py` untuk menerima parameter hyperparameter auto-arima dinamis: `start_p`, `start_q`, `max_p`, `max_q`, `seasonal`, `stepwise`
- [x] Uji fungsionalitas parameter baru di Flask secara lokal

### 14.3 Laravel Backend API & Integration
- [x] Tambahkan API routes untuk membaca dan mengubah konfigurasi global ARIMA
- [x] Implementasikan method `getConfig` dan `saveConfig` di `ArimaController.php`
- [x] Perbarui `runPrediction` di `ArimaController.php` untuk membaca konfigurasi global dari database dan mengirimkannya ke Flask
- [x] Buat unit test terintegrasi untuk menguji fungsionalitas `GET` / `POST` konfigurasi ARIMA

### 14.4 Frontend SPA Antarmuka (Opsi A)
- [x] Tambahkan tab baru **"Konfigurasi Global & Pipeline"** pada `ArimaDashboard.vue`
- [x] Bangun form penalaan parameter global ARIMA (Default periods, zero-fill, outlier smooth, coefficients bounds, search logic)
- [x] Hubungkan form ke API backend untuk operasi `GET` dan `POST` terintegrasi dengan toast notification
- [x] Bangun **Pipeline Visualizer** interaktif 6 langkah pemrosesan data science berbasis Tailwind CSS v4
- [x] Tambahkan modal/panel detail interaktif untuk tiap tahapan visualisasi pipeline (menyajikan matematika ARIMA, IQR rule, split logic, & pseudocode)

### 14.5 Verifikasi Akhir
- [x] Jalankan kompilasi frontend `npm run build`
- [x] Jalankan test suite `php artisan test` untuk memastikan kelulusan 100%
- [x] Lakukan verifikasi manual untuk menguji kelancaran UI konfigurasi dan visualizer pipeline

