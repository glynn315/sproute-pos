<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Coerce any NULL stock_quantity rows to 0. The column is declared NOT NULL
     * with default 0, but historical rows can carry NULL when MySQL strict
     * mode is off (an explicit NULL insert is silently coerced to default in
     * non-strict mode but can survive across schema migrations).
     *
     * The void/restock paths now also defend against NULL at runtime, but
     * fixing the data here means we don't lie about real stock counts.
     */
    public function up(): void
    {
        DB::table('products')->whereNull('stock_quantity')->update(['stock_quantity' => 0]);
    }

    public function down(): void
    {
        // No-op — we never want NULL stock again.
    }
};
