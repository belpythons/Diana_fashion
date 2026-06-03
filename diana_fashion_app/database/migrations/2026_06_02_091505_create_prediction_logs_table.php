<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('prediction_logs')) {
            Schema::create('prediction_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('product_name');
                $table->integer('forecast_periods');
                $table->json('tuning_parameters');
                $table->string('arima_order')->nullable();
                $table->decimal('mape_score', 5, 2);
                $table->integer('execution_time_ms');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('prediction_logs');
    }
};
