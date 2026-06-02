# **DOKUMENTASI RESOLUSI LOGIKA & ARSITEKTUR CUSTOM**

**Sistem: Diana Fashion Omnichannel (Custom Laravel Murni)**

Dokumen ini adalah hukum tertinggi (*Prime Directive*) yang menyelesaikan 12 inkonsistensi dan cacat logika yang ditemukan pada *Implementation Plan* arsitektur Custom Laravel. Dokumen ini wajib dipatuhi oleh *Developer / AI Agent* selama fase penulisan kode.

## **A. KEPUTUSAN ARSITEKTUR UTAMA (User Review Responses)**

### **1\. Arsitektur Front-End (Isu \#10)**

**Keputusan:** Gunakan **Opsi B (Vue Router SPA murni dengan API)**.

* **Alasan:** Sistem kita memiliki 3 ranah yang sangat terisolasi (E-Commerce Publik, POS Kasir, Admin Panel). Menggunakan API (routes/api.php) dikombinasikan dengan autentikasi berbasis *Cookie* (Sanctum SPA Authentication) jauh lebih cocok dan aman untuk memisahkan ketiga area ini daripada menggunakan *Inertia.js*.  
* **Instruksi:** Abaikan/jangan pasang Inertia.js. Bangun 3 *entry point* Vue terpisah (app.js, pos.js, admin.js) yang dilayani oleh 3 file Blade berbeda.

### **2\. Autentikasi Checkout Web (Isu \#1)**

**Keputusan:** **Wajib Login (Authenticated Checkout)**.

* **Alasan:** Mencegah *bot abuse* atau *spam checkout* yang dapat melumpuhkan stok secara artifisial melalui sistem antrean.  
* **Instruksi:** Pindahkan rute /api/storefront/checkout ke dalam grup *middleware* auth:sanctum. Pelanggan wajib mendaftar dan memverifikasi *login* sebelum dapat mengunci stok.

### **3\. Timeout Pesanan Pending (Isu \#2 & \#11)**

**Keputusan:** Timeout ditetapkan selama **12 Jam**.

* **Alasan:** Sesuai dengan spesifikasi dokumen pengujian awal.  
* **Instruksi:** *Cron Job* harus diimplementasikan secara eksplisit (Lihat Detail Resolusi).

### **4\. Evaluasi MAPE ARIMA (Isu \#8)**

**Keputusan:** Gunakan metode **Adaptif (Train/Test Split)**.

* **Instruksi:** Modifikasi *script* Flask. Jika data \>= 15 hari, lakukan *split* 80/20 untuk mendapatkan nilai MAPE yang jujur, lalu *re-train* 100% untuk hasil peramalan. Jika data \< 15 hari, gunakan *In-sample MAPE* dengan *flag warning* di JSON *response*.

## **B. RESOLUSI TEKNIS & KEAMANAN**

### **Resolusi Isu \#3: Penamaan Kolom Kuantitas**

* **Hukum:** Gunakan nama kolom **qty** secara seragam di seluruh aplikasi.  
* **Tindakan:** Pastikan *Migration* tabel order\_items menggunakan $table-\>integer('qty');, dan *Controller* Laravel / Flask menggunakan key qty saat menjumlahkan penjualan.

### **Resolusi Isu \#4 & \#5: Endpoint Proxy ARIMA**

* **Hukum:** Hanya boleh ada **SATU** *Endpoint Proxy* di Laravel, yaitu di ArimaController. Hapus rute duplikat di AdminDashboardController.  
* **Tindakan:** Gunakan ArimaController::runPrediction() sebagai jembatan tunggal antara Vue Admin dan Flask Python.

### **Resolusi Isu \#6: Optimasi Pencarian Produk (Indexing)**

* **Hukum:** Hindari pencarian LIKE '%keyword%' di tabel dengan data besar.  
* **Tindakan:**  
  * Untuk **POS (Pencarian SKU):** Wajib menggunakan *prefix match* LIKE 'keyword%' karena kolom sku sudah di-index.  
  * Untuk **E-Commerce (Pencarian Nama):** Tambahkan $table-\>fullText('name'); pada *Migration* tabel products dan gunakan *Query MATCH AGAINST* untuk pencarian berbasis kata.

### **Resolusi Isu \#7: Memory Leak pada Ekspor CSV**

* **Hukum:** Dilarang menggunakan method \-\>get() pada fungsi Ekspor CSV.  
* **Tindakan:** Wajib menggunakan *Lazy Loading* dengan method **\-\>cursor()**.  
  // Contoh yang Benar  
  $orders \= Order::where('status', 'completed')-\>cursor();  
  foreach ($orders as $order) { fputcsv(...); }

### **Resolusi Isu \#9: Dinamisasi Produk pada Cron Job ARIMA**

* **Hukum:** Dilarang melakukan *hardcode* nama produk (seperti 'Tunik molek') di dalam *Cron Job*.  
* **Tindakan:** *Script Cron Job* di routes/console.php wajib melakukan kueri dinamis untuk mencari 5 produk dengan jumlah penjualan (qty) terbanyak dalam 30 hari terakhir, lalu memicu *request* prediksi ARIMA untuk kelima produk tersebut guna di- *cache*.

### **Resolusi Isu \#11: Implementasi Auto-Cancel (Restock Otomatis)**

* **Hukum:** Penanganan stok yang menggantung (lebih dari 12 jam) adalah prioritas keamanan inventaris tertinggi.  
* **Tindakan:** Implementasikan *Task Scheduler* berikut menggunakan **DB::transaction** untuk mencegah bentrok:  
  // Di routes/console.php  
  Schedule::call(function () {  
      $expiredOrders \= \\App\\Models\\Order::where('status', 'pending')  
          \-\>where('channel', 'web')  
          \-\>where('created\_at', '\<', now()-\>subHours(12))  
          \-\>get();

      foreach ($expiredOrders as $order) {  
          \\Illuminate\\Support\\Facades\\DB::transaction(function () use ($order) {  
              $order-\>update(\['status' \=\> 'canceled'\]);  
              foreach ($order-\>items as $item) {  
                  // Pengembalian stok  
                  \\App\\Models\\Product::where('id', $item-\>product\_id)-\>increment('stock', $item-\>qty);  
              }  
          });  
      }  
  })-\>hourly()-\>timezone('Asia/Makassar')-\>withoutOverlapping();

### **Resolusi Isu \#12: Eksposur Password Hash**

* **Hukum:** Dilarang mengirimkan seluruh objek *Model* ke *Client-Side* jika terdapat data sensitif.  
* **Tindakan:** Selain mengandalkan array $hidden \= \['password'\] di Model User, pastikan semua *Response JSON* untuk Data *User/Staff* difilter menggunakan *Eloquent API Resources* atau seleksi .only(\['id', 'name', 'email', 'role'\]).

**Status Dokumen:**

Dengan terbitnya dokumen ini, fondasi arsitektur **Custom Laravel** telah disempurnakan. Tim pengembang atau AI Agent diinstruksikan untuk menggunakan dokumen ini sebagai panduan utama penyelesaian cacat logika sebelum dan selama proses *coding* berlangsung.