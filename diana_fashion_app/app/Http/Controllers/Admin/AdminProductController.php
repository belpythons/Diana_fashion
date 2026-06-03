<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            // SKU prefix B-Tree, Name FULLTEXT MATCH AGAINST (Resolusi #6)
            $query->where('sku', 'LIKE', $keyword . '%')
                  ->orWhereRaw("MATCH(name) AGAINST(? IN BOOLEAN MODE)", [$keyword . '*']);
        }

        if ($request->get('low_stock') === 'true') {
            $query->where('stock', '<', 5);
        }

        if ($request->get('all') === 'true') {
            return response()->json([
                'data' => $query->orderBy('created_at', 'desc')->get()
            ]);
        }

        return response()->json($query->orderBy('created_at', 'desc')->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'sku' => ['required', 'string', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'stock' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'image_url' => ['nullable', 'string', 'url'],
            'image' => ['nullable', 'image', 'max:5120'] // 5MB max
        ]);

        if ($request->hasFile('image')) {
            try {
                $validated['image_url'] = $this->uploadToSupabase($request->file('image'));
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Gagal mengupload gambar ke Supabase Storage.',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        // Hapus field 'image' dari input data mass assignment
        unset($validated['image']);

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Produk berhasil ditambahkan.',
            'product' => $product->load('category')
        ], 201);
    }

    public function show($id)
    {
        $product = Product::with('category')->findOrFail($id);
        return response()->json($product);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'sku' => ['required', 'string', Rule::unique('products', 'sku')->ignore($product->id)],
            'name' => ['required', 'string', 'max:255'],
            'stock' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'image_url' => ['nullable', 'string', 'url'],
            'image' => ['nullable', 'image', 'max:5120'] // 5MB max
        ]);

        if ($request->hasFile('image')) {
            try {
                $validated['image_url'] = $this->uploadToSupabase($request->file('image'));
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Gagal mengupload gambar ke Supabase Storage.',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        unset($validated['image']);

        $product->update($validated);

        return response()->json([
            'message' => 'Produk berhasil diperbarui.',
            'product' => $product->load('category')
        ]);
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        
        // Cek jika produk sudah pernah dipesan untuk mencegah integritas DB rusak
        if ($product->orderItems()->exists()) {
            return response()->json([
                'message' => 'Produk tidak dapat dihapus karena sudah memiliki riwayat transaksi.'
            ], 422);
        }

        $product->delete();

        return response()->json([
            'message' => 'Produk berhasil dihapus.'
        ]);
    }

    /**
     * Mengupload file gambar ke Supabase Storage REST API
     */
    private function uploadToSupabase($file)
    {
        $projectRef = config('services.supabase.project_ref');
        $apiKey = config('services.supabase.anon_key');
        $bucket = 'product-images';
        
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        
        $url = "https://{$projectRef}.supabase.co/storage/v1/object/{$bucket}/{$filename}";
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'apikey' => $apiKey,
        ])->attach(
            'file', file_get_contents($file->getRealPath()), $filename
        )->post($url);
        
        if ($response->failed()) {
            throw new \Exception('Respons Supabase Storage Gagal: ' . $response->body());
        }
        
        return "https://{$projectRef}.supabase.co/storage/v1/object/public/{$bucket}/{$filename}";
    }
}
