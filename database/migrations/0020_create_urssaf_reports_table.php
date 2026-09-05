<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('urssaf_report_day')->default(5)->after('restoration_fee');
            $table->string('urssaf_report_email')->nullable()->after('urssaf_report_day');
        });

        Schema::create('urssaf_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('period_month');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->unsignedInteger('transaction_count')->default(0);
            $table->string('pdf_path');
            $table->string('sent_to')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error')->nullable();
            $table->timestamps();
            $table->unique('period_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('urssaf_reports');
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn(['urssaf_report_day', 'urssaf_report_email']);
        });
    }
};
