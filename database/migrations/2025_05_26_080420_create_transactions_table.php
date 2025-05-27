<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id(); // Primary key 'id'
            $table->foreignId('user_id')->constrained('users', 'id');
            $table->foreignId('shift_id')->constrained('shifts', 'id');
            $table->foreignId('discount_id')->nullable()->constrained('discounts', 'id');
            $table->enum('payment_method', ['bank_transfer', 'e_wallet', 'qris', 'cash', 'card'])->nullable();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->decimal('total_payment', 10, 2)->nullable();
            $table->decimal('total_tax', 10, 2)->nullable();
            $table->tinyInteger('type_discount')->comment('0: none, 1: fixed, 2: percent')->nullable();
            $table->integer('amount_discount')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->nullable();
            $table->timestamp('date')->nullable();
            $table->tinyInteger('is_deleted')->default(0)->comment('0: no, 1: yes');
            $table->timestamp('created_at')->useCurrent();
            $table->foreignId('created_by')->constrained('users', 'id');
            $table->timestamp('updated_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users', 'id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
}; 