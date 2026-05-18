<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('playlist_members', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('shared_playlist_id')->constrained('shared_playlists')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users');
            $table->enum('role', ['collaborator', 'subscriber']);
            $table->string('member_nd_playlist_id', 100);
            $table->timestamps();
            $table->unique(['shared_playlist_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playlist_members');
    }
};
