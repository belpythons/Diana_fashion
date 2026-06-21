# Diana Fashion - Omnichannel & ARIMA Sales Forecasting 👗📈

> **Status:** Active Development / Hybrid Architecture Project

**Diana Fashion** adalah platform *omnichannel e-commerce* cerdas yang dirancang tidak hanya untuk mengelola penjualan dari berbagai saluran, tetapi juga dilengkapi dengan kemampuan analitik tingkat lanjut. Sistem ini menggunakan model statistik **ARIMA (AutoRegressive Integrated Moving Average)** untuk memprediksi tren pendapatan penjualan di masa depan.

Aplikasi ini mengadopsi arsitektur *hybrid* (*Microservices-lite*), di mana proses transaksional (*Core App*) dipisahkan dari pemrosesan data analitik (*AI/Data Service*) untuk menjaga performa sistem utama.

## ✨ Arsitektur & Modul Utama

Sistem ini terbagi menjadi dua *service* independen:

### 1. Core Application (Diana Fashion App)
Dibangun menggunakan **VILT Stack** (Vue.js, Inertia, Laravel, Tailwind CSS) untuk mengelola antarmuka pengguna dan logika bisnis.
* **Admin Dashboard:** Manajemen produk, kategori, pelanggan, dan staf. Dilengkapi dengan tampilan khusus untuk memonitor hasil prediksi ARIMA.
* **Storefront (Customer Portal):** Antarmuka pelanggan untuk berbelanja, melihat keranjang (*cart*), *checkout*, dan melacak riwayat pesanan.
* **Omnichannel Management:** Penanganan pesanan lintas saluran penjualan.
* **Inventory Control:** Modul peringatan stok rendah (*Low Stock Alerts*).

### 2. Analytics Service (Diana ARIMA Service)
Microservice berbasis **Python (Flask)** yang dikhususkan untuk komputasi statistik berat.
* **ARIMA Forecasting:** Mengambil agregasi data penjualan historis (via *Materialized Views*) dan menghitung prediksi pendapatan harian.
* **Tuning Configuration:** Menerima parameter *tuning* (p, d, q) secara dinamis dari *dashboard* Admin Laravel.
* **Prediction Logging:** Mencatat setiap hasil eksekusi model ARIMA untuk keperluan audit dan evaluasi akurasi.

## 🚀 Panduan Instalasi Lokal

Karena proyek ini menggunakan dua *service* berbeda, Anda perlu menjalankan keduanya secara bersamaan.

### A. Menjalankan Core App (Laravel)
```bash
# 1. Masuk ke direktori aplikasi utama
cd diana_fashion_app

# 2. Instalasi dependensi
composer install
npm install

# 3. Konfigurasi .env (Database)
cp .env.example .env
php artisan key:generate

# 4. Migrasi dan Seeding (Terdapat dummy data penjualan untuk testing ARIMA)
php artisan migrate --seed

# 5. Jalankan server
php artisan serve
npm run dev

### B. Menjalankan ARIMA Service (Flask)

Disarankan menggunakan *Virtual Environment* (`venv`).

```bash
# 1. Buka terminal baru, masuk ke direktori service
cd diana_arima_service

# 2. Aktifkan Virtual Environment (Windows)
venv\Scripts\activate
# Atau untuk Linux/macOS: source venv/bin/activate

# 3. Instalasi dependensi (pandas, statsmodels, flask, dll)
pip install -r requirements.txt

# 4. Jalankan Flask API
python app.py

```


