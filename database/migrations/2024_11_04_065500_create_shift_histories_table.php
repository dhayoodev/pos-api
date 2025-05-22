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
        Schema::create('shift_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts', 'id');
            $table->text('description');
            $table->tinyInteger('type')->comment('0: pay-in, 1: pay-out');
            $table->decimal('amount', 10, 2);
            $table->timestamp('created_at');
            $table->foreignId('created_by')->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_histories');
    }
};