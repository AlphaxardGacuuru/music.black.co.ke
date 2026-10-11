<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('audio');
            $table->string('name');
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            // Featured artist.
            $table->foreignUuid('ft_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->foreignUuid('audio_album_id')
                ->constrained('audio_albums')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('genre');
            $table->string('thumbnail')->nullable();
            $table->string('description')->nullable();
            $table->timestamp('released')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audios');
    }
};
