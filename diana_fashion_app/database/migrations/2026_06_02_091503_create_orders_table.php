<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();
                $table->string('increment_id')->unique();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->enum('channel', ['web', 'pos']);
                $table->enum('status', ['pending', 'completed', 'canceled']);
                $table->decimal('total_price', 12, 2);
                $table->string('payment_method')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
