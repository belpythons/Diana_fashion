<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArimaTuningConfig extends Model
{
    use HasFactory;

    protected $table = 'arima_tuning_configs';

    protected $fillable = [
        'product_id',
        'forecast_periods',
        'fill_missing_dates',
        'smooth_outliers',
        'start_p',
        'start_q',
        'max_p',
        'max_q',
        'seasonal',
        'stepwise',
        'last_tuned_at'
    ];

    protected $casts = [
        'product_id' => 'integer',
        'forecast_periods' => 'integer',
        'fill_missing_dates' => 'boolean',
        'smooth_outliers' => 'boolean',
        'start_p' => 'integer',
        'start_q' => 'integer',
        'max_p' => 'integer',
        'max_q' => 'integer',
        'seasonal' => 'boolean',
        'stepwise' => 'boolean',
        'last_tuned_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relasi ke model Product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
