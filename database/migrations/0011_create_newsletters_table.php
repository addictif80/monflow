<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('newsletters')) {
            Schema::create('newsletters', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('subject');
                $table->longText('html_body');
                $table->enum('status', ['draft', 'sending', 'sent'])->default('draft');
                $table->unsignedInteger('recipients_count')->default(0);
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('users', 'newsletter_optin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('newsletter_optin')->default(true)->after('stripe_customer_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletters');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('newsletter_optin');
        });
    }
};
