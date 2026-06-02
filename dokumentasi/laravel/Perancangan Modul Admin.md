# **PERANCANGAN MODUL ADMIN PANEL (BACK-OFFICE)**

**Versi: Custom Laravel Architecture (Clean & Standalone)**

## **1\. Konsep Utama Modul Admin**

Modul Admin berfungsi sebagai pusat komando operasional Toko Diana Fashion. Antarmuka ini dibangun menggunakan **Vue.js 3** dan **Tailwind CSS** dengan pola arsitektur *Single Page Application* (SPA) untuk transisi halaman yang instan.

Modul ini diakses melalui rute /admin dan dilindungi ketat oleh *Middleware* otentikasi (hanya mengizinkan role \= 'admin'). Di sinilah data *Omnichannel* (Web & POS) bermuara, dikelola (CRUD), dan dianalisis menggunakan *microservice* ARIMA.

## **2\. Rincian Fitur Admin Panel Terpadu**

### **A. Dashboard Analitik (Omnichannel & ARIMA)**

Pusat informasi (*helicopter view*) untuk pemilik toko.

* **Omnichannel Metrik:** Menampilkan total pendapatan (Harian/Bulanan), total pesanan, dan perbandingan omzet dari kasir (POS) vs Web.  
* **Widget ARIMA Forecasting:**  
  * Admin dapat memilih Kategori dan Produk.  
  * Menyediakan form pengaturan *Tuning Data* (centang *Fill Missing Dates* & *Outlier Smoothing*).  
  * Menampilkan hasil berupa **Grafik Tren (Chart.js)** dan **Nilai Error (MAPE)** yang ditarik dari *Microservice* Flask.

### **B. Manajemen Produk & Inventaris**

Fitur CRUD (*Create, Read, Update, Delete*) terpusat untuk katalog barang.

* **DataGrid Produk:** Menampilkan daftar produk dengan fitur *Pagination*, *Sorting* (harga/stok), dan *Filtering* pencarian (berdasarkan SKU atau Nama).  
* **Inventory Control:** Admin dapat melihat dan memperbarui stok fisik (stock) secara langsung. Karena ini *custom architecture*, stok disimpan langsung di tabel products.  
* **Peringatan Stok:** Indikator visual (warna merah) untuk produk yang stoknya menipis (misal \< 5 pcs).

### **C. Manajemen Pesanan & Riwayat Transaksi**

Pusat kendali dan rekaman transaksi.

* **Monitor Antrean Web:** Admin bisa melihat pesanan dengan status \= 'pending'. Meskipun validasi idealnya dilakukan oleh kasir POS, Admin tetap memiliki otoritas untuk mengintervensi (ACC/Reject) jika diperlukan.  
* **Riwayat Transaksi (Omnichannel History):** Tabel komprehensif berisi semua pesanan yang telah disahkan (completed) atau dibatalkan (canceled). Terdapat *Filter* khusus untuk menyortir berdasarkan kolom channel ('web' atau 'pos').

### **D. Manajemen Pelanggan (Customer Directory)**

* **DataGrid Pelanggan:** Menampilkan daftar *user* khusus yang memiliki role \= 'pelanggan'.  
* **Detail Pelanggan:** Admin dapat melihat riwayat belanja spesifik dari satu pelanggan (Total uang yang sudah dihabiskan dan daftar pesanan mereka).

### **E. Manajemen User Internal (Akses Karyawan)**

Fitur untuk mengatur sumber daya manusia yang memiliki akses ke dalam sistem (Back-Office dan POS).

* **CRUD User Internal:** Admin dapat membuat akun baru, mengedit, atau menonaktifkan akun karyawan.  
* **Role Assignment:** Saat membuat akun, Admin **wajib** memilih *role*:  
  * admin: Diberikan akses penuh ke /admin (Dashboard ini).  
  * kasir: Hanya diberikan akses ke layar /pos (Aplikasi Kasir). Mereka tidak bisa masuk ke layar laporan atau inventaris ini.  
* **Reset Password:** Fitur bagi admin untuk mereset *password* kasir yang lupa kata sandi.

### **F. Laporan Penjualan (Export CSV)**

Fitur rekapitulasi untuk keperluan pembukuan keuangan toko.

* **Filter Tanggal (Date Range Picker):** Admin memilih rentang waktu (misal: 1 Januari \- 31 Januari).  
* **Export Streamed:** Mengunduh data penjualan ke dalam format CSV. Menggunakan StreamedResponse pada Laravel agar server tidak kehabisan memori (*Memory Limit Exceeded*) saat mengekspor puluhan ribu baris transaksi.

## **3\. Tahap Implementasi (Langkah Eksekusi)**

Implementasi dibagi menjadi dua lapis: penyediaan *Endpoint* API untuk CRUD (Backend) dan pembangunan antarmuka (Frontend).

### **Tahap 1: Pembangunan API Backend (Laravel 11\)**

Buat *Controller* khusus Admin untuk menangani logika dan *query* basis data.

1. **Membuat Route API (routes/api.php):**  
   // Grup Rute yang dilindungi Middleware Admin  
   Route::middleware(\['auth:sanctum', 'role:admin'\])-\>prefix('admin')-\>group(function () {  
       // Dashboard & Analitik ARIMA  
       Route::get('/dashboard/metrics', \[AdminDashboardController::class, 'getMetrics'\]);  
       Route::post('/predict-arima', \[AdminDashboardController::class, 'proxyArimaRequest'\]); // Proxy ke Flask

       // Manajemen Master Data  
       Route::apiResource('products', AdminProductController::class);  
       Route::apiResource('customers', AdminCustomerController::class)-\>only(\['index', 'show'\]);

       // Manajemen User Internal (Kasir & Admin)  
       Route::apiResource('staff', AdminStaffController::class);

       // Manajemen Transaksi & Laporan  
       Route::get('/orders/history', \[AdminOrderController::class, 'getHistory'\]);  
       Route::get('/sales/export', \[AdminReportController::class, 'exportCSV'\]);  
   });

2. **Logika Manajemen User Internal (AdminStaffController.php):**  
   public function store(Request $request)  
   {  
       $request-\>validate(\[  
           'name' \=\> 'required|string|max:255',  
           'email' \=\> 'required|email|unique:users,email',  
           'password' \=\> 'required|min:6',  
           'role' \=\> 'required|in:admin,kasir' // Validasi ketat hanya untuk internal  
       \]);

       $staff \= User::create(\[  
           'name' \=\> $request-\>name,  
           'email' \=\> $request-\>email,  
           'password' \=\> bcrypt($request-\>password),  
           'role' \=\> $request-\>role  
       \]);

       return response()-\>json(\['message' \=\> 'Akun Karyawan Berhasil Dibuat', 'data' \=\> $staff\]);  
   }

3. **Logika Ekspor Laporan CSV (AdminReportController.php):**  
   public function exportCSV(Request $request)  
   {  
       $orders \= Order::where('status', 'completed')  
                      \-\>whereBetween('created\_at', \[$request-\>start\_date, $request-\>end\_date\])  
                      \-\>orderBy('created\_at', 'asc')  
                      \-\>get();

       $response \= new StreamedResponse(function() use ($orders) {  
           $handle \= fopen('php://output', 'w');  
           fputcsv($handle, \['No. Nota', 'Tanggal', 'Kanal', 'Total Bayar', 'Metode Pembayaran'\]);

           foreach ($orders as $order) {  
               fputcsv($handle, \[  
                   $order-\>increment\_id,  
                   $order-\>created\_at-\>format('Y-m-d H:i:s'),  
                   $order-\>channel,  
                   $order-\>total\_price,  
                   $order-\>payment\_method  
               \]);  
           }  
           fclose($handle);  
       });

       $response-\>headers-\>set('Content-Type', 'text/csv');  
       $response-\>headers-\>set('Content-Disposition', 'attachment; filename="Laporan\_Penjualan.csv"');

       return $response;  
   }

### **Tahap 2: Pembangunan UI/UX Frontend (Vue.js 3 \+ Tailwind)**

Membangun antarmuka manajemen berbasis *DataGrid* dan *Form*.

1. **Struktur Komponen User Management (StaffIndex.vue):**  
   \<template\>  
       \<div class="staff-management"\>  
           \<div class="header flex justify-between"\>  
               \<h2\>Manajemen Karyawan\</h2\>  
               \<button @click="openModal('add')" class="btn btn-primary"\>+ Tambah Karyawan\</button\>  
           \</div\>

           \<table class="table-auto w-full mt-4"\>  
               \<thead\>  
                   \<tr\>  
                       \<th\>Nama\</th\>  
                       \<th\>Email\</th\>  
                       \<th\>Role / Posisi\</th\>  
                       \<th\>Aksi\</th\>  
                   \</tr\>  
               \</thead\>  
               \<tbody\>  
                   \<tr v-for="staff in staffList" :key="staff.id"\>  
                       \<td\>{{ staff.name }}\</td\>  
                       \<td\>{{ staff.email }}\</td\>  
                       \<td\>  
                           \<span v-if="staff.role \=== 'admin'" class="badge bg-purple-100 text-purple-800"\>Admin Utama\</span\>  
                           \<span v-if="staff.role \=== 'kasir'" class="badge bg-blue-100 text-blue-800"\>Kasir Toko (POS)\</span\>  
                       \</td\>  
                       \<td\>  
                           \<button @click="editStaff(staff)" class="text-blue-500"\>Edit\</button\>  
                           \<button @click="deleteStaff(staff.id)" class="text-red-500 ml-2"\>Hapus\</button\>  
                       \</td\>  
                   \</tr\>  
               \</tbody\>  
           \</table\>  
       \</div\>  
   \</template\>

## **4\. Pemetaan Direktori (Directory Mapping) Modul Admin**

Struktur *folder* ini memastikan kode logika Admin terisolasi penuh dari area Publik (E-Commerce) dan Area Kasir (POS).

diana\_fashion\_app/  
│  
├── app/  
│   └── Http/  
│       └── Controllers/  
│           └── Admin/                      \# Logika khusus Manajemen Back-Office  
│               ├── AdminDashboardController.php \# Metrik Utama & Proxy ARIMA  
│               ├── AdminProductController.php   \# CRUD Produk & Inventaris  
│               ├── AdminOrderController.php     \# Riwayat Transaksi & Antrean  
│               ├── AdminCustomerController.php  \# Direktori Pelanggan  
│               ├── AdminStaffController.php     \# CRUD User Internal (Admin & Kasir)  
│               └── AdminReportController.php    \# Logika Unduh CSV  
│  
├── resources/  
│   ├── js/  
│   │   ├── admin.js                        \# Entry point Vue khusus panel Admin  
│   │   └── Pages/  
│   │       └── Admin/                      \# Komponen UI Panel Admin (Vue SPA)  
│   │           ├── Layouts/  
│   │           │   └── AdminLayout.vue     \# Sidebar Navigasi Admin & Header  
│   │           ├── Dashboard/  
│   │           │   ├── Index.vue           \# Tampilan Metrik & Form Analitik ARIMA  
│   │           │   └── ArimaChart.vue      \# Komponen Chart.js untuk render grafik  
│   │           ├── Products/  
│   │           │   ├── Index.vue           \# DataGrid Produk  
│   │           │   └── Form.vue            \# Form Tambah/Edit Produk  
│   │           ├── Orders/  
│   │           │   └── Index.vue           \# DataGrid Transaksi (Riwayat & Filter)  
│   │           ├── Users/  
│   │           │   ├── Customers.vue       \# Daftar Pelanggan  
│   │           │   └── Staff.vue           \# Daftar Karyawan & Form Role Kasir/Admin  
│   │           └── Reports/  
│   │               └── Index.vue           \# Filter Tanggal & Tombol Export CSV  
│   │  
│   └── views/  
│       └── admin/  
│           └── app.blade.php               \# File Blade kerangka yang me-load admin.js (Vite)  
│  
└── routes/  
    ├── web.php                             \# Route::get('/admin/{any}', ...) memanggil admin/app.blade.php  
    └── api.php                             \# Rute API dengan middleware 'role:admin'

**Kesimpulan Modul Admin:**

Dengan arsitektur *Custom Laravel* ini, panel admin tidak lagi membawa beban menu-menu berlebih dari *framework e-commerce* pihak ketiga. Dasbor menjadi sangat rapi, cepat, dan terfokus pada fungsionalitas inti: mengatur stok, melihat uang masuk dari semua kanal, mengatur akses karyawan (Admin/Kasir), dan meramalkan penjualan bulan depan (ARIMA) melalui satu *interface* terpadu.