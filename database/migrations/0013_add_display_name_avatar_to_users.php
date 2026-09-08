<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('display_name', 50)->nullable()->unique()->after('username');
            $table->string('avatar_path', 255)->nullable()->after('display_name');
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['display_name']);
            $table->dropColumn(['display_name', 'avatar_path']);
        });
    }
};
