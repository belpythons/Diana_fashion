<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminStaffController extends Controller
{
    public function index(Request $request)
    {
        $query = User::whereIn('role', ['admin', 'kasir']);

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'LIKE', '%' . $keyword . '%')
                  ->orWhere('email', 'LIKE', '%' . $keyword . '%');
            });
        }

        $paginator = $query->orderBy('created_at', 'desc')->paginate(15);

        // Resolusi #12: Response disaring ketat menggunakan .only() untuk menyembunyikan password hash
        $paginator->through(function ($user) {
            return $user->only(['id', 'name', 'email', 'role']);
        });

        return response()->json($paginator);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', 'in:admin,kasir']
        ]);

        $validated['password'] = bcrypt($validated['password']);

        $user = User::create($validated);

        // Resolusi #12: Hanya return data non-sensitif
        return response()->json([
            'message' => 'Karyawan internal berhasil didaftarkan.',
            'staff' => $user->only(['id', 'name', 'email', 'role'])
        ], 201);
    }

    public function show($id)
    {
        $user = User::whereIn('role', ['admin', 'kasir'])->findOrFail($id);
        
        // Resolusi #12
        return response()->json($user->only(['id', 'name', 'email', 'role']));
    }

    public function update(Request $request, $id)
    {
        $user = User::whereIn('role', ['admin', 'kasir'])->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'string', 'in:admin,kasir'],
            'password' => ['nullable', 'string', 'min:8'] // Password bersifat opsional saat update
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        // Resolusi #12
        return response()->json([
            'message' => 'Data karyawan berhasil diperbarui.',
            'staff' => $user->only(['id', 'name', 'email', 'role'])
        ]);
    }

    public function destroy($id)
    {
        $user = User::whereIn('role', ['admin', 'kasir'])->findOrFail($id);

        // Cegah menghapus diri sendiri
        if (auth()->id() == $user->id) {
            return response()->json([
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.'
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'Karyawan internal berhasil dihapus.'
        ]);
    }
}
