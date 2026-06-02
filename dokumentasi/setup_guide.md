# Diana Fashion Omnichannel — Panduan Instalasi & Setup Aplikasi

Panduan ini merinci seluruh langkah yang diperlukan untuk memasang, mengonfigurasi, dan menjalankan aplikasi **Diana Fashion Omnichannel** pada lingkungan lokal (development) maupun produksi.

Sistem ini terdiri dari dua komponen utama:
1. **Backend & Frontend SPA (`diana_fashion_app`):** Kerangka Laravel 11 terintegrasi dengan Vue 3 SPA murni (Multi-Entry Vite Bundler & Tailwind CSS v4).
2. **Flask ARIMA Microservice (`diana_arima_service`):** Layanan microservice berbasis Python untuk peramalan AI penjualan harian.

---

## 1. Persyaratan Sistem (Prerequisites)

Sebelum memulai, pastikan perangkat Anda telah memenuhi spesifikasi berikut:
* **Operating System:** Windows (Disarankan menggunakan stack **Laragon**), Linux, atau macOS.
* **PHP:** Versi 8.2 atau lebih baru (Lengkap dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `xml`, `bcmath`).
* **Node.js:** Versi 18.x atau lebih baru (Berserta NPM).
* **Python:** Versi 3.10 atau lebih baru (Berserta package manager `pip` dan `virtualenv`).
* **Database:** MySQL versi 8.0 atau MariaDB terbaru.
* **Composer:** Versi 2.x.

---

## 2. Setup Database & Lingkungan MySQL

Jika Anda menggunakan **Laragon** di Windows (sangat disarankan):
1. Jalankan control panel **Laragon**.
2. Klik tombol **"Start All"** untuk menyalakan MySQL Server dan Apache/Nginx.
3. Buka HeidiSQL (atau phpMyAdmin) bawaan Laragon, masuk ke koneksi lokal (`host: 127.0.0.1`, `user: root`, tanpa password).
4. Buat sebuah database kosong baru bernama **`diana_fashion_db`** dengan collation `utf8mb4_unicode_ci`.

---

## 3. Langkah Instalasi Backend & Frontend SPA (`diana_fashion_app`)

Buka terminal/PowerShell Anda, lalu ikuti langkah-langkah berikut:

### 3.1 Clone & Masuk ke Direktori Aplikasi
```bash
cd c:\laragon\www\Diana_fashion\diana_fashion_app
```

### 3.2 Pasang Dependensi Composer (PHP)
```bash
composer install
```

### 3.3 Konfigurasi Environment File (`.env`)
Salin berkas `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```
Buka berkas `.env` dan sesuaikan parameter berikut:
```env
APP_NAME="Diana Fashion"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=Asia/Makassar
APP_URL=http://localhost:8000

# Konfigurasi MySQL Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=diana_fashion_db
DB_USERNAME=root
DB_PASSWORD=

# Integrasi Microservice AI (Flask) & WhatsApp Checkout
ARIMA_SERVICE_URL=http://127.0.0.1:5000
WHATSAPP_NUMBER=628123456789
```

### 3.4 Generate Application Key
```bash
php artisan key:generate
```

### 3.5 Jalankan Migrasi Database & Seeding Awal
Perintah ini akan membuat seluruh tabel, indeks FULLTEXT pada produk, dan mengisi data awal akun kasir, admin, pelanggan, kategori, dan barang sampel:
```bash
php artisan migrate --seed
```

### 3.6 Pasang Dependensi Node.js & Compile Awal (Frontend)
```bash
npm install
```
* **Untuk Lingkungan Development (Hot Module Replacement):**
  ```bash
  npm run dev
  ```
* **Untuk Lingkungan Produksi (Kompilasi Ramping):**
  ```bash
  npm run build
  ```

---

## 4. Langkah Instalasi Flask ARIMA Microservice (`diana_arima_service`)

Buka tab terminal baru untuk mengonfigurasi microservice AI berbasis Python:

### 4.1 Masuk ke Direktori Microservice
```bash
cd c:\laragon\www\Diana_fashion\diana_arima_service
```

### 4.2 Buat & Aktifkan Virtual Environment (Venv)
* **Windows (PowerShell):**
  ```powershell
  python -m venv venv
  .\venv\Scripts\Activate.ps1
  ```
* **Linux / macOS:**
  ```bash
  python3 -m venv venv
  source venv/bin/activate
  ```

### 4.3 Pasang Dependensi Pustaka Python
```bash
pip install -r requirements.txt
```

### 4.4 Konfigurasi Environment File (`.env`)
Buat berkas `.env` baru di folder `diana_arima_service/` dan isi:
```env
FLASK_APP=app.py
FLASK_ENV=development
FLASK_HOST=127.0.0.1
FLASK_PORT=5000
```

---

## 5. Menjalankan Aplikasi di Lingkungan Lokal (Lokal Dev Server)

Untuk menjalankan seluruh stack sistem Diana Fashion Omnichannel secara bersamaan, buka **3 jendela/tab terminal terpisah**:

### 💻 Terminal 1: Backend API & Router Laravel
```bash
cd c:\laragon\www\Diana_fashion\diana_fashion_app
php artisan serve
```
*Layanan berjalan di:* [http://127.0.0.1:8000](http://127.0.0.1:8000)

### 💻 Terminal 2: Bundler Dev Vite (Frontend Vue 3 Reaktif)
```bash
cd c:\laragon\www\Diana_fashion\diana_fashion_app
npm run dev
```
*Layanan berjalan di:* [http://localhost:5173](http://localhost:5173) (Vite HMR secara otomatis menyalurkan aset reaktif ke Laravel server).

### 💻 Terminal 3: Flask ARIMA Microservice
```bash
cd c:\laragon\www\Diana_fashion\diana_arima_service
# Pastikan venv aktif (ditandai dengan awalan (venv) di terminal)
python app.py
```
*Layanan berjalan di:* [http://127.0.0.1:5000](http://127.0.0.1:5000)

---

## 6. Akun Bawaan (Default Credentials)

Setelah Anda menjalankan seeder database di langkah 3.5, akun-akun berikut siap digunakan untuk login ke storefront, POS, maupun dashboard admin:

| Peran (Role) | Email Login | Kata Sandi (Password) | Hak Akses Rute |
|--------------|-------------|-----------------------|----------------|
| **Administrator** | `admin@diana.com` | `password` | `/admin` (Manajemen data, Staff, Prediksi AI, Ekspor CSV) |
| **Kasir (Staf POS)**| `kasir@diana.com` | `password` | `/pos` (Transaksi Kasir, Antrean Pesanan Online, Parkir Cart) |
| **Pelanggan** | `customer@diana.com` | `password` | `/customer` (Riwayat Transaksi, Edit Profil, Publik Catalog) |

---

## 7. Setup Penjadwalan Tugas Otomatis (Schedulers)

Sistem ini memiliki dua tugas terjadwal yang krusial untuk operasional omnichannel:
1. **ARIMA Harian (Jam 01:00 WITA):** Mengalkulasi otomatis proyeksi AI untuk top 5 produk terlaris dalam 30 hari terakhir.
2. **Auto-Cancel Pesanan (Setiap Jam):** Membatalkan pesanan web pending yang berusia > 12 jam dan mengembalikan (*restock*) stok produk secara aman.

### 7.1 Menjalankan di Lingkungan Lokal (Development)
Untuk menyimulasikan scheduler di komputer lokal, jalankan perintah ini di terminal:
```bash
php artisan schedule:work
```

### 7.2 Menjalankan di Lingkungan Produksi (Live Server)
Tambahkan entri *Cronjob* berikut ke sistem operasi Linux server produksi Anda:
```cron
* * * * * cd /path-to-your-project/diana_fashion_app && php artisan schedule:run >> /dev/null 2>&1
```

---

## 8. Verifikasi Penyiapan Aplikasi

Untuk memastikan seluruh setup berjalan 100% sempurna, Anda dapat mengakses tautan pengujian berikut di browser Anda:
* **Storefront E-Commerce:** [http://localhost:8000/](http://localhost:8000/)
* **Terminal POS Kasir:** [http://localhost:8000/pos](http://localhost:8000/pos) (Akan dialihkan ke `/login` jika belum masuk sebagai kasir).
* **Dashboard Admin Panel:** [http://localhost:8000/admin](http://localhost:8000/admin) (Akan memicu 403 jika diakses tanpa akun admin).
* **Flask AI Health Check:** [http://localhost:5000/api/v1/health](http://localhost:5000/api/v1/health) (Harus merespons `{"service":"diana-arima-service","status":"ok"}`).
* **Menjalankan Seluruh Uji Fitur Otomatis:**
  ```bash
  php artisan test
  ```
  *(Memastikan seluruh 22 test cases lulus dengan status **PASSED**).*
