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
        // Every news story the news job has handled, posted or skipped, so
        // it never posts (or pays the AI to read) the same story twice.
        Schema::create('news_articles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // Feed links can run past 255 characters, so they're matched by hash.
            $table->text('url');
            $table->char('url_hash', 64)->unique();
            $table->string('title');
            $table->string('source')->nullable();
            // Null when the story was skipped (not about a local artist).
            $table->foreignUuid('post_id')
                ->nullable()
                ->constrained('posts')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('news_articles');
    }
};
