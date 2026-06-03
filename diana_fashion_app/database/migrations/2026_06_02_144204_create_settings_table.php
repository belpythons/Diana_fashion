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
        if (!Schema::hasTable('settings')) {
            Schema::create('settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('type')->default('string');
                $table->timestamps();
            });

            // Seed default ARIMA configurations
            $defaults = [
                ['key' => 'arima_forecast_periods', 'value' => '7', 'type' => 'integer'],
                ['key' => 'arima_fill_missing_dates', 'value' => '1', 'type' => 'boolean'],
                ['key' => 'arima_smooth_outliers', 'value' => '1', 'type' => 'boolean'],
                ['key' => 'arima_start_p', 'value' => '0', 'type' => 'integer'],
                ['key' => 'arima_start_q', 'value' => '0', 'type' => 'integer'],
                ['key' => 'arima_max_p', 'value' => '5', 'type' => 'integer'],
                ['key' => 'arima_max_q', 'value' => '5', 'type' => 'integer'],
                ['key' => 'arima_seasonal', 'value' => '0', 'type' => 'boolean'],
                ['key' => 'arima_stepwise', 'value' => '1', 'type' => 'boolean'],
            ];

            foreach ($defaults as $setting) {
                \Illuminate\Support\Facades\DB::table('settings')->insert(array_merge($setting, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
