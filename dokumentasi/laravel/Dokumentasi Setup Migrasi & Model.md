# **DOKUMENTASI MIGRASI & MODEL DATABASE**

**Proyek: Diana Fashion Omnichannel (Custom Laravel Architecture)**

Dokumen ini memuat panduan teknis untuk membangun struktur tabel (*Migrations*) dan representasi objek (*Models*) dari nol. Struktur ini didesain khusus agar efisien untuk pencarian *Debounce* Kasir, tahan terhadap bentrok data (*Race Condition*), dan siap menyuplai data *Time-Series* ke algoritma ARIMA.

## **TAHAP 1: Pembuatan Skema Tabel (Migrations)**

Kita akan membuat 4 tabel baru (selain modifikasi tabel users yang sudah dilakukan di tahap RBAC). Buka terminal dan jalankan perintah berikut secara berurutan:

php artisan make:migration create\_categories\_table  
php artisan make:migration create\_products\_table  
php artisan make:migration create\_orders\_table  
php artisan make:migration create\_order\_items\_table

Buka folder database/migrations/ dan isi masing-file dengan skema berikut:

### **1\. Tabel categories**

Sesuai struktur file Excel (Atasan, Bawahan, Gamis).

public function up(): void  
{  
    Schema::create('categories', function (Blueprint $table) {  
        $table-\>id();  
        $table-\>string('name')-\>unique();  
        $table-\>timestamps();  
    });  
}

### **2\. Tabel products (Katalog & Stok)**

Stok digabung di sini untuk mempermudah fitur *Atomic Lock* (lockForUpdate). Kolom sku di-*index* agar pencarian *debounce* di POS sangat cepat.

public function up(): void  
{  
    Schema::create('products', function (Blueprint $table) {  
        $table-\>id();  
        // Restrict onDelete: Cegah hapus kategori jika masih ada produk di dalamnya  
        $table-\>foreignId('category\_id')-\>constrained()-\>restrictOnDelete();  
          
        $table-\>string('sku')-\>unique()-\>index(); // Kunci kecepatan pencarian POS  
        $table-\>string('name');  
        $table-\>integer('stock')-\>default(0); // Stok aktual real-time  
        $table-\>decimal('price', 12, 2);  
        $table-\>string('image\_url')-\>nullable();  
        $table-\>timestamps();  
    });  
}

### **3\. Tabel orders (Header Transaksi / State Machine)**

Tabel ini merekam transaksi dari Web dan POS. Status diatur ketat (pending, completed, canceled) untuk mengontrol alur antrean.

public function up(): void  
{  
    Schema::create('orders', function (Blueprint $table) {  
        $table-\>id();  
        $table-\>string('increment\_id')-\>unique(); // Contoh: ORD-2026-001  
          
        // Nullable karena pelanggan fisik (POS) bisa checkout tanpa akun (Guest)  
        $table-\>foreignId('user\_id')-\>nullable()-\>constrained('users')-\>nullOnDelete();   
          
        $table-\>enum('channel', \['web', 'pos'\]); // Penanda sumber transaksi  
        $table-\>enum('status', \['pending', 'completed', 'canceled'\]); // State Machine  
          
        $table-\>decimal('total\_price', 12, 2);  
        $table-\>string('payment\_method')-\>nullable(); // 'whatsapp\_transfer', 'cash', 'qris'  
          
        $table-\>timestamps(); // created\_at ini yang akan disedot oleh Pandas ARIMA  
    });  
}

### **4\. Tabel order\_items (Rincian Pesanan)**

Menyimpan barang apa saja yang dibeli di dalam sebuah nota pesanan.

public function up(): void  
{  
    Schema::create('order\_items', function (Blueprint $table) {  
        $table-\>id();  
        // Cascade onDelete: Jika order dihapus, itemnya otomatis ikut terhapus  
        $table-\>foreignId('order\_id')-\>constrained()-\>cascadeOnDelete();  
        $table-\>foreignId('product\_id')-\>constrained();  
          
        $table-\>integer('qty');  
        // Menyimpan harga SAAT TRANSAKSI TERJADI (Kebal terhadap perubahan harga produk di masa depan)  
        $table-\>decimal('price\_at\_purchase', 12, 2);   
          
        $table-\>timestamps();  
    });  
}

**Eksekusi Migrasi:**

Setelah semua file di atas disalin, jalankan ke database MySQL Anda:

php artisan migrate

## **TAHAP 2: Pembuatan Model dan Relasi (Eloquent ORM)**

Model berfungsi untuk menghubungkan tabel-tabel di atas agar Laravel bisa mengambil data gabungan (seperti *"Tampilkan semua order item dari order milik pelanggan A"*) tanpa query SQL manual yang rumit.

Jalankan perintah ini untuk membuat file Model:

php artisan make:model Category  
php artisan make:model Product  
php artisan make:model Order  
php artisan make:model OrderItem

Buka folder app/Models/ dan tambahkan aturan relasi (Relationships) dan $fillable (keamanan *Mass Assignment*) berikut:

### **1\. Model User.php**

*(Tambahkan ini pada model User bawaan Laravel)*

public function orders()  
{  
    // Satu pelanggan bisa memiliki banyak pesanan  
    return $this-\>hasMany(Order::class);  
}

### **2\. Model Category.php**

namespace App\\Models;  
use Illuminate\\Database\\Eloquent\\Model;

class Category extends Model  
{  
    protected $fillable \= \['name'\];

    public function products()  
    {  
        // Satu kategori memiliki banyak produk  
        return $this-\>hasMany(Product::class);  
    }  
}

### **3\. Model Product.php**

namespace App\\Models;  
use Illuminate\\Database\\Eloquent\\Model;

class Product extends Model  
{  
    protected $fillable \= \['category\_id', 'sku', 'name', 'stock', 'price', 'image\_url'\];

    public function category()  
    {  
        // Setiap produk pasti milik satu kategori  
        return $this-\>belongsTo(Category::class);  
    }

    public function orderItems()  
    {  
        // Produk ini pernah dibeli di rincian pesanan mana saja  
        return $this-\>hasMany(OrderItem::class);  
    }  
}

### **4\. Model Order.php**

namespace App\\Models;  
use Illuminate\\Database\\Eloquent\\Model;

class Order extends Model  
{  
    protected $fillable \= \[  
        'increment\_id', 'user\_id', 'channel', 'status', 'total\_price', 'payment\_method'  
    \];

    public function user()  
    {  
        // Order ini milik siapa (Bisa null jika Guest Kasir)  
        return $this-\>belongsTo(User::class);  
    }

    public function items()  
    {  
        // Satu nota order berisi banyak item baju  
        return $this-\>hasMany(OrderItem::class, 'order\_id');  
    }  
}

### **5\. Model OrderItem.php**

namespace App\\Models;  
use Illuminate\\Database\\Eloquent\\Model;

class OrderItem extends Model  
{  
    protected $fillable \= \['order\_id', 'product\_id', 'qty', 'price\_at\_purchase'\];

    public function order()  
    {  
        return $this-\>belongsTo(Order::class);  
    }

    public function product()  
    {  
        // Mengetahui baju apa ini  
        return $this-\>belongsTo(Product::class);  
    }  
}

## **KESIMPULAN ARSITEKTUR DATA**

Dengan struktur Migrasi dan Model di atas:

1. **Pencarian Cepat POS:** Kasir mencari produk, API memanggil Product::where('sku', 'like', "%$keyword%")-\>get(). Karena ada klausa \-\>index() di migrasi SKU, pencarian ini akan berjalan dalam satuan milidetik.  
2. **Keamanan Transaksi:** Model Product menampung kolom stock. Ini mempersiapkan sistem untuk bisa menggunakan metode lockForUpdate() milik Laravel Eloquent saat mencegah *Race Condition* di *Controller Checkout* nanti.  
3. **Integritas Historis:** Dengan kolom price\_at\_purchase pada order\_items, riwayat pendapatan toko Diana Fashion tidak akan tiba-tiba berubah jika admin menaikkan harga baju master di bulan berikutnya.