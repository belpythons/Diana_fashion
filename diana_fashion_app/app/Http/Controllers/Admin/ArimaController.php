<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PredictionLog;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;
use App\Models\ArimaTuningConfig;

class ArimaController extends Controller
{
    // Resolusi #4 & #5: Jembatan tunggal (proxy) untuk komunikasi Laravel ke Flask ARIMA
    public function runPrediction(Request $request)
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'forecast_periods' => ['nullable', 'integer', 'min:1', 'max:30'],
            'fill_missing_dates' => ['nullable', 'boolean'],
            'smooth_outliers' => ['nullable', 'boolean']
        ]);

        $product = Product::findOrFail($request->product_id);

        // Load persistent settings from database
        $settings = Setting::all()->pluck('typed_value', 'key');

        // Cari custom tuning config untuk produk ini
        $customConfig = ArimaTuningConfig::where('product_id', $product->id)->first();

        $periods = $request->filled('forecast_periods') 
            ? intval($request->forecast_periods) 
            : ($customConfig ? $customConfig->forecast_periods : ($settings['arima_forecast_periods'] ?? 7));

        $tuningParams = [
            'fill_missing_dates' => $request->has('fill_missing_dates') 
                ? $request->boolean('fill_missing_dates') 
                : ($customConfig ? $customConfig->fill_missing_dates : ($settings['arima_fill_missing_dates'] ?? true)),
            'smooth_outliers' => $request->has('smooth_outliers') 
                ? $request->boolean('smooth_outliers') 
                : ($customConfig ? $customConfig->smooth_outliers : ($settings['arima_smooth_outliers'] ?? true)),
            'start_p' => $request->has('start_p') ? intval($request->start_p) : ($customConfig ? $customConfig->start_p : ($settings['arima_start_p'] ?? 0)),
            'start_q' => $request->has('start_q') ? intval($request->start_q) : ($customConfig ? $customConfig->start_q : ($settings['arima_start_q'] ?? 0)),
            'max_p' => $request->has('max_p') ? intval($request->max_p) : ($customConfig ? $customConfig->max_p : ($settings['arima_max_p'] ?? 5)),
            'max_q' => $request->has('max_q') ? intval($request->max_q) : ($customConfig ? $customConfig->max_q : ($settings['arima_max_q'] ?? 5)),
            'seasonal' => $request->has('seasonal') ? $request->boolean('seasonal') : ($customConfig ? $customConfig->seasonal : ($settings['arima_seasonal'] ?? false)),
            'stepwise' => $request->has('stepwise') ? $request->boolean('stepwise') : ($customConfig ? $customConfig->stepwise : ($settings['arima_stepwise'] ?? true)),
        ];

        // 1. Tarik Data Penjualan Historis
        // Menggunakan SUM(order_items.qty) secara konsisten (Resolusi #3: menggunakan qty)
        // Dan mengagregasikan per hari (konversi ke WITA/Asia/Makassar)
        // Di Laragon/Windows lokal, waktu database default biasanya mengikuti sistem lokal (WITA / +08:00)
        $salesData = OrderItem::select(
                DB::raw('DATE(orders.created_at) as date'),
                DB::raw('SUM(order_items.qty) as total_sales')
            )
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('order_items.product_id', $product->id)
            ->where('orders.status', 'completed')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // 2. Validasi jumlah data historis (minimal 7 data penjualan unik per hari)
        if ($salesData->count() < 7) {
            return response()->json([
                'message' => "Data historis tidak mencukupi untuk peramalan ARIMA. Minimal dibutuhkan data penjualan dari 7 hari yang berbeda. Data saat ini: {$salesData->count()} hari."
            ], 422);
        }

        // Format data historis untuk dikirim ke Flask
        $historicalData = $salesData->map(function ($row) {
            return [
                'date' => $row->date,
                'total_sales' => intval($row->total_sales)
            ];
        })->toArray();

        // 3. Panggil Layanan Flask ARIMA Microservice
        $flaskUrl = config('services.arima.url', 'http://127.0.0.1:5000') . '/api/v1/predict';

        try {
            $response = Http::timeout(15)->post($flaskUrl, [
                'product_name' => $product->name,
                'forecast_periods' => $periods,
                'tuning_parameters' => $tuningParams,
                'historical_data' => $historicalData
            ]);

            if ($response->failed()) {
                return response()->json([
                    'message' => 'Layanan ARIMA Microservice mengembalikan error.',
                    'details' => $response->body()
                ], 502);
            }

            $result = $response->json();

            // 4. Catat ke Tabel Prediction Logs (Resolusi #4: PredictionLog model)
            $log = PredictionLog::create([
                'user_id' => auth()->id(),
                'product_name' => $product->name,
                'forecast_periods' => $periods,
                'tuning_parameters' => $tuningParams,
                'arima_order' => $result['arima_order'] ?? 'N/A',
                'mape_score' => $result['evaluation']['mape_score'] ?? 0.0,
                'execution_time_ms' => $result['execution_time_ms'] ?? 0
            ]);

            // Update atau buat config tuning per produk dengan last_tuned_at = now()
            if ($customConfig) {
                $customConfig->update(['last_tuned_at' => now()]);
            } else {
                ArimaTuningConfig::create(array_merge($tuningParams, [
                    'product_id' => $product->id,
                    'forecast_periods' => $periods,
                    'last_tuned_at' => now()
                ]));
            }

            // 5. Caching Hasil untuk Badge Storefront (Resolusi #9)
            // Simpan produk ini beserta hasil prediksinya ke cache rekomendasi storefront
            // Sebagai tanda bahwa produk ini sedang trending di-predict
            $recommendations = Cache::get('arima_storefront_recommendations', collect());
            
            // Format ulang cache agar berisi produk-produk terlaris dengan badge ARIMA
            $updatedRecs = collect([$product])->concat(
                Product::with('category')
                    ->where('id', '!=', $product->id)
                    ->orderBy('stock', 'desc')
                    ->take(3)
                    ->get()
            )->take(4);

            Cache::put('arima_storefront_recommendations', $updatedRecs, now()->addHours(12));

            return response()->json([
                'message' => 'Prediksi ARIMA berhasil dijalankan.',
                'log' => $log->load('user:id,name'),
                'arima_order' => $result['arima_order'] ?? 'N/A',
                'mape_score' => $result['evaluation']['mape_score'] ?? 0.0,
                'mape_method' => $result['evaluation']['mape_method'] ?? 'unknown',
                'forecast_result' => $result['forecast_result'] ?? []
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal terhubung ke Flask ARIMA Microservice. Pastikan layanan microservice telah aktif.',
                'error' => $e->getMessage()
            ], 503);
        }
    }

    public function runGlobalPrediction(Request $request)
    {
        $request->validate([
            'forecast_periods' => ['nullable', 'integer', 'min:1', 'max:30'],
            'fill_missing_dates' => ['nullable', 'boolean'],
            'smooth_outliers' => ['nullable', 'boolean']
        ]);

        // Load persistent settings from database
        $settings = Setting::all()->pluck('typed_value', 'key');

        // Cari custom global tuning config (product_id null)
        $customConfig = ArimaTuningConfig::whereNull('product_id')->first();

        $periods = $request->filled('forecast_periods') 
            ? intval($request->forecast_periods) 
            : ($customConfig ? $customConfig->forecast_periods : ($settings['arima_forecast_periods'] ?? 7));

        $tuningParams = [
            'fill_missing_dates' => $request->has('fill_missing_dates') 
                ? $request->boolean('fill_missing_dates') 
                : ($customConfig ? $customConfig->fill_missing_dates : ($settings['arima_fill_missing_dates'] ?? true)),
            'smooth_outliers' => $request->has('smooth_outliers') 
                ? $request->boolean('smooth_outliers') 
                : ($customConfig ? $customConfig->smooth_outliers : ($settings['arima_smooth_outliers'] ?? true)),
            'start_p' => $request->has('start_p') ? intval($request->start_p) : ($customConfig ? $customConfig->start_p : ($settings['arima_start_p'] ?? 0)),
            'start_q' => $request->has('start_q') ? intval($request->start_q) : ($customConfig ? $customConfig->start_q : ($settings['arima_start_q'] ?? 0)),
            'max_p' => $request->has('max_p') ? intval($request->max_p) : ($customConfig ? $customConfig->max_p : ($settings['arima_max_p'] ?? 5)),
            'max_q' => $request->has('max_q') ? intval($request->max_q) : ($customConfig ? $customConfig->max_q : ($settings['arima_max_q'] ?? 5)),
            'seasonal' => $request->has('seasonal') ? $request->boolean('seasonal') : ($customConfig ? $customConfig->seasonal : ($settings['arima_seasonal'] ?? false)),
            'stepwise' => $request->has('stepwise') ? $request->boolean('stepwise') : ($customConfig ? $customConfig->stepwise : ($settings['arima_stepwise'] ?? true)),
        ];

        // 1. Tarik Data Penjualan Global (Pendapatan Toko Harian)
        $salesData = Order::select(
                DB::raw('DATE(orders.created_at) as date'),
                DB::raw('SUM(orders.total_price) as total_sales')
            )
            ->where('orders.status', 'completed')
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // 2. Validasi jumlah data historis (minimal 7 data penjualan unik per hari)
        if ($salesData->count() < 7) {
            return response()->json([
                'message' => "Data historis tidak mencukupi untuk peramalan ARIMA. Minimal dibutuhkan data penjualan dari 7 hari yang berbeda. Data saat ini: {$salesData->count()} hari."
            ], 422);
        }

        // Format data historis untuk dikirim ke Flask
        $historicalData = $salesData->map(function ($row) {
            return [
                'date' => $row->date,
                'total_sales' => floatval($row->total_sales)
            ];
        })->toArray();

        // 3. Panggil Layanan Flask ARIMA Microservice
        $flaskUrl = config('services.arima.url', 'http://127.0.0.1:5000') . '/api/v1/predict';

        try {
            $response = Http::timeout(15)->post($flaskUrl, [
                'product_name' => 'Toko Global',
                'forecast_periods' => $periods,
                'tuning_parameters' => $tuningParams,
                'historical_data' => $historicalData
            ]);

            if ($response->failed()) {
                return response()->json([
                    'message' => 'Layanan ARIMA Microservice mengembalikan error.',
                    'details' => $response->body()
                ], 502);
            }

            $result = $response->json();

            // 4. Catat ke Tabel Prediction Logs (product_name = 'GLOBAL_SALES')
            $log = PredictionLog::create([
                'user_id' => auth()->id(),
                'product_name' => 'GLOBAL_SALES',
                'forecast_periods' => $periods,
                'tuning_parameters' => $tuningParams,
                'arima_order' => $result['arima_order'] ?? 'N/A',
                'mape_score' => $result['evaluation']['mape_score'] ?? 0.0,
                'execution_time_ms' => $result['execution_time_ms'] ?? 0
            ]);

            // Update atau buat config tuning global dengan last_tuned_at = now()
            if ($customConfig) {
                $customConfig->update(['last_tuned_at' => now()]);
            } else {
                ArimaTuningConfig::create(array_merge($tuningParams, [
                    'product_id' => null,
                    'forecast_periods' => $periods,
                    'last_tuned_at' => now()
                ]));
            }

            return response()->json([
                'message' => 'Prediksi ARIMA Tren Penjualan Global berhasil dijalankan.',
                'log' => $log->load('user:id,name'),
                'arima_order' => $result['arima_order'] ?? 'N/A',
                'mape_score' => $result['evaluation']['mape_score'] ?? 0.0,
                'mape_method' => $result['evaluation']['mape_method'] ?? 'unknown',
                'forecast_result' => $result['forecast_result'] ?? []
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal terhubung ke Flask ARIMA Microservice. Pastikan layanan microservice telah aktif.',
                'error' => $e->getMessage()
            ], 503);
        }
    }

    public function getLogs()
    {
        // Mendapatkan 10 log pemantauan ARIMA terbaru beserta info admin (Resolusi #4)
        $logs = PredictionLog::with('user:id,name')
            ->latest()
            ->take(10)
            ->get();

        return response()->json($logs);
    }

    public function getConfig()
    {
        $settings = Setting::all()->pluck('typed_value', 'key');
        return response()->json($settings);
    }

    public function saveConfig(Request $request)
    {
        $validated = $request->validate([
            'arima_forecast_periods' => ['required', 'integer', 'min:1', 'max:30'],
            'arima_fill_missing_dates' => ['required', 'boolean'],
            'arima_smooth_outliers' => ['required', 'boolean'],
            'arima_start_p' => ['required', 'integer', 'min:0', 'max:5'],
            'arima_start_q' => ['required', 'integer', 'min:0', 'max:5'],
            'arima_max_p' => ['required', 'integer', 'min:1', 'max:10'],
            'arima_max_q' => ['required', 'integer', 'min:1', 'max:10'],
            'arima_seasonal' => ['required', 'boolean'],
            'arima_stepwise' => ['required', 'boolean'],
        ]);

        foreach ($validated as $key => $value) {
            Setting::where('key', $key)->update([
                'value' => is_bool($value) ? ($value ? '1' : '0') : strval($value)
            ]);
        }

    }

    public function getTuningConfig($productId = null)
    {
        // Load persistent settings untuk default fallback
        $settings = Setting::all()->pluck('typed_value', 'key');

        $config = null;
        if ($productId === 'global' || is_null($productId)) {
            $config = ArimaTuningConfig::whereNull('product_id')->first();
        } else {
            $config = ArimaTuningConfig::where('product_id', $productId)->first();
        }

        if ($config) {
            return response()->json($config);
        }

        // Return default values jika record belum dibuat di DB
        return response()->json([
            'product_id' => ($productId === 'global' || is_null($productId)) ? null : intval($productId),
            'forecast_periods' => intval($settings['arima_forecast_periods'] ?? 7),
            'fill_missing_dates' => boolval($settings['arima_fill_missing_dates'] ?? true),
            'smooth_outliers' => boolval($settings['arima_smooth_outliers'] ?? true),
            'start_p' => intval($settings['arima_start_p'] ?? 0),
            'start_q' => intval($settings['arima_start_q'] ?? 0),
            'max_p' => intval($settings['arima_max_p'] ?? 5),
            'max_q' => intval($settings['arima_max_q'] ?? 5),
            'seasonal' => boolval($settings['arima_seasonal'] ?? false),
            'stepwise' => boolval($settings['arima_stepwise'] ?? true),
            'last_tuned_at' => null
        ]);
    }

    public function saveTuningConfig(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['nullable', 'string'], // Bisa berupa ID string produk atau 'global'
            'forecast_periods' => ['required', 'integer', 'min:1', 'max:30'],
            'fill_missing_dates' => ['required', 'boolean'],
            'smooth_outliers' => ['required', 'boolean'],
            'start_p' => ['required', 'integer', 'min:0', 'max:5'],
            'start_q' => ['required', 'integer', 'min:0', 'max:5'],
            'max_p' => ['required', 'integer', 'min:1', 'max:10'],
            'max_q' => ['required', 'integer', 'min:1', 'max:10'],
            'seasonal' => ['required', 'boolean'],
            'stepwise' => ['required', 'boolean'],
        ]);

        $productId = $validated['product_id'];
        if ($productId === 'global' || is_null($productId)) {
            $productId = null;
        } else {
            $productId = intval($productId);
        }

        $config = ArimaTuningConfig::updateOrCreate(
            ['product_id' => $productId],
            [
                'forecast_periods' => $validated['forecast_periods'],
                'fill_missing_dates' => $validated['fill_missing_dates'],
                'smooth_outliers' => $validated['smooth_outliers'],
                'start_p' => $validated['start_p'],
                'start_q' => $validated['start_q'],
                'max_p' => $validated['max_p'],
                'max_q' => $validated['max_q'],
                'seasonal' => $validated['seasonal'],
                'stepwise' => $validated['stepwise'],
            ]
        );

        return response()->json([
            'message' => 'Konfigurasi tuning kustom ARIMA berhasil disimpan.',
            'config' => $config
        ]);
    }
}
