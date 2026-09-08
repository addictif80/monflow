<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shared_playlists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')->constrained('users');
            $table->string('owner_nd_playlist_id', 100);
            $table->string('name', 200);
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            $table->unique(['owner_id', 'owner_nd_playlist_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shared_playlists');
    }
};
