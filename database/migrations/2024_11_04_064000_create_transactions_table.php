<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id('trans_id');
            $table->foreignId('user_id')
                ->constrained('users', 'id');
            $table->date('date');
            $table->decimal('total_price', 10, 2);
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded']);
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
        Schema::dropIfExists('transactions');
    }
}; 