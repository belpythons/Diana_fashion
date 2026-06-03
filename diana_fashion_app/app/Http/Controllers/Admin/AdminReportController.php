<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReportController extends Controller
{
    public function exportCSV(Request $request)
    {
        $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'export_type' => ['nullable', 'string', 'in:detailed,summary']
        ]);

        $startDate = $request->start_date . ' 00:00:00';
        $endDate = $request->end_date . ' 23:59:59';
        $exportType = $request->query('export_type', 'detailed');

        // Resolusi #7: Menggunakan StreamedResponse + cursor() demi efisiensi memori (lazy loading)
        // Eager load items.product.category untuk mencegah N+1 queries
        $response = new StreamedResponse(function () use ($startDate, $endDate, $exportType) {
            $handle = fopen('php://output', 'w');
            
            // Tambahkan Byte Order Mark (BOM) UTF-8 agar kompatibel secara visual dengan MS Excel di Windows
            fputs($handle, "\xEF\xBB\xBF");
            
            if ($exportType === 'detailed') {
                // Tulis Header CSV Detail
                fputcsv($handle, [
                    'No. Nota', 
                    'Tanggal Transaksi', 
                    'Kanal Belanja', 
                    'Nama Produk', 
                    'Kategori', 
                    'Jumlah Terjual (Qty)', 
                    'Harga Satuan (Rp)', 
                    'Subtotal (Rp)', 
                    'Total Transaksi (Rp)', 
                    'Status Transaksi', 
                    'Metode Pembayaran'
                ]);

                // Stream data menggunakan cursor()
                Order::with(['items.product.category'])
                    ->where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->orderBy('created_at', 'asc')
                    ->cursor() // Lazy loading database rows
                    ->each(function ($order) use ($handle) {
                        foreach ($order->items as $item) {
                            $productName = $item->product ? $item->product->name : 'Produk Dihapus';
                            $categoryName = ($item->product && $item->product->category) ? $item->product->category->name : 'Tanpa Kategori';
                            $subtotal = $item->qty * $item->price_at_purchase;

                            fputcsv($handle, [
                                $order->increment_id,
                                $order->created_at->format('Y-m-d H:i:s'),
                                strtoupper($order->channel),
                                $productName,
                                $categoryName,
                                $item->qty,
                                (int)$item->price_at_purchase,
                                (int)$subtotal,
                                (int)$order->total_price,
                                ucfirst($order->status),
                                $order->payment_method
                            ]);
                        }
                    });
            } else {
                // Tulis Header CSV Ringkasan
                fputcsv($handle, [
                    'No. Nota', 
                    'Tanggal Transaksi', 
                    'Kanal Belanja', 
                    'Produk Terjual', 
                    'Total Pendapatan (Rp)', 
                    'Status Transaksi', 
                    'Metode Pembayaran'
                ]);

                // Stream data menggunakan cursor()
                Order::with(['items.product.category'])
                    ->where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->orderBy('created_at', 'asc')
                    ->cursor() // Lazy loading database rows
                    ->each(function ($order) use ($handle) {
                        $productsSummary = $order->items->map(function ($item) {
                            $productName = $item->product ? $item->product->name : 'Produk Dihapus';
                            $categoryName = ($item->product && $item->product->category) ? $item->product->category->name : 'Tanpa Kategori';
                            return "[{$categoryName}] {$productName} (x{$item->qty})";
                        })->implode(', ');

                        fputcsv($handle, [
                            $order->increment_id,
                            $order->created_at->format('Y-m-d H:i:s'),
                            strtoupper($order->channel),
                            $productsSummary,
                            (int)$order->total_price,
                            ucfirst($order->status),
                            $order->payment_method
                        ]);
                    });
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="Laporan_Penjualan_' . $request->start_date . '_to_' . $request->end_date . '.csv"');
        
        return $response;
    }
}
