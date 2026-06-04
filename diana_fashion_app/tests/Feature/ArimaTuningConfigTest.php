<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Product;
use App\Models\ArimaTuningConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ArimaTuningConfigTest extends TestCase
{
    /**
     * Uji CRUD dasar ArimaTuningConfig.
     */
    public function test_arima_tuning_config_crud()
    {
        // 1. Uji konfigurasi global (product_id = null)
        $globalConfig = ArimaTuningConfig::create([
            'product_id' => null,
            'forecast_periods' => 14,
            'fill_missing_dates' => false,
            'smooth_outliers' => true,
            'start_p' => 1,
            'start_q' => 1,
            'max_p' => 3,
            'max_q' => 3,
            'seasonal' => true,
            'stepwise' => false,
        ]);

        $this->assertDatabaseHas('arima_tuning_configs', [
            'product_id' => null,
            'forecast_periods' => 14,
        ]);

        // 2. Uji konfigurasi spesifik produk
        $product = Product::first();
        if ($product) {
            $productConfig = ArimaTuningConfig::create([
                'product_id' => $product->id,
                'forecast_periods' => 10,
                'fill_missing_dates' => true,
                'smooth_outliers' => false,
            ]);

            $this->assertDatabaseHas('arima_tuning_configs', [
                'product_id' => $product->id,
                'forecast_periods' => 10,
            ]);

            // Bersihkan data test
            $productConfig->delete();
        }

        // Bersihkan data global
        $globalConfig->delete();
    }
}
