# **PERANCANGAN MODUL E-COMMERCE (STOREFRONT)**

**Versi: Custom Laravel Architecture (Clean & Standalone)**

## **1\. Konsep Utama Modul E-Commerce**

Modul E-Commerce berfungsi sebagai wajah digital Toko Diana Fashion. Antarmuka ini dibangun menggunakan **Vue.js 3** dan **Tailwind CSS** (dirender melalui *Inertia.js* atau *Vue Router* dalam Laravel).

Fokus utama modul ini adalah memberikan pengalaman berbelanja yang sangat cepat, navigasi yang intuitif (melalui *Filtering, Sorting, Pagination*), menampilkan kecerdasan buatan (ARIMA) secara visual, serta menyediakan portal mandiri bagi pelanggan untuk melacak pesanan mereka.

## **2\. Rincian Fitur E-Commerce Terpadu**

### **A. Katalog Produk (Pagination, Filtering, Sorting)**

Agar browser pelanggan tidak berat saat memuat ratusan produk, sistem menggunakan teknik kueri dinamis.

* **Pagination:** Memuat produk secara bertahap (misal: 12 produk per halaman) menggunakan fitur bawaan Laravel paginate(12).  
* **Filtering (Penyaringan):** Pelanggan dapat menyaring produk berdasarkan kategori yang sesuai dengan Master Data Excel: **ATASAN**, **BAWAHAN**, dan **GAMIS**.  
* **Sorting (Pengurutan):** Pelanggan dapat mengurutkan produk berdasarkan:  
  * Terbaru (Created At \- Descending)  
  * Harga Termurah ke Termahal (Price \- Ascending)  
  * Harga Termahal ke Termurah (Price \- Descending)

### **B. ARIMA Recommendation Card & Badges (Cerdas & Dinamis)**

Ini adalah *novelty* (nilai jual utama) sistem. Data yang diprediksi oleh *Microservice* Flask akan disuntikkan langsung ke antarmuka pelanggan.

* **Recommendation Card:** Sebuah *card* besar berdesain premium yang menampilkan 1-3 produk dengan prediksi lonjakan penjualan tertinggi minggu depan.  
* **ARIMA Badges:** Label visual (Badge) yang menempel pada foto produk.  
  * Jika API Flask memprediksi tren naik, sistem Vue.js akan memunculkan *badge*: 🔥 Sedang Tren (Prediksi Naik) atau ⚡ Cepat Habis.  
  * *Badge* ini memicu efek FOMO (*Fear of Missing Out*) agar pelanggan segera melakukan *checkout*.

### **C. Keranjang Belanja (Cart Management)**

* Data keranjang disimpan di *Local Storage* atau *Vuex/Pinia State* milik browser pelanggan (bukan di database) hingga mereka menekan tombol *Checkout*. Ini menghemat beban server.

### **D. Checkout & WhatsApp Redirect (Sistem Antrean)**

* Saat pelanggan klik "Pesan Sekarang", Vue.js menembak API Laravel.  
* Laravel menggunakan DB::transaction dan lockForUpdate() untuk **memotong stok secara instan**.  
* Status pesanan menjadi pending (Masuk ke layar antrean Kasir POS).  
* Browser pelanggan langsung di-*redirect* (diarahkan) ke tautan wa.me/628... yang sudah berisi teks rincian pesanan (Nama, Baju yang dibeli, dan Total Harga).

### **E. Customer Portal (Dashboard Pelanggan)**

Portal mandiri bagi pelanggan yang telah memiliki akun terdaftar untuk memonitor aktivitas belanja mereka.

* **Riwayat Pesanan (Order History):** Menampilkan daftar semua pesanan yang pernah dilakukan beserta detail item yang dibeli.  
* **Pelacakan Status Real-Time:** Menampilkan status pesanan secara jelas:  
  * Pending (Masuk antrean, menunggu ACC Kasir).  
  * Completed (Pesanan sah, telah dibayar dan diproses).  
  * Canceled (Pesanan dibatalkan/kadaluarsa).  
* **Profil Pengguna:** Form untuk memperbarui data diri (Nama, Email, dan Password).

## **3\. Panduan Antarmuka (Layout System)**

Sistem menggunakan dua pendekatan *layout* utama: Bento Grid untuk halaman utama (katalog) dan Sidebar Layout untuk Customer Portal.

### **A. BENTO GRID LAYOUT (Halaman Utama / Katalog)**

Untuk memberikan kesan modern dan rapi, desain halaman utama menggunakan susunan kotak-kotak asimetris yang presisi.

\+-----------------------------------------------------------------------+  
|  \[ HEADER / NAVIGASI \]  Logo | Atasan | Bawahan | Gamis | Akun | Cart |  
\+-----------------------------------------------------------------------+  
|                                                                       |  
|  \+---------------------------+ \+-----------------------------------+  |  
|  | HERO BANNER (Promo)       | | ARIMA RECOMMENDATION CARD (Bento) |  |  
|  | Diskon Gamis 20%\!         | | 🔥 Hot Item Minggu Depan\!         |  |  
|  | \[Belanja Sekarang\]        | | Gambar: "Tunik Molek"             |  |  
|  |                           | | Prediksi Laku: \+25 Pcs            |  |  
|  | (Col-span-8)              | | (Col-span-4)                      |  |  
|  \+---------------------------+ \+-----------------------------------+  |  
|                                                                       |  
|  \+-------------+ \+-------------------------------------------------+  |  
|  | FILTERING   | | SORTING: \[ Harga Termurah v \]                   |  |  
|  | (Sidebar)   | \+-------------------------------------------------+  |  
|  | \[x\] Atasan  | |                                                 |  |  
|  | \[ \] Bawahan | |  \[Produk Card\]  \[Produk Card\]  \[Produk Card\]    |  |  
|  | \[ \] Gamis   | |    Gambar         Gambar         Gambar         |  |  
|  |             | |   ⚡ Trending     (Normal)       (Normal)       |  |  
|  | (Col-span-3)| |   Rp 150.000     Rp 250.000     Rp 120.000      |  |  
|  |             | |                                                 |  |  
|  |             | |   \< Pagination: Prev | 1 | 2 | 3 | Next \>       |  |  
|  |             | | (Col-span-9)                                    |  |  
|  \+-------------+ \+-------------------------------------------------+  |  
\+-----------------------------------------------------------------------+

### **B. CUSTOMER PORTAL LAYOUT (Dashboard Pelanggan)**

Fokus pada keterbacaan data tabel riwayat transaksi.

\+-----------------------------------------------------------------------+  
|  \[ HEADER / NAVIGASI \]  Logo | Atasan | Bawahan | Gamis | Akun | Cart |  
\+-----------------------------------------------------------------------+  
|                                                                       |  
|  \+-------------+ \+-------------------------------------------------+  |  
|  | MENU AKUN   | | RIWAYAT PESANAN (ORDER HISTORY)                 |  |  
|  | \> Pesanan   | |                                                 |  |  
|  | \- Profil    | | No. Nota  | Tanggal    | Total Bayar | Status   |  |  
|  | \- Logout    | | ORD-1001  | 05-May-26  | Rp 260.000  | PENDING  |  |  
|  |             | | ORD-0992  | 01-May-26  | Rp 150.000  | COMPLETED|  |  
|  | (Col-span-3)| |                                                 |  |  
|  |             | | \[Lihat Detail ORD-1001\]                         |  |  
|  \+-------------+ \+-------------------------------------------------+  |  
\+-----------------------------------------------------------------------+

## **4\. Tahap Implementasi (Langkah Eksekusi)**

### **Tahap 1: Pembangunan API Backend (Laravel)**

Buat *endpoint* yang fleksibel untuk Katalog dan rute terproteksi untuk Customer Portal.

1. **Membuat Route API (routes/api.php):**  
   // Rute Publik (Katalog)  
   Route::prefix('storefront')-\>group(function () {  
       Route::get('/products', \[StorefrontController::class, 'getProducts'\]);  
       Route::get('/recommendations', \[StorefrontController::class, 'getArimaRecommendations'\]);  
       Route::post('/checkout', \[StorefrontController::class, 'processWebCheckout'\]);  
   });

   // Rute Privat (Customer Portal \- Wajib Login)  
   Route::middleware(\['auth:sanctum'\])-\>prefix('customer')-\>group(function () {  
       Route::get('/orders', \[CustomerPortalController::class, 'getOrderHistory'\]);  
       Route::get('/orders/{id}', \[CustomerPortalController::class, 'getOrderDetail'\]);  
       Route::post('/profile/update', \[CustomerPortalController::class, 'updateProfile'\]);  
   });

2. **Logika Order History (CustomerPortalController):**  
   public function getOrderHistory(Request $request)  
   {  
       // Tarik data order milik user yang sedang login saja  
       $orders \= Order::where('user\_id', auth()-\>id())  
                      \-\>orderBy('created\_at', 'desc')  
                      \-\>paginate(10);

       return response()-\>json($orders);  
   }

3. **Logika Filter, Sort, & Paginate (StorefrontController):**  
   public function getProducts(Request $request)  
   {  
       $query \= Product::query();

       // 1\. Filtering berdasarkan Kategori  
       if ($request-\>has('category')) {  
           $query-\>whereHas('category', function($q) use ($request) {  
               $q-\>where('name', $request-\>category); // 'ATASAN', 'BAWAHAN', 'GAMIS'  
           });  
       }

       // 2\. Sorting  
       if ($request-\>sort \=== 'price\_asc') {  
           $query-\>orderBy('price', 'asc');  
       } elseif ($request-\>sort \=== 'price\_desc') {  
           $query-\>orderBy('price', 'desc');  
       } else {  
           $query-\>orderBy('created\_at', 'desc'); // Default: Terbaru  
       }

       // 3\. Pagination (Kirim 12 data per halaman)  
       $products \= $query-\>paginate(12);

       return response()-\>json($products);  
   }

### **Tahap 2: Integrasi ARIMA Recommendation (Laravel Proxy)**

Bagian ini mengambil hasil prediksi dari Flask, lalu mengirimkannya ke Vue.js sebagai data rekomendasi.

public function getArimaRecommendations()  
{  
    // Menggunakan Laravel Cache agar tidak menembak Flask setiap detik (performa)  
    $recommendations \= Cache::remember('arima\_hot\_items', 3600, function () {  
        // Panggil API Python Flask  
        $response \= Http::post(env('ARIMA\_SERVICE\_URL') . '/api/v1/predict/batch', \[  
            // Kirim data historis produk-produk unggulan...  
        \]);  
          
        return $response-\>json();  
    });

    return response()-\>json($recommendations);  
}

### **Tahap 3: Pembangunan UI/UX Frontend (Vue.js 3 \+ Tailwind)**

1. **Komponen Filter & Sort (Reaktivitas Vue):**  
   // Menyimpan status pencarian  
   const filters \= reactive({ category: '', sort: 'newest', page: 1 });

   // Fungsi mengambil produk (dipanggil tiap kali filter berubah)  
   const fetchProducts \= async () \=\> {  
       const response \= await axios.get('/api/storefront/products', { params: filters });  
       productList.value \= response.data.data;  
       paginationInfo.value \= response.data; // meta untuk next/prev page  
   };

   // Watcher: Jika user klik kategori baru, reset ke halaman 1 dan ambil data  
   watch(() \=\> filters, () \=\> { fetchProducts(); }, { deep: true });

2. **Logika Customer Portal (Menarik Histori):**  
   const orderHistory \= ref(\[\]);

   const fetchOrderHistory \= async () \=\> {  
       try {  
           const response \= await axios.get('/api/customer/orders');  
           orderHistory.value \= response.data.data;  
       } catch (error) {  
           toast.error("Gagal memuat riwayat pesanan.");  
       }  
   };

   // Fungsi format status badge  
   const statusClass \= (status) \=\> {  
       if(status \=== 'completed') return 'bg-green-100 text-green-800';  
       if(status \=== 'pending') return 'bg-yellow-100 text-yellow-800';  
       return 'bg-red-100 text-red-800';  
   };

### **Tahap 4: Logika Checkout & Redirect WA**

const submitWebCheckout \= async () \=\> {  
    try {  
        // Tembak Laravel API (Lock Stok)  
        const response \= await axios.post('/api/storefront/checkout', { items: cart.value });  
          
        if(response.data.status \=== 'success') {  
            // Bersihkan keranjang lokal  
            cart.value \= \[\];  
              
            // Redirect ke Tautan WhatsApp yang dikembalikan oleh Laravel  
            window.location.href \= response.data.whatsapp\_url;  
        }  
    } catch (error) {  
        toast.error("Gagal checkout. Stok mungkin habis.");  
    }  
};

## **5\. Pemetaan Direktori (Directory Mapping) Modul E-Commerce**

Untuk menjaga agar file E-Commerce publik dan Portal Pelanggan tidak tercampur dengan PWA Kasir (POS) maupun Dashboard Admin, ikuti struktur direktori Vue.js di bawah ini:

diana\_fashion\_app/  
│  
├── app/  
│   └── Http/  
│       └── Controllers/  
│           ├── StorefrontController.php    \# Logika Filter, Sort, Paginate & Checkout WA  
│           └── CustomerPortalController.php\# Logika Histori Pesanan & Profil Pelanggan  
│  
├── resources/  
│   ├── js/  
│   │   ├── app.js                          \# Entry point utama (Vue \+ Tailwind) untuk area publik  
│   │   └── Pages/  
│   │       └── Storefront/                 \# Area khusus Sisi Pelanggan  
│   │           ├── Layouts/  
│   │           │   ├── MainLayout.vue      \# Header, Navbar, Footer  
│   │           │   └── CustomerLayout.vue  \# Layout dengan Sidebar khusus Menu Akun  
│   │           ├── Components/  
│   │           │   ├── BentoPromo.vue      \# Komponen Grid Asimetris Promosi  
│   │           │   ├── ArimaCard.vue       \# Komponen khusus Rekomendasi ARIMA (Hasil Flask)  
│   │           │   ├── ProductCard.vue     \# Kartu produk tunggal (dengan label badge)  
│   │           │   ├── FilterSidebar.vue   \# Checkbox Kategori (Atasan/Bawahan/Gamis)  
│   │           │   └── Pagination.vue      \# Navigasi halaman Next/Prev  
│   │           ├── Views/  
│   │           │   ├── Home.vue            \# Halaman Utama (Merakit Bento Grid & List Produk)  
│   │           │   └── CartCheckout.vue    \# Halaman keranjang dan form Redirect WA  
│   │           │  
│   │           └── Customer/               \# Sub-modul Customer Portal  
│   │               ├── Dashboard.vue       \# Ringkasan akun pelanggan  
│   │               ├── OrderHistory.vue    \# Tabel riwayat pesanan (dengan status badge)  
│   │               └── ProfileSetting.vue  \# Pengaturan akun (Ubah nama, password)  
│   │  
│   └── views/  
│       └── storefront/  
│           └── index.blade.php             \# File Blade kerangka yang me-load app.js (Vite)

**Kesimpulan Modul E-Commerce:**

Dengan penambahan modul **Customer Portal**, alur pengguna E-Commerce menjadi utuh dari ujung ke ujung (*end-to-end*). Pelanggan tidak hanya dimanjakan dengan UI interaktif (Bento Grid) dan pemasaran cerdas (*ARIMA Badges*), tetapi juga diberi kemandirian penuh untuk melacak status "Antrean" pesanan mereka secara transparan, yang pada akhirnya akan mengurangi pertanyaan repetitif ke admin melalui WhatsApp.