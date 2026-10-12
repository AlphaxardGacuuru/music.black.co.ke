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
        Schema::table('song_payouts', function (Blueprint $table) {
            // The provider charge kept back from small withdrawals.
            $table->integer('fee')->default(0)->after('amount');
            // Set while the Kopokopo transfer is in flight.
            $table->string('kopokopo_reference')->nullable()->index()->after('fee');
            // Set once Kopokopo confirms the money arrived.
            $table->timestamp('paid_at')->nullable()->after('kopokopo_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('song_payouts', function (Blueprint $table) {
            $table->dropIndex(['kopokopo_reference']);
            $table->dropColumn(['fee', 'kopokopo_reference', 'paid_at']);
        });
    }
};
