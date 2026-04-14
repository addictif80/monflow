<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->enum('category', ['billing', 'technical', 'account', 'other'])->default('other');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'waiting_customer', 'resolved', 'closed'])->default('open');
            $table->foreignUuid('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('author_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_staff_reply')->default(false);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('user_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('device_name');
            $table->string('device_type', 100)->default('');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->default('');
            $table->string('session_key')->default('');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_active')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('smtp_configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->default('Default');
            $table->string('host');
            $table->integer('port')->default(587);
            $table->string('username')->default('');
            $table->string('password')->default('');
            $table->boolean('use_tls')->default(true);
            $table->boolean('use_ssl')->default(false);
            $table->string('from_email');
            $table->string('from_name')->default('MonFlow');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('email_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('template_type', 50)->unique();
            $table->string('subject');
            $table->longText('html_body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('smtp_configurations');
        Schema::dropIfExists('user_devices');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
    }
};
