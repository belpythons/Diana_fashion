# **DOKUMENTASI MICROSERVICE ARIMA & PIPELINE PEMANTAUAN**

**Proyek: Diana Fashion Omnichannel (Custom Laravel \+ Flask)**

Dokumen ini memandu pembuatan *microservice* Python (Flask) untuk pemrosesan algoritma ARIMA, serta bagaimana merakit **Pipeline Integrasi** agar Admin dapat melakukan *tuning* parameter, memantau log kinerja algoritma langsung dari antarmuka Admin Panel Laravel, dan menjalankan otomatisasi (*Cronjob*).

## **1\. Arsitektur Pipeline (Cara Kerja)**

Sistem menggunakan arsitektur pemisahan tugas (*Separation of Concerns*).

1. **Admin Panel (Vue.js):** Tempat Admin memilih produk, mengatur form *tuning* (Smoothing & Missing Dates), dan melihat log performa AI.  
2. **Laravel (API Proxy, Logger, & Automation):** Menerima *request* dari Vue, mengambil data historis dari *database* (status \= 'completed'), mengonversi waktu ke WITA, lalu meneruskannya ke Flask. Laravel juga menyimpan metrik performa AI ke tabel log dan menjalankan penarikan prediksi otomatis via *Cronjob*.  
3. **Flask (AI Microservice):** Menerima data matang, melakukan prapemrosesan (*Pandas*), menjalankan Auto-ARIMA, dan mengembalikan hasil prediksi beserta log metrik.

## **2\. Persiapan Skema Database Laravel (Tabel Log Prediksi)**

Untuk memantau aktivitas dan akurasi algoritma, kita perlu menambahkan satu tabel di Laravel untuk mencatat setiap kali sistem melakukan prediksi.

Jalankan: php artisan make:migration create\_prediction\_logs\_table

// File: database/migrations/xxxx\_create\_prediction\_logs\_table.php  
public function up()  
{  
    Schema::create('prediction\_logs', function (Blueprint $table) {  
        $table-\>id();  
        $table-\>foreignId('user\_id')-\>nullable()-\>constrained('users'); // Nullable jika dipicu oleh Cronjob otomatis  
        $table-\>string('product\_name');  
        $table-\>integer('forecast\_periods');  
        $table-\>json('tuning\_parameters'); // Simpan settingan tuning yang dipakai  
        $table-\>string('arima\_order'); // Contoh: (1, 1, 1\)  
        $table-\>decimal('mape\_score', 5, 2); // Simpan tingkat error (Akurasi)  
        $table-\>integer('execution\_time\_ms'); // Waktu proses algoritma  
        $table-\>timestamps();  
    });  
}

## **3\. Pembangunan Microservice Flask (Python)**

Buat folder terpisah dari proyek Laravel Anda untuk memisahkan ekosistem PHP dan Python.

### **A. Struktur Direktori Microservice (Directory Mapping)**

Pastikan struktur folder diana\_arima\_service Anda tertata seperti berikut sebelum melakukan instalasi:

diana\_arima\_service/  
│  
├── venv/                   \# Folder Virtual Environment (Dihasilkan saat instalasi)  
├── app.py                  \# Skrip utama berisi routing API dan logika Auto-ARIMA  
├── requirements.txt        \# File daftar pustaka/dependensi (Flask, pandas, dll)  
└── .env                    \# (Opsional) File untuk environment variables port/host

### **B. Instalasi Dependensi**

Buka terminal, masuk ke folder diana\_arima\_service, dan jalankan:

python \-m venv venv  
source venv/bin/activate  \# (Untuk Windows gunakan: venv\\Scripts\\activate)  
pip install flask pandas numpy pmdarima scikit-learn  
pip freeze \> requirements.txt

### **C. Kode Inti: app.py**

Skrip ini dilengkapi dengan pencatatan waktu eksekusi (*execution time*) dan penanganan metrik error.

from flask import Flask, request, jsonify  
import pandas as pd  
import numpy as np  
from pmdarima import auto\_arima  
from sklearn.metrics import mean\_absolute\_percentage\_error  
import time  
import warnings

warnings.filterwarnings("ignore")  
app \= Flask(\_\_name\_\_)

@app.route('/api/v1/predict', methods=\['POST'\])  
def predict():  
    start\_time \= time.time() \# Mulai catat waktu eksekusi  
      
    try:  
        payload \= request.get\_json()  
        product\_name \= payload.get('product\_name')  
        periods \= payload.get('forecast\_periods', 7\)  
        tuning \= payload.get('tuning\_parameters', {})  
        raw\_data \= payload.get('historical\_data', \[\])

        if len(raw\_data) \< 7:  
            return jsonify({"status": "error", "message": "Data historis tidak mencukupi (Minimal 7 record)."}), 400

        \# \--- 1\. PANDAS TUNING (PREPROCESSING) \---  
        df \= pd.DataFrame(raw\_data)  
        df\['date'\] \= pd.to\_datetime(df\['date'\])  
        df.set\_index('date', inplace=True)  
        df.sort\_index(inplace=True)

        \# Tuning: Missing Dates  
        if tuning.get('fill\_missing\_dates', True):  
            idx \= pd.date\_range(df.index.min(), df.index.max(), freq='D')  
            df \= df.reindex(idx, fill\_value=0)

        \# Tuning: Outlier Smoothing  
        if tuning.get('smooth\_outliers', True):  
            Q1 \= df\['total\_sales'\].quantile(0.25)  
            Q3 \= df\['total\_sales'\].quantile(0.75)  
            IQR \= Q3 \- Q1  
            upper\_bound \= Q3 \+ 1.5 \* IQR  
            df\['total\_sales'\] \= np.where(df\['total\_sales'\] \> upper\_bound, upper\_bound, df\['total\_sales'\])

        ts\_data \= df\['total\_sales'\].values

        \# \--- 2\. AUTO-ARIMA ENGINE \---  
        model \= auto\_arima(  
            ts\_data,   
            start\_p=0, start\_q=0, max\_p=5, max\_q=5,   
            d=None, seasonal=False, trace=False,   
            error\_action='ignore', suppress\_warnings=True, stepwise=True  
        )

        forecast\_values \= model.predict(n\_periods=periods)  
        order \= model.order \# Nilai (p, d, q) terbaik

        \# \--- 3\. EVALUASI (MAPE) \---  
        in\_sample\_preds \= model.predict\_in\_sample()  
        mape \= mean\_absolute\_percentage\_error(ts\_data, in\_sample\_preds) \* 100

        \# Susun data hasil  
        last\_date \= df.index.max()  
        future\_dates \= pd.date\_range(last\_date \+ pd.Timedelta(days=1), periods=periods, freq='D')  
          
        forecast\_result \= \[  
            {"date": future\_dates\[i\].strftime('%Y-%m-%d'), "predicted\_sales": max(0, int(round(val)))}  
            for i, val in enumerate(forecast\_values)  
        \]

        exec\_time \= int((time.time() \- start\_time) \* 1000\) \# dalam milidetik

        return jsonify({  
            "status": "success",  
            "arima\_order": f"({order\[0\]}, {order\[1\]}, {order\[2\]})",  
            "evaluation": {"mape\_score": round(mape, 2)},  
            "execution\_time\_ms": exec\_time,  
            "forecast\_result": forecast\_result  
        })

    except Exception as e:  
        return jsonify({"status": "error", "message": str(e)}), 500

if \_\_name\_\_ \== '\_\_main\_\_':  
    app.run(host='0.0.0.0', port=5000)

## **4\. Pipeline Laravel (Proxy, Logger, & Automation)**

### **A. Controller Proxy (Laravel \-\> Flask)**

Buat *Controller* di Laravel untuk menangani permintaan, mengonversi zona waktu ke WITA, mengirim ke Flask, lalu menyimpan Log.

// app/Http/Controllers/Admin/ArimaController.php  
namespace App\\Http\\Controllers\\Admin;

use App\\Http\\Controllers\\Controller;  
use Illuminate\\Http\\Request;  
use App\\Models\\Order;  
use App\\Models\\PredictionLog;  
use Illuminate\\Support\\Facades\\Http;  
use Illuminate\\Support\\Facades\\Cache;  
use Carbon\\Carbon;

class ArimaController extends Controller  
{  
    public function runPrediction(Request $request)  
    {  
        // 1\. Tarik & Agregasi Data Historis (Format WITA, Status Completed saja)  
        $historicalData \= Order::where('status', 'completed')  
            \-\>join('order\_items', 'orders.id', '=', 'order\_items.order\_id')  
            \-\>join('products', 'order\_items.product\_id', '=', 'products.id')  
            \-\>where('products.name', $request-\>product\_name)  
            \-\>get()  
            \-\>groupBy(function($order) {  
                return Carbon::parse($order-\>created\_at)-\>timezone('Asia/Makassar')-\>format('Y-m-d');  
            })  
            \-\>map(function ($row, $date) {  
                return \[  
                    'date' \=\> $date,  
                    'total\_sales' \=\> $row-\>sum('qty\_ordered')  
                \];  
            })-\>values()-\>toArray();

        // 2\. Tembak ke API Flask (Proxy Server)  
        $flaskResponse \= Http::post(env('ARIMA\_SERVICE\_URL', '\[http://127.0.0.1:5000\](http://127.0.0.1:5000)') . '/api/v1/predict', \[  
            'product\_name' \=\> $request-\>product\_name,  
            'forecast\_periods' \=\> $request-\>forecast\_periods,  
            'tuning\_parameters' \=\> $request-\>tuning\_parameters,  
            'historical\_data' \=\> $historicalData  
        \]);

        if ($flaskResponse-\>failed()) {  
            return response()-\>json(\['error' \=\> 'Gagal terhubung ke Microservice AI.'\], 500);  
        }

        $result \= $flaskResponse-\>json();

        // 3\. Simpan Log Pemantauan & Cache Hasil  
        if ($result\['status'\] \=== 'success') {  
            PredictionLog::create(\[  
                'user\_id' \=\> auth()-\>id() ?? null, // Null jika dipanggil oleh Cronjob  
                'product\_name' \=\> $request-\>product\_name,  
                'forecast\_periods' \=\> $request-\>forecast\_periods,  
                'tuning\_parameters' \=\> json\_encode($request-\>tuning\_parameters),  
                'arima\_order' \=\> $result\['arima\_order'\],  
                'mape\_score' \=\> $result\['evaluation'\]\['mape\_score'\],  
                'execution\_time\_ms' \=\> $result\['execution\_time\_ms'\],  
            \]);  
              
            // Simpan ke cache untuk ditampilkan di Storefront E-Commerce  
            Cache::put('arima\_recommendation\_' . md5($request-\>product\_name), $result, now()-\>addHours(24));  
        }

        return response()-\>json($result);  
    }

    public function getLogs()  
    {  
        return response()-\>json(PredictionLog::with('user:id,name')-\>latest()-\>take(10)-\>get());  
    }  
}

### **B. Otomatisasi Prediksi Harian (Cronjob WITA)**

Agar data prediksi dan "Badge Rekomendasi Tren" di halaman depan E-Commerce selalu *up-to-date* tanpa membebani server pada siang hari, kita membuat jadwal penarikan otomatis setiap jam 01:00 dini hari menggunakan zona waktu WITA.

Tambahkan kode ini pada file routes/console.php:

use Illuminate\\Support\\Facades\\Schedule;  
use App\\Http\\Controllers\\Admin\\ArimaController;  
use Illuminate\\Http\\Request;

// Menjadwalkan kalkulasi ARIMA otomatis setiap pukul 01:00 WITA (Asia/Makassar)  
Schedule::call(function () {  
    $controller \= app(ArimaController::class);  
      
    // Daftar produk unggulan yang ingin di-cache prediksinya secara otomatis setiap malam  
    $topProducts \= \['Tunik molek', 'rok plisket', 'gamis set outer alzea'\];  
      
    foreach ($topProducts as $productName) {  
        $request \= new Request(\[  
            'product\_name' \=\> $productName,  
            'forecast\_periods' \=\> 7,  
            'tuning\_parameters' \=\> \[  
                'fill\_missing\_dates' \=\> true,   
                'smooth\_outliers' \=\> true  
            \]  
        \]);  
          
        $controller-\>runPrediction($request);  
    }  
      
    \\Log::info("Cronjob: Pembaruan Data Prediksi ARIMA harian berhasil dieksekusi pada 01:00 WITA.");  
      
})-\>dailyAt('01:00')-\>timezone('Asia/Makassar');

## **5\. Implementasi Admin Panel (Vue.js)**

Buat komponen Vue.js di Admin Panel untuk menampilkan antarmuka *Tuning*, Grafik *Chart.js*, dan **Tabel Log Kinerja Algoritma**.

\<\!-- resources/js/Pages/Admin/ArimaDashboard.vue \--\>  
\<template\>  
  \<div class="arima-dashboard grid grid-cols-12 gap-6 p-6"\>  
      
    \<\!-- KOLOM KIRI: FORM TUNING \--\>  
    \<div class="col-span-12 md:col-span-4 bg-white p-5 rounded shadow"\>  
      \<h3 class="text-lg font-bold text-brand mb-4"\>Pengaturan & Tuning ARIMA\</h3\>  
      \<form @submit.prevent="runAI"\>  
        \<label class="block mb-2"\>Pilih Produk (Dari Excel):\</label\>  
        \<select v-model="form.product\_name" class="w-full border p-2 rounded mb-4"\>  
            \<option value="Tunik molek"\>Tunik molek\</option\>  
            \<option value="rok plisket"\>rok plisket\</option\>  
            \<option value="gamis set outer alzea"\>Gamis set outer alzea\</option\>  
        \</select\>

        \<label class="block mb-2"\>Periode Prediksi (Hari):\</label\>  
        \<input type="number" v-model="form.forecast\_periods" class="w-full border p-2 rounded mb-4" /\>

        \<div class="bg-gray-50 p-3 rounded mb-4"\>  
            \<p class="font-bold text-sm mb-2"\>Parameter Prapemrosesan:\</p\>  
            \<label class="flex items-center mb-2"\>  
                \<input type="checkbox" v-model="form.tuning\_parameters.fill\_missing\_dates" class="mr-2"\>  
                \<span class="text-sm"\>Isi Tanggal Kosong dengan 0\</span\>  
            \</label\>  
            \<label class="flex items-center"\>  
                \<input type="checkbox" v-model="form.tuning\_parameters.smooth\_outliers" class="mr-2"\>  
                \<span class="text-sm"\>Haluskan Lonjakan Outlier (IQR)\</span\>  
            \</label\>  
        \</div\>

        \<button type="submit" class="w-full bg-brand text-white p-2 rounded hover:bg-pink-700" :disabled="loading"\>  
            {{ loading ? 'Menganalisis...' : 'Jalankan AI Prediksi' }}  
        \</button\>  
      \</form\>  
    \</div\>

    \<\!-- KOLOM KANAN: VISUALISASI & LOG \--\>  
    \<div class="col-span-12 md:col-span-8 flex flex-col gap-6"\>  
          
        \<\!-- Area Grafik \--\>  
        \<div class="bg-white p-5 rounded shadow min-h-\[300px\]"\>  
            \<h3 class="text-lg font-bold mb-4"\>Hasil Proyeksi Tren\</h3\>  
            \<canvas id="predictionChart"\>\</canvas\>  
            \<div v-if="result" class="mt-4 flex gap-4 text-sm"\>  
                \<div class="p-3 bg-blue-50 text-blue-800 rounded"\>Model Ditemukan: \<b\>ARIMA {{ result.arima\_order }}\</b\>\</div\>  
                \<div class="p-3 bg-yellow-50 text-yellow-800 rounded"\>Kecepatan: \<b\>{{ result.execution\_time\_ms }} ms\</b\>\</div\>  
                \<div :class="result.evaluation.mape\_score \< 20 ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'" class="p-3 rounded"\>  
                    Error MAPE: \<b\>{{ result.evaluation.mape\_score }}%\</b\>  
                \</div\>  
            \</div\>  
        \</div\>

        \<\!-- Tabel Log Aktivitas AI \--\>  
        \<div class="bg-white p-5 rounded shadow"\>  
            \<h3 class="text-lg font-bold mb-4"\>Log Pemantauan Algoritma\</h3\>  
            \<table class="w-full text-sm text-left"\>  
                \<thead class="bg-gray-100"\>  
                    \<tr\>  
                        \<th class="p-2"\>Waktu Log\</th\>  
                        \<th class="p-2"\>Eksekutor\</th\>  
                        \<th class="p-2"\>Produk\</th\>  
                        \<th class="p-2"\>Parameter Tuning\</th\>  
                        \<th class="p-2"\>Model (p,d,q)\</th\>  
                        \<th class="p-2"\>MAPE (%)\</th\>  
                    \</tr\>  
                \</thead\>  
                \<tbody\>  
                    \<tr v-for="log in logs" :key="log.id" class="border-b"\>  
                        \<td class="p-2"\>{{ formatDate(log.created\_at) }}\</td\>  
                        \<td class="p-2"\>{{ log.user ? log.user.name : 'System (Cronjob)' }}\</td\>  
                        \<td class="p-2"\>{{ log.product\_name }}\</td\>  
                        \<td class="p-2 font-mono text-xs"\>{{ log.tuning\_parameters }}\</td\>  
                        \<td class="p-2"\>{{ log.arima\_order }}\</td\>  
                        \<td class="p-2" :class="log.mape\_score \< 20 ? 'text-green-600' : 'text-red-600'"\>  
                            \<b\>{{ log.mape\_score }}%\</b\>  
                        \</td\>  
                    \</tr\>  
                \</tbody\>  
            \</table\>  
        \</div\>

    \</div\>  
  \</div\>  
\</template\>

\<script setup\>  
import { ref, onMounted } from 'vue';  
import axios from 'axios';

const form \= ref({  
    product\_name: 'Tunik molek',  
    forecast\_periods: 7,  
    tuning\_parameters: { fill\_missing\_dates: true, smooth\_outliers: true }  
});

const loading \= ref(false);  
const result \= ref(null);  
const logs \= ref(\[\]);

const fetchLogs \= async () \=\> {  
    const res \= await axios.get('/api/admin/arima-logs');  
    logs.value \= res.data;  
};

const runAI \= async () \=\> {  
    loading.value \= true;  
    try {  
        const res \= await axios.post('/api/admin/predict', form.value);  
        result.value \= res.data;  
        // Panggil fungsi render Chart.js disini menggunakan result.value.forecast\_result  
          
        fetchLogs(); // Segarkan tabel log otomatis  
    } catch (e) {  
        alert("Gagal melakukan prediksi\!");  
    } finally {  
        loading.value \= false;  
    }  
};

onMounted(() \=\> {  
    fetchLogs();  
});

const formatDate \= (dateString) \=\> {  
    return new Date(dateString).toLocaleString('id-ID');  
}  
\</script\>

## **Ringkasan Alur Pemantauan (Monitoring Pipeline)**

1. Admin mencentang parameter *Tuning* dan klik prediksi. (Atau *Cronjob* memicu prediksi otomatis jam 01:00 WITA).  
2. Laravel mengirim data ke Flask dan menunggu respon.  
3. Flask menghitung **Milidetik** waktu eksekusi dan **MAPE (Persentase Error)** algoritma tersebut.  
4. Laravel menerima respon tersebut, menyimpannya ke tabel prediction\_logs di MySQL, dan meletakkannya di *Cache* untuk ditampilkan ke pelanggan web esok harinya.  
5. Vue.js Admin menampilkan **Tabel Pemantauan Kinerja** yang membedakan prediksi manual oleh Admin dan otomatis oleh Sistem (*Cronjob*).