<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->nullable()
                  ->constrained('subscription_plans')->nullOnDelete();

            $table->string('invoice_number', 32)->unique();
            $table->decimal('amount', 10, 2);
            $table->string('billing_cycle', 16)->default('monthly');

            // pending → invoice issued, awaiting payment
            // submitted → user reported a reference, awaiting verification
            // paid → confirmed (auto in dummy mode); plan was applied
            // cancelled → user cancelled
            // expired → past due_at without payment
            $table->string('status', 16)->default('pending');

            $table->string('payment_method', 24)->default('gcash');
            $table->string('gcash_number', 32)->nullable();
            $table->string('reference_number', 64)->nullable();

            $table->dateTime('due_at')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
