<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id('trans_detail_id');
            $table->foreignId('trans_id')
                ->constrained('transactions', 'trans_id')
                ->onDelete('cascade');
            $table->foreignId('product_id')
                ->constrained('products', 'product_id');
            $table->integer('qty');
            $table->decimal('price', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->boolean('is_hide')->default(false);
            $table->text('note_hide')->nullable();
            $table->boolean('is_deleted')->default(false);
            $table->text('note_deleted')->nullable();
            $table->timestamp('created_date')->useCurrent();
            $table->foreignId('created_by')
                ->constrained('users', 'id');
            $table->timestamp('updated_date')->nullable();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users', 'id');
            $table->timestamp('deleted_date')->nullable();
            $table->foreignId('deleted_by')
                ->nullable()
                ->constrained('users', 'id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_details');
    }
};