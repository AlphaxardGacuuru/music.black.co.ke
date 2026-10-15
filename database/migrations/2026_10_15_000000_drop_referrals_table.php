<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Referrals were removed, along with their reward settings.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('referrals');

        DB::table('settings')
            ->whereIn('key', ['referral_threshold', 'referral_reward_amount'])
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('referrals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('referrer_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignUuid('referred_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->decimal('amount_paid', 10, 2)->nullable();
            $table->string('kopokopo_reference')->nullable();
            $table->timestamps();

            $table->index('referrer_id');
        });
    }
};
