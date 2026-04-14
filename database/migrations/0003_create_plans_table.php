<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->default('');
            $table->decimal('price', 10, 2);
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'yearly'])->default('monthly');
            $table->string('stripe_price_id')->default('');
            $table->integer('max_devices')->default(3);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('promo_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->enum('discount_type', ['percentage', 'fixed', 'free_months']);
            $table->decimal('discount_value', 10, 2);
            $table->integer('max_uses')->default(0)->comment('0 = illimité');
            $table->integer('current_uses')->default(0);
            $table->timestamp('valid_from')->useCurrent();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('plan_promo_code', function (Blueprint $table) {
            $table->foreignUuid('plan_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('promo_code_id')->constrained()->cascadeOnDelete();
            $table->primary(['plan_id', 'promo_code_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_promo_code');
        Schema::dropIfExists('promo_codes');
        Schema::dropIfExists('plans');
    }
};
