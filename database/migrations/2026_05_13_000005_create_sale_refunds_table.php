<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('refunded_by_user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('total_refunded', 10, 2);
            $table->string('reason', 500);
            $table->enum('refund_method', ['cash', 'card', 'gcash', 'maya', 'bank_transfer', 'other'])
                ->default('cash');
            $table->timestamps();

            $table->index(['sale_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_refunds');
    }
};
