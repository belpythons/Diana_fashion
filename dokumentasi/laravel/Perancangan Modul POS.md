# **PERANCANGAN MODUL POS (POINT OF SALE)**

**Versi: Custom Laravel Architecture (Clean & Standalone)**

## **1\. Konsep Utama Modul POS**

Modul POS dibangun sebagai **Single Page Application (SPA)** menggunakan **Vue.js 3** dan **Tailwind CSS**. Modul ini diakses melalui rute /pos dan dilindungi oleh *middleware* yang hanya mengizinkan pengguna dengan role \= 'kasir'.

Karena berformat SPA, aplikasi tidak akan pernah memuat ulang halaman (*reload*) selama kasir beroperasi, memberikan kecepatan layaknya aplikasi *desktop* (*native*).

## **2\. Rincian Fitur POS Terpadu**

### **A. Pencarian Produk Cerdas (Debounce Mechanism)**

* **Deskripsi:** Mencegah aplikasi mengirim *request* ke *database* setiap kali kasir mengetik satu huruf.  
* **Cara Kerja:** Menggunakan fungsi setTimeout di Vue.js. *Request* pencarian produk (berdasarkan SKU atau Nama) hanya dikirim ke *server* jika kasir **berhenti mengetik selama 300 milidetik**. Ini menjaga server tetap ringan meski ribuan produk dicari.

### **B. Offline-to-Online Cart (Fleksibilitas Identitas)**

* **Deskripsi:** Kasir melayani dua tipe pelanggan fisik: Pelanggan Umum (Guest) dan Pelanggan Tetap (Member Web).  
* **Cara Kerja:** \* Terdapat kolom input "Pelanggan". Kasir bisa membiarkannya kosong atau mengetik nama manual (*Guest Checkout* \-\> customer\_id \= null).  
  * Kasir juga bisa mencari nomor HP / Email pelanggan yang sudah terdaftar di web. Jika ditemukan, akun tersebut di-*attach* ke keranjang (customer\_id \= ID Pelanggan), sehingga poin atau riwayat belanjanya menyatu (Omnichannel).

### **C. Hold & Recall Cart (Penundaan Keranjang yang Aman)**

* **Deskripsi:** Menunda transaksi pelanggan yang belum siap membayar agar kasir bisa melayani antrean lain.  
* **Cara Kerja:** \* Saat tombol "Hold" diklik, data keranjang (ID produk & qty) disimpan ke dalam memori *browser* (localStorage.setItem('held\_carts', data)).  
  * **Validasi Kritis (Recall):** Saat keranjang dipanggil kembali (*Recall*), Vue.js **wajib menembak API Backend** untuk memvalidasi apakah **harga berubah** atau **stok habis** selama masa penundaan. Jika ada perubahan, kasir akan menerima notifikasi *update* harga.

### **D. Multi-Payment (Administratif)**

* **Deskripsi:** Modul POS menyediakan tombol pilihan metode pembayaran: Tunai (Cash), QRIS Toko, dan Transfer Bank.  
* **Cara Kerja:** Karena ini bukan *payment gateway* otomatis, ini berfungsi sebagai pelabelan untuk pencatatan di pembukuan. Nilai string ('cash', 'qris') akan dikirim dan disimpan ke kolom payment\_method di tabel orders.

### **E. Fire and Forget (Auto-Reset State)**

* **Deskripsi:** Memaksimalkan kecepatan pelayanan kasir (kecepatan transisi dari satu pembeli ke pembeli berikutnya).  
* **Cara Kerja:** Begitu tombol "Bayar" diklik dan *server* mengembalikan respon 200 OK, seluruh variabel (*state*) di layar kasir seperti activeCart, customerData, dan searchKeyword langsung dikosongkan secara instan (Reaktivitas Vue), tanpa jeda *loading* atau *refresh* halaman.

### **F. Manajemen Antrean Web (Online Order Validation)**

* **Deskripsi:** Kasir bertindak sebagai verifikator pesanan E-Commerce yang masuk via WhatsApp.  
* **Cara Kerja:** Terdapat tab **"Antrean Web"** yang menampilkan pesanan dengan status \= 'pending'.  
  * Tombol **\[ACC\]**: Mengubah status pesanan menjadi completed. Stok yang tadinya terkunci menjadi terpotong permanen.  
  * Tombol **\[Reject\]**: Mengubah status pesanan menjadi canceled. Memicu *increment* (Restock) di tabel products.

## **3\. Tahap Implementasi (Langkah Eksekusi)**

Implementasi dibagi menjadi 3 tahap agar pengerjaan terstruktur, dimulai dari pondasi (Backend) hingga interaksi (Frontend).

### **Tahap 1: Pembangunan API Backend (Laravel 11\)**

Backend harus menyediakan *Endpoint* yang cepat dan dilindungi *Race Condition* (Bentrok Stok).

1. **Membuat Route API (routes/api.php):**  
   Route::middleware(\['auth:sanctum', 'role:kasir'\])-\>prefix('pos')-\>group(function () {  
       Route::get('/products/search', \[PosController::class, 'searchProducts'\]);  
       Route::post('/checkout', \[PosController::class, 'processCheckout'\]);  
       Route::get('/queue', \[PosController::class, 'getOnlineQueue'\]);  
       Route::post('/queue/{id}/validate', \[PosController::class, 'validateQueue'\]);  
       Route::post('/cart/validate-prices', \[PosController::class, 'validateHeldCart'\]);  
   });

2. **Logika Checkout Bebas Bentrok (Controller):**  
   Pada fungsi processCheckout, wajib gunakan DB::transaction dan lockForUpdate().  
   // Di dalam DB::transaction  
   foreach($request-\>items as $item) {  
       $product \= Product::where('id', $item\['id'\])-\>lockForUpdate()-\>first();  
       if($product-\>stock \< $item\['qty'\]) {  
           throw new \\Exception("Stok {$product-\>name} tidak cukup.");  
       }  
       $product-\>decrement('stock', $item\['qty'\]);  
   }  
   // Simpan ke tabel orders dengan status 'completed', channel 'pos'

### **Tahap 2: Pembangunan UI/UX Frontend (Vue.js 3 \+ Tailwind)**

Membuat antarmuka SPA menggunakan komponen Vue (*Composition API* direkomendasikan).

1. **Struktur Komponen Inti:**  
   * PosLayout.vue (Komponen induk pembungkus aplikasi).  
   * ProductSearch.vue (Kolom input dengan event @input yang dibungkus fungsi setTimeout 300ms).  
   * CartArea.vue (Menampilkan rincian harga, tombol *Hold*, metode pembayaran, dan tombol *Checkout*).  
   * OnlineQueueTab.vue (Tabel daftar antrean web dengan tombol ACC/Reject).  
2. **Implementasi Hold & Recall:**  
   // Menyimpan  
   const holdCart \= () \=\> {  
       const heldCarts \= JSON.parse(localStorage.getItem('held\_carts') || '\[\]');  
       heldCarts.push({ id: Date.now(), items: activeCart.value });  
       localStorage.setItem('held\_carts', JSON.stringify(heldCarts));  
       activeCart.value \= \[\]; // Reset layar  
   };

   // Memanggil kembali (Recall) \+ Validasi API  
   const recallCart \= async (cartId) \=\> {  
       const cartToRecall \= /\* cari dari localstorage \*/;  
       try {  
           // Tembak API untuk memastikan harga/stok terbaru  
           const response \= await axios.post('/api/pos/cart/validate-prices', { items: cartToRecall.items });  
           activeCart.value \= response.data.validated\_items; // Update dengan harga terbaru  
       } catch (error) {  
           toast.error("Ada barang yang stoknya habis\!");  
       }  
   };

### **Tahap 3: Proteksi Error & "Fire and Forget"**

Bagian ini memastikan aplikasi kasir tangguh di lapangan dan anti-macet.

1. **Penangkap Error Race Condition (Frontend):**  
   Saat API merespon *Error 422* (karena barang direbut pembeli web), Vue.js harus menampilkannya ke kasir dengan *Toast/Alert*.  
   try {  
       await axios.post('/api/pos/checkout', payload);  
       toast.success("Transaksi Sukses\!");  
       resetPosState(); // \<-- FIRE AND FORGET TRIGGER  
   } catch (error) {  
       if (error.response.status \=== 422\) {  
           toast.error(error.response.data.error); // Muncul "Stok tidak cukup"  
           fetchLatestStock(); // Segarkan stok di layar  
       }  
   }

2. **Fungsi Reset State (Fire and Forget):**  
   Fungsi ini dipanggil segera setelah *Checkout* berhasil.  
   const resetPosState \= () \=\> {  
       activeCart.value \= \[\];  
       customerId.value \= null;  
       customerKeyword.value \= '';  
       paymentMethod.value \= 'cash';  
       searchKeyword.value \= '';  
       // Layar seketika kembali kosong, siap untuk pelanggan berikutnya  
   };

## **4\. Pemetaan Direktori (Directory Mapping) Modul POS**

Untuk memastikan struktur proyek Anda tertata dengan baik secara arsitektural, berikut adalah referensi pemetaan *folder* dan *file* khusus untuk modul POS di dalam lingkungan proyek Laravel Anda:

diana\_fashion\_app/  
│  
├── app/  
│   └── Http/  
│       └── Controllers/  
│           └── PosController.php           \# Logika transaksi POS (Checkout, Search, Hold Validation)  
│  
├── resources/  
│   ├── js/  
│   │   ├── pos.js                          \# Entry point Vue.js khusus untuk aplikasi SPA POS  
│   │   └── Pages/  
│   │       └── POS/                        \# Kumpulan Komponen Vue untuk Layar Kasir  
│   │           ├── PosLayout.vue           \# Wrapper / Layout utama Kasir  
│   │           ├── Components/  
│   │           │   ├── ProductSearch.vue   \# Komponen pencarian barang (Debounce API)  
│   │           │   ├── CartArea.vue        \# Komponen perhitungan total dan Hold/Recall  
│   │           │   ├── PaymentModal.vue    \# Komponen popup metode pembayaran (Cash/QRIS)  
│   │           │   └── OnlineQueueTab.vue  \# Komponen daftar antrean order Web (ACC/Reject)  
│   │           └── Views/  
│   │               └── PosTerminal.vue     \# View perakitan semua komponen di atas  
│   │  
│   └── views/  
│       └── pos/  
│           └── index.blade.php             \# File Blade kerangka yang me-load file pos.js (Vite)  
│  
└── routes/  
    ├── web.php                             \# Terdapat Route::get('/pos', ... ) yang memanggil index.blade.php  
    └── api.php                             \# Terdapat rute-rute endpoint untuk menembak fungsi-fungsi di PosController

**Penjelasan Struktur:**

Dengan memisahkan file pos.js dan membungkusnya dalam folder resources/js/Pages/POS/, Anda menciptakan lingkungan **SPA terisolasi** yang tidak akan terpengaruh oleh *file scripting* dari halaman Admin atau Web E-Commerce Pelanggan. Pemuatan antarmuka akan menjadi sangat cepat dan terfokus.

## **5\. Rangkuman Manfaat Arsitektur Custom Ini**

Dengan merancang modul POS secara terpisah sebagai SPA Vue yang menembak API Laravel murni:

1. **Performa Maksimal:** Tidak ada jeda transisi (*loading page*) seperti pada aplikasi web tradisional.  
2. **Kendali Penuh Atas Stok:** Penggunaan lockForUpdate() menjamin tidak akan ada kejadian barang laku padahal stok kosong (*overselling*).  
3. **Data Sentralisasi yang Bersih:** Semua transaksi POS masuk ke tabel orders yang sama dengan E-Commerce, membuat data sangat mudah ditarik oleh algoritma peramalan **ARIMA** (Python).