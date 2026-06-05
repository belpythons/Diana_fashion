<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'increment_id',
        'user_id',
        'customer_name',
        'channel',
        'status',
        'total_price',
        'payment_method'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    protected static function booted()
    {
        static::saved(function ($order) {
            \Illuminate\Support\Facades\Cache::forget('admin_dashboard_metrics');
        });

        static::deleted(function ($order) {
            \Illuminate\Support\Facades\Cache::forget('admin_dashboard_metrics');
        });
    }
}
