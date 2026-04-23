<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('tenant_id')
                ->nullable()
                ->after('id')
                ->constrained('tenants')
                ->nullOnDelete();
            $table->enum('role', ['super_admin', 'owner', 'manager', 'cashier'])
                ->default('cashier')
                ->after('tenant_id');
            $table->string('pin')->nullable()->after('password');
            $table->boolean('is_active')->default(true)->after('pin');
            $table->timestamp('last_login_at')->nullable()->after('is_active');

            // Email must remain globally unique; tenant employees should use different emails
            // Super admin and owners use email+password; employees may use PIN only
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['tenant_id', 'role', 'pin', 'is_active', 'last_login_at']);
            $table->string('email')->nullable(false)->change();
        });
    }
};
