<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminCustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'pelanggan');

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'LIKE', '%' . $keyword . '%')
                  ->orWhere('email', 'LIKE', '%' . $keyword . '%');
            });
        }

        $paginator = $query->orderBy('created_at', 'desc')->paginate(15);
        $paginator->through(fn ($user) => $user->only(['id', 'name', 'email', 'role', 'created_at']));

        return response()->json($paginator);
    }

    public function show($id)
    {
        $customer = User::where('role', 'pelanggan')
            ->with(['orders' => function ($q) {
                $q->orderBy('created_at', 'desc');
            }])
            ->findOrFail($id);

        return response()->json([
            'customer' => $customer->only(['id', 'name', 'email', 'role', 'created_at']),
            'orders' => $customer->orders->map(function ($order) {
                return $order->only(['id', 'increment_id', 'channel', 'status', 'total_price', 'payment_method', 'created_at']);
            })
        ]);
    }
}
