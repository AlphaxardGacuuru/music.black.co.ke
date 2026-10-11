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
        Schema::create('karaoke_comment_likes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('karaoke_comment_id')
                ->constrained('karaoke_comments')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->unique(['karaoke_comment_id', 'user_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('karaoke_comment_likes');
    }
};
