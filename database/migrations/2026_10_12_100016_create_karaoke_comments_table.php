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
        Schema::create('karaoke_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('karaoke_id')
                ->constrained('karaokes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('text');
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
        Schema::dropIfExists('karaoke_comments');
    }
};
