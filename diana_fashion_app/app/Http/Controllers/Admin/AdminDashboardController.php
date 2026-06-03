<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function getMetrics(Request $request)
    {
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

        return response()->json([
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
        ]);
    }
}
