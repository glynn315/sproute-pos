<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE sales MODIFY status ENUM('pending','completed','voided','refunded','draft','partially_refunded') NOT NULL DEFAULT 'completed'"
        );

        Schema::table('sales', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->after('status');
            $table->string('suspension_note', 500)->nullable()->after('suspended_at');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['suspended_at', 'suspension_note']);
        });

        DB::statement(
            "ALTER TABLE sales MODIFY status ENUM('pending','completed','voided','refunded') NOT NULL DEFAULT 'completed'"
        );
    }
};
