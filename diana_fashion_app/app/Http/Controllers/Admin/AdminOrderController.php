<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function getHistory(Request $request)
    {
        $query = Order::with(['user', 'items.product']);

        // 1. Filter Channel (web/pos)
        if ($request->filled('channel')) {
            $query->where('channel', $request->channel);
        }

        // 2. Filter Status (pending/completed/canceled)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 3. Filter Date Range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [$request->start_date . ' 00:00:00', $request->end_date . ' 23:59:59']);
        }

        // 4. Search by increment_id
        if ($request->filled('keyword')) {
            $query->where('increment_id', 'LIKE', $request->keyword . '%');
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($orders);
    }
}
