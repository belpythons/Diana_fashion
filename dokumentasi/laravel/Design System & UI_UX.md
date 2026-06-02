# **DOKUMENTASI UI/UX & DESIGN SYSTEM**

**Proyek: Diana Fashion Omnichannel (E-Commerce, POS, Admin Panel)**

Dokumen ini memuat standar visual, panduan tata letak (layout), dan komponen antarmuka yang akan digunakan di seluruh ekosistem aplikasi. Pendekatan desain didasarkan pada **Minimalisme Fungsional** dan **Golden Ratio (1:1.618)**.

## **1\. Filosofi Desain**

1. Minimalisme **Fungsional:** Mengutamakan ruang kosong (*white space*) untuk mengurangi beban kognitif pengguna. Menghilangkan elemen dekoratif yang tidak perlu, bayangan (shadow) yang tebal, dan emotikon berlebih.  
2. **Proporsi Golden Ratio:** Pembagian ruang, ukuran tipografi, dan dimensi komponen dikalkulasi menggunakan rasio 1.618 untuk menghasilkan harmoni visual yang matematis dan natural. Pada sistem grid 12-kolom, ini diterjemahkan menjadi pembagian proporsi kira-kira 8:4 (66% : 33%) yang mendekati *Golden Ratio* (61.8% : 38.2%).  
3. **Fokus pada Konten & Aksi:** Penggunaan warna mencolok hanya diaplikasikan pada elemen interaktif utama (*Call to Action*).

## **2\. Palet Warna (Color Palette)**

Pewarnaan disusun untuk menjaga kebersihan antarmuka. Distribusi warna pada halaman mematuhi aturan visual yang didominasi oleh warna netral.

### **Warna Utama (Brand Primary)**

Digunakan secara sangat selektif (hanya untuk tombol bayar, tombol ACC, dan badge ARIMA).

* **Primary Base:** \#FF1F8F (Magenta)  
* Primary **Hover/Active:** \#D91678 (Magenta Gelap)

### **Warna Netral (Background & Text)**

* Background Utama (Surface **1):** \#FFFFFF (Putih Murni) \- Memakan 62% proporsi ruang visual.  
* Background Sekunder (Surface 2): \#F9FAFB (Abu-abu sangat terang) \- Untuk latar area sidebar atau form (38% proporsi visual).  
* Teks **Utama (Heading & Konten):** \#111827 (Hitam Netral)  
* Teks Sekunder (Caption & **Placeholder):** \#6B7280 (Abu-abu Medium)  
* **Garis Batas (Borders/Dividers):** \#E5E7EB (Abu-abu Terang)

### **Warna Semantik (Sistem)**

* **Sukses (Completed/ACC):** \#34D399 (Hijau Lembut)  
* **Peringatan (Pending/Hold):** \#FBBF24 (Kuning Lembut)  
* **Gagal (Reject/Error):** \#F87171 (Merah Lembut)

## **3\. Tipografi Skala Golden Ratio**

Skala ukuran huruf (font) dikalikan atau dibagi dengan rasio 1.618 dari ukuran dasar (base size). Jenis huruf yang digunakan adalah **Inter** atau **Roboto** (Sans-Serif geometris).

* **Base Size (Body Text / p):** 16px  
* **Sub-heading (h3):** 16px \* 1.618 \= 26px (Dibulatkan)  
* **Main Heading (h1):** 26px \* 1.618 \= 42px (Dibulatkan)  
* **Caption / Badge (small):** 16px / 1.618 \= 10px

## **4\. Panduan Komponen Antarmuka (UI Components)**

### **A. Tombol (Buttons)**

Bentuk balok solid dengan sudut melengkung tipis (border-radius: 4px / Tailwind: rounded-sm). Tanpa efek *drop-shadow*.

* **Primary Button:** Background \#FF1F8F, Text \#FFFFFF.  
* **Secondary Button:** Background Transparent, Border 1px solid \#E5E7EB, Text \#111827.

### **B. ARIMA Recommendation Card (Bento Grid)**

Kartu rekomendasi yang menampilkan hasil prediksi ARIMA. Menggunakan rasio ukuran yang mendominasi grid.

* **Tampilan:** Background \#F9FAFB dengan border atas tebal berwarna \#FF1F8F (sebagai penanda cerdas).  
* **Elemen:** Berisi judul "Rekomendasi AI", nama produk, proyeksi angka penjualan, dan tombol CTA.

### **C. ARIMA Badges**

Label visual pada produk di katalog.

* **Desain:** Kotak pipih kecil (tinggi 24px), background \#FF1F8F, warna teks \#FFFFFF, dengan tulisan kapital minimalis: \[ TRENDING \] atau \[ HOT ITEM \].

## **5\. Ikonografi (SVG Murni)**

Hanya menggunakan ikon SVG berbasis garis tipis (stroke 1.5px hingga 2px), tanpa isian (unfilled). Emotikon dilarang keras.

**Ikon ARIMA (Analytics/Trending):**

\<svg xmlns="\[http://www.w3.org/2000/svg\](http://www.w3.org/2000/svg)" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"\>  
    \<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"\>\</polyline\>  
    \<polyline points="16 7 22 7 22 13"\>\</polyline\>  
\</svg\>

**Ikon Cart (Keranjang):**

\<svg xmlns="\[http://www.w3.org/2000/svg\](http://www.w3.org/2000/svg)" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"\>  
    \<path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"\>\</path\>  
    \<line x1="3" y1="6" x2="21" y2="6"\>\</line\>  
    \<path d="M16 10a4 4 0 0 1-8 0"\>\</path\>  
\</svg\>

## **6\. Layouting (Penerapan Golden Ratio di 3 Modul Utama)**

Sistem layout menggunakan Tailwind CSS Grid 12 Kolom. Proporsi Emas (62% : 38%) diterjemahkan menjadi **Col-span-8** (Konten Utama) dan **Col-span-4** (Panel Samping).

### **A. E-Commerce Storefront (Bento Grid Layout)**

Halaman utama katalog menggunakan desain kotak-kotak presisi (Bento).

\+-----------------------------------------------------------------------------+  
| \[ HEADER \]  Diana Fashion    ATASAN | BAWAHAN | GAMIS     \[Akun\] \[Cart(2)\]  |  
\+-----------------------------------------------------------------------------+  
|                                                                             |  
| \+------------------------------------+ \+----------------------------------+ |  
| | BENTO 1: HERO BANNER (Col-8)       | | BENTO 2: ARIMA CARD (Col-4)      | |  
| | Koleksi Eksklusif Diana Fashion    | | \[ Ikon Trend \] Rekomendasi AI    | |  
| | Minimalis & Elegan                 | | Produk: Tunik Molek              | |  
| | \[ Lihat Koleksi \]                  | | Prediksi Laku: 25 Pcs / Minggu   | |  
| \+------------------------------------+ \+----------------------------------+ |  
|                                                                             |  
| \+-------------------------+ \+---------------------------------------------+ |  
| | FILTERING (Col-3)       | | SORTING (Col-9): \[ Harga Terendah (v) \]     | |  
| |                         | \+---------------------------------------------+ |  
| | Kategori:               | |                                             | |  
| | \[x\] Atasan              | |  \[ Gambar \]      \[ Gambar \]     \[ Gambar \]  | |  
| | \[ \] Bawahan             | |  \[TRENDING\]                                 | |  
| | \[ \] Gamis               | |  Tunik Molek     Kemeja Khaliqa Rok Plisket | |  
| |                         | |  Rp 150.000      Rp 120.000     Rp 90.000   | |  
| | Harga:                  | |                                             | |  
| | \[ Rp 0 \- Rp 200k \]      | |  \[ Gambar \]      \[ Gambar \]     \[ Gambar \]  | |  
| |                         | |                                             | |  
| | \[ Terapkan Filter \]     | |  \[ \< Sebelumnya \]  1  2  3  \[ Berikutnya \> \]| |  
| \+-------------------------+ \+---------------------------------------------+ |  
\+-----------------------------------------------------------------------------+

### **B. Point of Sale (POS) Layout**

Kecepatan adalah prioritas. Layar dibagi dua berdasarkan Golden Ratio: 62% untuk katalog/pencarian visual, 38% untuk eksekusi transaksi.

\+-----------------------------------------------------------------------------+  
| \[ NAV POS \]  Kasir Utama              \[Antrean Online (3)\]  \[Hold Cart\]     |  
\+-----------------------------------------------------------------------------+  
|                                      |                                      |  
| PENCARIAN & KATALOG (Col-8, 62%)     | KERANJANG & PEMBAYARAN (Col-4, 38%)  |  
|                                      |                                      |  
| \[ Cari SKU / Nama Baju... (Search) \] | Identitas: \[ Guest / Nama Member \]   |  
|                                      |                                      |  
|  \[ Gambar \]      \[ Gambar \]          | 1x Tunik Molek         Rp 150.000    |  
|  Tunik Molek     Rok Plisket         | 2x Rok Plisket         Rp 180.000    |  
|  Stok: 12        Stok: 8             | \------------------------------------ |  
|                                      | Sub Total:             Rp 330.000    |  
|  \[ Gambar \]      \[ Gambar \]          |                                      |  
|  Gamis Set       Kemeja Kotak        | Metode: (o) Cash  ( ) QRIS           |  
|  Stok: 3         Stok: 24            |                                      |  
|                                      | \[ HOLD CART \]  \[ BAYAR (Rp 330.000) \]|  
\+-----------------------------------------------------------------------------+

### **C. Admin Dashboard & ARIMA Tuning Layout**

Halaman kontrol tempat kecerdasan buatan dikelola. Menggunakan *layout* bersih agar Admin fokus menganalisa data numerik.

\+-----------------------------------------------------------------------------+  
| \[ SIDEBAR \] (Col-2)      | \[ KONTEN UTAMA DASHBOARD \] (Col-10)              |  
|                          |                                                  |  
| \- Dashboard              | \+----------------------------------------------+ |  
| \- Manajemen Produk       | | PANEL TUNING ARIMA (Col-4 / 38% area ini)      | |  
| \- Transaksi (Web & POS)  | | Produk: \[ Tunik Molek (v) \]                    | |  
| \- Pelanggan              | | Periode Prediksi: \[ 7 \] Hari                   | |  
|                          | |                                                | |  
| \> Analitik ARIMA         | | Parameter Data:                                | |  
|                          | | \[v\] Isi Tanggal Kosong dengan 0                | |  
| \- Pengaturan Sistem      | | \[v\] Haluskan Lonjakan (Outlier Smoothing)      | |  
|                          | |                                                | |  
|                          | | \[ JALANKAN PREDIKSI \]                          | |  
|                          | \+----------------------------------------------+ |  
|                          |                                                  |  
|                          | \+----------------------------------------------+ |  
|                          | | HASIL PREDIKSI & GRAFIK (Col-8 / 62% area ini) | |  
|                          | |                                                | |  
|                          | |  |                                             | |  
|                          | |  |     /\\               \- \- \- \- \- (Prediksi)   | |  
|                          | |  |    /  \\      /\\     \-                       | |  
|                          | |  |\_\_\_/\_\_\_\_\\\_\_\_\_/\_\_\\\_\_\_-\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_\_   | |  
|                          | |                                                | |  
|                          | | \[ MAPE Error: 12% \] \[ Eksekusi: 240ms \]        | |  
|                          | \+----------------------------------------------+ |  
\+-----------------------------------------------------------------------------+

## **Kesimpulan Implementasi Visual**

Dengan mematuhi dokumen *Design System* ini, pengembangan komponen *Front-End* di Laravel (melalui Vue.js dan Tailwind CSS) akan menghasilkan antarmuka yang sangat bersih (tanpa hiasan/emotikon yang mendistraksi), memiliki tata letak yang proporsional secara matematis (*Golden Ratio*), serta menonjolkan fitur-fitur krusial (seperti *ARIMA Card* dan *Cart*) melalui manipulasi warna tunggal \#FF1F8F.