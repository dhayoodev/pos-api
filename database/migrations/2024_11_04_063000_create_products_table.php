<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id('product_id');
            $table->foreignId('product_category_id')
                ->constrained('product_categories', 'product_category_id');
            $table->string('product_name');
            $table->string('picture')->nullable();
            $table->integer('stock')->default(0);
            $table->decimal('price', 10, 2);
            $table->text('desc_product')->nullable();
            $table->enum('discount_type', ['percentage', 'fixed'])->nullable();
            $table->decimal('discount_amount', 10, 2)->nullable();
            $table->timestamp('start_date_disc')->nullable();
            $table->timestamp('end_date_disc')->nullable();
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
}; 