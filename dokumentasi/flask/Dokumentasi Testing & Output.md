# **DOKUMENTASI TESTING, HARAPAN, DAN OUTPUT SISTEM**

**Proyek: Diana Fashion Omnichannel (Custom Laravel \+ Vue \+ Flask)**

Dokumen ini mendefinisikan skenario pengujian (Quality Assurance), harapan dari sisi bisnis dan teknis, serta output konkret yang akan dihasilkan setelah seluruh tahap *Software Design Document* (SDD) diimplementasikan.

## **1\. Harapan Implementasi (Expectations)**

Dengan membuang platform pihak ketiga dan beralih ke arsitektur *Custom Laravel Murni*, sistem ini diharapkan mampu mencapai target berikut:

### **A. Harapan Teknis**

1. **Kecepatan Tinggi & Ringan:** Modul POS berjalan sebagai *Single Page Application* (SPA) yang instan tanpa *reload* halaman, mempercepat pelayanan di kasir fisik.  
2. **Integritas Data Sempurna:** Menghilangkan risiko *Double Deduction* (pengurangan stok ganda) atau *Overselling* (barang terjual padahal kosong) berkat penerapan *Atomic Locks* (lockForUpdate) di *database* InnoDB.  
3. **Kemandirian Sistem:** Arsitektur yang bersih (hanya 5 tabel inti transaksional) membuat perawatan (*maintenance*) dan penambahan fitur ke depannya sangat mudah dilakukan tanpa batasan *framework* pihak ketiga.

### **B. Harapan Bisnis (Toko Diana Fashion)**

1. **Pemasaran Cerdas (FOMO):** Hasil prediksi ARIMA tidak hanya menjadi laporan mati di meja admin, tetapi berubah menjadi *Badge* "🔥 Sedang Tren" di katalog publik untuk memicu pembelian pelanggan.  
2. **Manajemen Antrean yang Rapi:** Kasir tidak perlu lagi mengecek mutasi bank secara manual dan mencatat di buku. Cukup melihat tab "Antrean Web" di layar POS dan menekan tombol ACC.  
3. **Pemesanan Stok yang Akurat (Data-Driven):** Pemilik toko (Admin) tidak lagi menggunakan insting untuk membeli stok baju dari *supplier*, melainkan berdasarkan perhitungan matematis MAPE dan grafik prediksi AI.

## **2\. Output yang Akan Dihasilkan (Deliverables)**

Setelah fase *coding* selesai, sistem akan menghasilkan 5 (*lima*) output produk digital utama yang siap di-*deploy* ke *server* (*Production*):

1. **Web Storefront (E-Commerce):** Antarmuka publik yang responsif (Bento Grid Layout) untuk pelanggan mencari pakaian, memfilter kategori, melihat rekomendasi ARIMA, dan *checkout* via WhatsApp.  
2. **Customer Portal:** Dasbor mandiri bagi pelanggan terdaftar untuk melacak status pesanan mereka (Antrean, Selesai, atau Batal) serta melihat riwayat belanja.  
3. **POS Terminal (Kasir SPA):** Aplikasi kasir web-based khusus karyawan toko fisik dengan fitur pencarian *debounce*, *Hold/Recall*, identitas *guest/member*, dan *Fire & Forget*.  
4. **Admin Back-Office:** Panel kontrol terpusat untuk mengatur CRUD produk, memantau riwayat transaksi seluruh kanal, mengekspor laporan CSV, dan memicu *tuning* ARIMA.  
5. **ARIMA Flask Microservice:** *Service* Python independen yang berjalan di latar belakang (port 5000\) yang siap menerima data JSON, memproses *Pandas*, dan mengembalikan hasil peramalan pmdarima.

## **3\. Skenario Pengujian Sistem (Testing Plan)**

Untuk memastikan sistem berjalan sempurna sebelum digunakan secara nyata (UAT \- *User Acceptance Test*), langkah-langkah pengujian berikut wajib dilakukan oleh tim *Developer* atau QA (*Quality Assurance*).

### **3.1. Pengujian Modul Transaksi & Inventaris (Krusial)**

Fokus: Memastikan stok akurat dan tidak ada *Race Condition*.

* **Skenario 1: Checkout Web (Masuk Antrean)**  
  * *Tindakan:* Pelanggan web *checkout* barang X (stok awal 10).  
  * *Ekspektasi Output:* Status order menjadi pending. Stok barang X di *database* langsung berubah menjadi 9\. URL di-*redirect* ke WhatsApp.  
* **Skenario 2: Validasi POS (Kasir ACC & Reject)**  
  * *Tindakan:* Kasir membuka tab "Antrean Online". Klik ACC pada pesanan Web di Skenario 1\.  
  * *Ekspektasi Output:* Status order menjadi completed. Stok tetap 9 (tidak berkurang lagi).  
  * *Tindakan Lanjutan:* Kasir klik Reject pada order lain yang berstatus pending (stok awal 5, terpotong jadi 4).  
  * *Ekspektasi Output:* Status order menjadi canceled. Stok barang otomatis **kembali menjadi 5** (*Restock*).  
* **Skenario 3: Uji Bentrok Stok (Race Condition Test)**  
  * *Tindakan:* Barang Y tersisa 1 pcs. Buka dua *browser* berbeda. Satu *checkout* via Web, satu *checkout* via POS. Tekan tombol bayar di **milidetik yang persis sama**.  
  * *Ekspektasi Output:* Transaksi pertama berhasil memotong stok menjadi 0\. Transaksi kedua tertahan (di-*lock* oleh *database*), lalu mengembalikan **Error 422**, dan layar menampilkan notifikasi: *"Gagal. Stok tidak mencukupi atau keduluan pembeli lain."*

### **3.2. Pengujian Modul POS (Front-End)**

Fokus: Kecepatan dan manajemen *state* memori lokal.

* **Skenario 1: Debounce Search**  
  * *Tindakan:* Ketik kata "Kemeja" dengan sangat cepat di kolom pencarian kasir, lalu pantau tab *Network* di *Developer Tools browser*.  
  * *Ekspektasi Output:* Aplikasi HANYA mengirimkan 1 *request API* ke server (tepat 300ms setelah selesai mengetik), bukan mengirim 6 *request* (K-e-m-e-j-a).  
* **Skenario 2: Hold & Recall Cart dengan Validasi Harga**  
  * *Tindakan:* Masukkan "Baju A (Rp 100.000)" ke keranjang. Klik **Hold Cart**. Pindah ke layar Admin, ubah harga Baju A menjadi Rp 150.000. Kembali ke POS, klik **Recall Cart**.  
  * *Ekspektasi Output:* Keranjang kembali muncul, harga otomatis diperbarui menjadi Rp 150.000, dan muncul peringatan: *"Harga barang telah diperbarui dari database"*.  
* **Skenario 3: Fire & Forget**  
  * *Tindakan:* Selesaikan pembayaran tunai di POS.  
  * *Ekspektasi Output:* Notifikasi "Sukses" muncul, keranjang langsung kosong, data pelanggan di layar kembali netral secara instan **tanpa adanya *refresh/reload* halaman**.

### **3.3. Pengujian Microservice ARIMA & Automasi**

Fokus: Logika Python, komunikasi Server-to-Server, dan penjadwalan.

* **Skenario 1: Pengujian Missing Dates & Outlier (Tuning)**  
  * *Tindakan:* Buat data historis palsu di mana tanggal 15 kosong, dan tanggal 16 penjualannya melonjak aneh (100 pcs). Buka Admin Panel, centang opsi *Tuning*, jalankan Prediksi.  
  * *Ekspektasi Output:* Sistem berhasil mengirim JSON ke Flask. Grafik Chart.js muncul. Log mencatat nilai MAPE. Angka penjualan yang meledak (100 pcs) diredam oleh algoritma menjadi batas wajar (misal: 15 pcs) sesuai metode IQR.  
* **Skenario 2: Integrasi Badge Rekomendasi di Storefront**  
  * *Tindakan:* Setelah hasil ARIMA muncul di Dashboard Admin, buka halaman depan E-Commerce sebagai pelanggan.  
  * *Ekspektasi Output:* Pakaian yang memiliki proyeksi tren naik di ARIMA otomatis memiliki *badge* khusus (sesuai desain *Golden Ratio* di *Design System*) di katalog pelanggan.  
* **Skenario 3: Automasi Cron Job (Timezone WITA)**  
  * *Tindakan:* Ubah waktu *server* menjadi jam 01:00 WITA. Jalankan *scheduler*.  
  * *Ekspektasi Output:* Laravel secara otomatis menarik log ARIMA terbaru di latar belakang (menyimpannya di *Cache*). Selain itu, pesanan Web yang masih pending lebih dari 12 jam otomatis berubah menjadi canceled dan stok bertambah kembali.

## **4\. Kriteria Penerimaan Akhir (Acceptance Criteria)**

Proyek dinyatakan **SELESAI 100%** dan siap digunakan oleh Diana Fashion apabila:

1. Tidak ada error CORS (*Cross-Origin Resource Sharing*) saat Admin meminta prediksi ARIMA.  
2. Tidak ada angka stok yang menjadi minus (negatif) di *database*.  
3. Aplikasi POS dapat dibuka dan dioperasikan dengan lancar di komputer kasir toko.  
4. Laporan CSV dari Dashboard Admin dapat diunduh dan datanya cocok (menyatukan omzet Web dan POS dengan tepat).