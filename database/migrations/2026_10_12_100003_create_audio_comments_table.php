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
        Schema::create('audio_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('audio_id')
                ->constrained('audios')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('text')->nullable();
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audio_comments');
    }
};
