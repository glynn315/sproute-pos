<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('supplier_id')
                ->nullable()
                ->after('category_id')
                ->constrained('suppliers')
                ->nullOnDelete();

            $table->date('expiration_date')->nullable()->after('reorder_level');

            $table->index(['tenant_id', 'expiration_date']);
            $table->index(['tenant_id', 'supplier_id']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'supplier_id']);
            $table->dropIndex(['tenant_id', 'expiration_date']);
            $table->dropForeign(['supplier_id']);
            $table->dropColumn(['supplier_id', 'expiration_date']);
        });
    }
};
