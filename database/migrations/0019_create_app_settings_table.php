<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->decimal('restoration_fee', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('deleted_with_data_kept')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('deleted_with_data_kept');
        });
        Schema::dropIfExists('app_settings');
    }
};
