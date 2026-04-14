<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->decimal('wallet_amount', 10, 2)->default(0);
            $table->decimal('stripe_amount', 10, 2)->default(0);
            $table->enum('status', ['pending', 'succeeded', 'failed', 'refunded', 'partially_refunded'])->default('pending');
            $table->enum('payment_method', ['stripe', 'wallet', 'mixed'])->default('stripe');
            $table->string('stripe_payment_intent_id')->default('');
            $table->string('stripe_invoice_id')->default('');
            $table->string('description', 500)->default('');
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->text('reason')->default('');
            $table->enum('status', ['pending', 'processed', 'failed'])->default('pending');
            $table->enum('refund_to', ['original', 'wallet'])->default('original');
            $table->string('stripe_refund_id')->default('');
            $table->foreignUuid('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
    }
};
