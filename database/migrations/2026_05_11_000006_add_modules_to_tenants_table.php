<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->json('modules')->nullable()->after('status');
        });

        // Backfill: every existing tenant gets the retail POS module enabled so
        // the existing Sales/Products/Inventory features don't break overnight.
        DB::table('tenants')->whereNull('modules')->update([
            'modules' => json_encode(['pos']),
        ]);
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('modules');
        });
    }
};
