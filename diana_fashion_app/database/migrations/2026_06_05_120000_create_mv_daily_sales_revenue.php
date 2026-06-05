<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Buat Materialized View untuk pendapatan harian dari order completed
        DB::statement("
            CREATE MATERIALIZED VIEW IF NOT EXISTS mv_daily_sales_revenue AS
            SELECT 
                DATE(created_at) as sales_date,
                SUM(total_price) as total_revenue
            FROM orders
            WHERE status = 'completed'
            GROUP BY DATE(created_at)
        ");

        // 2. Buat Unique Index untuk memungkinkan REFRESH CONCURRENTLY
        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS idx_mv_daily_sales_date 
            ON mv_daily_sales_revenue (sales_date)
        ");

        // 3. Aktifkan extension pg_cron di Supabase (jike belum) secara aman
        try {
            DB::statement("CREATE EXTENSION IF NOT EXISTS pg_cron");
        } catch (\Exception $e) {
            // Abaikan jika extension pg_cron tidak diizinkan diinstal lewat SQL (misal keterbatasan role)
            // Namun di Supabase biasanya diaktifkan otomatis atau via UI
            Log::warning("Gagal mengaktifkan pg_cron extension: " . $e->getMessage());
        }

        // 4. Jadwalkan cron job untuk me-refresh MV setiap jam (menit 0)
        try {
            // Unschedule jika sudah ada sebelumnya
            DB::statement("
                SELECT cron.unschedule(jobid) 
                FROM cron.job 
                WHERE jobname = 'refresh-daily-sales-mv'
            ");
        } catch (\Exception $e) {
            // Abaikan jika skema cron atau tabel job belum siap/tidak diizinkan
        }

        try {
            DB::statement("
                SELECT cron.schedule(
                    'refresh-daily-sales-mv', 
                    '0 * * * *', 
                    'REFRESH MATERIALIZED VIEW CONCURRENTLY mv_daily_sales_revenue'
                )
            ");
        } catch (\Exception $e) {
            Log::warning("Gagal mendaftarkan cron job refresh-daily-sales-mv: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Hapus cron job
        try {
            DB::statement("
                SELECT cron.unschedule(jobid) 
                FROM cron.job 
                WHERE jobname = 'refresh-daily-sales-mv'
            ");
        } catch (\Exception $e) {
            // Abaikan jika skema cron tidak ada
        }

        // 2. Hapus Materialized View
        DB::statement("DROP MATERIALIZED VIEW IF EXISTS mv_daily_sales_revenue");
    }
};
