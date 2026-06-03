<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
                $table->string('sku')->unique()->index();
                $table->string('name');
                $table->integer('stock')->default(0);
                $table->decimal('price', 12, 2);
                $table->string('image_url')->nullable();
                $table->timestamps();
    
                // FULLTEXT index untuk pencarian nama produk (Resolusi #6)
                $table->fullText('name');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
