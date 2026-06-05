<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected static function booted()
    {
        static::saved(function ($category) {
            \Illuminate\Support\Facades\Cache::forget('product_categories_list');
        });

        static::deleted(function ($category) {
            \Illuminate\Support\Facades\Cache::forget('product_categories_list');
        });
    }
}
