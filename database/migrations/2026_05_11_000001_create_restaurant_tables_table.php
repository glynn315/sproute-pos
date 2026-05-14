<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id('restaurant_table_id');
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('table_number');
            $table->string('label', 80)->nullable();
            $table->unsignedSmallInteger('seats')->default(4);
            $table->enum('status', ['available', 'occupied', 'not_yet_paid'])->default('available');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'table_number']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_tables');
    }
};
