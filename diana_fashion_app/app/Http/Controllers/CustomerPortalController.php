<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class CustomerPortalController extends Controller
{
    public function getOrderHistory(Request $request)
    {
        $user = $request->user();
        
        $orders = Order::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($orders);
    }

    public function getOrderDetail(Request $request, $id)
    {
        $user = $request->user();

        // Mencegah kerentanan IDOR (Insecure Direct Object Reference) dengan validasi kepemilikan user_id
        $order = Order::with(['items.product.category'])
            ->where('user_id', $user->id)
            ->where('id', $id)
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Pesanan tidak ditemukan atau Anda tidak memiliki hak akses.'
            ], 404);
        }

        return response()->json($order);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        // Resolusi #12: Selalu gunakan filter .only() agar password hash tidak pernah bocor
        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => $user->only(['id', 'name', 'email', 'role'])
        ]);
    }
}
