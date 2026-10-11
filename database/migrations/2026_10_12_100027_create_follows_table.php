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
        Schema::create('follows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // The user being followed.
            $table->foreignUuid('followed_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            // The follower.
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->jsonb('muted')->nullable();
            $table->string('blocked')->nullable();

            $table->unique(['followed_id', 'user_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};
