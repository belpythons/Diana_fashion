<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PredictionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_name',
        'forecast_periods',
        'tuning_parameters',
        'arima_order',
        'mape_score',
        'execution_time_ms'
    ];

    protected $casts = [
        'tuning_parameters' => 'array',
        'mape_score' => 'float',
        'execution_time_ms' => 'integer',
        'forecast_periods' => 'integer'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
