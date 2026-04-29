<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['bug', 'suggestion', 'ui', 'performance', 'other'])->default('bug');
            $table->string('subject');
            $table->text('body');
            $table->enum('status', ['new', 'reviewed', 'in_progress', 'resolved', 'dismissed'])->default('new');
            $table->text('admin_note')->nullable();
            $table->foreignUuid('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
