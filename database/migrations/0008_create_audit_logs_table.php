<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('admin_id')->constrained('users')->cascadeOnDelete();
            $table->string('action'); // e.g. user.suspend, user.delete, refund.create
            $table->string('target_type')->nullable(); // e.g. App\Models\User
            $table->uuid('target_id')->nullable();
            $table->json('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index(['action']);
            $table->index(['target_type', 'target_id']);
            $table->index(['created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('audit_logs'); }
};
