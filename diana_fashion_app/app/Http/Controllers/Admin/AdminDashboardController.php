<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PredictionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Cache;

class AdminDashboardController extends Controller
{
    /**
     * Endpoint gabungan untuk memuat seluruh data dashboard dalam 1 request (Batch Loading)
     */
    public function getDashboardInit(Request $request)
    {
        $metrics = $this->getMetricsData();
        $realityCheck = $this->getRealityCheckData();
        
        // 1. Mengambil log ARIMA terbaru
        $arimaLogs = PredictionLog::with('user:id,name')
            ->latest()
            ->take(10)
            ->get();

        // 2. Menyusun data tren ARIMA global dari log dan cache
        $latestGlobalLog = PredictionLog::where('product_name', 'GLOBAL_SALES')
            ->latest()
            ->first();

        $cachedForecast = Cache::get("arima_global_forecast", []);

        // Ambil 14 hari terakhir data historis dari materialized view daily sales
        $historis = DB::table('mv_daily_sales_revenue')
            ->select('sales_date as date', 'total_revenue as sales')
            ->orderBy('sales_date', 'desc')
            ->take(14)
            ->get()
            ->reverse()
            ->values();

        // Format data prediksi dari cache
        $prediksi = collect($cachedForecast)->map(function ($item) {
            return [
                'date' => $item['date'],
                'sales' => floatval($item['sales'])
            ];
        });

        $arimaTrend = [
            'arima_order' => $latestGlobalLog ? $latestGlobalLog->arima_order : 'N/A',
            'mape_score' => $latestGlobalLog ? floatval($latestGlobalLog->mape_score) : 0.0,
            'last_tuning_time' => $latestGlobalLog ? $latestGlobalLog->created_at->toDateTimeString() : null,
            'historis' => $historis,
            'prediksi' => $prediksi
        ];

        return response()->json([
            'metrics_data' => $metrics,
            'reality_check_data' => $realityCheck,
            'arima_logs' => $arimaLogs,
            'arima_trend' => $arimaTrend
        ]);
    }

    public function getRealityCheck(Request $request)
    {
        $result = $this->getRealityCheckData();

        if (empty($result) && empty(Cache::get("arima_global_forecast", []))) {
            return response()->json([
                'message' => 'Belum ada data prediksi ARIMA Tren Penjualan Global. Silakan tunggu atau muat ulang halaman.',
                'data' => []
            ]);
        }

        return response()->json([
            'message' => 'Reality check berhasil dimuat.',
            'data' => $result
        ]);
    }

    public function getMetrics(Request $request)
    {
        return response()->json($this->getMetricsData());
    }

    /**
     * Helper: Mendapatkan data metrik dashboard (dengan Caching 1 Jam)
     */
    private function getMetricsData()
    {
        return Cache::remember('admin_dashboard_metrics', now()->addHour(), function () {
            $today = now()->startOfDay();
            $thisMonth = now()->startOfMonth();

            // 1. Pendapatan harian & bulanan
            $revenueToday = Order::where('status', 'completed')
                ->where('created_at', '>=', $today)
                ->sum('total_price');

            $revenueThisMonth = Order::where('status', 'completed')
                ->where('created_at', '>=', $thisMonth)
                ->sum('total_price');

            // 2. Jumlah pesanan harian & bulanan
            $ordersToday = Order::where('status', 'completed')
                ->where('created_at', '>=', $today)
                ->count();

            $ordersThisMonth = Order::where('status', 'completed')
                ->where('created_at', '>=', $thisMonth)
                ->count();

            // 3. Perbandingan POS vs Web (Sales count & Nominal)
            $channelComparison = Order::select('channel', DB::raw('count(*) as count'), DB::raw('sum(total_price) as total_price'))
                ->where('status', 'completed')
                ->groupBy('channel')
                ->get()
                ->keyBy('channel');

            $posSales = $channelComparison->get('pos');
            $webSales = $channelComparison->get('web');

            return [
                'metrics' => [
                    'revenue_today' => doubleval($revenueToday),
                    'revenue_month' => doubleval($revenueThisMonth),
                    'orders_today' => $ordersToday,
                    'orders_month' => $ordersThisMonth,
                ],
                'comparison' => [
                    'pos' => [
                        'count' => $posSales ? $posSales->count : 0,
                        'total' => $posSales ? doubleval($posSales->total_price) : 0,
                    ],
                    'web' => [
                        'count' => $webSales ? $webSales->count : 0,
                        'total' => $webSales ? doubleval($webSales->total_price) : 0,
                    ]
                ]
            ];
        });
    }

    /**
     * Helper: Mendapatkan data perbandingan actual vs predicted (Reality Check)
     */
    private function getRealityCheckData()
    {
        // 1. Ambil forecast global dari cache
        $cachedForecast = Cache::get("arima_global_forecast", []);

        if (empty($cachedForecast)) {
            return [];
        }

        // 2. Ambil data aktual pendapatan toko harian dari Materialized View
        $dates = collect($cachedForecast)->pluck('date');
        $minDate = $dates->min();
        $maxDate = $dates->max();

        $actualSales = DB::table('mv_daily_sales_revenue')
            ->where('sales_date', '>=', $minDate)
            ->where('sales_date', '<=', $maxDate)
            ->get()
            ->keyBy('sales_date');

        // 3. Bandingkan per tanggal forecast
        $today = now()->format('Y-m-d');
        $result = [];

        foreach ($cachedForecast as $forecast) {
            $date = $forecast['date'];

            // Hanya tampilkan hari yang sudah berlalu atau hari ini
            if ($date > $today) {
                $result[] = [
                    'date' => $date,
                    'predicted' => round($forecast['sales']),
                    'actual' => null,
                    'deviation_percent' => null,
                    'status' => 'upcoming',  // Hari yang belum terjadi
                ];
                continue;
            }

            $predicted = floatval($forecast['sales']);
            $actual = isset($actualSales[$date]) ? floatval($actualSales[$date]->total_revenue) : 0.0;

            // Hitung deviasi persentase
            if ($predicted > 0) {
                $deviation = (($predicted - $actual) / $predicted) * 100;
            } else {
                $deviation = $actual > 0 ? -100 : 0;
            }

            // Tentukan status warna
            if ($deviation <= 10) {
                $status = 'green';
            } elseif ($deviation <= 30) {
                $status = 'yellow';
            } else {
                $status = 'red';
            }

            $result[] = [
                'date' => $date,
                'predicted' => round($predicted),
                'actual' => round($actual),
                'deviation_percent' => round($deviation, 1),
                'status' => $status,
            ];
        }

        return $result;
    }
}
