# **DOKUMENTASI SETUP AWAL & RBAC (ROLE-BASED ACCESS CONTROL)**

**Sistem Terintegrasi Toko Diana Fashion (Custom Laravel 11 \+ Vue.js)**

Dokumen ini memandu tim pengembang dari tahap instalasi awal proyek hingga pengaturan struktur keamanan (Autentikasi). Sistem ini dirancang menggunakan **Satu Gerbang Login Terpusat**, di mana sistem akan secara otomatis mengarahkan pengguna ke ruang kerjanya masing-masing berdasarkan *Role* (Admin, Kasir, atau Pelanggan).

## **TAHAP 1: Inisialisasi Teknologi (Tech Setup)**

Kita akan menggunakan **Laravel 11** dan memanfaatkan **Laravel Breeze (Vue/Inertia)** sebagai kerangka awal yang menyediakan fitur *Login/Register* bawaan yang siap dimodifikasi.

**1\. Membuat Proyek Laravel Baru:**

Buka terminal dan jalankan perintah berikut:

composer create-project laravel/laravel diana\_fashion\_app  
cd diana\_fashion\_app

**2\. Instalasi Starter Kit (Breeze \+ Vue):**

composer require laravel/breeze \--dev  
php artisan breeze:install vue

*Catatan: Perintah ini otomatis memasang Vue.js 3, Tailwind CSS, dan Inertia.js ke dalam proyek Anda.*

**3\. Konfigurasi Environment (.env):**

Buka file .env, sesuaikan nama database (pastikan Anda sudah membuat database kosong di MySQL bernama diana\_fashion\_db):

DB\_CONNECTION=mysql  
DB\_HOST=127.0.0.1  
DB\_PORT=3306  
DB\_DATABASE=diana\_fashion\_db  
DB\_USERNAME=root  
DB\_PASSWORD=

## **TAHAP 2: Setup Migrasi Database (Fokus pada Tabel Users)**

Kita harus memodifikasi tabel users bawaan Laravel untuk mengakomodasi sistem multi-role.

**1\. Mengedit File Migration users:**

Buka file di database/migrations/0001\_01\_01\_000000\_create\_users\_table.php (tanggal bisa berbeda). Tambahkan kolom role dengan tipe *Enum*.

public function up(): void  
{  
    Schema::create('users', function (Blueprint $table) {  
        $table-\>id();  
        $table-\>string('name');  
        $table-\>string('email')-\>unique();  
        $table-\>timestamp('email\_verified\_at')-\>nullable();  
        $table-\>string('password');  
          
        // TAMBAHKAN BARIS INI UNTUK RBAC  
        $table-\>enum('role', \['admin', 'kasir', 'pelanggan'\])-\>default('pelanggan');  
          
        $table-\>rememberToken();  
        $table-\>timestamps();  
    });

    // ... (kode reset password & sessions biarkan default)  
}

**2\. Jalankan Migrasi:**

php artisan migrate

## **TAHAP 3: Setup RBAC & Satu Gerbang Login (Unified Gateway)**

Bagian ini memodifikasi logika bawaan Laravel agar sistem *Login* dan *Register* beroperasi sesuai aturan bisnis Diana Fashion.

### **A. Modifikasi Logika Register (Khusus Pelanggan)**

Kita harus memastikan bahwa siapapun yang mendaftar melalui halaman registrasi web otomatis menjadi pelanggan dan langsung diarahkan ke beranda E-Commerce.

Buka file app/Http/Controllers/Auth/RegisteredUserController.php:

public function store(Request $request): RedirectResponse  
{  
    $request-\>validate(\[  
        'name' \=\> 'required|string|max:255',  
        'email' \=\> 'required|string|lowercase|email|max:255|unique:'.User::class,  
        'password' \=\> \['required', 'confirmed', Rules\\Password::defaults()\],  
    \]);

    $user \= User::create(\[  
        'name' \=\> $request-\>name,  
        'email' \=\> $request-\>email,  
        'password' \=\> Hash::make($request-\>password),  
        'role' \=\> 'pelanggan', // PAKSA ROLE HANYA UNTUK PELANGGAN  
    \]);

    event(new Registered($user));  
    Auth::login($user);

    // Arahkan kembali ke halaman depan E-Commerce  
    return redirect(route('home', absolute: false));  
}

*(Catatan: Akun Admin dan Kasir TIDAK DIBUAT lewat form register ini, melainkan dibuat dari dalam Dashboard Admin).*

### **B. Modifikasi Logika Login Terpusat (Gateway Redirector)**

Saat *user* memasukkan email dan password, sistem harus mengecek *role*\-nya dan melemparnya ke kanal yang benar.

Buka file app/Http/Controllers/Auth/AuthenticatedSessionController.php:

public function store(LoginRequest $request): RedirectResponse  
{  
    $request-\>authenticate();  
    $request-\>session()-\>regenerate();

    // LOGIKA PENGECEKAN ROLE (SATU GERBANG)  
    $role \= $request-\>user()-\>role;

    if ($role \=== 'admin') {  
        return redirect()-\>intended(route('admin.dashboard', absolute: false));  
    } elseif ($role \=== 'kasir') {  
        return redirect()-\>intended(route('pos.terminal', absolute: false));  
    } else {  
        // Jika pelanggan, arahkan ke beranda atau portal customer  
        return redirect()-\>intended(route('home', absolute: false));  
    }  
}

## **TAHAP 4: Pembuatan Middleware (Pelindung Rute)**

*Middleware* bertindak sebagai satpam satpam yang menjaga pintu masuk. Jika Pelanggan mencoba mengakses URL /admin, *middleware* akan menendangnya keluar.

**1\. Buat Middleware:**

Jalankan perintah di terminal:

php artisan make:middleware RoleMiddleware

**2\. Isi Logika Middleware:**

Buka file app/Http/Middleware/RoleMiddleware.php:

namespace App\\Http\\Middleware;

use Closure;  
use Illuminate\\Http\\Request;  
use Symfony\\Component\\HttpFoundation\\Response;

class RoleMiddleware  
{  
    public function handle(Request $request, Closure $next, $role): Response  
    {  
        // Jika user belum login, atau role-nya tidak cocok dengan yang diminta di Route  
        if (\!$request-\>user() || $request-\>user()-\>role \!== $role) {  
            // Tolak akses dengan kode 403 Forbidden  
            abort(403, 'Akses Ditolak. Anda tidak memiliki izin ke halaman ini.');  
        }

        return $next($request);  
    }  
}

**3\. Daftarkan Middleware di Laravel 11:**

Buka file bootstrap/app.php dan daftarkan *alias* untuk middleware tersebut:

\-\>withMiddleware(function (Middleware $middleware) {  
    // Daftarkan middleware role  
    $middleware-\>alias(\[  
        'role' \=\> \\App\\Http\\Middleware\\RoleMiddleware::class,  
    \]);  
})

## **TAHAP 5: Implementasi Keamanan pada File Routing**

Sekarang kita terapkan pelindung (*Middleware*) tersebut ke dalam file routes/web.php. Struktur ini memastikan pemisahan akses yang sangat ketat.

Buka file routes/web.php:

\<?php  
use Illuminate\\Support\\Facades\\Route;

// 1\. RUTE PUBLIK (E-Commerce Storefront)  
Route::get('/', \[StorefrontController::class, 'index'\])-\>name('home');

require \_\_DIR\_\_.'/auth.php'; // Memuat rute Login/Register bawaan Breeze

// 2\. RUTE KHUSUS PELANGGAN ONLINE  
Route::middleware(\['auth', 'role:pelanggan'\])-\>prefix('customer')-\>group(function () {  
    Route::get('/dashboard', \[CustomerPortalController::class, 'dashboard'\])-\>name('customer.dashboard');  
    // Rute riwayat pesanan pelanggan...  
});

// 3\. RUTE KHUSUS KASIR (Modul POS)  
Route::middleware(\['auth', 'role:kasir'\])-\>prefix('pos')-\>group(function () {  
    Route::get('/', \[PosController::class, 'index'\])-\>name('pos.terminal');  
    // Rute transaksi kasir...  
});

// 4\. RUTE KHUSUS ADMIN (Dashboard Back-Office)  
Route::middleware(\['auth', 'role:admin'\])-\>prefix('admin')-\>group(function () {  
    Route::get('/dashboard', \[AdminDashboardController::class, 'index'\])-\>name('admin.dashboard');  
    // Rute manajemen produk, laporan, prediksi ARIMA, dan pembuatan akun Kasir...  
});

## **KESIMPULAN SETUP**

Dengan menyelesaikan tahap di atas, Anda telah membangun fondasi arsitektur **Clean Custom Laravel** yang kokoh.

* Terdapat satu form login (/login) yang pintar membaca siapa yang masuk.  
* Pelanggan luar hanya bisa mendaftar sebagai pelanggan.  
* Keamanan tiap kanal (Web, POS, Admin) dijaga ketat oleh RoleMiddleware.  
  Langkah selanjutnya adalah membangun antarmuka (*UI*) untuk masing-masing halaman menggunakan Vue.js\!