<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adjustment_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products', 'id');
            $table->foreignId('user_id')->constrained('users', 'id');
            $table->foreignId('stock_id')->constrained('stock_products', 'id');
            $table->tinyInteger('type')->default(0)->comment('0: plus, 1: minus');
            $table->integer('quantity');
            $table->text('note')->nullable();
            $table->string('image')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adjustment_products');
    }
};