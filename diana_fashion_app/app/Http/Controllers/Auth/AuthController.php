<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Resolusi #1: Paksa role = 'pelanggan' untuk pendaftaran publik
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'pelanggan',
        ]);

        Auth::login($user);

        // Resolusi #12: Hanya kembalikan data non-sensitif
        return response()->json([
            'message' => 'Pendaftaran berhasil.',
            'user' => $user->only(['id', 'name', 'email', 'role'])
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Resolusi #12: Hanya kembalikan data non-sensitif
            return response()->json([
                'message' => 'Login berhasil.',
                'user' => $user->only(['id', 'name', 'email', 'role'])
            ]);
        }

        return response()->json([
            'message' => 'Email atau password yang Anda masukkan salah.'
        ], 422);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logout berhasil.'
        ]);
    }

    public function user(Request $request)
    {
        // Resolusi #12: Hanya kembalikan data non-sensitif
        return response()->json([
            'user' => $request->user()->only(['id', 'name', 'email', 'role'])
        ]);
    }
}
