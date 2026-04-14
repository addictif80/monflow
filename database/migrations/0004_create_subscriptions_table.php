<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('plan_id')->constrained();
            $table->enum('status', ['active', 'suspended', 'cancelled', 'expired', 'pending'])->default('pending');
            $table->string('stripe_subscription_id')->default('');
            $table->foreignUuid('promo_code_id')->nullable()->constrained('promo_codes')->nullOnDelete();
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->boolean('is_gift')->default(false);
            $table->foreignUuid('gifted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('gift_recipient_email')->default('');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'current_period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
