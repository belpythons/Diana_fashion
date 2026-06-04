<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('arima_tuning_configs')) {
            Schema::create('arima_tuning_configs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->nullable()->unique()->constrained('products')->cascadeOnDelete();
                $table->integer('forecast_periods')->default(7);
                $table->boolean('fill_missing_dates')->default(true);
                $table->boolean('smooth_outliers')->default(true);
                $table->integer('start_p')->default(0);
                $table->integer('start_q')->default(0);
                $table->integer('max_p')->default(5);
                $table->integer('max_q')->default(5);
                $table->boolean('seasonal')->default(false);
                $table->boolean('stepwise')->default(true);
                $table->timestamp('last_tuned_at')->nullable();
                $table->timestamps();
            });

            // Index parsial unik PostgreSQL untuk menjamin hanya ada 1 konfigurasi global (product_id IS NULL)
            if (config('database.default') === 'pgsql') {
                \Illuminate\Support\Facades\DB::statement(
                    'CREATE UNIQUE INDEX arima_tuning_configs_global_idx ON arima_tuning_configs ((1)) WHERE product_id IS NULL'
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arima_tuning_configs');
    }
};
