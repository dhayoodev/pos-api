<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->foreignId('trans_id')->constrained('transactions', 'id')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products', 'id');
            $table->integer('quantity')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('subtotal', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_details');
    }
};